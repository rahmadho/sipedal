// =============================================================================
// Provider: MOCK  (default)
// -----------------------------------------------------------------------------
// Emulates a WhatsApp Business API provider with zero external dependencies and
// zero outbound internet. Deterministic → ideal for demo, CI and offline dev.
// =============================================================================
import { id, log, now, sleep, RingLog, forwardInbound } from "../lib/shared.js";
import { botReply } from "../lib/bot.js";

export function createMockProvider(config = {}) {
  const provider = "mock";
  const latencyMs = config.latencyMs ?? 0;
  const autoReply = config.autoReply ?? true;
  const callbackUrl = config.callbackUrl || "";

  const outbox = new RingLog(200);
  const inboundEvents = new RingLog(200);

  async function send(reqBody) {
    const to = reqBody.to || reqBody.phone || reqBody.recipient;
    if (!to) {
      const e = new Error("`to` is required");
      e.statusCode = 400;
      throw e;
    }
    await sleep(latencyMs);

    const msg = {
      id: id("wamid"),
      channel: "whatsapp",
      provider,
      to: String(to),
      type: reqBody.type || (reqBody.media_url ? "media" : reqBody.template ? "template" : "text"),
      text: reqBody.text ?? reqBody.message ?? null,
      template: reqBody.template ?? null,
      media_url: reqBody.media_url ?? null,
      status: "delivered", // mock: assume instant delivery
      delivered_at: now(),
      created_at: now(),
    };
    outbox.push(msg);
    log({ evt: "outbound.delivered", provider, to: msg.to, type: msg.type, id: msg.id });
    return msg;
  }

  async function receive(body) {
    const from = body.from || body.phone;
    if (!from) {
      const e = new Error("`from` is required");
      e.statusCode = 400;
      throw e;
    }
    const text = body.text ?? body.message ?? "";

    const event = {
      event: "message.received",
      id: id("evt"),
      provider,
      from: String(from),
      profile_name: body.profile_name || "Customer",
      type: body.type || "text",
      text: String(text),
      timestamp: Math.floor(Date.now() / 1000),
      received_at: now(),
    };
    inboundEvents.push(event);
    log({ evt: "inbound.received", provider, from: event.from, text: event.text });

    const forwarded_to_api = await forwardInbound(event, callbackUrl);

    let auto_reply = null;
    if (autoReply) {
      const replyText = body.reply ?? botReply(text);
      auto_reply = await send({ to: from, text: replyText });
    }

    return { event, forwarded_to_api, auto_reply };
  }

  function status() {
    return {
      status: "ok",
      provider,
      connected: true, // mock is always "connected"
      autoreply: autoReply,
      outbox: outbox.length,
      inbound: inboundEvents.length,
    };
  }

  async function getQr() {
    // Mock has no pairing flow.
    return { provider, qr: null, message: "mock provider does not require pairing" };
  }

  /**
   * Canonical session view (GET /session). The mock is always "paired" — there
   * is no real WhatsApp account — so UIs can treat it uniformly.
   */
  function session() {
    return {
      provider,
      state: "paired",
      connected: true,
      needs_pairing: false,
      paired_number: null,
      paired_name: "mock",
      qr_pending: false,
      qr_issued_at: null,
      auth_dir: null,
      autoreply: autoReply,
      last_disconnect: null,
      actions: { status: "GET /session" },
      note: "mock provider — no real session/pairing; switch WA_PROVIDER=baileys for real WhatsApp",
    };
  }

  async function start() {
    log({ evt: "startup", provider, callback: callbackUrl || "(none)" });
  }

  async function stop() {
    log({ evt: "shutdown", provider });
  }

  /**
   * Pairing lifecycle. The mock has no real WhatsApp account to link, so `pair`
   * and `logout` are no-ops that report why — the HTTP layer still returns 501
   * for these routes on mock (they are documented "baileys only"). Exposed so
   * the provider interface stays uniform and a call can never throw undefined.
   */
  async function pair() {
    return { provider, message: "mock provider does not require pairing" };
  }

  async function logout() {
    return { provider, message: "mock provider has no session to clear" };
  }

  return { provider, send, receive, status, session, getQr, start, stop, pair, logout, outbox, inboundEvents };
}
