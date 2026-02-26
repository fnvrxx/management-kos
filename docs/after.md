# Current System State (After Remodel)
> Documented: February 26, 2026

---

## Summary of Changes

This document compares the **current** system state to the original state documented in `before.md`. Every issue identified in the gap analysis has been resolved.

---

## Database Schema Changes

### `penyewa` — Column Removed

| Column | Before | After | Notes |
|---|---|---|---|
| id | ✅ | ✅ | PK |
| nama_lengkap | ✅ | ✅ | |
| no_wa | ✅ | ✅ | |
| start_date | ✅ | ✅ | |
| rencana_lama_kos | ✅ | ✅ | |
| end_date | ✅ | ✅ | |
| ~~tgl_jatuh_tempo_berikutnya~~ | ✅ | ❌ **REMOVED** | Moved to `tempat_kos.tgl_jatuh_tempo` — now auto-computed |

### `tempat_kos` — Major Restructure

| Column | Before | After | Notes |
|---|---|---|---|
| id | ✅ | ✅ | PK |
| lokasi | ✅ enum | ✅ enum | Malang / Surabaya / Kediri |
| nomor_kamar | ✅ | ✅ | |
| ~~kode_unik~~ | ✅ (auto-gen MLG-001) | ❌ **REMOVED** | `lokasi + nomor_kamar` is now the natural identifier |
| ~~id_transaksi~~ | ✅ FK → transaksi_kos | ❌ **REMOVED** | Reversed: transaksi_kos now has FK to tempat_kos |
| id_penyewa | ✅ FK → penyewa | ✅ | |
| **harga** | ❌ | ✅ **ADDED** | Base monthly room price (int) |
| **status** | ❌ | ✅ **ADDED** | enum: Ditempati / Kosong — **auto-derived** from `id_penyewa` |
| **tgl_jatuh_tempo** | ❌ | ✅ **ADDED** | Auto-computed: `start_date + floor(totalPaid / harga) months` |

### `transaksi_kos` — Major Restructure

| Column | Before | After | Notes |
|---|---|---|---|
| id | ✅ | ✅ | PK |
| id_penyewa | ✅ FK | ✅ FK | |
| **id_tempat_kos** | ❌ | ✅ **ADDED** | FK → tempat_kos (replaces lokasi_kos) |
| ~~lokasi_kos~~ | ✅ free-text | ❌ **REMOVED** | Now derived from `tempatKos.lokasi` |
| ~~price_kos~~ | ✅ int | ❌ **REMOVED** | Moved to `tempat_kos.harga` |
| nominal | ✅ | ✅ | Actual amount paid (supports partial/cicilan) |
| tanggal_pembayaran | ✅ | ✅ | |
| durasi_bulan_dibayar | ✅ | ✅ | Use 0 for partial/cicilan payments |
| metode_pembayaran | ✅ | ✅ | Transfer / Tunai / QRIS |
| bukti_transfer | ✅ | ✅ | |
| periode_mulai | ✅ | ✅ | |
| periode_selesai | ✅ | ✅ | |
| history_pembayaran | ✅ | ✅ | |

### `reminder` — Column Added

| Column | Before | After | Notes |
|---|---|---|---|
| id | ✅ | ✅ | PK |
| id_penyewa | ✅ FK | ✅ FK | |
| end_date | ✅ | ✅ | |
| broadcast | ✅ | ✅ | |
| history_reminder | ✅ | ✅ | |
| **tanggungan** | ❌ | ✅ **ADDED** | Outstanding amount (Rp) — auto-synced on payment |

### `pengeluaran` — No Changes
Unchanged. Now has a full Filament resource (previously dead code).

### `template_messages` — **NEW TABLE**

| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| nama_template | varchar | Template name |
| isi_template | text | Message body with `{variable}` placeholders |
| is_default | boolean | Only one can be default at a time |
| timestamps | | |

**Supported variables:** `{nama}`, `{lokasi}`, `{kamar}`, `{tagihan}`, `{jatuh_tempo}`

### `exports`, `imports`, `failed_import_rows` — Filament System Tables
Published from Filament vendor package to support Excel export functionality.

---

## Relationship Changes

### Before (Problematic)
```
penyewa ──< reminder
penyewa ──< transaksi_kos
penyewa ──  tempat_kos        (via id_penyewa)
tempat_kos ── transaksi_kos   (via id_transaksi on tempat_kos) ← ONE-TO-ONE, WRONG DIRECTION
pengeluaran                   (standalone)
```

### After (Fixed)
```
penyewa ──< reminder          (one-to-many via id_penyewa)
penyewa ──< transaksi_kos     (one-to-many via id_penyewa)
penyewa ──  tempat_kos        (one-to-one via id_penyewa on tempat_kos)
tempat_kos ──< transaksi_kos  (one-to-many via id_tempat_kos on transaksi_kos) ← FIXED
pengeluaran                   (standalone)
template_messages             (standalone, config data)
```

**Key fix:** Relationship direction reversed. Rooms can now have full payment history.

---

## Payment Status Logic Changes

### Before
```
LUNAS      → tanggal_pembayaran is in current calendar month (fragile!)
BELUM BAYAR → everything else
AMAN       → tgl_jatuh_tempo_berikutnya > today + 7 days (manually managed, can go stale)
```

