/*
  SOKROSOK firmware v2 (ESP32 + HC-SR04)
  Kontrak API sama dengan firmware lama:
    GET  /api/bins/config?bin_code=...      (header x-api-key; balasan memuat wifi_reset)
    POST /api/bins/status-update            (header x-api-key)
  Library: WiFiManager (tzapu). Lainnya bawaan board ESP32.
*/
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <WiFiManager.h>

// ====== ISI SESUAI HALAMAN DETAIL TONG DI ADMIN ======
const char* BIN_CODE     = "BIN-001";
const char* API_KEY      = "sk_43gbw3Vnyvcbewi2Zbn6QXYAy3N5ijivOJV1S8W4";
// Lokal : "http://192.168.x.x"  (IP Mac, tanpa slash di akhir; tambah :port bila bukan 80)
// Produksi: "https://sokrosok.decaasoftwares.com"
// const char* API_BASE_URL = "http://192.168.18.92";
const char* API_BASE_URL = "https://sokrosok.decaasoftwares.com";
// =====================================================

// Uji coba: isi >0 untuk memaksa tidur singkat (detik). Produksi: 0 = ikut server.
#define SLEEP_OVERRIDE_SEC 30

#define TRIG_PIN 5
#define ECHO_PIN 18
#define SAMPLES 10
#define DEFAULT_HEIGHT_CM 150
#define DEFAULT_SLEEP_SEC 300

// Portal WiFiManager: SSID = BIN_CODE. Dibuka otomatis bila WiFi gagal,
// atau atas perintah tombol "Reset WiFi" di aplikasi (terbuka lebih lama).
const char* PORTAL_PASS = "1sampai8";
#define PORTAL_AUTO_TIMEOUT_SEC  60
#define PORTAL_RESET_TIMEOUT_SEC 300

SET_LOOP_TASK_STACK_SIZE(16 * 1024);

int  binHeightCm = DEFAULT_HEIGHT_CM;
long sleepSec    = DEFAULT_SLEEP_SEC;
bool wifiResetRequested = false;  // diisi dari balasan /api/bins/config

void goSleep() {
  long s = SLEEP_OVERRIDE_SEC > 0 ? SLEEP_OVERRIDE_SEC : sleepSec;
  Serial.printf("Tidur %ld detik\n", s);
  Serial.flush();
  esp_sleep_enable_timer_wakeup((uint64_t)s * 1000000ULL);
  esp_deep_sleep_start();
}

// Jarak terkecil dari beberapa sampel valid (1-399 cm). -1 bila semua gagal.
float readDistanceCm() {
  delay(100);  // beri waktu sensor stabil setelah bangun/WiFi
  for (int attempt = 1; attempt <= 3; attempt++) {
    float best = -1;
    int ok = 0;
    for (int i = 0; i < SAMPLES; i++) {
      digitalWrite(TRIG_PIN, LOW);  delayMicroseconds(2);
      digitalWrite(TRIG_PIN, HIGH); delayMicroseconds(10);
      digitalWrite(TRIG_PIN, LOW);
      unsigned long dur = pulseIn(ECHO_PIN, HIGH, 30000UL);
      if (dur > 0) {
        float d = dur * 0.0343f / 2.0f;
        if (d >= 1 && d <= 399) { ok++; if (best < 0 || d < best) best = d; }
      }
      delay(60);
    }
    Serial.printf("Percobaan %d: %d/%d sampel valid\n", attempt, ok, SAMPLES);
    if (best > 0) return best;
    delay(300);
  }
  return -1;
}

// Kirim request; pilih klien HTTP/HTTPS otomatis dari awalan URL.
int sendRequest(const String& url, const char* method, const String& body, String& response) {
  bool https = url.startsWith("https://");
  WiFiClient* client;
  if (https) {
    WiFiClientSecure* sc = new WiFiClientSecure();
    sc->setInsecure();
    client = sc;
  } else {
    client = new WiFiClient();
  }
  int code = -1;
  {
    HTTPClient http;  // dibuat setelah client, dihancurkan sebelum client
    if (http.begin(*client, url)) {
      http.setTimeout(15000);
      http.addHeader("x-api-key", API_KEY);
      http.addHeader("Accept", "application/json");
      if (String(method) == "POST") {
        http.addHeader("Content-Type", "application/json");
        code = http.POST(body);
      } else {
        code = http.GET();
      }
      if (code > 0) response = http.getString();
      http.end();
    } else {
      Serial.println("http.begin gagal (cek API_BASE_URL)");
    }
  }
  delete client;
  return code;
}

