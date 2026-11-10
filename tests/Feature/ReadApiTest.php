<?php

namespace Tests\Feature;

use App\Models\Bin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Tahap 4 — tes API baca-saja /api/v1 */
class ReadApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'tes-token-baca-saja-0123456789-abcdefghijklmnopqrstuvwxyz';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sokrosok.read_tokens' => [self::TOKEN]]);
    }

    private function bin(array $attrs = [], array $state = []): Bin
    {
        static $n = 0;
        $n++;

        $bin = Bin::create($attrs + [
            'code' => 'TONG' . $n,
            'name' => 'Tong ' . $n,
            'location' => 'Lokasi ' . $n,
            'latitude' => -8.1 + $n / 1000,
            'longitude' => 111.1 + $n / 1000,
            'height_cm' => 60,
            'sleep_interval_sec' => 14400,
            'is_active' => true,
        ]);

        $bin->forceFill($state + ['fill_level_percent' => 50, 'last_reported_at' => now()])->save();

        return $bin->fresh();
    }

    private function auth(): static
    {
        return $this->withToken(self::TOKEN);
    }

    // --- Autentikasi ---

    public function test_tanpa_token_401(): void
    {
        foreach (['/api/v1/bins', '/api/v1/bins/ABC', '/api/v1/map'] as $url) {
            $this->getJson($url)->assertStatus(401);
        }
    }

    public function test_token_salah_401(): void
    {
        $this->withToken('salah-' . self::TOKEN)->getJson('/api/v1/bins')->assertStatus(401);
    }

    public function test_api_key_esp32_tidak_bisa_dipakai_sebagai_token_baca(): void
    {
        $bin = $this->bin();

        $this->withToken($bin->api_key)->getJson('/api/v1/bins')->assertStatus(401);
    }

    public function test_token_pendek_ditolak_walau_cocok(): void
    {
        config(['services.sokrosok.read_tokens' => ['pendek']]);

        $this->withToken('pendek')->getJson('/api/v1/bins')->assertStatus(401);
    }

    public function test_banyak_token_untuk_rotasi(): void
    {
        $baru = 'token-baru-0123456789-abcdefghijklmnopqrstuvwxyz-ABC';
        config(['services.sokrosok.read_tokens' => [self::TOKEN, $baru]]);

        $this->withToken(self::TOKEN)->getJson('/api/v1/bins')->assertOk();
        $this->withToken($baru)->getJson('/api/v1/bins')->assertOk();
    }

    // --- Struktur respons ---

    public function test_bins_struktur_json_dan_ringkasan(): void
    {
        $this->bin([], ['fill_level_percent' => 90]);                       // full
        $this->bin([], ['fill_level_percent' => 55]);                       // medium
        $this->bin([], ['fill_level_percent' => 10]);                       // empty
        $this->bin([], ['last_reported_at' => now()->subDays(3)]);          // offline
        $this->bin([], ['sensor_error' => true, 'fill_level_percent' => null]); // error

        $res = $this->auth()->getJson('/api/v1/bins')->assertOk()
            ->assertJsonStructure([
                'summary' => ['total', 'full', 'medium', 'empty', 'offline', 'error', 'unknown'],
                'data' => [['code', 'name', 'location', 'height_cm', 'fill_percent', 'status', 'status_label',
                    'lat', 'lng', 'last_reported_at', 'is_offline', 'sensor_error']],
                'updated_at',
            ]);

        $this->assertSame(
            ['total' => 5, 'full' => 1, 'medium' => 1, 'empty' => 1, 'offline' => 1, 'error' => 1, 'unknown' => 0],
            $res->json('summary')
        );
    }

    public function test_detail_dan_limit_riwayat(): void
    {
        $bin = $this->bin();

        DB::table('bin_logs')->insert(
            collect(range(1, 205))->map(fn ($i) => [
                'bin_id' => $bin->id,
                'fill_level_percent' => $i % 100,
                'distance_cm' => 12.5,
                'status' => 'medium',
                'sensor_error' => false,
                'created_at' => now()->subMinutes(300 - $i),
            ])->all()
        );

        $url = '/api/v1/bins/' . strtolower($bin->code);

        $res = $this->auth()->getJson($url)->assertOk()
            ->assertJsonStructure([
                'data' => ['code', 'status'],
                'logs' => [['fill_percent', 'status', 'sensor_error', 'reported_at']],
                'updated_at',
            ]);
        $this->assertCount(48, $res->json('logs'));

        $this->assertCount(200, $this->auth()->getJson($url . '?limit=999')->json('logs'));
        $this->assertCount(1, $this->auth()->getJson($url . '?limit=0')->json('logs'));
        $this->assertCount(10, $this->auth()->getJson($url . '?limit=10')->json('logs'));
    }

    public function test_map_struktur_dan_warna(): void
    {
        $this->bin([], ['fill_level_percent' => 95]);

        $this->auth()->getJson('/api/v1/map')->assertOk()
            ->assertJsonStructure([
                'data' => [['code', 'name', 'lat', 'lng', 'percent', 'status', 'label', 'color']],
                'updated_at',
            ])
            ->assertJsonPath('data.0.status', 'full')
            ->assertJsonPath('data.0.color', '#dc2626');
    }

    // --- Filter data ---

    public function test_tong_nonaktif_tidak_muncul(): void
    {
        $aktif = $this->bin();
        $nonaktif = $this->bin(['is_active' => false]);

        $codes = collect($this->auth()->getJson('/api/v1/bins')->json('data'))->pluck('code');
        $this->assertTrue($codes->contains($aktif->code));
        $this->assertFalse($codes->contains($nonaktif->code));

        $mapCodes = collect($this->auth()->getJson('/api/v1/map')->json('data'))->pluck('code');
        $this->assertFalse($mapCodes->contains($nonaktif->code));

        $this->auth()->getJson('/api/v1/bins/' . $nonaktif->code)->assertStatus(404);
        $this->auth()->getJson('/api/v1/bins/TIDAKADA')->assertStatus(404);
    }

    public function test_tong_tanpa_koordinat_tidak_muncul_di_map_tapi_di_daftar(): void
    {
        $tanpa = $this->bin(['latitude' => null, 'longitude' => null]);

        $this->assertTrue(collect($this->auth()->getJson('/api/v1/bins')->json('data'))->pluck('code')->contains($tanpa->code));
        $this->assertFalse(collect($this->auth()->getJson('/api/v1/map')->json('data'))->pluck('code')->contains($tanpa->code));
    }

    // --- Keamanan ---

    public function test_respons_tidak_membocorkan_kredensial_perangkat(): void
    {
        $bin = $this->bin([], ['wifi_reset_requested_at' => now()]);

        foreach (['/api/v1/bins', '/api/v1/bins/' . $bin->code, '/api/v1/map'] as $url) {
            $body = $this->auth()->getJson($url)->assertOk()->getContent();

            $this->assertStringNotContainsString($bin->api_key, $body);
            foreach (['api_key', 'wifi', 'sleep_interval', 'distance_cm', 'x-api-key'] as $bocor) {
                $this->assertStringNotContainsString($bocor, $body, "{$url} memuat '{$bocor}'");
            }
        }
    }
}