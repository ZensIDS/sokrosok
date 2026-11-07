<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BinLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['bin_id', 'fill_level_percent', 'distance_cm', 'status', 'sensor_error', 'created_at'];

    protected function casts(): array
    {
        return [
            'sensor_error' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function bin(): BelongsTo
    {
        return $this->belongsTo(Bin::class);
    }
}