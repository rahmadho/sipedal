// =============================================================================
// Provider: BAILEYS  (real WhatsApp, multi-device)
// -----------------------------------------------------------------------------
// Connects to WhatsApp Web via the Baileys library (unofficial, multi-device).
// Optional dependency: the module is imported LAZILY so mock mode still boots
// with zero deps and no internet.
//
// Responsibilities:
//   • QR pairing      → emits QR into an in-memory buffer + console (ASCII)
//   • Persistent auth → multi-file creds under BAILEYS_AUTH_DIR (survives restart)
//   • Inbound         → maps WA messages to the same event shape as mock
//   • Outbound        → send() posts a text/media message through the socket
//
// Security note: an auth session is a full WhatsApp login. Treat BAILEYS_AUTH_DIR
// as a secret (volume + restrictive perms). Never commit it.
// =============================================================================
import path from "node:path";
import fs from "node:fs/promises";
import { createReadStream } from "node:fs";

import { id, log, now, toE164, toJid, RingLog, forwardInbound } from "../lib/shared.js";
import { botReply } from "../lib/bot.js";

const CONNECTION_STATES = ["connecting", "open", "close"];

/**
 * Lazily load Baileys. Returns the module namespace or throws a helpful error
 * if the dependency is not installed (mock-only deployments don't need it).
 */
async function loadBaileys() {
  try {
    return await import("@whiskeysockets/baileys");
  } catch (err) {
    const e = new Error(
      "Baileys is not installed. Run `npm install` inside infra/wa-gateway, " +
        "or keep WA_PROVIDER=mock for a dependency-free gateway. " +
        `(original: ${err?.message || err})`,
    );
    e.code = "BAILEYS_MISSING";
    throw e;
  }
}

/**
 * Resolve the multi-file auth state directory.
 *
 * BAILEYS_AUTH_DIR is HONOURED strictly:
 *   • an explicit value (config.authDir or $BAILEYS_AUTH_DIR) is resolved to an
 *     ABSOLUTE path against the process cwd, so the location never depends on
 *     where the process happens to be launched from;
 *   • when the directory cannot be created/written we FAIL FAST (do NOT fall
 *     back to a different path) — silently relocating the session to /tmp is
 *     what made "BAILEYS_AUTH_DIR tidak dihormati": creds landed in an
 *     ephemeral tmpdir and the pairing vanished on every restart;
 *   • only when NO value is configured do we use a clearly-labelled default.
 *     That default is stable ($HOME/.cuciiku/wa-auth when HOME is writable,
 *     otherwise <cwd>/.wa-auth) instead of an ephemeral tmpdir, and it is
 *     reported by status().auth_dir so operators can see the real path.
 */
async function resolveAuthDir(dir) {
  const configured = String(dir || "").trim();
  const target = configured
    ? path.resolve(configured) // relative → absolute against cwd
    : path.resolve(process.env.WA_BAILEYS_AUTH_DIR_DEFAULT || path.join(process.cwd(), ".wa-auth"));

  try {
    await fs.mkdir(target, { recursive: true, mode: 0o700 });
    await fs.access(target, fs.constants?.W_OK ?? 2);
  } catch (err) {
    const e = new Error(
      `BAILEYS_AUTH_DIR tidak dapat dipakai: "${target}" (${err?.message || err}). ` +
        "Perbaiki path/izin (atau lepaskan variabel untuk memakai default) — sesi WhatsApp TIDAK akan dialihkan diam-diam ke lokasi lain.",
    );
    e.code = "BAILEYS_AUTH_DIR_UNUSABLE";
    throw e;
  }
  return target;
}

/**
 * Synchronous, side-effect-free preview of the auth dir — used by status() so
 * the effective path is visible BEFORE start() runs (and without creating it).
 * MUST stay in sync with resolveAuthDir().
 */
export function previewAuthDir(dir) {
  const configured = String(dir || "").trim();
  return configured
    ? path.resolve(configured)
    : path.resolve(process.env.WA_BAILEYS_AUTH_DIR_DEFAULT || path.join(process.cwd(), ".wa-auth"));
}

