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
- [ ] Upload file baru ke server, isi token di `.env` server
- [ ] `php artisan config:clear` lalu `config:cache` / `route:cache` bila dipakai
- [ ] Tes dengan `curl` dari server (domain `sokrosok.decaasoftwares.com`)

---

## B. Sisi App Laravel lain (penerima data)

### Tahap 6 — Kenali app lain
- [ ] Kirim zip app (tanpa `.env`, `vendor/`, `node_modules/`)
- [ ] Cek versi Laravel & PHP di `composer.json`
- [ ] Pelajari struktur: routes, layout, sistem login & role, framework CSS/JS yang dipakai
- [ ] Tentukan letak menu baru dan siapa yang boleh mengaksesnya

### Tahap 7 — Klien API di app lain
- [ ] Tambah `SOKROSOK_URL` dan `SOKROSOK_TOKEN` di `.env`, daftarkan di `config/services.php`
- [ ] Buat service `SokrosokClient` (HTTP client, timeout ±5 detik)
- [ ] Cache hasil 30–60 detik; bila Sokrosok gagal, tampilkan data cache terakhir dengan penanda "data terakhir"

### Tahap 8 — Endpoint internal untuk browser
- [ ] Rute JSON di app lain (dilindungi login app itu) yang meneruskan data dari `SokrosokClient`
- [ ] Browser hanya memanggil rute ini, tidak pernah memanggil Sokrosok langsung

### Tahap 9 — Halaman daftar & detail tong
- [ ] Daftar tong (persen isi, status, laporan terakhir) dengan polling 30–60 detik
- [ ] Halaman detail satu tong: info + grafik riwayat

### Tahap 10 — Halaman peta
- [ ] Peta Leaflet + OpenStreetMap, penanda berwarna sesuai status
- [ ] Popup berisi nama, persen isi, status, link ke detail
- [ ] Penanda ikut diperbarui lewat polling

### Tahap 11 — Menu & hak akses
- [ ] Tambah menu di app lain
- [ ] Batasi akses sesuai role yang dipilih di tahap 6

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