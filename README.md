# ❄️ AC Management System - CorpU
> **Enterprise Asset Management & Predictive Maintenance for Air Conditioning Units**

[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue?style=for-the-badge&logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

**AC Management System (CorpU)** adalah platform manajemen aset digital yang dirancang khusus untuk memantau inventaris, hierarki lokasi, serta otomatisasi penjadwalan pemeliharaan AC secara efisien dan terintegrasi.

---

## 🌟 Fitur Unggulan

### 1. Manajemen Lokasi & Visualisasi Hierarkis
Sistem mengelola aset dengan struktur organisasi yang mendalam:
* **Struktur Bangunan:** Manajemen data Gedung (dengan unggahan fasad), Lantai, hingga detail Ruangan.
* **Interactive Floorplan:** Visualisasi posisi unit AC pada denah (PDF/Gambar) menggunakan koordinat interaktif untuk akurasi lokasi pengerjaan.

### 2. Inventaris & Manajemen Data Massal
Efisiensi input data skala besar dengan fitur:
* **Master Data AC:** Dokumentasi teknis lengkap (Merk, Tipe, Model, hingga Serial Number Indoor/Outdoor).
* **Bulk Processing:** Impor ribuan data melalui Excel serta sinkronisasi foto unit otomatis dari file ZIP berdasarkan pencocokan nomor seri (Serial Number Matching).

### 3. Smart Maintenance & Monitoring
Transformasi pemeliharaan reaktif menjadi preventif:
* **Real-time Status:** Monitoring kondisi unit (Baik, Rusak, atau Dalam Perbaikan).
* **Predictive Overdue:** Notifikasi otomatis untuk unit yang telah melewati batas waktu servis (6 bulan).
* **Proof of Work:** Sistem unggah bukti dokumentasi foto setelah pengerjaan servis selesai.

### 4. Audit Trail & Analytics
Transparansi data dan performa sistem:
* **Activity Logs:** Pencatatan jejak audit (Audit Trail) untuk setiap perubahan data (siapa, melakukan apa, kapan).
* **Service History:** Log riwayat servis per unit yang dapat diakses instan untuk analisis performa perangkat.

---

## 🛠 Spesifikasi Teknis

Aplikasi ini dibangun dengan arsitektur modern untuk menjamin reaktivitas dan keamanan:

| Komponen | Teknologi |
| :--- | :--- |
| **Backend** | PHP 8.1+ | Laravel 10.x |
| **Frontend** | Tailwind CSS | Alpine.js (Reactive UI) |
| **Database** | MySQL / MariaDB |
| **Core Libraries** | `spatie/laravel-activitylog`, `maatwebsite/excel`, `PDF.js`, `JSZip` |

---

## 📂 Arsitektur Controller Utama

Sistem ini diorganisir menggunakan pola desain yang modular:

* `AcController.php`: Menangani logika CRUD unit AC dan pemrosesan *bulk storage*.
* `BuildingController.php`: Mengatur entitas bangunan dan pemetaan visual denah.
* `ScheduleController.php`: Algoritma penjadwalan servis dan logika notifikasi *overdue*.
* `ActivityLogController.php`: Manajemen antarmuka jejak audit sistem.
* `DashboardController.php`: Agregasi data statistik untuk visualisasi ringkasan eksekutif.

---

## 🚀 Panduan Instalasi

Pastikan perangkat Anda telah terpasang **Composer**, **Node.js**, dan **MySQL**.

### 1. Persiapan Repositori
```bash
git clone [https://github.com/IndraMuh/AC_Management_CorpU.git](https://github.com/IndraMuh/AC_Management_CorpU.git)
cd AC_Management_CorpU
composer install
npm install && npm run dev

### 2. Konfigurasi Lingkungan
Salin file .env.example menjadi .env dan sesuaikan kredensial database Anda.
```bash
php artisan key:generate
php artisan storage:link
```
### 3. Migrasi Database
Jalankan migrasi beserta data awal (seeder):
```bash
php artisan migrate --seed
```
### 4. Jalankan Server
```bash
php artisan serve
```
Akses sistem melalui http://localhost:8000.

👨‍💻 Kontributor
Indra Muhammad - Web & Mobile Developer

GitHub: @IndraMuh

Instagram: @_indramhmd

Developed for Digital Transformation in Asset Management @ Telkom CorpU.
