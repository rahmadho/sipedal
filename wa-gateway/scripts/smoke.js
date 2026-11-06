#!/usr/bin/env node
// =============================================================================
// Smoke test — boots the gateway in MOCK mode and exercises the full surface.
// Zero external deps → safe to run in CI without Baileys or a WA account.
//
//   node scripts/smoke.js
// =============================================================================
import { spawn } from "node:child_process";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const PORT = process.env.SMOKE_PORT || "8099";
const BASE = `http://localhost:${PORT}`;

const child = spawn(process.execPath, [path.join(__dirname, "..", "src", "server.js")], {
  env: { ...process.env, WA_PROVIDER: "mock", PORT, WA_MOCK_AUTOREPLY: "true" },
  stdio: ["ignore", "pipe", "pipe"],
});
child.stdout.on("data", (d) => process.stdout.write(`[gw] ${d}`));
child.stderr.on("data", (d) => process.stderr.write(`[gw:err] ${d}`));

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function waitForHealth(tries = 40) {
  for (let i = 0; i < tries; i++) {
    try {
      const r = await fetch(`${BASE}/health`);
      if (r.ok) return await r.json();
    } catch {
      /* not up yet */
    }
    await sleep(250);
  }
  throw new Error("gateway did not become healthy");
}

let failures = 0;
function check(name, cond, detail) {
  const ok = Boolean(cond);
  console.log(`${ok ? "✅" : "❌"} ${name}${detail ? ` — ${detail}` : ""}`);
  if (!ok) failures++;
}

try {
  const health = await waitForHealth();
  check("health ok", health.status === "ok", `provider=${health.provider}`);
  check("default provider = mock", health.provider === "mock");

  // outbound
  const sendRes = await fetch(`${BASE}/v1/messages`, {
    method: "POST",
    headers: { "content-type": "application/json" },
    body: JSON.stringify({ to: "628123456789", text: "Halo dari smoke test" }),
  });
  const sendBody = await sendRes.json();
  check("outbound send 201", sendRes.status === 201 && sendBody.success === true);
  check("outbound has wamid", /^wamid_/.test(sendBody.data?.id || ""));

  // inbound simulation → bot auto-reply
  const inRes = await fetch(`${BASE}/v1/webhook/simulate`, {
    method: "POST",
    headers: { "content-type": "application/json" },
    body: JSON.stringify({ from: "628123456789", text: "menu" }),
  });
  const inBody = await inRes.json();
  check("inbound simulate 200", inRes.status === 200 && inBody.success === true);
  check("bot auto-replied", Boolean(inBody.data?.auto_reply?.text), inBody.data?.auto_reply?.text?.split("\n")[0]);

  // outbox log
  const listRes = await fetch(`${BASE}/v1/messages?limit=5`);
  const listBody = await listRes.json();
  check("outbox log has ≥2 entries", listBody.data.length >= 2, `total=${listBody.meta?.total}`);

  // inbound events log
  const evRes = await fetch(`${BASE}/v1/webhook/events?limit=5`);
  const evBody = await evRes.json();
  check("inbound events logged", evBody.data.length >= 1);

  // pairing endpoints are baileys-only → 501 on mock (canonical + aliases)
  const pairRes = await fetch(`${BASE}/v1/pair`, { method: "POST" });
  check("pair returns 501 on mock", pairRes.status === 501);
  const qrRes = await fetch(`${BASE}/qr`);
  check("qr returns 501 on mock", qrRes.status === 501);

  // ---- /session/* (canonical surface) --------------------------------------
  const sessionRes = await fetch(`${BASE}/session`);
  const sessionBody = await sessionRes.json();
  check("GET /session 200 on mock", sessionRes.status === 200 && sessionBody.success === true);
  check("session has state + actions", Boolean(sessionBody.data?.state) && Boolean(sessionBody.data?.actions));
  check("mock session state = paired", sessionBody.data?.state === "paired", `state=${sessionBody.data?.state}`);

  const cpair = await fetch(`${BASE}/session/pair`, { method: "POST" });
  check("POST /session/pair 501 on mock", cpair.status === 501);
  const cqr = await fetch(`${BASE}/session/qr`);
  check("GET /session/qr 501 on mock", cqr.status === 501);
  const clogout = await fetch(`${BASE}/session`, { method: "DELETE" });
  check("DELETE /session 501 on mock", clogout.status === 501);
  const v1logout = await fetch(`${BASE}/v1/logout`, { method: "POST" });
  check("POST /v1/logout 501 on mock (alias)", v1logout.status === 501);

  // console HTML
  const html = await fetch(`${BASE}/`);
  const body = await html.text();
  check("console HTML served", html.ok && body.includes("CuciKu WhatsApp Gateway"));

  console.log(`\n${failures === 0 ? "🎉 semua smoke test lulus" : `⚠️  ${failures} pemeriksaan gagal`}`);
} catch (err) {
  console.error("smoke test error:", err);
  failures++;
} finally {
  child.kill("SIGTERM");
  await sleep(300);
  process.exit(failures === 0 ? 0 : 1);
}
