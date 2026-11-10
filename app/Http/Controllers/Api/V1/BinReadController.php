<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BinResource;
use App\Models\Bin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** API baca-saja untuk app lain. Hanya tong aktif; tidak ada data kredensial perangkat. */
class BinReadController extends Controller
{
    /** Warna penanda peta per status. */
    private const COLORS = [
        'full' => '#dc2626',
        'medium' => '#f59e0b',
        'empty' => '#16a34a',
        'offline' => '#64748b',
        'error' => '#9333ea',
        'unknown' => '#94a3b8',
    ];

    /** GET /api/v1/bins */
    public function index(): JsonResponse
    {
        $items = collect(BinResource::collection(Bin::active()->orderBy('name')->get())->resolve());

        $count = fn (string $status) => $items->where('status', $status)->count();

        return response()->json([
            'summary' => [
                'total' => $items->count(),
                'full' => $count('full'),
                'medium' => $count('medium'),
                'empty' => $count('empty'),
                'offline' => $count('offline'),
                'error' => $count('error'),
                'unknown' => $count('unknown'),
            ],
            'data' => $items->values(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /** GET /api/v1/bins/{code}?limit=48 (maks. 200) */
    public function show(Request $request, string $code): JsonResponse
    {
        $bin = Bin::active()->where('code', strtoupper($code))->first();

        if (! $bin) {
            return response()->json(['message' => 'Tong tidak ditemukan'], 404);
        }

        $limit = min(max((int) $request->query('limit', 48), 1), 200);

        $logs = $bin->logs()
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($log) => [
                'fill_percent' => $log->fill_level_percent,
                'status' => $log->sensor_error ? 'error' : $log->status,
                'sensor_error' => (bool) $log->sensor_error,
                'reported_at' => $log->created_at?->toIso8601String(),
            ])->values();

        return response()->json([
            'data' => (new BinResource($bin))->resolve(),
            'logs' => $logs,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /** GET /api/v1/map : penanda siap pakai untuk Leaflet (hanya tong ber-koordinat). */
    public function map(): JsonResponse
    {
        $markers = collect(BinResource::collection(
            Bin::active()->whereNotNull('latitude')->whereNotNull('longitude')->orderBy('name')->get()
        )->resolve())->map(fn (array $b) => [
            'code' => $b['code'],
            'name' => $b['name'],
            'lat' => $b['lat'],
            'lng' => $b['lng'],
            'percent' => $b['fill_percent'],
            'status' => $b['status'],
            'label' => $b['status_label'],
            'color' => self::COLORS[$b['status']] ?? self::COLORS['unknown'],
        ])->values();

        return response()->json([
            'data' => $markers,
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}