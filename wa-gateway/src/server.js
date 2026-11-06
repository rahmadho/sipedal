// =============================================================================
// WhatsApp Gateway — CuciKu
// -----------------------------------------------------------------------------
// One HTTP surface, two interchangeable providers:
//
//   WA_PROVIDER=mock     (DEFAULT) in-memory WhatsApp Business API emulator.
//                        Zero deps, zero internet, deterministic → demo & CI.
//   WA_PROVIDER=baileys  real WhatsApp (multi-device) via @whiskeysockets/baileys,
//                        with QR pairing + persistent auth session.
//
// HTTP surface (identical for both providers):
//   POST /v1/messages              -> outbound send (text, template, media)
//   GET  /v1/messages              -> outbox log (last N)
//   POST /v1/webhook/simulate      -> inject an *inbound* message (mock ONLY, 501 on baileys)
//   GET  /v1/webhook/events        -> inbound event log (last N)
//   GET  /session                  -> session/pairing status (all providers)
//   POST /session/pair             -> start/reset QR pairing            (baileys only)
//   GET  /session/qr               -> scan QR: ?format=json|text|html    (baileys only)
//   DELETE /session                -> unpair & clear session             (baileys only)
//   POST /v1/pair                  -> alias of POST /session/pair        (baileys only)
//   GET  /qr                       -> alias of GET  /session/qr          (baileys only)
//   POST /v1/logout                -> alias of DELETE /session           (baileys only)
//   GET  /health                   -> liveness (200 while the process runs)
//   GET  /health/ready             -> readiness (503 until baileys paired)
//   GET  /                         -> human-readable console (HTML)
//
// Inbound messages are forwarded to WA_CALLBACK_URL (default in docker-compose).
//
// Canonical vs. alias: /session/* is the canonical surface. The legacy
// /v1/pair, /qr and /v1/logout paths are kept as thin aliases and MUST behave
// byte-for-byte identically (same providers calls, same status codes, same
// response bodies) — both delegate to the handlers below.
// =============================================================================
import http from "node:http";

import { log } from "./lib/shared.js";
import { consoleHtml, qrPngBuffer } from "./lib/console.js";
import { createProvider, providerConfigFromEnv } from "./providers/index.js";

const PORT = Number(process.env.PORT || 8090);
const cfg = providerConfigFromEnv();
const provider = createProvider(cfg);

// -----------------------------------------------------------------------------
// HTTP plumbing
// -----------------------------------------------------------------------------
function readJson(req) {
  return new Promise((resolve, reject) => {
    let data = "";
    req.on("data", (c) => {
      data += c;
      if (data.length > 2_000_000) reject(new Error("payload too large"));
    });
    req.on("end", () => {
      if (!data) return resolve({});
      try {
        resolve(JSON.parse(data));
      } catch {
        reject(Object.assign(new Error("invalid JSON body"), { statusCode: 400 }));
      }
    });
    req.on("error", reject);
  });
}

function json(res, code, payload) {
  const body = JSON.stringify(payload);
  res.writeHead(code, {
    "content-type": "application/json; charset=utf-8",
    "content-length": Buffer.byteLength(body),
    "access-control-allow-origin": "*",
  });
  res.end(body);
}

function html(res, code, body) {
  res.writeHead(code, { "content-type": "text/html; charset=utf-8" });
  res.end(body);
}

function notFound(res, method, pathname) {
  return json(res, 404, { success: false, error: { code: "NOT_FOUND", message: `no route ${method} ${pathname}` } });
}

/** Guard: routes that only make sense on the baileys provider. */
function requireBaileys(res) {
  if (provider.provider === "baileys") return true;
  json(res, 501, {
    success: false,
    error: {
      code: "PROVIDER_UNSUPPORTED",
      message: `this endpoint is only available when WA_PROVIDER=baileys (current: ${provider.provider})`,
    },
  });
  return false;
}

// -----------------------------------------------------------------------------
// Session handlers — ONE implementation shared by the canonical /session/* route
// and its legacy alias (/v1/pair, /qr, /v1/logout). Keeping the logic here (not
// duplicated per path) is what guarantees the alias stays identical.
// -----------------------------------------------------------------------------

