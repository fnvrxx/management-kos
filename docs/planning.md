# Remodel Planning
> Documented: February 26, 2026

---

## Gap Analysis

| # | Issue | Impact | Priority |
|---|---|---|---|
| 1 | Relationship direction `tempat_kos ↔ transaksi_kos` is reversed | Cannot query full payment history per room | Critical |
| 2 | `transaksi_kos.lokasi_kos` duplicates `tempat_kos.lokasi` | Data inconsistency risk | High |
| 3 | `transaksi_kos.price_kos` should be on `tempat_kos` | Price changes cause historical inaccuracy | High |
| 4 | `tgl_jatuh_tempo_berikutnya` on `penyewa` can go stale | Wrong status shown on dashboard | High |
| 5 | No `status` column on `tempat_kos` | Must join + compute to know if room is vacant | Medium |
| 6 | Payment status logic uses calendar month, not period coverage | LUNAS/BELUM BAYAR can be wrong for multi-month payments | Medium |
| 7 | No Filament UI for `pengeluaran` | Expense tracking is dead code | Medium |
| 8 | Reminder not linked to payment flow | Must manually create reminders after recording payment | Low |

---

## Proposed New Schema

### `penyewa` — Minor changes
| Column | Change | Notes |
|---|---|---|
| id | keep | |
| nama_lengkap | keep | |
| no_wa | keep | |
| start_date | keep | |
| rencana_lama_kos | keep | |
| end_date | keep | |
| ~~tgl_jatuh_tempo_berikutnya~~ | **REMOVE** | Moved to `tempat_kos` |

### `tempat_kos` — Major changes
| Column | Change | Notes |
|---|---|---|
| id | keep | |
| lokasi | keep | enum: Malang/Surabaya/Kediri |
| nomor_kamar | keep | |
| kode_unik | keep | Auto-generated |
| **harga** | **ADD** | Base monthly room price |
| **status** | **ADD** | enum: Ditempati / Kosong |
| id_penyewa | keep | Current occupant (nullable) |
| **tgl_jatuh_tempo** | **ADD** | Auto-synced from latest transaksi.periode_selesai + 1 day |
| ~~id_transaksi~~ | **REMOVE** | Replaced by new relationship direction |

### `transaksi_kos` — Major changes
| Column | Change | Notes |
|---|---|---|
| id | keep | |
| id_penyewa | keep | Who paid |
| **id_tempat_kos** | **ADD** | FK → tempat_kos (replaces lokasi_kos) |
| ~~lokasi_kos~~ | **REMOVE** | Derived from tempat_kos.lokasi |
| ~~price_kos~~ | **REMOVE** | Moved to tempat_kos.harga |
| nominal | keep | Actual amount paid (may differ from harga × durasi) |
| tanggal_pembayaran | keep | |
| durasi_bulan_dibayar | keep | |
| metode_pembayaran | keep | |
| bukti_transfer | keep | |
| periode_mulai | keep | Period this payment covers (start) |
| periode_selesai | keep | Period this payment covers (end) |
| history_pembayaran | keep | |

### `reminder` — No structural changes
- Consider auto-creating a reminder when TransaksiKos is saved (set end_date = periode_selesai)

### `pengeluaran` — No structural changes
- Needs a new Filament Resource (UI currently missing)

---

## New Relationships

```
penyewa ──< transaksi_kos     (one-to-many via id_penyewa)
penyewa ──< reminder          (one-to-many via id_penyewa)
penyewa ──  tempat_kos        (one-to-one via id_penyewa on tempat_kos)

tempat_kos ──< transaksi_kos  (one-to-many via id_tempat_kos) ← FIXED

pengeluaran                   (standalone, no relations — by design)
```

---

## New Payment Status Logic

```
LUNAS      → tempat_kos.tgl_jatuh_tempo >= today
BELUM BAYAR → tempat_kos.tgl_jatuh_tempo >= today BUT no transaction recorded yet this period
TUNGGAKAN  → tempat_kos.tgl_jatuh_tempo < today
```