export function createBaileysProvider(config = {}) {
  const provider = "baileys";
  const autoReply = config.autoReply ?? false; // real WA: default OFF (bot opt-in)
  const callbackUrl = config.callbackUrl || "";
  const authDirCfg = config.authDir || process.env.BAILEYS_AUTH_DIR || "";
  const printQrToConsole = config.printQr ?? true;
  const botName = config.botName || "CuciKu";

  // ---- observable state -----------------------------------------------------
  let socket = null;
  let authDir = "";
  let connected = false;
  let lastQr = null; // latest QR string
  let lastQrAt = null;
  let me = null; // { id, name }
  let lastDisconnect = null;
  let starting = null; // in-flight start promise (idempotent)
  let stopped = false;

  const outbox = new RingLog(200);
  const inboundEvents = new RingLog(200);

  // ---- QR pairing -----------------------------------------------------------
  function renderQrConsole(qr) {
    if (!printQrToConsole) return;
    // ASCII QR via the optional `qrcode-terminal` helper if present; otherwise
    // tell the operator to read the QR from the HTTP console (/qr).
    import("qrcode-terminal")
      .then((m) => m.default.generate(qr, { small: true }))
      .catch(() => {
        log({
          evt: "qr.ready",
          provider,
          hint: `QR tersedia di konsol HTTP (GET /qr) — scan dari WhatsApp > Perangkat Tertaut`,
        });
      });
  }

  // ---- inbound mapping ------------------------------------------------------
  function extractText(m) {
    const msg = m.message || {};
    return (
      msg.conversation ||
      msg.extendedTextMessage?.text ||
      msg.imageMessage?.caption ||
      msg.videoMessage?.caption ||
      msg.buttonsResponseMessage?.selectedDisplayText ||
      msg.listResponseMessage?.title ||
      ""
    );
  }

  async function handleInbound(m) {
    // Ignore our own messages, protocol noise and broadcast/status updates.
    if (!m.message || m.key?.fromMe) return;
    const remoteJid = m.key?.remoteJid || "";
    if (!remoteJid || remoteJid.endsWith("@g.us") || remoteJid === "status@broadcast") return;

    const text = extractText(m);
    if (!text && !m.message?.imageMessage) return; // nothing actionable

    const from = toE164(remoteJid);
    const event = {
      event: "message.received",
      id: id("evt"),
      provider,
      from,
      profile_name: m.pushName || "Customer",
      type: m.message?.imageMessage ? "image" : "text",
      text: String(text),
      media_url: null, // media download is a future enhancement (see docs)
      wa_message_id: m.key?.id || null,
      timestamp: Number(m.messageTimestamp) || Math.floor(Date.now() / 1000),
      received_at: now(),
    };
    inboundEvents.push(event);
    log({ evt: "inbound.received", provider, from: event.from, text: event.text });

    const forwarded_to_api = await forwardInbound(event, callbackUrl);

    if (autoReply) {
      try {
        await send({ to: from, text: botReply(text) });
      } catch (err) {
        log({ evt: "autoreply.failed", provider, from, error: String(err?.message || err) });
      }
    }

    return { event, forwarded_to_api };
  }

  // ---- socket lifecycle -----------------------------------------------------
  async function start() {
    if (starting) return starting;
    if (connected && socket) return;

    starting = (async () => {
      // Resolve + validate BAILEYS_AUTH_DIR FIRST, so an unusable path fails
      // fast and status().auth_dir always reflects the real location (never
      // null before the socket is up).
      authDir = await resolveAuthDir(authDirCfg);

      const baileys = await loadBaileys();
      const makeWASocket = baileys.default?.default || baileys.default || baileys.makeWASocket;
      const { useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion, Browsers } = baileys;
      const pino = (await import("pino")).default;

      const { state, saveCreds } = await useMultiFileAuthState(authDir);

      let version;
      try {
        ({ version } = await fetchLatestBaileysVersion());
      } catch {
        version = undefined; // fall back to the bundled default
      }

      socket = makeWASocket({
        version,
        auth: state,
        printQRInTerminal: false,
        browser: Browsers.macOS("Chrome"),
        logger: pino({ level: "info" }),
        markOnlineOnConnect: false,
        syncFullHistory: false,
        connectTimeoutMs: 60000,
        defaultQueryTimeoutMs: 60000,
        keepAliveIntervalMs: 25000,
        qrTimeout: 40000,
      });

      socket.ev.on("creds.update", saveCreds);

      socket.ev.on("connection.update", (update) => {
        const { connection, lastDisconnect: ld, qr } = update;

        if (qr) {
          lastQr = qr;
          lastQrAt = now();
          log({ evt: "qr.ready", provider, hint: "scan via GET /qr atau terminal" });
          renderQrConsole(qr);
        }

        if (CONNECTION_STATES.includes(connection)) {
          log({ evt: "connection.update", provider, connection });
        }

        if (connection === "open") {
          connected = true;
          lastQr = null;
          const jid = socket.user?.id || "";
          me = { id: toE164(jid), name: socket.user?.name || botName };
          log({ evt: "connection.open", provider, me: me.id });
        }

        if (connection === "close") {
          connected = false;
          const code = ld?.error?.output?.statusCode ?? ld?.error?.statusCode;
          lastDisconnect = { code: code ?? null, reason: String(ld?.error?.message || ld?.error || "unknown"), at: now() };
          log({ evt: "connection.close", provider, code: lastDisconnect.code, reason: lastDisconnect.reason });

          const loggedOut = code === (DisconnectReason?.loggedOut ?? 401);
          const replaced = code === (DisconnectReason?.connectionReplaced ?? 440);
          if (!stopped && !loggedOut && !replaced) {
            // Transient drop → reconnect automatically.
            log({ evt: "connection.retry", provider, in_ms: 3000 });
            setTimeout(() => {
              if (!stopped) start().catch((err) => log({ evt: "connection.retry.failed", error: String(err?.message || err) }));
            }, 3000);
          } else if (loggedOut || replaced) {
            log({
              evt: "connection.terminal",
              provider,
              reason: loggedOut ? "loggedOut (pairing revoked)" : "connectionReplaced (opened elsewhere)",
              hint: "Jalankan ulang pair / hapus BAILEYS_AUTH_DIR untuk pairing baru",
            });
          }
        }
      });

      socket.ev.on("messages.upsert", async ({ messages, type }) => {
        if (type !== "notify") return; // ignore history/append
        for (const m of messages) {
          try {
            await handleInbound(m);
          } catch (err) {
            log({ evt: "inbound.error", provider, error: String(err?.message || err) });
          }
        }
      });
    })();

    try {
      await starting;
    } finally {
      starting = null;
    }
  }

  async function stop() {
    stopped = true;
    try {
      await socket?.end?.(undefined);
    } catch {
      /* ignore */
    }
    socket = null;
    connected = false;
    log({ evt: "shutdown", provider });
  }

  // ---- outbound -------------------------------------------------------------
  async function send(reqBody) {
    if (!socket || !connected) {
      const e = new Error("Baileys socket not connected (scan QR first; see GET /qr)");
      e.statusCode = 503;
      throw e;
    }
    const to = toE164(reqBody.to || reqBody.phone || reqBody.recipient);
    if (!to) {
      const e = new Error("`to` is required");
      e.statusCode = 400;
      throw e;
    }

    const jid = toJid(to);
    const text = reqBody.text ?? reqBody.message ?? "";
    let content;
    if (reqBody.media_url) {
      const isImage = /\.(jpe?g|png|gif|webp)$/i.test(reqBody.media_url) || reqBody.type === "image";
      const media = await buildMedia(reqBody.media_url, reqBody.mimetype);
      content = isImage
        ? { image: media, caption: text || undefined }
        : { document: media, mimetype: reqBody.mimetype || "application/octet-stream", fileName: reqBody.filename || "file", caption: text || undefined };
    } else {
      content = { text: text || "(empty message)" };
    }

    const sent = await socket.sendMessage(jid, content);
    const key = sent?.key || {};
    const msg = {
      id: key.id || id("wamid"),
      channel: "whatsapp",
      provider,
      to,
      type: reqBody.media_url ? (content.image ? "media" : "document") : "text",
      text: text || null,
      template: reqBody.template ?? null,
      media_url: reqBody.media_url ?? null,
      status: "delivered", // WA Web accepted it (no delivery receipt tracking here)
      delivered_at: now(),
      created_at: now(),
    };
    outbox.push(msg);
    log({ evt: "outbound.delivered", provider, to: msg.to, type: msg.type, id: msg.id });
    return msg;
  }

  /** Resolve media_url (http(s) URL or /path inside the container) to a buffer/stream. */
  async function buildMedia(mediaUrl, mimetype) {
    if (/^https?:\/\//i.test(mediaUrl)) {
      const res = await fetch(mediaUrl, { signal: AbortSignal.timeout(15000) });
      if (!res.ok) throw new Error(`media fetch failed: HTTP ${res.status}`);
      return Buffer.from(await res.arrayBuffer());
    }
    // Local path (must exist inside the container/volume)
    return { stream: createReadStream(mediaUrl) };
  }

  // ---- status / qr ----------------------------------------------------------
  function status() {
    return {
      status: "ok",
      provider,
      connected,
      me: me?.id || null,
      me_name: me?.name || null,
      // Always show the EFFECTIVE auth dir — never null before start(), so an
      // operator can verify BAILEYS_AUTH_DIR is honoured even while unpaired.
      auth_dir: authDir || previewAuthDir(authDirCfg),
      qr_pending: Boolean(lastQr),
      qr_issued_at: lastQrAt,
      autoreply: autoReply,
      last_disconnect: lastDisconnect,
      outbox: outbox.length,
      inbound: inboundEvents.length,
    };
  }

  async function getQr(format = "json") {
    if (connected) return { provider, connected: true, qr: null, message: "already paired" };
    if (!lastQr) return { provider, connected: false, qr: null, message: "no QR yet — call POST /v1/pair to start" };
    if (format === "text") return { provider, connected: false, qr: lastQr, issued_at: lastQrAt };
    return { provider, connected: false, qr: lastQr, issued_at: lastQrAt };
  }

  /**
   * Canonical session view (GET /session). A single, provider-aware summary of
   * the pairing lifecycle: `state` is one of
   *   paired | pairing (QR pending) | starting | unpaired | logged_out
   * and `needs_pairing` tells an operator/UI whether action is required.
   */
  function session() {
    let state;
    if (connected) state = "paired";
    else if (lastQr) state = "pairing";
    else if (starting) state = "starting";
    else if (lastDisconnect?.code === 401) state = "logged_out";
    else state = "unpaired";

    return {
      provider,
      state,
      connected,
      needs_pairing: !connected,
      paired_number: me?.id || null,
      paired_name: me?.name || null,
      qr_pending: Boolean(lastQr),
      qr_issued_at: lastQrAt,
      auth_dir: authDir || previewAuthDir(authDirCfg),
      autoreply: autoReply,
      last_disconnect: lastDisconnect,
      // Concrete next actions for a human/UI:
      actions: connected
        ? { qr: "GET /session/qr", logout: "DELETE /session" }
        : { pair: "POST /session/pair", qr: "GET /session/qr", status: "GET /session" },
    };
  }

  /** Force a fresh pairing: wipe the auth dir and reconnect. */
  async function pair() {
    stopped = false;
    try {
      await socket?.logout?.();
    } catch {
      /* already down */
    }
    try {
      await socket?.end?.(undefined);
    } catch {
      /* ignore */
    }
    socket = null;
    connected = false;
    lastQr = null;
    if (authDir) {
      await fs.rm(authDir, { recursive: true, force: true });
      await resolveAuthDir(authDir);
    }
    await start();
    return { provider, message: "pairing started — scan GET /qr" };
  }

  async function logout() {
    stopped = true;
    connected = false;
    try {
      await socket?.logout?.();
    } catch {
      /* ignore */
    }
    try {
      await socket?.end?.(undefined);
    } catch {
      /* ignore */
    }
    socket = null;
    lastQr = null;
    me = null;
    if (authDir) await fs.rm(authDir, { recursive: true, force: true }).catch(() => {});
    return { provider, message: "logged out & auth cleared" };
  }

  return { provider, send, receive: null, status, session, getQr, start, stop, pair, logout, outbox, inboundEvents };
}
