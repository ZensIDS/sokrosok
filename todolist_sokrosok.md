# TODOLIST SOKROSOK

> Rebuild **EcoCycle** (web + IoT smart bin) → **Sokrosok** di `sokrosok.decaasoftwares.com`
> Dibuat: 6 Nov 2026 · Status: **Tahap 0–3 selesai · Tahap 4 AKTIF (revisi 4D: auto-update AJAX)**

## Cara kerja
- Todolist dibuat dan dikerjakan **bertahap**. Hanya tahap yang sedang aktif yang dirinci; tahap berikutnya dirinci setelah tahap sebelumnya selesai.
- Perubahan kode dikirim sebagai **file yang berubah saja**, bukan zip seluruh project dan tidak ditempel di chat.
- Tiap selesai, selalu kirimkan update todolistnya.
- Centang `[x]` saat selesai. Tanda ⚠ = butuh keputusan atau data dari Anda.
- berikan jawaban singkat hanya yang penting saja dan juga tempat path file yang dimaskud jangan sampai terlewat

## Target
- Web saja: **tanpa Flutter, tanpa Node.js, tanpa Firebase**.
- Satu aplikasi menangani web (admin / petugas / nasabah) **dan** API untuk ESP32.
- Kontrak API ESP32 lama tetap kompatibel selama masa transisi (perangkat di lapangan tidak boleh putus).
- pekerjaan diselesaikan secepat mungkin. jadi sistem dibaut simple saja. pengembangan selanjutnya bertahap.

---

## A. Hasil mempelajari project lama

### A1. Isi project
| Komponen | Isi | Nasib di Sokrosok |
|---|---|---|
| Web PHP native | Front controller `index.php` + `routes.php` (array), `pages/` (±8 rb baris), `libraries/models/` (±2,5 rb), `actions/` (±2,5 rb) | Diganti Laravel |
| API Node.js (Express, port 8080, di-proxy lewat `/api`) | 18 controller, dibuat untuk Flutter dan sebagai jembatan ke Python | Dihapus, logika dipindah ke Laravel |
| Python | `ml_service.py`, `dashboard_engine.py`, `optimize_route.py`, Flask `models/app.py` (prediksi isi bin + rute; logikanya **tumpang tindih 3x**) | Diputuskan di D3 |
| Firmware ESP32 | `sampa_ok.ino`: HC-SR04, deep sleep, WiFiManager, GET config + POST status | Dijaga kompatibel, lalu firmware baru (Tahap 8) |
| MySQL | 24 tabel. Dump 18 Des 2025: 27 bin, 28.878 `bin_logs`, 11 user, 33 deposit, 14 permintaan jemput, 6 rute, 4 truk | Dimigrasi (Tahap 2) |
| Lain-lain | `page/*.html` (peta/dashboard statis lama), `page/firmware.php` (uploader OTA), `simulasi_bin_*.php`, model klasifikasi sampah `.h5/.tflite` (30 kelas) | Dibuang / ditunda |
| Firebase FCM | Hanya dipakai Node untuk push ke Flutter | Dihapus |

### A2. Fitur bisnis yang ada
- **Admin**: dashboard, CRUD nasabah/petugas/admin, bin, kategori sampah (harga per kg), truk, rute, deposit, verifikasi penarikan, penjualan massal, artikel, pengumuman, hadiah & penukaran, tiket dukungan, pengaturan, log pengumpulan.
- **Petugas**: dashboard, status bin, rute saya, permintaan jemput, catat & verifikasi deposit, log pengumpulan, kategori sampah.
- **Nasabah**: dashboard (saldo & poin), ajukan jemput, riwayat deposit, rewards, rekening, tarik saldo, artikel/pengumuman.
- **Publik**: home, artikel, pengumuman, peta bin publik.

