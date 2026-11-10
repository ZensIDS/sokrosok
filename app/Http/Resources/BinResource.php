<?php

namespace App\Http\Resources;

use App\Models\Bin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Format data tong untuk API baca-saja (app lain).
 * Sengaja memakai daftar kolom eksplisit: api_key, id internal, interval tidur,
 * dan status reset WiFi TIDAK PERNAH ikut keluar.
 *
 * @mixin Bin
 */
class BinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->apiStatus();

        return [
            'code' => $this->code,
            'name' => $this->name,
            'location' => $this->location,
            'height_cm' => (int) $this->height_cm,
            'fill_percent' => $this->fill_level_percent,
            'status' => $status,
            'status_label' => $status === 'offline' ? 'Offline' : $this->statusLabel(),
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'last_reported_at' => $this->last_reported_at?->toIso8601String(),
            'is_offline' => $this->isOffline(),
            'sensor_error' => (bool) $this->sensor_error,
        ];
    }

    /**
     * empty | medium | full | offline | error  (+ unknown bila belum pernah ada data).
     * Prioritas: offline > error > tingkat isi.
     */
    private function apiStatus(): string
    {
        if ($this->isOffline()) {
            return 'offline';
        }

        return $this->statusKey();
    }
}