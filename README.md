# ❄️ Management AC Telkom CorpU
> **Sistem Monitoring & Inventaris Maintenance AC Real-Time**

[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue?style=for-the-badge&logo=php)](https://php.net)
[![Database](https://img.shields.io/badge/Database-MySQL-orange?style=for-the-badge&logo=mysql)](https://mysql.com)

**Management AC Telkom CorpU** adalah aplikasi manajemen aset khusus untuk unit pendingin ruangan (AC) di lingkungan Corporate University. Aplikasi ini mendigitalisasi proses pencatatan, pemantauan status, hingga manajemen perbaikan unit secara terpusat.

---

## 🚀 Teknologi Utama

Aplikasi ini dibangun dengan stack teknologi modern untuk menjamin performa dan skalabilitas:

* **Backend:** PHP dengan Framework **Laravel 12** (Latest Version).
* **Database:** **MySQL / MariaDB** untuk penyimpanan relasi data aset yang kompleks.
* **Frontend:** **Blade Templating Engine** dipadukan dengan desain dashboard yang interaktif dan responsif.
* **Authentication:** Sistem hak akses terproteksi untuk manajemen user.

---

## 📂 Struktur Penting Repositori

Memahami struktur proyek untuk pengembangan lebih lanjut:

* 📂 `app/Http/Controllers/`: Inti dari logika program dan kontrol alur data aplikasi.
* 📂 `database/migrations/`: Definisi skema database (Tabel aset, user, dan manajemen unit).
* 📂 `resources/views/`: File UI/Tampilan yang diorganisir berdasarkan modul (Admin, Dashboard, dll).
* 📂 `routes/web.php`: Daftar seluruh rute (URL) dan endpoint aplikasi.

---

## ✨ Fitur & Fungsi

1.  **Inventarisasi Aset:** Pencatatan detail unit AC, lokasi gedung, dan spesifikasi unit.
2.  **Maintenance Tracking:** Pengelolaan jadwal pemeliharaan rutin dan perbaikan (rusak/proses/selesai).
3.  **User Management:** Pengaturan hak akses login untuk admin dan petugas operasional di lingkungan CorpU.
4.  **Reporting:** Monitoring status aset secara real-time melalui dashboard utama.

---

## 🛠️ Panduan Instalasi Lokal

Ikuti langkah berikut untuk menjalankan proyek di lingkungan lokal Anda:

1. **Clone Repositori**
   ```bash
   git clone [https://github.com/IndraMuh/AC_Management_CorpU.git](https://github.com/IndraMuh/AC_Management_CorpU.git)
   cd AC_Management_CorpU
Instalasi Dependensi

Bash
composer install
Konfigurasi Environment Salin file .env.example menjadi .env dan sesuaikan konfigurasi database Anda.

Bash
cp .env.example .env
php artisan key:generate
Migrasi Database Pastikan database MySQL sudah dibuat, lalu jalankan:

Bash
php artisan migrate
Jalankan Aplikasi

Bash
php artisan serve
Akses di browser melalui: http://localhost:8000

👨‍💻 Author
Indra Muhammad Fullstack Web & Mobile Developer

Project ini dikembangkan untuk digitalisasi efisiensi maintenance aset di lingkungan Telkom CorpU.


---

### Apa yang saya tambahkan?
* **Badges:** Menambahkan badge Laravel, PHP, dan MySQL di bagian atas agar terlihat seperti repositori profesional.
* **Struktur Folder:** Saya masukkan poin nomor 2 dari ringkasanmu agar developer lain paham anatomi kodemu.
* **Instalasi:** Saya buatkan instruksi langkah-demi-langkah (CLI) agar siapapun yang ingin mencoba tidak bingung.
* **Emoji:** Memberikan aksen visual (❄️, 🚀, 📂) agar teks yang padat lebih enak dibaca.

README ini sudah siap tempel, Ndra. Apakah kamu ingin saya bantu buatkan file **LICENSE** (biasanya MIT) juga agar repository-mu terlihat lebih "legal" secara open-source?