`tgl_jatuh_tempo` auto-updates when a new `transaksi_kos` is saved:
```
tempat_kos.tgl_jatuh_tempo = transaksi_kos.periode_selesai (of the latest transaction for that room)
```

---

## Proposed New User Flow

```
1. Login → Dashboard
   - Stats: income this month, belum bayar count, tunggakan count, vacant rooms

2. Tempat Kos (Room Master)
   - Admin first registers all rooms with: lokasi, nomor_kamar, harga
   - kode_unik is auto-generated
   - Status defaults to Kosong

3. Data Penyewa
   - Register new tenant: nama_lengkap, no_wa, start_date, rencana_lama_kos (optional)
   - After creating → prompt: assign to a room?
     → select available room (status = Kosong)
     → room status changes to Ditempati, id_penyewa is set

4. Record Payment (Transaksi Kos)
   - Triggered manually OR from "Bayar Tagihan" quick action on penyewa list
   - Select: tempat_kos (room), auto-fills id_penyewa and harga
   - Fill: durasi_bulan_dibayar, metode_pembayaran, bukti_transfer, tanggal_pembayaran
   - System auto-computes:
       periode_mulai = tanggal_pembayaran
       periode_selesai = periode_mulai + durasi_bulan_dibayar months
       nominal = harga × durasi_bulan_dibayar (editable for custom amounts)
   - On save → auto-sync: tempat_kos.tgl_jatuh_tempo = periode_selesai

5. Kirim Tagihan WA
   - List: all rooms with tgl_jatuh_tempo <= today (TUNGGAKAN) or approaching (BELUM BAYAR)
   - Admin clicks Send WA → custom message → Fonnte API
   - Reminder linked to tempat_kos, not just penyewa

6. Pengeluaran (NEW UI)
   - Admin logs expenses: judul, nominal, tanggal, kategori, bukti_foto

7. Transaksi Kos (Ledger)
   - Full history filterable by room, tenant, date range, location
   - Export to Excel
```

---

## Migration Plan (Execution Order)

> All steps use new migrations — do NOT edit existing migration files.

1. **Add** `harga`, `status`, `tgl_jatuh_tempo` to `tempat_kos`
2. **Add** `id_tempat_kos` (FK, nullable first) to `transaksi_kos`
3. **Data migration** — populate `id_tempat_kos` from existing records
4. **Remove** `tempat_kos.id_transaksi`
5. **Remove** `transaksi_kos.lokasi_kos`, `transaksi_kos.price_kos`
6. **Remove** `penyewa.tgl_jatuh_tempo_berikutnya`
7. **Populate** `tempat_kos.tgl_jatuh_tempo` from latest `transaksi_kos.periode_selesai` per room
8. **Update Models** — fix relationships
9. **Update Resources** — fix forms, tables, actions
10. **Create PengeluaranResource** — new Filament UI
11. **Update Seeders** — align with new schema
12. **Update StatsOverview widget** — use new status logic

---

## Files That Will Change

| File | Type of Change |
|---|---|
| `database/migrations/*` | New migration files (add/remove columns) |
| `app/Models/TempatKos.php` | New relationships, new boot() sync logic |
| `app/Models/TransaksiKos.php` | New relationship to tempat_kos, remove old |
| `app/Models/Penyewa.php` | Remove tgl_jatuh_tempo cast |
| `app/Filament/Resources/PenyewaResource.php` | Fix form, fix status logic |
| `app/Filament/Resources/TempatKosResource.php` | Add harga, status, tgl_jatuh_tempo columns |
| `app/Filament/Resources/TransaksiKosResource.php` | Replace lokasi_kos with room select, remove price_kos |
| `app/Filament/Resources/ReminderResource.php` | Link to tempat_kos |
| `app/Filament/Resources/PengeluaranResource.php` | **CREATE NEW** |
| `app/Filament/Widgets/StatsOverview.php` | Fix status logic |
| `database/seeders/*` | Update all seeders |
