<?php

namespace Tests\Feature;

use App\Models\Bin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BinApiTest extends TestCase
{
    use RefreshDatabase;

    private function bin(array $attrs = []): Bin
    {
        return Bin::create($attrs + ['code' => 'BIN-001', 'name' => 'Tong Uji', 'height_cm' => 70, 'sleep_interval_sec' => 14400]);
    }

    public function test_config_mengembalikan_tinggi_dan_interval(): void
    {
        $bin = $this->bin();

        $this->withHeaders(['x-api-key' => $bin->api_key])
            ->getJson('/api/bins/config?bin_code=BIN-001')
            ->assertOk()
            ->assertExactJson(['config' => ['capacity_liters' => 70, 'sleep_interval_sec' => 14400]]);
    }

    public function test_key_salah_atau_hilang_ditolak(): void
    {
        $this->bin();

        $this->withHeaders(['x-api-key' => 'salah'])->getJson('/api/bins/config?bin_code=BIN-001')->assertStatus(401);
        $this->getJson('/api/bins/config?bin_code=BIN-001')->assertStatus(401);
        $this->getJson('/api/bins/config')->assertStatus(401);
    }

    public function test_bin_nonaktif_ditolak(): void
    {
        $bin = $this->bin(['is_active' => false]);

        $this->withHeaders(['x-api-key' => $bin->api_key])
            ->getJson('/api/bins/config?bin_code=BIN-001')->assertStatus(401);
    }

    public function test_payload_lama_menyimpan_volume_dan_log(): void
    {
        $bin = $this->bin();

        $this->withHeaders(['x-api-key' => $bin->api_key])
            ->postJson('/api/bins/status-update', ['bin_code' => 'BIN-001', 'fill_level_percent' => 85])
            ->assertStatus(201)
            ->assertJson(['status' => 'full', 'fill_level_percent' => 85]);

        $this->assertSame(85, $bin->fresh()->fill_level_percent);
        $this->assertDatabaseHas('bin_logs', ['bin_id' => $bin->id, 'fill_level_percent' => 85, 'status' => 'full']);
    }

    public function test_batas_status(): void
    {
        $this->assertSame('full', Bin::statusFor(81));
        $this->assertSame('medium', Bin::statusFor(80));
        $this->assertSame('medium', Bin::statusFor(40));
        $this->assertSame('empty', Bin::statusFor(39));
        $this->assertSame('unknown', Bin::statusFor(null));
    }

    public function test_sensor_error_tidak_menimpa_volume_terakhir(): void
    {
        $bin = $this->bin();
        $h = ['x-api-key' => $bin->api_key];

        $this->withHeaders($h)->postJson('/api/bins/status-update', ['bin_code' => 'BIN-001', 'fill_level_percent' => 50])->assertStatus(201);
        $this->withHeaders($h)->postJson('/api/bins/status-update', ['bin_code' => 'BIN-001', 'sensor_error' => true])->assertStatus(201);

        $fresh = $bin->fresh();
        $this->assertSame(50, $fresh->fill_level_percent);
        $this->assertTrue($fresh->sensor_error);
        $this->assertSame('error', $fresh->statusKey());
    }

    public function test_validasi_menolak_nilai_di_luar_rentang(): void
    {
        $bin = $this->bin();

        $this->withHeaders(['x-api-key' => $bin->api_key])
            ->postJson('/api/bins/status-update', ['bin_code' => 'BIN-001', 'fill_level_percent' => 150])
            ->assertStatus(422);
    }
}