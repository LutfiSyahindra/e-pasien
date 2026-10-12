# Pengaturan Informasi Landing Page

Modul tersedia di **Pengaturan → Landing Page & Data → Informasi Landing Page**, dengan URL `/e-pasien/settings/landing-page-data/informasi`.

## Penggunaan

1. Pilih bagian halaman pada navigasi editor.
2. Edit identitas rumah sakit, banner, pengantar aplikasi dan layanan pasien, layanan, poli, pengantar jadwal dan dokter, kontak, atau lokasi.
3. Daftar layanan, poli, dan kontak tambahan mendukung tambah, hapus, dan pengaturan urutan; maksimal 30 item per daftar. Layanan utama dan poli wajib memiliki minimal satu item.
4. Logo, latar banner, dan foto gedung menerima JPG, PNG, atau WEBP hingga 4 MB dan 6000 × 6000 piksel. Centang **Gunakan gambar bawaan** untuk mengembalikan gambar.
5. Tekan **Simpan perubahan**. Tombol **Lihat landing page** membuka informasi yang sudah tersimpan.

Nomor kontak utama dan darurat diperbarui bersamaan dengan tautan telepon. Kontak utama dan media sosial pada footer mengikuti data editor. Jadwal praktik dan foto dokter dikelola melalui modul yang sudah tersedia.

## Akses dan penyimpanan

- Pengguna harus memiliki `EPASIEN.SETTINGS` dan `EPASIEN.SETTINGS.LANDING_PAGE`. Super Admin memakai aturan akses penuh aplikasi. Migrasi memberi permission modul kepada peran Administrator dan Admin yang sudah tersedia.
- Konten tersimpan di `landing_page_settings` sebagai satu konfigurasi dengan waktu pembaruan dan ID pengguna yang menyimpan. Gambar tersimpan pada disk `public`, direktori `landing-page`.
- Nilai bawaan, skema formulir, dan pilihan validasi berada di `config/landing-page.php`. Halaman publik menggunakan nilai bawaan sebelum ada konfigurasi tersimpan.
- Teks ditampilkan sebagai teks biasa dengan escaping Blade. URL media sosial dan navigasi harus memakai HTTPS; URL iframe peta dibatasi ke `https://www.google.com/maps` atau `https://google.com/maps`.

## Pemasangan di lingkungan lain

Jalankan migrasi aplikasi seperti biasa:

```shell
php artisan migrate
php artisan storage:link
php artisan optimize:clear
```

Jangan jalankan seeder akun untuk mengaktifkan modul ini; permission modul dipasang oleh migrasi.

## Pengujian

```shell
php artisan test --filter="LandingPageTest|LandingPageSettingsTest|DoctorScheduleSettingsPageTest|RegistrationRoleConfigurationTest"
```

Konfigurasi pengujian memakai SQLite dalam memori. Aktifkan ekstensi PDO SQLite pada PHP pengujian. Aset editor berupa CSS dan JavaScript langsung di `public/epasien/assets`, sehingga tidak memerlukan build Vite.
