# Operasional Promo Sehat

Fitur Promo Sehat menggunakan database notification, Laravel Reverb, queue, scheduler, dan Web Push. Migrasi aplikasi harus dijalankan sebelum fitur digunakan:

```bash
php artisan migrate --force
php artisan storage:link
npm run build
```

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

Pada lingkungan pengembangan, `composer dev` sudah menjalankan web server, queue, Vite, log viewer, dan Reverb bersama-sama. Push notification hanya bekerja pada HTTPS atau `localhost`, dan pada iPhone/iPad pengguna perlu menambahkan E-Pasien ke Home Screen sebelum mengaktifkan notifikasi.
