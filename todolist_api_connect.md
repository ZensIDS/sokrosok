# Todolist Integrasi Sokrosok → App Laravel Lain

Tujuan: app Laravel lain (Laravel 8/9) bisa menampilkan **peta**, **daftar tempat sampah**, **detail**, dan **live update** isi tong dari Sokrosok.

## Keputusan (sudah disepakati)

- Semua app ada di 1 server aman, kendali penuh di tangan sendiri.
- Sokrosok menyediakan **API baca-saja** dengan **token terpisah** dari API key ESP32.
- App lain memanggil API **server-ke-server** (token tidak pernah sampai ke browser).
- Live update lewat **polling 30–60 detik** + cache singkat di app lain.
- **Firmware ESP32 tidak diubah lagi.**
- Peta memakai Leaflet + OpenStreetMap (tanpa API key berbayar).

## Cara kerja kita

- Dikerjakan **satu tahap per giliran**; setelah tahap selesai dan dites, centang `[x]`.
- Kirim file yang diperlukan (bukan bentuk zip dan bukan di tempel di chat).
- Tahap 1–5 di **Sokrosok** (zip-nya sudah ada). Tahap 6 dst. di **app lain** (zip dikirim saat masuk tahap 6).
- Jangan sertakan `.env`, `vendor/`, `node_modules/` saat mengirim zip.

---

## A. Sisi Sokrosok (penyedia data)

### Tahap 1 — Token akses baca-saja
- [x] Buat token acak panjang (min. 40 karakter), simpan di `.env` Sokrosok (`SOKROSOK_READ_TOKENS`, boleh lebih dari satu dipisah koma supaya mudah rotasi)
- [x] Daftarkan di `config/services.php`
- [x] Buat middleware `AuthenticateReadToken` (header `Authorization: Bearer <token>`, bandingkan dengan `hash_equals`, balas 401 jika salah)
- [x] Pasang rate limit (`throttle`) untuk grup rute baru

### Tahap 2 — Format data (JSON)
- [x] Buat `BinResource` (atau array transformer) yang hanya memuat: kode, nama, lokasi, tinggi tong, persen isi, status (`empty/medium/full/offline/error`), label status, lat, lng, waktu laporan terakhir, `is_offline`, `sensor_error`
- [x] Pastikan **tidak ada** `api_key`, data WiFi, atau kolom internal perangkat
- [x] Hanya tong `is_active = true` yang tampil

### Tahap 3 — Endpoint `/api/v1/...`
- [x] `GET /api/v1/bins` — daftar tong + ringkasan jumlah (total, penuh, sedang, kosong, offline)
- [x] `GET /api/v1/bins/{code}` — detail + riwayat laporan terbaru (`?limit=`, default 48, maksimal 200)
- [x] `GET /api/v1/map` — daftar penanda (lat, lng, persen, warna, label) siap pakai untuk Leaflet
- [x] Semua respons menyertakan `updated_at` (waktu server)

### Tahap 4 — Tes API Sokrosok
- [x] Tanpa token → 401
- [x] Token salah → 401
- [x] Token benar → 200 dan struktur JSON sesuai
- [x] Tong nonaktif tidak muncul; tong tanpa koordinat tidak muncul di `/map`
- [x] Respons tidak membocorkan kredensial perangkat

### Tahap 5 — Deploy Sokrosok
- [x] Upload file baru ke server, isi token di `.env` server
- [x] `php artisan config:clear` lalu `config:cache` / `route:cache` bila dipakai
- [x] Tes dengan `curl` dari server (domain `sokrosok.decaasoftwares.com`)

---

## B. Sisi App Laravel lain (penerima data)