/** GET /session — provider-aware pairing summary. Available on ALL providers. */
function handleSessionStatus(res) {
  return json(res, 200, { success: true, data: provider.session() });
}

/**
 * POST /session/pair — start/reset QR pairing. Baileys only: the mock has no
 * real account to link, so it stays 501 there (contract: "baileys only").
 */
async function handlePair(res) {
  if (!requireBaileys(res)) return;
  const result = await provider.pair();
  return json(res, 200, { success: true, data: result });
}

/**
 * GET /session/qr — pairing QR. `?format=json|text` returns JSON; `format=html`
 * renders the pairing console page. Baileys only (501 on mock).
 */
async function handleQr(res, url) {
  if (!requireBaileys(res)) return;
  const format = url.searchParams.get("format") || "json";
  if (format === "html") {
    const qr = (await provider.getQr()).qr;
    const body = await consoleHtml({ provider: provider.provider, port: PORT, statusData: provider.status(), qr });
    return html(res, 200, body);
  }
  if (format === "png" || format === "image") {
    // Render the QR to a PNG locally — no third-party image service. The client
    // (console /settings) points an <img> straight at this endpoint.
    const qr = (await provider.getQr()).qr;
    if (!qr) return json(res, 404, { success: false, error: { code: "NO_QR", message: "no QR yet — call POST /session/pair to start" } });
    const width = Number(url.searchParams.get("size") || 320);
    const png = await qrPngBuffer(qr, Number.isFinite(width) ? Math.min(Math.max(width, 160), 640) : 320);
    res.writeHead(200, {
      "content-type": "image/png",
      "content-length": png.length,
      "cache-control": "no-store",
      "access-control-allow-origin": "*",
    });
    return res.end(png);
  }
  const out = await provider.getQr(format);
  return json(res, 200, { success: true, data: out });
}

/**
 * DELETE /session — unpair & clear the stored auth session. Baileys only
 * (501 on mock). The legacy alias is POST /v1/logout (same handler).
 */
async function handleLogout(res) {
  if (!requireBaileys(res)) return;
  const result = await provider.logout();
  return json(res, 200, { success: true, data: result });
}

