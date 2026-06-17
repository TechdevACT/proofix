# 🚀 Proofix - Panduan Deployment ke Synology NAS (Local Network)

Dokumen ini berisi panduan langkah-demi-langkah dari awal sampai akhir untuk mendeploy project **Proofix (Laravel + Vue + MySQL)** ke Synology NAS (DS1522+) menggunakan Web Station secara native (tanpa Docker), dan hanya diakses melalui Local Network (LAN).

---

## 🛠️ Tahap 1: Persiapan di Komputer Lokal (Laragon)

Sebelum project dipindahkan ke NAS, kita harus mem-*build* file Vue (Frontend) menjadi file statis terlebih dahulu.

1. Buka CMD / Terminal di dalam folder project ini (`c:\laragon\www\proofix`).
2. Jalankan perintah untuk meng-compile Vue (Vite):
   ```bash
   npm run build
   ```
   *(Tunggu sampai proses selesai. File hasil build akan otomatis masuk ke folder `public/build`)*.
3. Setelah build selesai, jadikan seluruh folder `proofix` ini menjadi file zip (misalnya: `proofix.zip`). **PENTING:** Anda tidak perlu menjalankan `npm run dev` atau `php artisan serve` lagi setelah ini.

---

## 📦 Tahap 2: Persiapan di Synology NAS

Pastikan Anda sudah meng-install aplikasi (package) berikut dari **Package Center** di NAS Anda:
- **Web Station**
- **PHP 8.x** (Misalnya PHP 8.2 - Sesuaikan dengan versi yang dipakai di lokal)
- **MariaDB 10** (Database Server)
- **phpMyAdmin** (Database Manager)

---

## 📂 Tahap 3: Memindahkan File ke NAS

1. Buka aplikasi **File Station** di NAS.
2. Buka folder `web`.
3. Upload file `proofix.zip` dari komputer Anda ke dalam folder `web` tersebut.
4. Klik kanan pada file zip, lalu pilih **Extract Here**.
5. Sekarang project Anda berada di struktur path: `/volume1/web/proofix`.

---

## 🗄️ Tahap 4: Mengatur Database MySQL (MariaDB)

1. Di komputer lokal Anda (Laragon), *Export* database Proofix menjadi file `.sql`.
2. Buka aplikasi **phpMyAdmin** di NAS (Login menggunakan username `root` dan password MariaDB Anda).
3. Buat database baru dengan nama yang sama (misal: `proofix_db`).
4. **Import** file `.sql` Anda ke database baru tersebut.
5. Kembali ke **File Station** NAS, buka file `web/proofix/.env` dengan Text Editor bawaan.
6. Sesuaikan konfigurasi database dengan milik NAS:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3307        # PENTING: MariaDB 10 Synology menggunakan port 3307
   DB_DATABASE=proofix_db
   DB_USERNAME=root
   DB_PASSWORD=password_mariadb_nas_anda
   ```
   *Simpan file .env tersebut.*

---

## 🔑 Tahap 5: Mengatur Izin Akses Folder (Permission)

Laravel membutuhkan izin `write` (menulis) pada folder `storage` dan `bootstrap/cache`.
1. Di **File Station**, klik kanan pada folder `web/proofix/storage`, pilih **Properties**.
2. Masuk ke tab **Permission** -> Klik **Create/Add**.
3. Pilih User/Group: `http` (Ini adalah user khusus Web Station).
4. Centang kotak **Read** dan **Write** agar berwarna biru penuh.
5. Klik **Done/Save**.
6. Ulangi langkah 1-5 untuk folder `web/proofix/bootstrap/cache`.

---

## 🌐 Tahap 6: Mengatur Web Station

1. Buka aplikasi **Web Station**.
2. Masuk ke menu **Web Service**, lalu klik **Create** (Buat).
3. Pilih **Virtual Host** (atau *Native Portal*).
4. Lakukan pengaturan berikut:
   - **Port-based:** Centang, lalu masukkan port khusus, misal `8080` (HTTP).
   - **Document Root:** Klik Browse, lalu pilih folder `web/proofix/public`. 
     *(PENTING: Harus diarahkan ke folder `/public`, bukan `/proofix` saja!)*
   - **HTTP Backend / PHP Profile:** Pilih **PHP 8.x** yang sudah diinstall tadi.
5. Klik **Create** / **Save**.

---

## 🎉 Tahap 7: Cara Mengakses Website

Website Proofix Anda sudah menyala! Untuk mengaksesnya, pastikan komputer/HP Anda terhubung ke Wi-Fi atau LAN yang sama dengan NAS, lalu buka browser dan ketikkan alamat IP NAS beserta Port-nya.

Contoh: 
`http://192.168.1.50:8080`

---

## ⚙️ Tahap 8: Cara Menjalankan Perintah PHP Artisan (Opsional)

Karena project ini sudah live dengan Web Station, Anda tidak butuh `php artisan serve`. Namun, jika Anda perlu menjalankan perintah seperti `php artisan migrate`, Anda harus melakukannya via SSH.

1. Di NAS, buka **Control Panel** -> **Terminal & SNMP** -> Centang **Enable SSH service**.
2. Di komputer lokal Anda, buka CMD / Terminal, lalu ketik:
   ```bash
   ssh username_nas_anda@IP_NAS_ANDA
   ```
3. Masukkan password NAS Anda.
4. Masuk ke folder project:
   ```bash
   cd /volume1/web/proofix
   ```
5. Jalankan perintah artisan menggunakan versi PHP yang terinstall (misal PHP 8.2):
   ```bash
   sudo /usr/local/bin/php82 artisan migrate
   ```
