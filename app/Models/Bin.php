<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Bin extends Model
{
    protected $fillable = [
        'code', 'name', 'location', 'latitude', 'longitude',
        'height_cm', 'sleep_interval_sec', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sensor_error' => 'boolean',
            'last_reported_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Bin $bin) {
            $bin->api_key ??= self::newKey();
        });
    }

    public static function newKey(): string
    {
        return 'sk_' . Str::random(40);
    }

    /** > 80 Penuh, 40–80 Sedang, < 40 Kosong (kontrak lama). */
    public static function statusFor(?int $percent): string
    {
        return match (true) {
            $percent === null => 'unknown',
            $percent > 80 => 'full',
            $percent >= 40 => 'medium',
            default => 'empty',
        };
    }

    public function statusKey(): string
    {
        return $this->sensor_error ? 'error' : self::statusFor($this->fill_level_percent);
    }

    public function statusLabel(): string
    {
        return match ($this->statusKey()) {
            'full' => 'Penuh',
            'medium' => 'Sedang',
            'empty' => 'Kosong',
            'error' => 'Sensor error',
            default => 'Belum ada data',
        };
    }

    /** Tidak melapor lebih dari 2x interval tidur (+2 menit toleransi). */
    public function isOffline(): bool
    {
        if (! $this->last_reported_at) {
            return false;
        }

        return $this->last_reported_at->lt(now()->subSeconds(max((int) $this->sleep_interval_sec, 60) * 2 + 120));
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BinLog::class);
    }
}