### Tahap 6 — Kenali app lain
- [x] Kirim zip app (tanpa `.env`, `vendor/`, `node_modules/`)
- [x] Cek versi Laravel & PHP di `composer.json`
- [x] Pelajari struktur: routes, layout, sistem login & role, framework CSS/JS yang dipakai
- [x] Tentukan letak menu baru dan siapa yang boleh mengaksesnya
- **Hasil:** Laravel 9.19+ (`^9.19`), PHP `^8.0.2`, Guzzle 7 (jadi `Http` facade siap pakai), Sanctum 3. Login session lewat `AuthController` (`/user-login`); role di `users.role` (`admin`, `pengepul`, `masyarakat`), dijaga middleware `verified.role:...`.
- **Layout admin:** `layouts/master.blade.php` + `layouts/sidebar.blade.php` (AdminLTE 2, Bootstrap 3, jQuery 2.1.4, Font Awesome 4.4). Leaflet 1.9.1 + OSM sudah dipakai di `dashboard/index.blade.php`.
- **Keputusan:** hanya role `admin` yang boleh mengakses. Menu "Smart Bin" di `layouts/sidebar.blade.php`, tepat setelah "Pengambilan". Layout pengepul/masyarakat tidak diubah.

### Tahap 7 — Klien API di app lain
- [x] Tambah `SOKROSOK_URL` dan `SOKROSOK_TOKEN` di `.env`, daftarkan di `config/services.php`
- [x] Buat service `SokrosokClient` (HTTP client, timeout ±5 detik)
- [x] Cache hasil 30–60 detik; bila Sokrosok gagal, tampilkan data cache terakhir dengan penanda "data terakhir"
- Catatan: pakai `Illuminate\Support\Facades\Http` (Laravel 9); blok `sokrosok` ditambahkan ke `config/services.php` app lain

### Tahap 8 — Endpoint internal untuk browser
- [x] Rute JSON di app lain (dilindungi login app itu) yang meneruskan data dari `SokrosokClient`
- [x] Browser hanya memanggil rute ini, tidak pernah memanggil Sokrosok langsung

### Tahap 9 — Halaman daftar & detail tong
- [ ] Daftar tong (persen isi, status, laporan terakhir) dengan polling 30–60 detik
- [ ] Halaman detail satu tong: info + grafik riwayat
- Catatan: halaman `/smart-bin` (daftar) dan `/smart-bin/tong/{code}` (detail), view di `resources/views/smartbin/`; grafik pakai Highcharts 10.3.3 (sama seperti dashboard); polling 30 detik, berhenti saat tab tersembunyi

### Tahap 10 — Halaman peta
- [ ] Peta Leaflet + OpenStreetMap, penanda berwarna sesuai status
- [ ] Popup berisi nama, persen isi, status, link ke detail
- [ ] Penanda ikut diperbarui lewat polling

### Tahap 11 — Menu & hak akses
- [ ] Tambah menu "Smart Bin" di `layouts/sidebar.blade.php` (setelah "Pengambilan"), ikon `fa fa-trash-o` atau sejenis, state `active` mengikuti rute
- [ ] Rute daftar, detail, peta, dan endpoint JSON internal diletakkan di grup `['auth', 'verified.role:admin']` di `routes/web.php` (hanya admin)

---

## C. Penutup

### Tahap 12 — Tes menyeluruh
- [ ] ESP32 melapor → data muncul di app lain dalam satu siklus polling
- [ ] Tong offline / sensor error tampil dengan benar
- [ ] Sokrosok dimatikan sementara → app lain tetap tampil dari cache, tidak error
- [ ] Token diganti → app lain gagal dengan pesan jelas, bukan layar error

### Tahap 13 — Keamanan & operasional
- [ ] Rencana rotasi token (tambah token baru → ganti di app lain → hapus token lama)
- [ ] Pastikan `.env` kedua app tidak masuk repositori
- [ ] Catat alur dan lokasi token di dokumentasi singkat
- [ ] (Saran, di app lain) Hapus atau batasi ke admin rute `/optimize`, `/preview-order-email`, `/preview-verification-email` di `routes/web.php` karena saat ini bisa dibuka tanpa login