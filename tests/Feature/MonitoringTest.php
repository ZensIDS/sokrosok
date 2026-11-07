<?php

namespace Tests\Feature;

use App\Models\Bin;
use App\Models\BinLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function bin(array $extra = []): Bin
    {
        $bin = Bin::create(['code' => 'BIN-001', 'name' => 'Tong Depan', 'latitude' => -8.2, 'longitude' => 111.1]);
        $bin->forceFill(array_merge(['fill_level_percent' => 85, 'last_reported_at' => now()], $extra))->save();

        foreach ([20, 50, 85] as $i => $p) {
            BinLog::create([
                'bin_id' => $bin->id, 'fill_level_percent' => $p,
                'status' => Bin::statusFor($p), 'created_at' => now()->subMinutes(30 - $i * 10),
            ]);
        }

        return $bin;
    }

    private function login(string $role): static
    {
        return $this->actingAs(User::factory()->create(['role' => $role, 'is_active' => true]));
    }

    public function test_dashboard_tampil_untuk_semua_role(): void
    {
        $this->bin();

        foreach (['admin', 'petugas', 'nasabah'] as $role) {
            $this->login($role)->get("/$role")->assertOk()->assertSee('BIN-001')->assertSee('Penuh');
        }
    }

    public function test_dashboard_kosong_tidak_error(): void
    {
        $this->login('petugas')->get('/petugas')->assertOk()->assertSee('Belum ada tong sampah');
    }

    public function test_petugas_dan_nasabah_bisa_lihat_detail_tanpa_kredensial(): void
    {
        $bin = $this->bin();

        foreach (['petugas', 'nasabah'] as $role) {
            $this->login($role)->get("/$role/bins/{$bin->id}")
                ->assertOk()->assertSee('Grafik volume')->assertDontSee($bin->api_key);
        }
    }

    public function test_tong_nonaktif_tidak_bisa_dibuka_petugas(): void
    {
        $bin = $this->bin(['is_active' => false]);

        $this->login('petugas')->get("/petugas/bins/{$bin->id}")->assertNotFound();
    }

    public function test_role_lain_tidak_boleh_masuk(): void
    {
        $bin = $this->bin();

        $this->login('nasabah')->get('/petugas')->assertForbidden();
        $this->login('petugas')->get("/nasabah/bins/{$bin->id}")->assertForbidden();
    }

    public function test_tong_offline_ditandai(): void
    {
        $this->bin(['last_reported_at' => now()->subDays(2)]);

        $this->login('admin')->get('/admin')->assertOk()->assertSee('Offline');
    }
}