### A3. Kontrak IoT yang HARUS dijaga
```
GET  /api/bins/config?bin_code=BIN-xxx      header: x-api-key
     → { "config": { "capacity_liters": 60, "sleep_interval_sec": 14400 } }
POST /api/bins/status-update                header: x-api-key
     body: { "bin_code": "BIN-xxx", "fill_level_percent": 0..100 }   → 200/201
```
- Status dihitung server: `> 80` = Penuh, `40–80` = Sedang, `< 40` = Kosong. Tiap update menulis `bins` + `bin_logs`.
- Firmware memakai `capacity_liters` sebagai **tinggi tong (cm)**. Di data: 26 bin bernilai 60, 1 bin bernilai 70; semua `sleep_interval_sec` = 14400 (4 jam).
- Firmware lama mengarah ke `ecocycle.co.id`; simulator dan halaman statis ke `ecocycle.pelitaindonesia.ac.id`. Ada **dua domain** tertanam.

### A4. Temuan: keamanan 🔴
1. `iot/catatan.txt` menyimpan **login panel hosting/VPS dalam teks biasa**.
2. **Password database** tertulis langsung di 5 file: `web/config.php`, `web/api/.env`, `web/api/ml_service.py`, `web/api/dashboard_engine.py`, `web/models/app.py`.
3. **Satu kunci IoT untuk semua bin**, tertanam di 4 file (`sampa_ok.ino`, `.env`, 2 file `simulasi_bin_*.php`). Firmware juga memakai `setInsecure()` (sertifikat TLS tidak diverifikasi).
4. **Service-account key Firebase** ikut di dalam project.
5. Form PHP **tanpa CSRF**; `display_errors` menyala di beberapa halaman produksi; `api.php` ber-CORS `*` dan menjalankan `shell_exec`.
6. Login PHP membuat "JWT" buatan sendiri dengan secret hardcoded.

### A5. Temuan: bug & ketidakkonsistenan 🟠
1. Node `processWithdrawal`: `const userFcmToken = userFcmToken[0] ...` → selalu error, sehingga proses penarikan via API Node pasti gagal.
2. Token buatan PHP vs middleware Node: **secret berbeda dan nama field payload berbeda** (`user_id` vs `id`) → tombol "generate rute optimal" di dashboard admin hampir pasti ditolak 401.
3. PHP memakai kolom `users.total_points`, tetapi **kolom itu tidak ada di dump mana pun**. Poin juga dihitung dua cara (`floor(total_price/10)` dan kolom). ⚠ cek DB live.
4. **Hash password**: 5 akun berawalan `$2b$` (dibuat Node). Diuji di PHP 8.3: `password_verify` lolos, tetapi `password_get_info` mengembalikan `unknown`. Laravel memeriksa algoritma itu, jadi akun tsb berpotensi gagal login. Solusi: ubah prefix ke `$2y$` saat impor (algoritma sama). Sisi Laravel diverifikasi di Tahap 1.
5. 3 rute menunjuk file yang tidak ada: `admin/edit-penjualan-massal`, `nasabah/transaksi`, `nasabah/transaksi/detail`. `actions/officer/proses_deposit.php` kosong (duplikat). `routes.truck_id` tanpa foreign key.
6. Firmware: bila sensor gagal baca, dikirim **0%** (tampak "Kosong" padahal rusak). Server OTA (`firmware.php`) ada, tetapi firmware tidak memeriksa update; form-nya juga menunjuk `admin.php` yang tidak ada.

### A6. Temuan: data
- **±53% `bin_logs` (15.292 dari 28.878) berasal dari 2 hari saja (1–2 Nov 2025)**, ditambah lonjakan 6 Des. Ada generator data dummy di `ml_service.py` dan simulator yang menembak endpoint produksi. Kesimpulan: model prediksi dilatih sebagian besar dari **data sintetis**. ⚠ konfirmasi mana yang data asli.

---

## B. TAHAP 0 — Keputusan & persiapan (SELESAI)

### 0A. Keamanan segera (tidak perlu menunggu migrasi)
- [ ] Ganti password akun panel hosting/VPS yang tertulis di `iot/catatan.txt`, lalu hapus file itu dari project/zip. Simpan di password manager.
- [ ] Cabut (revoke) service-account key Firebase di Google Cloud Console, karena Flutter tidak dipakai lagi.
- [ ] Ganti password user MySQL lama dan perbarui ke 5 file (A4 no.2) **atau** lakukan saat cutover jika sistem lama masih berjalan.
- [ ] **Jangan** ganti kunci IoT sekarang (perangkat lapangan akan putus). Diganti per-perangkat di Tahap 3 dan 8.
- [ ] (Opsional) matikan `display_errors` di halaman produksi lama.

