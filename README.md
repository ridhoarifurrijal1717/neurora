# Neurora

Neurora adalah web untuk membahas masalah lingkungan yang dekat dengan keseharian. Pengguna bisa menceritakan keluhannya, bertanya kepada AI, menyusun rencana tindakan, lalu mencoba perkiraan dampak dari rencana tersebut.

Topik yang tersedia meliputi udara, air, sampah, tanah, energi, dan ruang hijau. Kalau belum menemukan kategori yang sesuai, pengguna tetap bisa memulai percakapan tanpa memilih topik terlebih dahulu.

## Cara menggunakan web

### 1. Kenali masalahnya

Buka halaman Problem dan pilih keluhan yang paling mendekati kondisi di sekitar. Misalnya, sampah rumah tangga masih tercampur atau pemakaian air terasa berlebihan. Pilihan ini membantu AI memahami konteks awal percakapan.

Kolom pencarian juga bisa dipakai untuk menemukan topik. Pencarian membaca katalog masalah yang sudah tersedia di aplikasi.

### 2. Ceritakan kondisinya

Pada halaman Solusi AI, tulis apa yang terjadi dan bantuan seperti apa yang dibutuhkan. Cerita yang cukup jelas akan lebih berguna daripada pertanyaan yang terlalu umum.

Contohnya, “Sampah dapur di rumah langsung dicampur dengan plastik. Kami ingin mulai memilah, tetapi tempat yang tersedia sempit. Apa yang bisa dilakukan terlebih dahulu?”

Setelah mendapat jawaban, pengguna bisa bertanya lagi. Riwayat percakapan dipakai sebagai konteks untuk pertanyaan berikutnya. Satu percakapan dibatasi sampai 12 tanya jawab. Tombol Chat baru tersedia untuk memulai pembahasan lain.

### 3. Susun rencana tindakan

Pilih Susun rencana dari chat, lalu lengkapi ringkasan masalah, kondisi tempat, nilai awal, target perbaikan, dan waktu evaluasi. Pengguna juga bisa langsung mengisi formulir ini tanpa memulai chat.

Tekan Buat solusi AI untuk mendapatkan rencana yang berisi kemungkinan penyebab, langkah tindakan, dugaan perubahan, dan cara memeriksa hasilnya.

Tombol Lihat contoh alur bisa dipakai untuk mencoba prosesnya tanpa meminta jawaban AI. Hasil dari tombol ini berupa contoh statis dan diberi keterangan di halaman.

### 4. Coba perkiraan dampaknya

Dari hasil rencana, buka Dugaan Dampak. Ubah nilai awal, target, atau lama pelaksanaan untuk melihat bagaimana perhitungannya berubah.

Untuk indikator harian, rumus yang dipakai adalah:

```text
Perkiraan manfaat = nilai awal × jumlah hari × target / 100
```

Sebagai contoh, pemakaian air 200 liter per hari dengan target penghematan 20 persen selama 30 hari menghasilkan perkiraan penghematan 1.200 liter.

Untuk indikator luas ruang hijau, perhitungannya memakai luas tersedia dikalikan target, tanpa dikalikan jumlah hari. Pilihan periode tetap berguna sebagai waktu evaluasi.

Mengubah angka di halaman dampak hanya menghitung ulang skenario. Isi rencana tindakan tetap mengikuti konteks saat rencana dibuat. Angka tersebut belum menunjukkan hasil nyata di lapangan. Catat kondisi awal dan ukur kembali setelah tindakan dilakukan.

### 5. Kirim masukan

Formulir kontak di halaman utama menerima nama, email, dan pesan. Pesan baru tersimpan jika koneksi MySQL sudah tersedia dan tabel `contact_messages` sudah dibuat.

## Isi proyek

`app/` menyimpan halaman PHP dan logika aplikasi. Halaman utamanya berada di `app/index.php`, sedangkan halaman Problem, Solusi AI, dan Dugaan Dampak berada dalam folder yang sama.

`app/api/` menangani pencarian, chat, pembuatan rencana, dan pengiriman pesan kontak. Folder `app/includes/` berisi fungsi bersama, katalog masalah, serta penghubung ke penyedia AI. Folder `app/sections/` berisi bagian halaman utama.

`app/config/` menyimpan pembaca konfigurasi dan contoh pengaturan lokal. File contoh tidak berisi kunci API aktif maupun kata sandi hosting.

`assets/` berisi CSS, JavaScript, dan gambar. Tampilan memakai JavaScript biasa, dengan Three.js untuk globe pada halaman utama.

`api/index.php` menjadi pintu masuk permintaan di Vercel. Daftar rute di file ini menentukan halaman yang boleh dibuka. Folder konfigurasi dan SQL tidak disajikan sebagai halaman publik.

`database/` berisi SQL. `tools/` berisi alat pemeriksaan AI dari terminal. File `start.php` dan `router.php` dipakai untuk menjalankan proyek di komputer sendiri.