// -----------------------------------------------------------------------------
// Router
// -----------------------------------------------------------------------------
const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://${req.headers.host}`);
  const { pathname } = url;
  const method = req.method || "GET";

  if (method === "OPTIONS") {
    res.writeHead(204, {
      "access-control-allow-origin": "*",
      "access-control-allow-methods": "GET,POST,DELETE,OPTIONS",
      "access-control-allow-headers": "content-type,x-wa-signature",
    });
    return res.end();
  }

  try {
    // ---- console ------------------------------------------------------------
    if (method === "GET" && pathname === "/") {
      const qr = provider.provider === "baileys" ? (await provider.getQr()).qr : null;
      const body = await consoleHtml({ provider: provider.provider, port: PORT, statusData: provider.status(), qr });
      return html(res, 200, body);
    }

    // ---- health -------------------------------------------------------------
    // Liveness: the process is up and serving. This MUST stay 200 even when the
    // baileys session is not paired yet — an unpaired gateway is perfectly alive
    // (it serves /session, /session/qr and the console). Reporting 503 here made
    // orchestrators/compose mark a healthy, waiting-for-pairing gateway as DOWN
    // and block dependent services. Readiness is signalled separately by
    // /health/ready (and the `ready` flag below).
    if (method === "GET" && pathname === "/health") {
      const s = provider.status();
      const ready = provider.provider !== "baileys" || s.connected;
      return json(res, 200, { ...s, ready, linked: s.connected, uptime_s: Math.round(process.uptime()) });
    }

    // Readiness: only 200 once the provider can actually serve traffic (baileys
    // must be paired). Use this — NOT /health — for gate checks that need WA.
    if (method === "GET" && pathname === "/health/ready") {
      const s = provider.status();
      const ready = provider.provider !== "baileys" || s.connected;
      return json(res, ready ? 200 : 503, {
        status: ready ? "ready" : "not_ready",
        ready,
        provider: s.provider,
        connected: s.connected,
        hint: ready ? undefined : "baileys belum dipasangkan — POST /session/pair lalu scan GET /session/qr",
        uptime_s: Math.round(process.uptime()),
      });
    }

    // ---- outbound -----------------------------------------------------------
    if (method === "POST" && pathname === "/v1/messages") {
      const body = await readJson(req);
      const msg = await provider.send(body);
      return json(res, 201, { success: true, data: msg });
    }

    if (method === "GET" && pathname === "/v1/messages") {
      const limit = Math.min(Number(url.searchParams.get("limit") || 20), 200);
      return json(res, 200, { success: true, data: provider.outbox.last(limit), meta: { total: provider.outbox.length } });
    }

    // ---- inbound (mock simulation) -----------------------------------------
    if (method === "POST" && pathname === "/v1/webhook/simulate") {
      if (provider.provider !== "mock") {
        return json(res, 501, {
          success: false,
          error: { code: "PROVIDER_UNSUPPORTED", message: "inbound simulation only exists on the mock provider" },
        });
      }
      const body = await readJson(req);
      const result = await provider.receive(body);
      return json(res, 200, { success: true, data: result });
    }

    if (method === "GET" && pathname === "/v1/webhook/events") {
      const limit = Math.min(Number(url.searchParams.get("limit") || 20), 200);
      return json(res, 200, { success: true, data: provider.inboundEvents.last(limit), meta: { total: provider.inboundEvents.length } });
    }

    // ---- session / pairing --------------------------------------------------
    // Canonical surface: /session/*
    if (method === "GET" && pathname === "/session") return handleSessionStatus(res);
    if (method === "POST" && pathname === "/session/pair") return handlePair(res);
    if (method === "GET" && pathname === "/session/qr") return handleQr(res, url);
    if (method === "DELETE" && pathname === "/session") return handleLogout(res);

    // Legacy aliases — identical behaviour, same handlers (see header note).
    if (method === "POST" && pathname === "/v1/pair") return handlePair(res);
    if (method === "GET" && pathname === "/qr") return handleQr(res, url);
    if (method === "POST" && pathname === "/v1/logout") return handleLogout(res);

    return notFound(res, method, pathname);
  } catch (err) {
    const code = err.statusCode || 500;
    log({ evt: "error", provider: provider.provider, path: pathname, error: String(err?.message || err) });
    return json(res, code, {
      success: false,
      error: {
        code: code === 400 ? "VALIDATION_ERROR" : code === 503 ? "NOT_CONNECTED" : "INTERNAL",
        message: String(err?.message || err),
      },
    });
  }
});

// -----------------------------------------------------------------------------
// Boot
// -----------------------------------------------------------------------------
server.listen(PORT, async () => {
  log({
    evt: "startup",
    provider: provider.provider,
    port: PORT,
    callback: cfg.callbackUrl || "(none)",
  });
  console.log(`[wa-gateway] provider=${provider.provider} listening on http://0.0.0.0:${PORT}  (console: /)`);

  // Start the provider AFTER the server is up so /health and /qr are reachable
  // while Baileys is still negotiating the connection / showing a QR.
  try {
    await provider.start();
  } catch (err) {
    log({ evt: "provider.start.failed", provider: provider.provider, error: String(err?.message || err) });
    if (err?.code === "BAILEYS_MISSING") {
      console.error(`[wa-gateway] ${err.message}`);
      console.error("[wa-gateway] tip: set WA_PROVIDER=mock for the dependency-free gateway.");
    }
  }
});

const shutdown = (sig) => {
  log({ evt: "shutdown", sig, provider: provider.provider });
  Promise.resolve(provider.stop?.())
    .catch(() => {})
    .finally(() => {
      server.close(() => process.exit(0));
      setTimeout(() => process.exit(0), 2000).unref();
    });
};
process.on("SIGTERM", () => shutdown("SIGTERM"));
process.on("SIGINT", () => shutdown("SIGINT"));

export { server, provider };
