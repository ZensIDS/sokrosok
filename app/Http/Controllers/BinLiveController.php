<?php

namespace App\Http\Controllers;

use App\Models\Bin;
use Illuminate\Http\JsonResponse;

/** Endpoint AJAX detail tong (admin, petugas, nasabah). Tidak membocorkan kredensial perangkat. */
class BinLiveController extends Controller
{
    public function __invoke(Bin $bin): JsonResponse
    {
        $isAdmin = auth()->user()->role === 'admin';

        abort_unless($isAdmin || $bin->is_active, 404);

        $logs = $bin->logs()
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit($isAdmin ? 20 : 48)
            ->get();

        return response()->json([
            'level' => view('admin.bins._level', ['bin' => $bin])->render(),
            'last_reported' => $bin->last_reported_at?->diffForHumans() ?? 'Belum pernah',
            'chart' => view('monitoring._chart', ['logs' => $logs])->render(),
            'rows' => view('monitoring._log_rows', ['logs' => $logs->take(20), 'withDistance' => $isAdmin])->render(),
            'updated_at' => now()->format('H:i:s'),
        ]);
    }
}