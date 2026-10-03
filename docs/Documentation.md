# Management Kos — Project Documentation
> Laravel 12 + Filament 3 Admin Panel for Boarding House (Kos-Kosan) Management  
> Last Updated: February 26, 2026

---

## Table of Contents

1. [Tentang Proyek](#1-tentang-proyek)
2. [Tech Stack](#2-tech-stack)
3. [Instalasi & Setup (Developer)](#3-instalasi--setup-developer)
4. [Struktur Database](#4-struktur-database)
5. [Model & Relasi](#5-model--relasi)
6. [Fitur Aplikasi](#6-fitur-aplikasi)
7. [Panduan Pengguna](#7-panduan-pengguna)
8. [Logika Bisnis Penting](#8-logika-bisnis-penting)
9. [Struktur File](#9-struktur-file)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Tentang Proyek

Aplikasi admin panel untuk mengelola usaha kos-kosan di beberapa kota (Malang, Surabaya, Kediri). Dibangun untuk **satu admin** yang mengelola semua properti.

**Fitur utama:**
- Manajemen penyewa (check-in, checkout, data lengkap)
- Manajemen kamar di beberapa lokasi
- Pencatatan pembayaran (bayar penuh & cicilan)
- Penghitungan jatuh tempo otomatis
- Kirim tagihan via WhatsApp (klik-untuk-chat)
- Template pesan WhatsApp yang bisa dikustomisasi
- Pencatatan pengeluaran operasional
- Dashboard dengan ringkasan status & kartu penyewa
- Export laporan transaksi ke Excel

---

## 2. Tech Stack

| Komponen | Teknologi | Versi |
|---|---|---|
| Framework | Laravel | 12.x |
| Admin Panel | Filament | 3.x |
| Reactivity | Livewire | 3.x |
| PHP | PHP | 8.3+ |
| Database | MySQL | 8.x |
| Frontend Build | Vite | ESbuild |
| WhatsApp | api.whatsapp.com | Click-to-chat URL (tanpa API key) |

---

## 3. Instalasi & Setup (Developer)

### Prasyarat
- PHP 8.3+ dengan extensions: `pdo_mysql`, `mbstring`, `openssl`, `intl`, `fileinfo`
- Composer 2.x
- Node.js 18+ (untuk build assets)
- MySQL 8.x

### Langkah Instalasi

```bash
# 1. Clone repository
git clone <repo-url> management-kos
cd management-kos

# 2. Install dependencies
composer install
npm install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Konfigurasi database di .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=management-kos-dios
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Pastikan QUEUE_CONNECTION=sync di .env
# (agar export Excel langsung selesai tanpa perlu queue worker)

# 6. Jalankan migrasi & seeder
php artisan migrate:fresh --seed

# 7. Build assets
npm run build

# 8. Jalankan server
php artisan serve
```

### Login Default
- **URL:** `http://localhost:8000/admin`
- **Username demo:** `demo`
- **Password demo:** `demo12345`
- Akun admin lama tetap bisa login dengan email `admin@gmail.com` dan password `password` setelah seeding.

---

## 4. Struktur Database

### Entity Relationship Diagram

```
┌───────────┐       ┌──────────────┐       ┌──────────────┐
│  penyewa  │──────<│ transaksi_kos│>──────│  tempat_kos  │
│           │       │              │       │              │
│ id        │       │ id           │       │ id           │
│ nama      │       │ id_penyewa   │──┐    │ lokasi       │
│ no_wa     │       │ id_tempat_kos│  │    │ nomor_kamar  │
│ start_date│       │ nominal      │  │    │ harga        │
│ end_date  │       │ tanggal      │  │    │ status*      │
│           │       │ metode       │  │    │ id_penyewa   │──┐
│           │       │ durasi_bulan │  │    │ tgl_jatuh    │  │
│           │       │ bukti        │  │    │  _tempo*     │  │
└───────────┘       └──────────────┘  │    └──────────────┘  │
      │                               │                      │
      │     ┌──────────────┐          │                      │
      └────<│  reminder    │          └──────────────────────-┘
             │              │
             │ id_penyewa   │      * = auto-computed, not user input
             │ end_date     │
             │ tanggungan   │
             │ broadcast    │
             └──────────────┘

┌──────────────────┐       ┌──────────────┐
│ template_messages│       │ pengeluaran  │
│                  │       │              │
│ nama_template    │       │ judul        │
│ isi_template     │       │ nominal      │
│ is_default       │       │ tanggal      │
│                  │       │ kategori     │
└──────────────────┘       └──────────────┘
        (standalone)            (standalone)
```

### Tabel: `penyewa`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| nama_lengkap | varchar | Nama penyewa |
| no_wa | varchar | Nomor WhatsApp (format: 08xxxx) |
| start_date | date | Tanggal mulai kos |
| rencana_lama_kos | date | Rencana sampai kapan (opsional) |
| end_date | date | Tanggal checkout (null = masih aktif) |

### Tabel: `tempat_kos`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| lokasi | enum | Malang / Surabaya / Kediri |
| nomor_kamar | varchar | Nomor kamar (contoh: A01, B02) |
| harga | int | Harga sewa per bulan (Rp) |
| status | enum | Ditempati / Kosong — **otomatis dari id_penyewa** |
| id_penyewa | FK → penyewa | Penyewa yang menempati (nullable) |
| tgl_jatuh_tempo | date | **Otomatis dihitung** dari total pembayaran |

### Tabel: `transaksi_kos`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| id_penyewa | FK → penyewa | Siapa yang bayar |
| id_tempat_kos | FK → tempat_kos | Kamar yang dibayar |
| tanggal_pembayaran | date | Tanggal bayar |
| nominal | int | Jumlah uang yang dibayar |
| durasi_bulan_dibayar | int | Jumlah bulan (0 = cicilan) |
| metode_pembayaran | varchar | Transfer / Tunai / QRIS |
| bukti_transfer | varchar | Path file bukti bayar |
| periode_mulai | date | Awal periode yang dibayar |
| periode_selesai | date | Akhir periode yang dibayar |
| history_pembayaran | text | Catatan otomatis |

### Tabel: `reminder`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| id_penyewa | FK → penyewa | Penyewa yang ditagih |
| end_date | date | Tanggal jatuh tempo |
| tanggungan | int | Jumlah tagihan (Rp) |
| broadcast | boolean | Sudah dikirim via WA? |
| history_reminder | text | Log pengiriman |

### Tabel: `pengeluaran`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| judul | varchar | Judul pengeluaran |
| nominal | int | Jumlah (Rp) |
| tanggal | date | Tanggal |
| kategori | varchar | Operasional / Perbaikan / Gaji / Lainnya |
| bukti_foto | varchar | Foto bukti (opsional) |
| keterangan | text | Catatan tambahan |

### Tabel: `template_messages`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| nama_template | varchar | Nama template |
| isi_template | text | Isi pesan dengan variabel `{nama}`, `{lokasi}`, dll |
| is_default | boolean | Template yang digunakan otomatis (hanya 1) |

---

## 5. Model & Relasi

### Relasi Antar Model

```
Penyewa  ──hasOne──>  TempatKos    (satu penyewa satu kamar)
Penyewa  ──hasMany──> TransaksiKos (satu penyewa banyak transaksi)
Penyewa  ──hasMany──> Reminder     (satu penyewa banyak reminder)

TempatKos ──belongsTo──> Penyewa   (kamar punya satu penyewa)
TempatKos ──hasMany──>   TransaksiKos (satu kamar banyak transaksi)

TransaksiKos ──belongsTo──> Penyewa   (transaksi milik penyewa)
TransaksiKos ──belongsTo──> TempatKos (transaksi untuk kamar tertentu)

Reminder ──belongsTo──> Penyewa (reminder untuk penyewa tertentu)
```

### Auto-Computed Fields

**`tempat_kos.status`** — Dihitung otomatis di model boot `saving`:
- Jika `id_penyewa` ada → `Ditempati`
- Jika `id_penyewa` null → `Kosong`

**`tempat_kos.tgl_jatuh_tempo`** — Dihitung otomatis setiap ada transaksi:
```
tgl_jatuh_tempo = penyewa.start_date + floor(totalPaid / harga) bulan
```

**`reminder` auto-sync** — Setiap transaksi disimpan/dihapus:
- Jika penyewa sudah LUNAS → hapus reminder yang belum terkirim
- Jika masih nunggak → update `tanggungan` dan `end_date` di reminder

---

## 6. Fitur Aplikasi

### 6.1 Dashboard
- **Ringkasan Statistik** (5 kartu):
  - Pemasukan Bulan Ini
  - Jumlah Tunggakan
  - Jumlah Cicilan
  - Jumlah Belum Bayar
  - Ketersediaan Kamar

- **Kartu Penyewa** (dikelompokkan per lokasi):
  - Warna berdasarkan lokasi: Malang (biru), Surabaya (hijau), Kediri (ungu)
  - Status badge per penyewa (LUNAS/CICILAN/TUNGGAKAN/BELUM BAYAR)
  - Klik kartu → modal riwayat pembayaran
  - Tombol sort: Tunggakan Terlama, Nama, Kamar

### 6.2 Data Penyewa
- Daftar semua penyewa aktif
- Status bayar otomatis (5 kategori dengan warna)
- Informasi jatuh tempo real-time
- **Quick Action — Bayar Tagihan**: Catat pembayaran langsung dari daftar penyewa
- **Quick Action — Checkout**: Mengeluarkan penyewa, kamar otomatis jadi Kosong
- Filter: Penyewa Aktif / Alumni / Semua

### 6.3 Kirim Tagihan WA
- Daftar reminder yang perlu dikirim
- Klik "Kirim WA" → muncul preview pesan → klik tombol hijau besar → buka WhatsApp
- Pesan otomatis diisi dari template default di database
- Variabel otomatis diganti: `{nama}`, `{lokasi}`, `{kamar}`, `{tagihan}`, `{jatuh_tempo}`
- Tandai sebagai terkirim setelah dikirim

### 6.4 Tempat Kos
- Kelola kamar: lokasi, nomor kamar, harga
- Assign penyewa ke kamar (otomatis ubah status ke Ditempati)
- Lihat jatuh tempo per kamar
- Filter: lokasi, status

### 6.5 Transaksi Kos
- Riwayat lengkap semua pembayaran
- Pilih kamar → otomatis isi penyewa & nominal
- Mendukung pembayaran penuh & cicilan
- Filter tanggal
- **Export ke Excel** (tombol hijau "Download Laporan")

### 6.6 Pengeluaran
- Catat semua pengeluaran operasional
- Kategori: Operasional, Perbaikan, Gaji, Lainnya
- Upload bukti foto
- Filter kategori & tanggal
- Total otomatis di bawah kolom nominal

### 6.7 Template Pesan WA
- Buat template pesan kustom untuk tagihan WhatsApp
- Tombol klik variabel: `{nama}`, `{lokasi}`, `{kamar}`, `{tagihan}`, `{jatuh_tempo}`
- Set satu template sebagai default (otomatis digunakan saat kirim tagihan)

---

## 7. Panduan Pengguna

### 7.1 Login
1. Buka browser → ketik `http://localhost:8000/admin`
2. Username: `demo`
3. Password: `demo12345`

### 7.2 Menambah Kamar Baru
1. Klik **Data Master** → **Tempat Kos** di sidebar
2. Klik tombol **"Buat"** di pojok kanan atas
3. Isi: Lokasi, Nomor Kamar, Harga Sewa / Bulan
4. Biarkan "Nama Penyewa" kosong (belum ada penghuni)
5. Klik **Buat**

### 7.3 Menambah Penyewa Baru
1. Klik **Data Penyewa** di sidebar
2. Klik tombol **"Buat"**
3. Isi: Nama Lengkap, Nomor WhatsApp, Tanggal Mulai Kos
4. Klik **Buat**

### 7.4 Memasukkan Penyewa ke Kamar
1. Buka **Data Master** → **Tempat Kos**
2. Klik **Edit** pada kamar yang masih Kosong
3. Pilih penyewa di dropdown "Nama Penyewa"
4. Klik **Simpan** — status otomatis berubah ke "Ditempati"

### 7.5 Mencatat Pembayaran
**Cara Cepat (dari Data Penyewa):**
1. Buka **Data Penyewa**
2. Klik ikon **titik tiga (⋮)** di baris penyewa
3. Pilih **"Bayar Tagihan"**
4. Isi: Tanggal, Nominal (sudah terisi otomatis), Bukti Transfer, Metode
5. Klik **Bayar Tagihan**
6. Jatuh tempo otomatis dihitung ulang

**Cara Manual (dari Transaksi Kos):**
1. Buka **Data Master** → **Transaksi Kos**
2. Klik **"Buat"**
3. Pilih Kamar → otomatis terisi Penyewa & Nominal
4. Isi detail pembayaran
5. Klik **Buat**

**Cicilan (Pembayaran Sebagian):**
- Isi nominal yang lebih kecil dari harga kamar
- Set "Durasi (Bulan)" ke 0
- Status akan berubah menjadi "CICILAN" sampai jumlah total cukup

### 7.6 Mengirim Tagihan WhatsApp
1. Buka **Kirim Tagihan WA** di sidebar
2. Klik **"Kirim WA"** pada penyewa yang ingin ditagih
3. Preview pesan akan muncul (sudah terisi otomatis dari template)
4. Klik tombol hijau **"📱 Buka WhatsApp & Kirim Pesan"**
5. WhatsApp Web/App terbuka dengan pesan terisi
6. Kirim pesan di WhatsApp
7. Kembali ke admin panel, klik **"✅ Tandai Terkirim"**

### 7.7 Mengustomisasi Template Pesan
1. Buka **Template Pesan WA** di sidebar
2. Edit template yang ada, atau buat baru
3. Gunakan tombol variabel (`+ {nama}`, `+ {lokasi}`, dll) untuk menyisipkan kode
4. Aktifkan **"Jadikan Template Default"** pada template yang ingin digunakan
5. Template default akan otomatis dipakai saat kirim tagihan WA

### 7.8 Checkout Penyewa
1. Buka **Data Penyewa**
2. Klik ikon **titik tiga (⋮)**
3. Pilih **"Checkout / Pindah"**
4. Konfirmasi → Kamar otomatis jadi Kosong, reminder dihapus

### 7.9 Mencatat Pengeluaran
1. Buka **Data Master** → **Pengeluaran**
2. Klik **"Buat"**
3. Isi: Judul, Nominal, Tanggal, Kategori, Bukti Foto (opsional), Keterangan
4. Klik **Buat**

### 7.10 Download Laporan Excel
1. Buka **Data Master** → **Transaksi Kos**
2. Klik tombol hijau **"Download Laporan (Excel)"** di pojok kanan atas
3. File akan langsung ter-download

---

## 8. Logika Bisnis Penting

### 8.1 Penghitungan Jatuh Tempo

Jatuh tempo dihitung secara otomatis dari total pembayaran:

```
totalPaid = SUM(nominal) dari semua transaksi penyewa di kamar tersebut
monthsCovered = floor(totalPaid / harga_kamar)
tgl_jatuh_tempo = start_date + monthsCovered bulan
```

**Contoh:**
- Penyewa mulai 6 Desember 2025, harga kamar Rp 800.000
- Bayar 3× Rp 800.000 = total Rp 2.400.000
- `floor(2.400.000 / 800.000) = 3 bulan`
- `tgl_jatuh_tempo = 6 Des 2025 + 3 bulan = 6 Mar 2026`

### 8.2 Status Pembayaran

| Status | Kondisi | Warna |
|---|---|---|
| ✅ LUNAS | `tgl_jatuh_tempo > hari ini` | Hijau |
| 💰 CICILAN | `totalPaid % harga > 0` (ada sisa pembagian) | Biru |
| ❌ TUNGGAKAN | `tgl_jatuh_tempo ≤ hari ini` dan tidak ada cicilan | Merah |
| 🕐 BELUM BAYAR | Belum ada transaksi sama sekali | Kuning |
| ⬜ BELUM ASSIGN | Penyewa belum ditempatkan di kamar | Abu-abu |

### 8.3 Auto-Sync Reminder

Setiap kali transaksi disimpan atau dihapus:
1. Jatuh tempo dihitung ulang
2. Jika status berubah jadi **LUNAS** → semua reminder yang belum terkirim dihapus otomatis
3. Jika masih **TUNGGAKAN** → update jumlah tagihan & tanggal jatuh tempo di reminder

### 8.4 Template Variabel

| Variabel | Diganti Dengan | Contoh |
|---|---|---|
| `{nama}` | Nama penyewa | Budi Santoso |
| `{lokasi}` | Lokasi kos | Malang |
| `{kamar}` | Nomor kamar | A01 |
| `{tagihan}` | Jumlah tagihan (format Rp) | 800.000 |
| `{jatuh_tempo}` | Tanggal jatuh tempo | 06/03/2026 |

---

## 9. Struktur File

```
app/
├── Filament/
│   ├── Exports/
│   │   └── TransaksiKosExporter.php     # Kolom export Excel
│   ├── Resources/
│   │   ├── PenyewaResource.php          # Kelola penyewa + quick pay
│   │   ├── TempatKosResource.php        # Kelola kamar
│   │   ├── TransaksiKosResource.php     # Kelola transaksi + export
│   │   ├── ReminderResource.php         # Kirim tagihan WA
│   │   ├── PengeluaranResource.php      # Kelola pengeluaran
│   │   ├── TemplateMessageResource.php  # Template pesan WA
│   │   └── */Pages/                     # Halaman List/Create/Edit per resource
│   └── Widgets/
│       ├── StatsOverview.php            # Widget statistik dashboard
│       └── PenyewaCardWidget.php        # Widget kartu penyewa
├── Models/
│   ├── Penyewa.php                      # Model penyewa
│   ├── TempatKos.php                    # Model kamar (auto-derive status)
│   ├── TransaksiKos.php                 # Model transaksi (auto-compute jatuh tempo)
│   ├── Reminder.php                     # Model reminder
│   ├── Pengeluaran.php                  # Model pengeluaran
│   ├── TemplateMessage.php              # Model template pesan
│   └── User.php                         # Model user (admin)
├── Providers/
│   └── Filament/
│       └── AdminPanelProvider.php       # Konfigurasi panel Filament
database/
├── migrations/                          # 14 file migrasi
├── seeders/                             # 7 seeder (data contoh)
resources/views/
└── filament/widgets/
    ├── penyewa-cards.blade.php          # Template kartu penyewa (dashboard)
    └── penyewa-history.blade.php        # Template modal riwayat pembayaran
```

### Urutan Navigasi Sidebar

| No | Menu | Resource |
|---|---|---|
| 1 | Data Penyewa (Utama) | PenyewaResource |
| 2 | Kirim Tagihan WA | ReminderResource |
| 3 | Tempat Kos | TempatKosResource (under "Data Master") |
| 4 | Transaksi Kos | TransaksiKosResource (under "Data Master") |
| 5 | Pengeluaran | PengeluaranResource (under "Data Master") |
| 6 | Template Pesan WA | TemplateMessageResource |

---

## 10. Troubleshooting

### Export Excel tidak jalan / tidak ada tombol download
- Pastikan `QUEUE_CONNECTION=sync` di file `.env`
- Jika baru diubah, jalankan: `php artisan optimize:clear`

### Jatuh tempo tidak terupdate setelah bayar
- Pastikan pembayaran mencantumkan `id_tempat_kos` yang benar
- Cek harga kamar tidak 0 atau null
- Jatuh tempo dihitung dari `penyewa.start_date` — pastikan field ini terisi

### Status kamar tidak berubah setelah assign penyewa
- Status dihitung otomatis — tidak perlu diisi manual
- Pastikan `id_penyewa` terisi di data kamar

### WhatsApp tidak terbuka
- Pastikan nomor WA penyewa diisi dengan format angka (contoh: `081234567890`)
- Format `08` di depan akan otomatis diganti `62` (kode Indonesia)

### Halaman error setelah `migrate:fresh`
```bash
php artisan optimize:clear
php artisan view:cache
```

### Menjalankan ulang dari awal (reset semua data)
```bash
php artisan migrate:fresh --seed --force
```
> **Peringatan:** Ini menghapus SEMUA data dan menggantinya dengan data contoh.

### Data contoh (seeder)

| Penyewa | Kamar | Lokasi | Status | Keterangan |
|---|---|---|---|---|
| Budi Santoso | A01 | Malang | ✅ LUNAS | 3 bulan dibayar penuh (Rp 2.4M) |
| Siti Rahayu | B01 | Surabaya | 💰 CICILAN | 1 bulan + Rp 500k dari Rp 1M |
| Ahmad Fauzi | C01 | Kediri | ❌ TUNGGAKAN | 1 bulan bayar, sudah lewat |
| — | A02 | Malang | Kosong | — |
| — | B02 | Surabaya | Kosong | — |