### 0B. Keputusan arsitektur ⚠ (rekomendasi saya sudah diisi; tinggal setuju/ubah)
| # | Keputusan | Rekomendasi | Alasan | Status |
|---|---|---|---|---|
| D1 | Framework | **Laravel 13** (butuh PHP ≥ 8.3) | Auth, role, CSRF, validasi, migrasi, scheduler sudah tersedia. Menghilangkan lapisan Node + jembatan Python. Laravel 12 masa bug-fix-nya sudah berakhir (Agt 2026), jadi tidak disarankan untuk proyek baru. | [x] |
| D2 | Hosting `sokrosok` | Perlu PHP ≥ 8.3, Composer, SSH, cron | Menentukan D1 dan D3 | [x] |
| D3 | Prediksi & rute | **Port ke PHP**: tren linear + nearest-neighbor/2-opt (haversine) | Titik hanya ±30; model RF dilatih data sintetis; tanpa Python di server. Bisa ditinjau lagi bila data asli sudah cukup | [x] |
| D4 | Node API + Firebase | **Dihapus total**; notifikasi in-app (tabel `notifications` sudah ada) | Hanya untuk Flutter | [x] |
| D5 | Data lama | Migrasi user, bin, transaksi; `bin_logs` sintetis dipisah/dibuang | Data bank sampah itu nyata; log sintetis merusak prediksi | [x] |
| D6 | Nama & brand | **Sokrosok** menggantikan EcoCycle | Domain baru | [x] |
| D7 | Perangkat lapangan | Endpoint Laravel **kompatibel** dengan firmware lama; sistem lama tetap hidup sampai firmware baru terpasang | Perangkat tidak boleh putus | [x] |
| D8 | Cakupan fitur | Pertahankan semua modul bank sampah, rombak desain. Model klasifikasi sampah ditunda | Belum dirujuk kode web | [x] |
| D9 | Sumber DB | Pakai dump 18 Des 2025, atau Anda kirim export terbaru bila kolom `total_points` ada di DB live | Dump dan kode tidak sinkron | [x] |

### 0C. Data yang saya butuhkan dari Anda
- [x] Hosting `sokrosok`: shared atau VPS? Versi PHP? Ada SSH/Composer/cron? Batas koneksi MySQL? -> shared versi php8.3, ada ssh/composer/cron
- [x] Export DB terbaru (jika berbeda dari dump 18 Des 2025) kita nol kan saja. anggap saja ini baru. jadi sebetulnya kamu juga boleh modifikasi yang di arduino nya juga.
- [x] Berapa perangkat ESP32 **asli** yang aktif di lapangan (vs yang hanya simulasi)? -> anggap saja ini masih uji coba untuk 1 alat dulu
- [x] Fitur mana yang benar-benar dipakai / boleh dibuang? sementara yang akan kita pakai adalah. admin untuk add tempat sampah, add petugas dan add nasabah. kita fokus pada deteksi dari volume sampah yang di dalam tong sampahnya terlihat oleh admin petugas dan nasabah. dan sisanya kita buat menyusul. karena ini yang paling urgent. (bagian yang ini harus selesai dalam waktu singkat)
- [x] (Opsional, bisa belakangan) logo, warna, dan referensi desain Sokrosok (ini juga diawal saja. jadi kan isinya langsung halaman login. lalu dashboard adminnya) -> view nya cukup penting agar terlihat professional dan menarik.

### 0D. Kriteria selesai Tahap 0
- [x] D1–D9 terisi, dan data 0C yang wajib sudah ada → **Tahap 1 saya rinci**.

---

