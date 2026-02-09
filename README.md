# ❄️ Management AC Telkom CorpU
> **Enterprise Asset Management System for High-Efficiency Maintenance Tracking**

[![Framework](https://img.shields.io/badge/Laravel-12.x-E3342F?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Database](https://img.shields.io/badge/MySQL-00758F?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

---

## 📋 Ikhtisar Proyek

**Management AC Telkom CorpU** adalah sistem manajemen aset digital yang dirancang khusus untuk mengoptimalkan siklus hidup pemeliharaan unit pendingin ruangan (AC) di lingkungan **Telkom Corporate University**. 

Sistem ini mentransformasi proses manual menjadi alur kerja digital yang terukur, memungkinkan pengambilan keputusan berbasis data terkait perbaikan, penggantian aset, dan manajemen vendor maintenance.

---

## 🛠️ Arsitektur & Teknologi

Sistem ini dibangun dengan arsitektur **Monolithic Robust** menggunakan teknologi terkini:

* **Core Engine:** Laravel 12 (Modern PHP Framework)
* **Database:** MySQL (Relational Database Management System)
* **Templating:** Blade Engine dengan implementasi Reusable Components
* **Styling:** Tailwind CSS (Utility-first CSS framework untuk UI yang responsif)
* **Architecture:** MVC (Model-View-Controller) Pattern

---

## 📂 Struktur Repositori & Modul

Aplikasi ini mengikuti standar struktur Laravel untuk memastikan kemudahan pemeliharaan (*maintainability*):

| Folder / File | Deskripsi |
| :--- | :--- |
| `app/Http/Controllers/` | Mengelola logika bisnis dan koordinasi antara Model dan View. |
| `database/migrations/` | Skema database terpusat (Aset AC, Lokasi, Status Maintenance, & User). |
| `resources/views/` | Arsitektur UI yang terbagi menjadi modul Admin, Dashboard, dan Reporting. |
| `routes/web.php` | Definisi jalur navigasi dan proteksi middleware aplikasi. |

---

## 🚀 Fitur Utama

1.  **Centralized Asset Inventory:** Katalog lengkap unit AC mencakup serial number, brand, kapasitas (PK), dan lokasi spesifik.
2.  **Maintenance Lifecycle Tracking:** Pantau status unit secara real-time: `Ready`, `On-Repair` (Proses), atau `Broken` (Rusak).
3.  **Role-Based Access Control (RBAC):** Keamanan berlapis untuk Admin, Teknisi, dan Manajemen.
4.  **Operational Insights:** Dashboard statistik untuk memantau kesehatan aset secara keseluruhan di seluruh gedung CorpU.

---

## 💻 Panduan Implementasi (Local Development)

### Prasyarat
* PHP >= 8.2
* Composer
* Node.js & NPM
* MySQL/MariaDB

### Langkah Instalasi
1. **Clone & Navigate**
   ```bash
   git clone [https://github.com/IndraMuh/AC_Management_CorpU.git](https://github.com/IndraMuh/AC_Management_CorpU.git)
   cd AC_Management_CorpU
Dependency Management

Bash
composer install
npm install && npm run build
Environment Setup

Bash
cp .env.example .env
php artisan key:generate
Database Synchronization

Bash
php artisan migrate --seed
Deployment

Bash
php artisan serve
👤 Informasi Pengembang
Indra Muhammad Fullstack Web & Mobile Developer Spesialisasi dalam pembangunan ekosistem digital berbasis Laravel & Flutter.