## Menjalankan di komputer sendiri

Siapkan PHP 8.2 atau lebih baru. Aktifkan ekstensi cURL, mbstring, PDO, dan pdo_mysql. MySQL atau MariaDB diperlukan bila ingin memakai formulir kontak. Apache, Node.js, dan Composer tidak diperlukan untuk menjalankan server lokal yang disediakan di proyek ini.

1. Unduh isi repositori atau salin proyek ke komputer.
2. Salin `app/config/ai.local.example.php` menjadi `app/config/ai.local.php`.
3. Isi penyedia AI, kunci API, dan nama model di file salinan tersebut. Contoh pengaturan memakai Gemini. Sesuaikan model dengan akses yang tersedia pada akun penyedia.
4. Jika ingin mengaktifkan kontak, siapkan database sesuai panduan SQL di bawah. Salin `app/config/database.local.example.php` menjadi `app/config/database.local.php`, lalu isi koneksinya.
5. Buka terminal di folder utama proyek dan jalankan:

```sh
php start.php
```

6. Buka `http://127.0.0.1:8000` di browser. Gunakan kombinasi Ctrl dan C di terminal untuk menghentikan server.

Server lokal ini hanya mendengarkan koneksi dari komputer sendiri. Jangan gunakan server bawaan PHP sebagai server publik.

File konfigurasi lokal sudah masuk `.gitignore`. Kunci API dan kata sandi cukup disimpan di komputer atau pengaturan server.

## Menyiapkan database

Ada tiga pilihan SQL. Pilih sesuai kebutuhan, lalu impor satu pilihan ke database tujuan. Tidak perlu menjalankan semuanya pada database yang sama.

### Instalasi baru dengan skema lengkap

Gunakan `database/install.sql`. File ini menyiapkan database `neurora`, tabel kontak, katalog awal, dan tabel untuk pengembangan penyimpanan percakapan serta dampak. File ini juga membuat view perhitungan skenario.

Target skemanya adalah MySQL 8.0.16 atau lebih baru, atau MariaDB 10.4 atau lebih baru. Tabel memakai InnoDB dan karakter utf8mb4.

Di phpMyAdmin, buka tab SQL atau Import, pilih file tersebut, lalu jalankan. Jika akun hosting tidak diizinkan membuat database, buat database melalui panel hosting terlebih dahulu. Pada salinan SQL yang akan diimpor, hapus perintah `CREATE DATABASE` dan sesuaikan perintah `USE` dengan nama database dari hosting.

File instalasi memakai `IF NOT EXISTS` untuk tabel. Mengimpornya lagi tidak memperbarui struktur tabel lama yang sudah ada. Cadangkan data sebelum mengubah skema.

### Salinan ekspor yang dilampirkan

`database/neurora.sql` adalah salinan utuh ekspor phpMyAdmin dari berkas yang dilampirkan bersama proyek. Isinya disertakan agar struktur dan data awal dari sumber tetap tersedia.

Gunakan database kosong saat mengimpor ekspor ini karena beberapa perintah membuat tabel secara langsung. Ekspor juga mencantumkan pemilik view `root` pada `localhost`. Jika hosting menolak bagian pemilik view tersebut, gunakan `database/install.sql` untuk instalasi baru atau sesuaikan definisi view dengan akun database tujuan.

### Hanya mengaktifkan kontak

Gunakan `database/contact.sql` jika yang diperlukan hanya tabel pesan kontak. File ini cukup untuk fitur database yang sudah dipakai aplikasi saat ini.

### Bagian yang sudah memakai database

Saat ini, aplikasi hanya menulis pesan kontak melalui PDO. Kategori masalah masih dibaca dari kode PHP. Percakapan, rencana solusi, dan hasil simulasi masih disimpan dalam sesi PHP.

Tabel dengan awalan `eco_` sudah tersedia dalam skema lengkap, tetapi belum dihubungkan ke alur penyimpanan aplikasi. Mengimpor SQL tidak otomatis membuat riwayat chat tersimpan permanen.

## Mengatur layanan AI

Aplikasi mendukung Gemini, Grok melalui xAI, dan Ollama. Pilihan penyedia dibaca dari `NEURORA_AI_PROVIDER`, dengan nilai `gemini`, `xai`, atau `ollama`.

Untuk Gemini, isi `GEMINI_API_KEY` dan `GEMINI_MODEL`. Untuk Grok, isi `XAI_API_KEY` dan `XAI_MODEL`. Untuk Ollama, isi `OLLAMA_BASE_URL` dan `OLLAMA_MODEL`.

Pengaturan melalui environment akan didahulukan daripada file konfigurasi lokal. File `.env` tidak dibaca otomatis oleh aplikasi ini. Pada komputer sendiri, gunakan file konfigurasi lokal yang sudah dicontohkan atau atur environment melalui sistem operasi.

