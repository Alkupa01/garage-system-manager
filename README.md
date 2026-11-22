# Sistem Manajemen Sewa Garasi (Garage System Manager)

Aplikasi web berbasis **Laravel 13** untuk mengelola penyewaan unit garasi harian dan bulanan, verifikasi identitas penyewa (KTP), validasi kompatibilitas kendaraan (termasuk kalkulasi pengisian daya kendaraan listrik/EV), perpanjangan masa sewa, serta pelaporan keuangan.

---

## Fitur Utama

### 1. Autentikasi & Verifikasi Penyewa (`tenant` & `admin`)
- Registrasi penyewa baru dengan unggah foto KTP (`nik` 16 digit, email unik, dan nomor telepon).
- Verifikasi KTP oleh Admin (`pending`, `approved`, `rejected`) sebelum penyewa dapat melakukan pemesanan garasi.
- Fitur unggah ulang KTP apabila status verifikasi ditolak.

### 2. Manajemen Garasi & Kendaraan
- **Tipe Unit Garasi:**
  - **Tipe S (Standard):** Khusus untuk kendaraan standar (sepeda).
  - **Tipe C (Charging):** Untuk kendaraan mobil standar maupun mobil listrik.
  - **Tipe E (Exclusive):** Khusus untuk mobil listrik dengan fasilitas pengisian daya penuh.
- **Tipe Kendaraan Penyewa:**
  - `standard` (hanya dapat menyewa garasi tipe **S**, plat nomor opsional).
  - `charging` (dapat menyewa garasi tipe **C**, wajib plat nomor unik).
  - `exclusive` (dapat menyewa garasi tipe **C** atau **E**, wajib plat nomor unik serta kapasitas baterai & sisa daya dalam kWh).

### 3. Pemesanan, Perhitungan Tarif & Perpanjangan Sewa
- **Skema Tarif Harian (`daily`):** `tarif_harian × jumlah_hari` (maksimal `jumlah_hari_bulan_tersebut - 1`).
- **Skema Tarif Bulanan (`monthly`):** `tarif_harian × 25` untuk durasi 1 bulan kalender penuh.
- **Biaya Charging EV:** Otomatis dihitung pada transaksi kendaraan `exclusive` di garasi tipe `E` dengan rumus `(kapasitas_baterai_kwh - sisa_kwh) × tarif_kwh_garasi`.
- **Deteksi Bentrok Jadwal:** Mencegah pemesanan ganda pada unit garasi maupun kendaraan yang sama di rentang tanggal yang bertabrakan.
- **Perpanjangan Sewa (`Extend`):** Diproses oleh Admin untuk sewa berstatus `booked` atau `active` (harian maupun bulanan), mendukung perpindahan unit garasi yang kompatibel serta pencatatan bukti bayar tambahan.

### 4. Laporan Keuangan & Okupansi
- Rekapitulasi pendapatan dari pembayaran sewa awal (`initial`) dan perpanjangan (`extension`).
- Statistik jumlah transaksi berdasarkan status (`booked`, `active`, `completed`, `cancelled`) serta rincian okupansi dan pendapatan per unit garasi dengan filter rentang tanggal.

---

## Arsitektur & Struktur Kode

Aplikasi menerapkan pola **MVC + Service Layer** dengan pemisahan tanggung jawab:
- `app/Models/`: Eloquent ORM (`User`, `Garage`, `Vehicle`, `Rental`, `RentalExtension`, `Payment`).
- `app/Services/`: Enkapsulasi logika bisnis (`AuthService`, `AvailabilityService`, `GarageService`, `RentalPricingService`, `ExtensionService`, `ReportService`).
- `app/Http/Controllers/`: Penanganan HTTP request & validasi (`AuthController`, `GarageController`, `VehicleController`, `RentalController`, `ReportController`).
- `app/Http/Middleware/RoleMiddleware.php`: Pembatasan hak akses berbasis peran (`admin` dan `tenant`).
- `tests/Feature/GarageRentalUnitTest.php`: Pengujian otomatis skenario bisnis utama menggunakan PHPUnit.

---

## Prasyarat Sistem

- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL / MariaDB / SQLite

---

## Panduan Instalasi & Menjalankan Aplikasi

1. **Clone repositori dan masuk ke direktori proyek:**
   ```bash
   git clone <url-repositori-anda>
   cd garage-system-manager

2. **Install dependensi PHP dan JavaScript:**
    composer install
    npm install

3. **Konfigurasi file environment:**
    cp .env.example .env
    php artisan key:generate 

    Sesuaikan konfigurasi koneksi database (DB_CONNECTION, DB_DATABASE, DB_USERNAME, DB_PASSWORD) pada file .env

4. **Jalankan migrasi database, seeder awal, dan symbolic link storage:**
    php artisan migrate --seed
    php artisan storage:link

5. **Build aset frontend dan jalankan server lokal:**
    npm run build
    php artisan serve

    Akses aplikasi melalui browser di http://127.0.0.1:8000.

## Panduan Menjalankan Unit Testing

    php artisan test