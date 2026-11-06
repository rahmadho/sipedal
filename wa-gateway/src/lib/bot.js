// =============================================================================
// Interactive booking bot fixture (keyword-driven state machine)
// -----------------------------------------------------------------------------
// Mirrors the PRD rule "Booking WhatsApp = model INTERAKTIF". Used by:
//   • mock provider   → auto-replies to POST /v1/webhook/simulate
//   • baileys provider → auto-replies to real inbound messages (if enabled)
// Pure function: text in → reply text out. No I/O, no deps.
// =============================================================================
import { id } from "./shared.js";

const MENU = [
  "Selamat datang di CuciKu 🧺",
  "",
  "Balas dengan angka:",
  "1️⃣  Booking cuci (kiloan)",
  "2️⃣  Cek status pesanan",
  "3️⃣  Harga & layanan",
  "0️⃣  Bicara dengan admin",
].join("\n");

export function botReply(text) {
  const t = String(text ?? "").trim().toLowerCase();

  if (/^(hai|hi|halo|hello|start|menu|\/start)$/.test(t)) return MENU;
  if (t === "1") return "Baik! Berapa kilogram (contoh: 3.5) dan alamat pickup?";
  if (t === "2") return "Kirim kode pesanan kamu (contoh: LDR-20260115-0042).";
  if (t === "3")
    return "Daftar harga:\n• Cuci Kering Setrika — Rp7.000/kg\n• Cuci Kering — Rp5.000/kg\n• Selimut — Rp50.000/pcs\n• Bed Cover — Rp75.000/pcs";
  if (t === "0") return "Admin akan segera membalas. Terima kasih 🙏";
  if (/^ldr-\d{8}-\d{4}$/.test(t))
    return "Pesanan " + String(text).trim().toUpperCase() + " berstatus: READY ✅ (siap diambil)";
  if (/^\d+(\.\d+)?$/.test(t))
    return "Tercatat " + t + " kg. Estimasi selesai 2 hari. Balas YA untuk konfirmasi pickup.";
  if (/^(ya|yes|oke|ok)$/.test(t))
    return "Booking dikonfirmasi! Kurir akan menjemput dalam 60 menit 🚚. Kode: " + id("LDR").slice(4, 12).toUpperCase();

  return "Maaf, saya belum paham 🙏 Balas *menu* untuk melihat pilihan.";
}

export { MENU };