Ollama dalam versi ini dibatasi ke alamat loopback. Pilihan tersebut cocok ketika Ollama dan PHP berjalan pada komputer yang sama. Alamat `127.0.0.1` di Vercel tidak menunjuk ke komputer pengguna.

Saat pengguna mengirim pertanyaan, isi pertanyaan, konteks, dan riwayat terkait dikirim ke penyedia AI yang dipilih. Jawaban AI perlu diperiksa sebelum dijadikan keputusan atau tindakan di lapangan.

Untuk memeriksa konfigurasi dari terminal, jalankan:

```sh
php tools/check_ai.php
```

Pada Gemini dan Grok, perintah ini memeriksa konfigurasi tanpa mengirim permintaan generasi. Pada Ollama, alat akan mencoba memeriksa model pada layanan lokal. Uji jawaban sebenarnya bisa dilakukan dari halaman Solusi AI.

## Memasang di Vercel

1. Masuk ke Vercel, buat proyek, lalu impor repositori ini.
2. Gunakan direktori utama repositori sebagai Root Directory dan pilih framework Other.
3. Kosongkan Build Command, Install Command, dan Output Directory. Konfigurasi fungsi PHP sudah tersedia dalam `vercel.json`.
4. Tambahkan `NEURORA_AI_PROVIDER`, lalu kunci API dan model sesuai penyedia yang digunakan.
5. Untuk kontak, tambahkan `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASS`. Database harus bisa menerima koneksi dari server Vercel. Impor SQL melalui pengelola database secara terpisah.
6. Jalankan deployment. Bila environment diubah setelahnya, lakukan deployment ulang agar pengaturan baru digunakan.

PHP dijalankan melalui runtime komunitas yang sudah ditentukan dalam `vercel.json`. Aset disajikan sebagai file statis. SQL dan konfigurasi lokal dikecualikan dari unggahan deployment melalui `.vercelignore`.

Setelah situs tersedia, buka `/api/health.php` pada domain proyek. Nilai `status` yang berisi `ok` berarti fungsi PHP merespons. Nilai `database` yang berisi `ready` berarti tabel kontak dapat dibaca. Nilai `unavailable` berarti koneksi atau tabel kontak masih perlu diperiksa.

Pada pemeriksaan deployment awal tanggal 30 Oktober 2026, halaman utama, pencarian, dan chat Gemini sudah berjalan. Database kontak belum tersedia. Status ini adalah catatan pemeriksaan saat itu dan bisa berubah setelah konfigurasi diperbarui.

## Sesi dan batas penggunaan

Percakapan dan rencana tersedia selama sesi masih tersimpan, dengan batas umur data 24 jam sejak dibuat. Aplikasi menyimpan paling banyak lima percakapan dan lima rencana dalam satu sesi. Chat dan pembuatan rencana berbagi batas 10 permintaan dalam 10 menit per sesi.

Di Vercel, file sesi berada di penyimpanan sementara `/tmp`. Data bisa hilang saat instance dihentikan, diganti, atau saat permintaan berpindah ke instance lain. Untuk riwayat yang tetap tersedia, aplikasi perlu dihubungkan ke penyimpanan sesi bersama atau database. Bagian ini belum diterapkan.

## Jika ada kendala

### Halaman tampil, tetapi AI tidak menjawab

Periksa penyedia, kunci API, nama model, akses akun, dan kuota pada penyedia. Pastikan cURL aktif. Di Vercel, batas waktu fungsi saat ini 60 detik, sehingga permintaan yang lebih lama bisa terhenti walaupun waktu tunggu pada konfigurasi AI lebih panjang.

### Pesan kontak tidak tersimpan

Periksa koneksi MySQL dan keberadaan tabel `contact_messages`. Hosting database yang hanya menerima koneksi dari servernya sendiri tidak bisa langsung digunakan dari Vercel.

### Muncul pesan sesi tidak valid

Muat ulang halaman lalu coba kembali. Jika terus terjadi di Vercel, periksa penyimpanan sesi sementara. Pastikan browser juga mengizinkan cookie untuk situs tersebut.

### Globe atau font tidak tampil

Globe memuat Three.js dari CDN dan font memakai Google Fonts. Periksa koneksi internet serta pembatasan akses pada jaringan atau browser.

### Ingin mengganti isi kategori

Ubah katalog di `app/includes/ecosystem.php`. Mengubah tabel kategori di database saja belum mengubah tampilan web karena sumber katalog aktif masih berada di PHP.

## Catatan tentang hasil

Neurora membantu menyusun langkah awal dan mencoba asumsi yang bisa dilihat perhitungannya. Hasil simulasi tidak membuktikan bahwa kualitas lingkungan sudah membaik. Gunakan ukuran yang sesuai, catat hasil pelaksanaan, dan sesuaikan rencana berdasarkan kondisi yang benar terjadi.

Proyek ini mencantumkan kredit pembuat RWS pada bagian footer.
