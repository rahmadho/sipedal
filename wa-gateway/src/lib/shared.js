// =============================================================================
// Shared helpers — logging, ids, crypto signing, sleep
// =============================================================================
import crypto from "node:crypto";

export const now = () => new Date().toISOString();
export const id = (p) => `${p}_${crypto.randomBytes(8).toString("hex")}`;
export const sleep = (ms) => (ms > 0 ? new Promise((r) => setTimeout(r, ms)) : Promise.resolve());

export function log(...a) {
  console.log(
    JSON.stringify({
      ts: now(),
      svc: "wa-gateway",
      ...(a[0] && typeof a[0] === "object" ? a[0] : { msg: a.join(" ") }),
    }),
  );
}

/** HMAC-SHA256 signature in the `sha256=<hex>` shape used by Meta/Mock. */
export function sign(body, secret = process.env.WA_WEBHOOK_SECRET || "mock-secret") {
  return "sha256=" + crypto.createHmac("sha256", secret).update(body).digest("hex");
}

// -----------------------------------------------------------------------------
// Phone normalisation — E.164 without `+` (AGENT.md §3.9).
// Baileys uses JIDs like `62812xxxx@s.whatsapp.net`; the API expects `62812xxxx`.
// -----------------------------------------------------------------------------
export function toE164(jidOrPhone) {
  if (!jidOrPhone) return "";
  let s = String(jidOrPhone).trim();
  // Strip Baileys JID suffix / device tag.
  s = s.replace(/@.*$/, "").replace(/:\d+$/, "");
  s = s.replace(/[^\d]/g, "");
  return s;
}

/** Build a Baileys JID from an E.164 number (digits only). */
export function toJid(phone) {
  const digits = toE164(phone);
  return digits ? `${digits}@s.whatsapp.net` : "";
}

/**
 * Forward an inbound event to the API callback (best effort; never throws).
 * Shared by every provider so the API contract stays identical.
 */
export async function forwardInbound(event, callbackUrl = process.env.WA_CALLBACK_URL || "") {
  if (!callbackUrl) return { skipped: true, reason: "no WA_CALLBACK_URL" };
  try {
    const body = JSON.stringify(event);
    const res = await fetch(callbackUrl, {
      method: "POST",
      headers: { "content-type": "application/json", "x-wa-signature": sign(body) },
      body,
      signal: AbortSignal.timeout(5000),
    });
    return { status: res.status, ok: res.ok };
  } catch (err) {
    return { error: String(err?.message || err) };
  }
}

/** Bounded in-memory ring buffer used for outbox / inbound logs. */
export class RingLog {
  constructor(max = 200) {
    this.max = max;
    this.items = [];
  }
  push(item) {
    this.items.push(item);
    if (this.items.length > this.max) this.items.shift();
    return item;
  }
  last(n = 20) {
    return this.items.slice(-n);
  }
  get length() {
    return this.items.length;
  }
}
