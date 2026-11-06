// =============================================================================
// Provider factory — resolves WA_PROVIDER to a concrete adapter.
// -----------------------------------------------------------------------------
// Supported:
//   mock    → in-memory WhatsApp Business API emulator (DEFAULT, zero deps)
//   baileys → real WhatsApp multi-device via @whiskeysockets/baileys
//
// The HTTP server talks to providers through ONE interface:
//   { provider, send(body), status(), getQr(fmt), start(), stop() }
//   ⤷ mock also exposes receive(body) for POST /v1/webhook/simulate
// =============================================================================
import { createMockProvider } from "./mock.js";
import { createBaileysProvider } from "./baileys.js";
import { log } from "../lib/shared.js";

/** Read provider config from the environment (single source of truth). */
export function providerConfigFromEnv(env = process.env) {
  const bool = (v, dflt) => (v === undefined ? dflt : String(v).toLowerCase() === "true");
  const provider = String(env.WA_PROVIDER || "mock").toLowerCase();

  return {
    provider,
    callbackUrl: env.WA_CALLBACK_URL || "",
    mock: {
      autoReply: bool(env.WA_MOCK_AUTOREPLY, true),
      latencyMs: Number(env.WA_MOCK_LATENCY_MS || 0),
    },
    baileys: {
      // Real WA: auto-reply is opt-in (default OFF) to avoid accidental bot spam.
      autoReply: bool(env.WA_BAILEYS_AUTOREPLY, false),
      authDir: env.BAILEYS_AUTH_DIR || "",
      printQr: bool(env.WA_BAILEYS_PRINT_QR, true),
      botName: env.WA_BAILEYS_BOT_NAME || "Sipedal",
      logLevel: env.WA_BAILEYS_LOG_LEVEL || "warn",
    },
  };
}

export function createProvider(cfg = providerConfigFromEnv()) {
  switch (cfg.provider) {
    case "baileys":
      return createBaileysProvider({
        ...cfg.baileys,
        callbackUrl: cfg.callbackUrl,
      });
    case "mock":
      return createMockProvider({
        ...cfg.mock,
        callbackUrl: cfg.callbackUrl,
      });
    default:
      log({
        evt: "provider.unknown",
        requested: cfg.provider,
        fallback: "mock",
        hint: "WA_PROVIDER must be 'mock' or 'baileys'",
      });
      return createMockProvider({
        ...cfg.mock,
        callbackUrl: cfg.callbackUrl,
      });
  }
}
