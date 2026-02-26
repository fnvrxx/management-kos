# Current System State (Before Remodel)
> Documented: February 26, 2026

---

## What Is This Project?

A Laravel 12 + Filament 3 admin panel for managing Indonesian boarding house (*kos-kosan*) rentals across multiple cities (Malang, Surabaya, Kediri). Built for a single admin user to manage tenants, rooms, payments, reminders, and expenses.

---

## Current Database Schema

### `penyewa`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| nama_lengkap | varchar | Tenant name |
| no_wa | varchar | WhatsApp number |
| start_date | date | Move-in date |
| rencana_lama_kos | date | Planned end date (optional) |
| end_date | date | Actual move-out date |
| tgl_jatuh_tempo_berikutnya | date | Next due date — manually managed |

### `tempat_kos`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| lokasi | enum | Malang / Surabaya / Kediri |
| nomor_kamar | varchar | Room number |
| kode_unik | varchar | Auto-generated (e.g. MLG-001) |
| id_penyewa | FK → penyewa | Current occupant |
| id_transaksi | FK → transaksi_kos | Latest transaction (one-to-one) |

### `transaksi_kos`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| id_penyewa | FK → penyewa | Who paid |
| lokasi_kos | varchar | Free-text location string |
| price_kos | int | Price at time of payment |
| nominal | int | Actual amount paid |
| tanggal_pembayaran | date | Payment date |
| durasi_bulan_dibayar | int | How many months paid |
| metode_pembayaran | varchar | Transfer / Tunai / QRIS |
| bukti_transfer | varchar | Payment proof image path |
| periode_mulai | date | Period start |
| periode_selesai | date | Period end |
| history_pembayaran | text | Notes |

### `reminder`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| id_penyewa | FK → penyewa | |
| end_date | date | Due date reminder |
| broadcast | boolean | Was WA message sent? |
| history_reminder | text | Log |

### `pengeluaran`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| judul | varchar | Expense title |
| nominal | int | Amount |
| tanggal | date | Date |
| kategori | varchar | Operasional / Perbaikan / Gaji |
| bukti_foto | varchar | Photo proof |
| keterangan | text | Notes |

### `users`
Standard Laravel auth table. Single admin user.

---

## Current Relationships

```
penyewa ──< reminder          (one-to-many)
penyewa ──< transaksi_kos     (one-to-many via id_penyewa)
penyewa ──  tempat_kos        (one-to-one via id_penyewa on tempat_kos)
tempat_kos ── transaksi_kos   (one-to-one via id_transaksi on tempat_kos) ← PROBLEMATIC
pengeluaran                   (standalone, no relations)
```

---

## Current User Flow

```
1. Login → Dashboard
   - See: income this month, unpaid tenants count, vacant rooms count
   - Stats refresh every 30 seconds

2. Data Penyewa (Main Hub)
   - List all active tenants (end_date IS NULL or in future)
   - See per-tenant: payment status (LUNAS/BELUM BAYAR), billing status (AMAN/Jatuh Tempo/NUNGGAK)
   - Quick Pay button → fill form → records TransaksiKos manually
   - Add / Edit tenant data

3. Kirim Tagihan WA
   - List all reminders sorted by closest due date
   - Click "Send WhatsApp" → calls Fonnte API → sends WA message to tenant
   - Tracks whether message was sent (broadcast flag)

4. Data Master → Tempat Kos
   - Add/edit rooms
   - Assign tenant to room
   - Filter by location
   - See per-room payment status

5. Data Master → Transaksi Kos
   - Full payment history, sorted by latest
   - Filter by date range
   - Export to Excel
```

---

## Current Payment Status Logic

```php
// LUNAS: tanggal_pembayaran is in current month & year
// BELUM BAYAR: everything else

// AMAN: tgl_jatuh_tempo_berikutnya > today (more than 7 days)
// Jatuh Tempo: due within 7 days
// NUNGGAK: tgl_jatuh_tempo_berikutnya < today
```

`tgl_jatuh_tempo_berikutnya` is manually set on `penyewa` — can go stale.

---

## Pros of Current Design

- Simple to understand at a glance
- Quick Pay action on PenyewaResource is a good UX shortcut
- Auto-generated `kode_unik` per room is useful
- Fonnte WA API integration is already wired up
- Export to Excel already works
- Dashboard stats widget is functional

---

## Cons / Known Issues

1. **Relationship direction is wrong** — `tempat_kos.id_transaksi` means a room can only track ONE transaction ever. Full payment history per room is not queryable.

2. **`transaksi_kos.lokasi_kos` is redundant** — free-text string duplicates the enum `tempat_kos.lokasi`, with risk of mismatch.

3. **`transaksi_kos.price_kos` is misplaced** — room price is a property of the room, not each transaction. Stored per-transaction = inconsistency risk.

4. **`tgl_jatuh_tempo_berikutnya` on `penyewa` is fragile** — it's a derived value stored manually. Can go out of sync with actual transaction data.

5. **No `PengeluaranResource`** — the `pengeluaran` table and model exist but there is no Filament UI to manage expenses. Feature is incomplete.

6. **Status logic is fragile** — LUNAS/BELUM BAYAR is determined by checking if `tanggal_pembayaran` falls in the current calendar month, which doesn't account for multi-month payments or exact period coverage.

7. **`reminder` is separate from payment flow** — reminders and payments are disconnected. No automatic reminder creation when a payment is recorded.