## B2. Temuan firmware lama (`iot/arduino/sampa_ok/sampa_ok.ino`) — dasar kompatibilitas
- Sensor HC-SR04: **TRIG=GPIO5, ECHO=GPIO18**, `pulseIn` manual, 10 sampel, ambil jarak **terkecil**, valid 1–399 cm.
- Persen = `(tinggi - jarak) / tinggi * 100`; jarak ≤5 cm = 100%, jarak ≥ tinggi = 0%.
- Alur: WiFiManager (AP bernama `BIN_CODE`, sandi `1sampai8`, timeout 60 dtk) → `GET /config` → baca sensor → `POST /status-update` → deep sleep. Default tinggi 150 cm, default tidur 5 menit.
- `BIN_CODE`, `API_KEY`, `API_BASE_URL` **tertanam di kode** (satu kali flash per alat). HTTPS dengan `setInsecure()`.
- Sensor gagal → dikirim 0% (bug: terbaca "Kosong").
- **Aturan firmware v2**: pin, library, alur, dan JSON request/response sama persis; hanya ditambah (a) URL basis mudah diganti (uji lokal via `http://IP-Mac`, produksi HTTPS), (b) sensor gagal → kirim `sensor_error:true`, bukan 0%, (c) field opsional `distance_cm`. Server tetap menerima payload lama.

## B3. Keputusan tambahan
- Dev: **Laravel Sail** (docker-compose dari Anda: PHP 8.3, MySQL 8, phpMyAdmin :8080). Frontend: Blade + Tailwind + Alpine (Vite).
- Logo & warna: saya tentukan. Warna utama hijau-teal, ikon tong sampah + indikator level. Dibuat sebagai SVG di Tahap 1.
- Jalur cepat: Tahap 1–4 dikerjakan berurutan tanpa jeda; tujuan pertama = **ESP32 mengirim data dan tampil di dashboard**.

---

## C0. TAHAP 1 — Fondasi Laravel (SELESAI)

### 1A. Anda jalankan (sekali, di Mac)
- [x] Buat project Laravel 13 `sokrosok`, pakai `docker-compose.yml` Anda (Laravel 13.40.0, PHP 8.3 terkunci via `composer config platform.php 8.3.0`)
- [x] `git init` + commit awal
- [x] Kirim hasil `artisan --version`

### 1B. Saya kerjakan (file yang berubah saja)
- [ ] `.env` + `config/app.php` (nama app, timezone `Asia/Jakarta`, locale `id`): **perintah di chat**, dijalankan Anda
- [x] `database/migrations/2026_11_06_000001_add_role_to_users_table.php` : kolom `role` (admin/petugas/nasabah), `phone`, `is_active`
- [x] `app/Models/User.php`, `app/Http/Middleware/EnsureRole.php`
- [x] `database/seeders/DatabaseSeeder.php` : 3 akun dev (admin, petugas, nasabah)
- [x] `routes/web.php`, `app/Http/Controllers/Auth/LoginController.php`
- [x] `resources/css/app.css` (design tokens), `resources/views/layouts/{guest,app}.blade.php`
- [x] `public/images/logo.svg`, `resources/views/auth/login.blade.php`
- [x] `resources/views/admin/dashboard.blade.php` (kerangka: sidebar + kartu ringkasan) + dashboard petugas & nasabah (placeholder)
- [x] Redirect setelah login per role: admin / petugas / nasabah

### 1C. Selesai bila
- [x] (dianggap lulus: zip terbaru memuat semua file tahap 1; koreksi bila ada yang gagal) Login admin, petugas, nasabah berjalan; halaman login & dashboard admin tampil rapi; role salah → ditolak.

---

## C1. TAHAP 2 — Bin, petugas, nasabah (SELESAI)

