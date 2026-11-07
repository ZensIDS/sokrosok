<?php

namespace App\Http\Controllers;

use App\Models\Bin;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

/** Dashboard monitoring volume: dipakai admin, petugas, dan nasabah. */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('monitoring.dashboard', $this->payload());
    }

    /** Endpoint AJAX: data terbaru untuk dashboard (tanpa reload halaman). */
    public function data(): JsonResponse
    {
        $p = $this->payload();

        return response()->json([
            'counts' => $p['counts'],
            'markers' => $p['markers'],
            'cards' => view('monitoring._cards', ['bins' => $p['bins'], 'role' => $p['role']])->render(),
            'updated_at' => now()->format('H:i:s'),
        ]);
    }

    private function payload(): array
    {
        $role = auth()->user()->role;

        $bins = Bin::where('is_active', true)->orderBy('name')->get()
            ->sortByDesc(fn (Bin $b) => $b->fill_level_percent ?? -1)
            ->values();

        $counts = [
            'total' => $bins->count(),
            'full' => $bins->filter(fn (Bin $b) => $b->statusKey() === 'full')->count(),
            'medium' => $bins->filter(fn (Bin $b) => $b->statusKey() === 'medium')->count(),
            'empty' => $bins->filter(fn (Bin $b) => $b->statusKey() === 'empty')->count(),
            'offline' => $bins->filter(fn (Bin $b) => $b->isOffline() || $b->sensor_error)->count(),
        ];

        $colors = ['full' => '#dc2626', 'medium' => '#f59e0b', 'empty' => '#16a34a'];

        $markers = $bins
            ->filter(fn (Bin $b) => $b->latitude !== null && $b->longitude !== null)
            ->map(fn (Bin $b) => [
                'code' => $b->code,
                'name' => $b->name,
                'lat' => $b->latitude,
                'lng' => $b->longitude,
                'percent' => $b->fill_level_percent,
                'label' => $b->statusLabel(),
                'color' => $colors[$b->statusKey()] ?? '#94a3b8',
                'url' => $role === 'admin' ? route('admin.bins.show', $b) : route($role . '.bins.show', $b),
            ])->values();

        return compact('bins', 'counts', 'markers', 'role');
    }
}