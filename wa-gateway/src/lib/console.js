// =============================================================================
// Human-readable HTML console (GET / and GET /qr)
// -----------------------------------------------------------------------------
// Provider-aware: mock shows endpoint cheatsheet; baileys shows pairing status
// and an inline QR when a pairing is pending.
// =============================================================================
import QRCode from "qrcode";

const CSS = `
 body{font-family:ui-monospace,Menlo,monospace;background:#0b141a;color:#e9edef;margin:0;padding:24px}
 h1{color:#25d366;font-size:18px} .card{background:#111b21;border:1px solid #223;border-radius:10px;padding:16px;margin:12px 0}
 code{color:#25d366} a{color:#53bdeb} pre{white-space:pre-wrap;word-break:break-word;max-height:320px;overflow:auto}
 .qr{background:#fff;padding:12px;border-radius:8px;display:inline-block} .badge{padding:2px 8px;border-radius:999px;font-size:12px}
 .ok{background:#0a2e1a;color:#25d366} .warn{background:#3a2e0a;color:#e9c46a} .off{background:#3a0a0a;color:#ff6b6b}
 img{image-rendering:pixelated}
`;

async function qrDataUrl(text) {
  try {
    return await QRCode.toDataURL(text, { margin: 1, width: 320 });
  } catch {
    return null;
  }
}

/**
 * Render a pairing QR to a PNG buffer (server-side, no third-party service).
 * The pairing string is long (~280 chars); relying on an external renderer such
 * as api.qrserver.com proved unreliable (timeouts / blank image). Rendering here
 * with the bundled `qrcode` package keeps the QR working offline and instantly.
 */
export async function qrPngBuffer(text, width = 320) {
  return QRCode.toBuffer(text, { type: "png", margin: 1, width });
}

export async function consoleHtml({ provider, port, statusData, qr }) {
  const badge =
    statusData?.connected === true
      ? `<span class="badge ok">connected</span>`
      : `<span class="badge warn">not paired</span>`;

  const providerBlock =
    provider === "baileys"
      ? await baileysBlock({ port, statusData, qr })
      : mockBlock({ port });

  return `<!doctype html><html><head><meta charset="utf-8">
<title>WhatsApp Gateway — ${provider}</title><style>${CSS}</style></head><body>
<h1>🧺 CuciKu WhatsApp Gateway — provider: <code>${provider}</code> ${badge}</h1>
<div class="card"><b>Status</b><pre>${escapeHtml(JSON.stringify(statusData, null, 2))}</pre></div>
${providerBlock}
<div class="card"><b>Endpoints</b><pre>POST /v1/messages          # outbound
GET  /v1/messages?limit=   # outbox log
${provider === "mock" ? `POST /v1/webhook/simulate  # inject inbound (mock only)` : `GET  /session              # pairing status
POST /session/pair         # start/reset QR pairing
GET  /session/qr           # scan QR (HTML) / ?format=text|json
DELETE /session            # unpair & clear session`}
GET  /session              # session status (all providers)
GET  /v1/webhook/events?limit=  # inbound log
GET  /health
GET  /</pre></div>
</body></html>`;
}

function mockBlock({ port }) {
  return `<div class="card"><b>Coba cepat</b>
<pre>curl -s localhost:${port}/v1/messages -H 'content-type: application/json' \\
  -d '{"to":"628123456789","text":"Pesanan kamu READY ✅"}'

curl -s localhost:${port}/v1/webhook/simulate -H 'content-type: application/json' \\
  -d '{"from":"628123456789","text":"menu"}'</pre></div>`;
}

async function baileysBlock({ port, statusData, qr }) {
  if (statusData?.connected) {
    return `<div class="card"><b>Terhubung ✅</b><pre>Nomor : ${escapeHtml(statusData.me || "-")}
Nama  : ${escapeHtml(statusData.me_name || "-")}</pre></div>`;
  }
  const dataUrl = qr ? await qrDataUrl(qr) : null;
  if (!qr) {
    return `<div class="card"><b>Belum dipasangkan</b>
<p>Belum ada QR. Mulai pairing:</p>
<pre>curl -s -X POST localhost:${port}/session/pair</pre>
<p>…lalu buka <a href="/session/qr">/session/qr</a> untuk memindai.</p></div>`;
  }
  return `<div class="card"><b>Scan QR untuk menautkan WhatsApp</b>
<p>WhatsApp → <i>Perangkat Tertaut</i> → <i>Tautkan Perangkat</i></p>
${dataUrl ? `<div class="qr"><img src="${dataUrl}" alt="WhatsApp pairing QR" width="320" height="320"/></div>` : "<pre>(QR renderer unavailable)</pre>"}
<details><summary>QR mentah</summary><pre>${escapeHtml(qr)}</pre></details></div>`;
}

function escapeHtml(s) {
  return String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);
}