### 2A. Saya kerjakan (file yang berubah saja)
- [x] `database/migrations/2026_11_06_150001_create_bins_table.php` : kode, nama, lokasi, lat/lng, tinggi tong, interval tidur, `api_key` per-perangkat, volume terakhir
- [x] `database/migrations/2026_11_06_150002_create_bin_logs_table.php` : riwayat volume per bin
- [x] `app/Models/Bin.php`, `app/Models/BinLog.php` (status Penuh >80 / Sedang 40–80 / Kosong <40)
- [x] `app/Http/Controllers/Admin/BinController.php` : CRUD + buat ulang API key
- [x] `app/Http/Controllers/Admin/UserController.php` : CRUD petugas dan nasabah (satu controller)
- [x] `routes/web.php`, `resources/views/layouts/app.blade.php` (menu aktif + pesan sukses)
- [x] `resources/views/admin/bins/{index,form,show,_level}.blade.php`
- [x] `resources/views/admin/users/{index,form}.blade.php`

### 2B. Anda jalankan
- [x] Salin file ke project, lalu `./vendor/bin/sail artisan migrate`
- [x] Uji: tambah 1 tong, 1 petugas, 1 nasabah; ubah, nonaktifkan, hapus; login sebagai petugas baru

### 2C. Selesai bila
- [x] Admin bisa tambah/ubah/hapus tong, petugas, nasabah; halaman detail tong menampilkan `BIN_CODE` + `API_KEY` untuk firmware.

## C2. TAHAP 3 — API IoT (SELESAI)

### 3A. Saya kerjakan (file yang berubah saja)
- [x] `bootstrap/app.php` : daftarkan `routes/api.php`; semua `/api/*` selalu membalas JSON
- [x] `routes/api.php` : `GET /api/bins/config`, `POST /api/bins/status-update` (throttle 120/menit)
- [x] `app/Http/Middleware/AuthenticateBin.php` : cek `x-api-key` terhadap key milik `bin_code` (bin nonaktif ditolak, 401)
- [x] `app/Http/Controllers/Api/BinDeviceController.php` : config + simpan volume ke `bins` dan `bin_logs`, status Penuh/Sedang/Kosong dihitung server
- [x] `tests/Feature/BinApiTest.php` : 7 tes (config, auth, payload lama, batas status, sensor error, validasi)
- Tambahan kompatibel: `sensor_error:true` tidak menimpa volume terakhir; `distance_cm` opsional

### 3B. Anda jalankan
- [x] Salin file; `bootstrap/app.php` menggantikan yang lama (bila sudah Anda ubah sendiri, gabungkan 2 bagian: `api:` di `withRouting` dan `shouldRenderJsonWhen`)
- [x] `./vendor/bin/sail artisan test --filter=BinApiTest`
- [x] Uji curl dengan key dari halaman detail tong (perintah di chat) → 7 tes hijau; curl `config` dan `status-update` 85% → `status: full` ✔

### 3C. Selesai bila
- [x] Tes hijau, dan curl `status-update` membuat baris baru di riwayat detail tong.


## C3. TAHAP 4 — Monitoring volume (AKTIF)

### 4A. Saya kerjakan (file yang berubah saja)
- [x] `app/Http/Controllers/DashboardController.php` : satu dashboard untuk 3 role (ringkasan Penuh/Sedang/Kosong, daftar tong, data peta)
- [x] `app/Http/Controllers/MonitoringController.php` : detail tong untuk petugas & nasabah (tanpa API key)
- [x] `app/Models/Bin.php` : tambah `isOffline()` (tidak lapor > 2x interval)
- [x] `routes/web.php` : dashboard 3 role pakai controller; `petugas/bins/{bin}`, `nasabah/bins/{bin}`
- [x] `resources/views/layouts/app.blade.php` : `@stack` head/scripts; menu "Status Tong (Segera)" dihapus
- [x] `resources/views/monitoring/{dashboard,show,_chart}.blade.php` : kartu ringkasan, peta Leaflet (OSM), kartu tong, grafik SVG riwayat, auto-refresh 60 dtk
- [x] `resources/views/admin/bins/show.blade.php` : grafik volume ditambahkan
- [x] `tests/Feature/MonitoringTest.php` : 6 tes (3 role, kosong, tanpa kredensial, nonaktif 404, role salah 403, offline)