### After
```
LUNAS       → tgl_jatuh_tempo > today (auto-computed from sum of all payments)
CICILAN     → partial payment exists (totalPaid % harga > 0)
TUNGGAKAN   → tgl_jatuh_tempo ≤ today AND no partial payment
BELUM BAYAR → no transaction recorded AND no tgl_jatuh_tempo
BELUM ASSIGN → penyewa has no room assigned
```

**Key improvement:** `tgl_jatuh_tempo` is auto-computed using the formula:
```
tgl_jatuh_tempo = penyewa.start_date + floor(totalPaid / harga) months
```
This eliminates stale data — status is always accurate.

---

## Feature Changes

### Before → After Comparison

| Feature | Before | After |
|---|---|---|
| Payment tracking | 1 transaction per room (relationship limit) | Full history per room (one-to-many) |
| Installments | Not supported | ✅ Sum-based cicilan — partial payments tracked |
| Status computation | Manual `tgl_jatuh_tempo_berikutnya` on penyewa | Auto-computed on `tempat_kos` via boot hooks |
| Room status | No column — must join to check | Auto-derived `status` column (Ditempati/Kosong) |
| WhatsApp | Fonnte API (external service) | `api.whatsapp.com/send` click-to-chat (no dependency) |
| WA Message | Hardcoded text | Customizable templates with `{variable}` system |
| Expense Management | Table + model existed, no UI | Full CRUD PengeluaranResource |
| Dashboard | Basic stats only | Stats widget + penyewa cards grouped by lokasi |
| Dashboard Cards | Not available | Color-coded cards with payment status, sortable |
| Payment History | Not viewable | Click-to-view modal on dashboard cards |
| Reminder Sync | Manual — disconnected from payments | Auto-sync: payment clears/updates reminders |
| Room Identifier | `kode_unik` (auto-generated MLG-001) | `lokasi + nomor_kamar` (simpler, more relatable) |
| Excel Export | Worked but used old columns | Updated: uses `tempatKos.lokasi` + `tempatKos.nomor_kamar` |
| Filter UI | Mixed (some AboveContent, some dropdown) | Consistent funnel icon dropdown on all resources |
| Checkout | Manually set `status='Kosong'` | Auto-derived — just clear `id_penyewa` |
| Queue | Database (requires worker) | Sync (immediate processing, no worker needed) |

---

## New Filament Resources

| Resource | Before | After |
|---|---|---|
| PenyewaResource | ✅ Basic | ✅ Enhanced (cicilan support, quick pay, checkout) |
| TempatKosResource | ✅ Basic | ✅ Enhanced (harga, auto-status, jatuh_tempo) |
| TransaksiKosResource | ✅ Basic | ✅ Enhanced (room select, export, sum-based) |
| ReminderResource | ✅ Basic (Fonnte) | ✅ Enhanced (WA click-to-chat, DB templates) |
| PengeluaranResource | ❌ Not existed | ✅ **NEW** (full CRUD with filters) |
| TemplateMessageResource | ❌ Not existed | ✅ **NEW** (template CRUD with variable buttons) |

---

## New Widgets

| Widget | Before | After |
|---|---|---|
| StatsOverview | ✅ Basic (2-3 stats) | ✅ Enhanced (5 stats: Pemasukan, Tunggakan, Cicilan, Belum Bayar, Ketersediaan) |
| PenyewaCardWidget | ❌ Not existed | ✅ **NEW** (grouped by lokasi, sortable, click for history) |

---

## Gap Analysis Resolution

| # | Original Issue | Resolution |
|---|---|---|
| 1 | Relationship direction reversed | ✅ `transaksi_kos.id_tempat_kos` FK — one room has many transactions |
| 2 | `lokasi_kos` duplicates `tempat_kos.lokasi` | ✅ Removed — derived from `tempatKos.lokasi` via relationship |
| 3 | `price_kos` misplaced on transactions | ✅ Moved to `tempat_kos.harga` |
| 4 | `tgl_jatuh_tempo_berikutnya` can go stale | ✅ Auto-computed `tgl_jatuh_tempo` on `tempat_kos` |
| 5 | No status column on rooms | ✅ Auto-derived `status` from `id_penyewa` presence |
| 6 | Calendar-month payment logic is fragile | ✅ Sum-based: `floor(totalPaid / harga)` months |
| 7 | No Filament UI for pengeluaran | ✅ Full PengeluaranResource created |
| 8 | Reminder disconnected from payments | ✅ `syncReminders()` on `TransaksiKos::saved/deleted` |

---

## Migration History

```
# Original migrations
2026_02_17_195038  create_penyewas_table
2026_02_17_195042  create_transaksi_kos_table
2026_02_17_195047  create_tempat_kos_table
2026_02_17_195051  create_reminders_table
2026_02_17_212804  add_penyewa_id_to_transaksi_kos_table
2026_02_17_213849  upgrade_system_features

# Remodel migrations
2026_02_26_000001  remodel_tempat_kos_add_columns (harga, status, tgl_jatuh_tempo)
2026_02_26_000002  remodel_transaksi_kos_add_id_tempat_kos
2026_02_26_000003  remodel_data_migration_and_cleanup (drop old columns)
2026_02_26_100000  add_tanggungan_to_reminder
2026_02_26_200000  drop_kode_unik_add_templates

# Filament system tables
2026_02_26_105014  create_imports_table
2026_02_26_105015  create_exports_table
2026_02_26_105016  create_failed_import_rows_table
```
