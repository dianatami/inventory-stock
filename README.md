<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Sistem Inventori TPN

Aplikasi ini menyediakan dashboard gudang dan portal User Outlet dengan pemisahan stok gudang dan stok outlet. Database aplikasi menggunakan `db_inventori_distribusi`; database lama tidak dihapus.

### Menjalankan aplikasi

1. Jalankan MySQL dari Laragon dan sesuaikan kredensial koneksi pada `.env`.
2. Jalankan `php artisan migrate --seed` untuk membuat skema dan akun Manager awal.
3. Jalankan `npm install` lalu `npm run build`, kemudian buka virtual host Laragon atau mulai aplikasi menggunakan `php artisan serve`.
4. Masuk sebagai Manager, buat outlet pada menu **Data Outlet**, lalu buat akun **User Outlet** dari **Manajemen Pengguna** dan hubungkan ke outlet.

Halaman login tersedia di `/` (route bernama `login`). Dashboard gudang tersedia di `/dashboard`; portal User Outlet tersedia di `/outlet/dashboard`. URL `/home` merupakan alias terlindungi autentikasi yang meneruskan pengguna ke dashboard sesuai role.

Alur pesanan, pengiriman, validasi penerimaan, retur, stok opname, dan laporan barang terpakai tersedia pada menu masing-masing. Pengiriman mengurangi stok gudang, sedangkan validasi penerimaan menambah stok outlet.

Ubah password awal Manager segera setelah login pertama.

### Distribusi, retur, dan notifikasi stok

Outlet hanya dapat mengajukan jumlah yang tersedia saat pesanan dibuat. Admin Prolog memeriksa stok lagi sebelum menyetujui dan saat mengirim; stok gudang baru berkurang ketika pengiriman dikonfirmasi. Stok outlet baru bertambah setelah penerimaan divalidasi gudang. Untuk retur, outlet mengirim barang setelah persetujuan; stok outlet berkurang saat pengiriman retur dan stok gudang hanya bertambah untuk jumlah yang dinyatakan layak setelah pemeriksaan fisik.

Perubahan status pesanan, penerimaan, dan retur dicatat bersama pengguna, waktu, jumlah, serta catatan. Stok awal produk juga dicatat sebagai transaksi masuk; saldo stok tidak dapat diedit dari formulir edit produk.

Notifikasi stok dan aktivitas transaksi pada lonceng navbar mengambil kondisi dari database. Pemeriksaan stok berjalan setelah transaksi stok berhasil, saat feed dimuat, dan setiap 60 detik selama halaman aktif. Untuk pemeriksaan berkala saat tidak ada halaman yang terbuka, jalankan `php artisan schedule:work` di terminal Laragon terpisah; scheduler menjalankan `notifications:sync-stock` setiap menit. Pusat notifikasi tersedia di `/notifications`. Batas kritis ditetapkan pada stok positif yang sama dengan atau di bawah setengah batas minimum (dibulatkan ke bawah); stok nol ditandai habis. Notifikasi internal ini tidak mengirim pesan WhatsApp. Pengguna Laragon yang memperbarui aplikasi perlu menjalankan `php artisan migrate` setelah mencadangkan database, lalu `npm run build` untuk membangun aset frontend. Tes otomatis dapat dijalankan dengan `php artisan test`.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