### 4D. Revisi: auto-update AJAX (tanpa reload halaman)
- [x] `app/Http/Controllers/DashboardController.php` : tambah `data()` (JSON: hitungan, marker peta, HTML kartu, jam update)
- [x] `app/Http/Controllers/BinLiveController.php` (baru) : JSON detail tong (level, laporan terakhir, grafik, tabel); admin 20 log + kolom jarak, petugas/nasabah 48 log; tong nonaktif 404 untuk non-admin
- [x] `routes/web.php` : `{role}.dashboard.data`, `{role}.bins.data` (admin, petugas, nasabah)
- [x] `resources/views/layouts/app.blade.php` : helper `livePoll()` (tiap 60 dtk, berhenti saat tab tersembunyi, langsung refresh saat tab aktif lagi, reload bila sesi habis) + menu **Nasabah disembunyikan sementara** (route tetap ada)
- [x] `resources/views/monitoring/dashboard.blade.php` : `<meta refresh>` dihapus; kartu, hitungan, peringatan offline, dan peta (marker diperbarui di tempat, zoom tidak direset) diperbarui via AJAX
- [x] `resources/views/monitoring/show.blade.php` + `resources/views/admin/bins/show.blade.php` : `<meta refresh>` dihapus; volume, laporan terakhir, grafik, tabel diperbarui via AJAX
- [x] Partial baru: `resources/views/monitoring/{_cards,_log_rows,_live_status}.blade.php`
- [x] Revisi UX: indikator auto-update (tombol "perbarui sekarang", titik status, "Memperbarui...", bar + hitung mundur ke update berikutnya, gagal → coba lagi 15 dtk)
- [x] `app/Http/Controllers/Admin/BinController.php` + `resources/views/admin/bins/{index,_rows}.blade.php` : daftar tong admin ikut auto-update (AJAX, halaman paginasi aktif tetap)
- [ ] Uji (Anda): buka dashboard + detail tong + daftar tong, kirim curl `status-update` baru → angka/grafik berubah tanpa reload; matikan server sebentar → muncul "Gagal memperbarui", pulih sendiri

### 4B. Anda jalankan
- [ ] Salin file ke project; hapus 3 view lama yang tak terpakai: `resources/views/{admin,petugas,nasabah}/dashboard.blade.php`
- [ ] `docker-compose exec laravel.test php artisan test --filter=MonitoringTest`
- [ ] Isi lat/lng pada tong (menu Ubah) agar muncul di peta; kirim curl status-update beberapa kali dengan nilai berbeda (mis. 20, 50, 85) agar grafik terisi
- [ ] Login admin, petugas, nasabah: cek dashboard + klik kartu tong

### 4C. Selesai bila
- [ ] Tes hijau; ketiga role melihat volume, peta, dan grafik; tong yang tidak melapor tampil "Offline".

> Catatan: peta memakai Leaflet + tile OpenStreetMap lewat CDN (butuh internet di browser).

## C. Kerangka tahap berikutnya

| Tahap | Isi | Keluaran |
|---|---|---|
| 2 | **DB ringkas**: `bins` (kode, nama, lokasi, lat/lng, tinggi tong), `bin_logs`, kunci per-perangkat; CRUD admin untuk bin, petugas, nasabah | Admin bisa tambah bin/petugas/nasabah |
| 3 (SELESAI) | **API IoT**: `GET /api/bins/config`, `POST /api/bins/status-update` kompatibel firmware lama, validasi, test otomatis | ESP32 bisa kirim data |
| 4 (AKTIF, lihat C3) | **Monitoring volume**: dashboard admin/petugas/nasabah, status Penuh/Sedang/Kosong, grafik riwayat, peta Leaflet | Volume terlihat semua role |
| 5 | Operasional lapangan (truk, rute, jemput, prediksi) | Alur petugas |
| 6 | Modul bank sampah (deposit, saldo, penarikan, poin) | Alur nasabah |
| 7 | Konten & dukungan (artikel, pengumuman, tiket, notifikasi) | Pelengkap |
| 8 | **Firmware ESP32 v2** (sesuai B2) | Firmware baru |
| 9 | Deploy & cutover (shared hosting, SSL, backup) | Go-live |

> Tahap 5–9 ditunda sampai jalur cepat (1–4) selesai.