# Operasional Promosi & Informasi

Fitur Promosi & Informasi menggunakan database notification, Laravel Reverb, queue, scheduler, dan Web Push. Migrasi aplikasi harus dijalankan sebelum fitur digunakan:

```bash
php artisan migrate --force
php artisan storage:link
npm run build
```

Setiap konten memiliki kategori `promotion` (Promosi) atau `information` (Informasi). Konten yang sudah ada otomatis dikategorikan sebagai Promosi saat migrasi dijalankan. Kategori ditampilkan sebagai label berbeda pada kartu, halaman detail, filter pasien, dan notifikasi.

## Environment produksi

Isi seluruh variabel `REVERB_*`, `VITE_REVERB_*`, `VAPID_SUBJECT`, `VAPID_PUBLIC_KEY`, dan `VAPID_PRIVATE_KEY` yang dicontohkan pada `.env.example`. Gunakan HTTPS untuk domain produksi. `VAPID_SUBJECT` harus berupa URL publik yang valid atau alamat `mailto:` dan kunci VAPID tidak boleh diganti setelah pasien mulai berlangganan.

Kunci Web Push dapat dibuat satu kali dengan:

```bash
php artisan webpush:vapid
```

## Proses yang wajib aktif

Selain web server, jalankan tiga proses berikut melalui supervisor atau service manager platform deployment:

```bash
php artisan queue:work --tries=3 --timeout=600
php artisan reverb:start
php artisan schedule:work
```

Pada lingkungan pengembangan, `composer dev` sudah menjalankan web server, queue, scheduler, Vite, log viewer, dan Reverb bersama-sama. Push notification hanya bekerja pada HTTPS atau `localhost`, dan pada iPhone/iPad pengguna perlu menambahkan E-Pasien ke Home Screen sebelum mengaktifkan notifikasi.

Scheduler juga memeriksa antrean poli setiap 10 detik. Saat nomor yang sedang dipanggil cocok dengan registrasi pasien aktif, sistem mengirim notifikasi in-app, broadcast real-time, dan Web Push satu kali kepada akun pasien dengan nomor rekam medis yang sesuai. Karena jadwal ini berjalan sub-menit, proses `php artisan schedule:work` atau pemanggilan `php artisan schedule:run` setiap menit harus tetap aktif bersama queue worker.

Izin notifikasi dan langganan Web Push bersifat wajib pada seluruh halaman E-Pasien. Pengguna baru harus menekan **Aktifkan Sekarang** dan memilih **Izinkan** pada dialog browser sebelum dapat melanjutkan. Pastikan VAPID dan HTTPS sudah siap sebelum fitur ini diterapkan; konfigurasi yang belum siap akan membuat gerbang notifikasi tetap terkunci. Jika izin dicabut atau ditolak, pengguna harus mengubah izin situs menjadi **Izinkan** melalui pengaturan browser, lalu menekan **Periksa Kembali**.

Scheduler menjalankan pembersihan konten Promosi & Informasi setiap lima menit agar beban pemeriksaan database tetap ringan. Pengelola dapat mengatur durasi bawaan, mengaktifkan/nonaktifkan pembersihan, dan menentukan masa tenggang melalui **Promosi & Informasi > Konfigurasi Konten**. Nilai masa tenggang `0` menghapus konten terbit/arsip pada siklus pembersihan berikutnya setelah waktu tayangnya berakhir; draf tidak ikut dihapus.

Gambar unggahan dibatasi hingga 4096 × 4096 piksel. Jika ekstensi PHP GD dengan dukungan WebP tersedia, gambar otomatis diperkecil maksimal 1600 × 1200 dan disimpan sebagai WebP agar daftar konten serta notifikasi lebih ringan. Pastikan paket `ext-gd` aktif di produksi untuk memperoleh optimasi ini.

## Target penerima notifikasi konten

Target notifikasi diatur melalui **Pengaturan Akses > Konfigurasi Roles**. Penerima merupakan gabungan user aktif dari role yang dicentang dan user/pasien yang dipilih langsung. Untuk pengujian satu akun, nonaktifkan seluruh role pada kolom **Notifikasi Konten**, lalu pilih hanya akun uji pada bagian **Pilih Pasien/User Tertentu** sebelum konten diterbitkan.
