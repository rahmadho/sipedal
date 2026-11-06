#!/usr/bin/env node
// =============================================================================
// Standalone pairing helper — run outside Docker to link a WhatsApp account.
// -----------------------------------------------------------------------------
// Usage:
//   WA_PROVIDER=baileys BAILEYS_AUTH_DIR=./.auth node scripts/pair.js
//
// Prints an ASCII QR in the terminal, then persists creds into BAILEYS_AUTH_DIR
// (same directory the gateway mounts). Once "connection open" is printed, the
// session is saved and the gateway can reuse it without re-pairing.
// =============================================================================
import { createBaileysProvider } from "../src/providers/baileys.js";

const provider = createBaileysProvider({
  autoReply: false,
  authDir: process.env.BAILEYS_AUTH_DIR || "./.auth",
  printQr: true,
  botName: process.env.WA_BAILEYS_BOT_NAME || "CuciKu",
});

console.log("[pair] memulai pairing — pindai QR dengan WhatsApp > Perangkat Tertaut");

await provider.start();

// Poll until connected (or give up after 5 minutes).
const started = Date.now();
const timer = setInterval(() => {
  const s = provider.status();
  if (s.connected) {
    clearInterval(timer);
    console.log(`[pair] ✅ terhubung sebagai ${s.me} (${s.me_name})`);
    console.log(`[pair] sesi tersimpan di: ${s.auth_dir}`);
    console.log("[pair] aman untuk keluar — jalankan gateway dengan WA_PROVIDER=baileys");
    setTimeout(() => process.exit(0), 500);
  } else if (Date.now() - started > 5 * 60 * 1000) {
    clearInterval(timer);
    console.error("[pair] ⏱️  timeout 5 menit tanpa pairing — coba lagi");
    process.exit(1);
  }
}, 1000);

process.on("SIGINT", () => {
  console.log("\n[pair] dibatalkan");
  process.exit(130);
});