// Ambil angka setelah "key": pada JSON sederhana.
long jsonNumber(const String& json, const char* key, long fallback) {
  int k = json.indexOf(String("\"") + key + "\"");
  if (k < 0) return fallback;
  int c = json.indexOf(':', k);
  if (c < 0) return fallback;
  long v = json.substring(c + 1).toInt();
  return v > 0 ? v : fallback;
}

// Baca nilai boolean setelah "key": pada JSON sederhana.
bool jsonBool(const String& json, const char* key, bool fallback) {
  int k = json.indexOf(String("\"") + key + "\"");
  if (k < 0) return fallback;
  int c = json.indexOf(':', k);
  if (c < 0) return fallback;
  String v = json.substring(c + 1);
  v.trim();
  return v.startsWith("true");
}

void fetchConfig() {
  String res;
  int code = sendRequest(String(API_BASE_URL) + "/api/bins/config?bin_code=" + BIN_CODE, "GET", "", res);
  Serial.printf("GET config -> %d %s\n", code, res.c_str());
  if (code == 200) {
    binHeightCm = jsonNumber(res, "capacity_liters", DEFAULT_HEIGHT_CM);
    sleepSec    = jsonNumber(res, "sleep_interval_sec", DEFAULT_SLEEP_SEC);
    wifiResetRequested = jsonBool(res, "wifi_reset", false);
  }
}

void postStatus(float distance) {
  String body;
  if (distance < 0) {
    body = String("{\"bin_code\":\"") + BIN_CODE + "\",\"sensor_error\":true}";
  } else {
    float pct = distance <= 5 ? 100 : (distance >= binHeightCm ? 0 : (binHeightCm - distance) / binHeightCm * 100.0f);
    body = String("{\"bin_code\":\"") + BIN_CODE + "\",\"fill_level_percent\":" + String((int)round(pct)) +
           ",\"distance_cm\":" + String(distance, 1) + "}";
  }
  String res;
  int code = sendRequest(String(API_BASE_URL) + "/api/bins/status-update", "POST", body, res);
  Serial.printf("POST %s -> %d %s\n", body.c_str(), code, res.c_str());
}

void setup() {
  Serial.begin(115200);
  delay(300);
  Serial.printf("\nSOKROSOK v2 | %s\n", BIN_CODE);
  Serial.println("Firmware: v2.1 (dengan reset WiFi dari aplikasi)");
  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);

  WiFiManager wm;
  wm.setConfigPortalTimeout(PORTAL_AUTO_TIMEOUT_SEC);
  if (!wm.autoConnect(BIN_CODE, PORTAL_PASS)) {
    Serial.println("WiFi gagal, tidur lalu coba lagi");
    goSleep();
  }
  Serial.print("IP: "); Serial.println(WiFi.localIP());

  Serial.println("Ambil config...");
  fetchConfig();
  Serial.printf("Reset WiFi diminta server: %s\n", wifiResetRequested ? "YA" : "tidak");

  // Perintah "Reset WiFi" dari aplikasi: buka portal agar WiFi bisa diganti lewat HP.
  if (wifiResetRequested) {
    Serial.printf("Perintah reset WiFi diterima. Portal '%s' dibuka %d detik...\n", BIN_CODE, PORTAL_RESET_TIMEOUT_SEC);
    wm.setConfigPortalTimeout(PORTAL_RESET_TIMEOUT_SEC);
    wm.startConfigPortal(BIN_CODE, PORTAL_PASS);
    if (WiFi.status() != WL_CONNECTED) {
      Serial.println("Tidak tersambung setelah portal, tidur lalu coba lagi");
      goSleep();
    }
    Serial.print("IP: "); Serial.println(WiFi.localIP());
  }

  Serial.println("Baca sensor...");
  float d = readDistanceCm();
  Serial.printf("Jarak: %.1f cm (tinggi %d cm)\n", d, binHeightCm);
  postStatus(d);
  goSleep();
}

void loop() {}
