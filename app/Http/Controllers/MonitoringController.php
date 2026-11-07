<?php

namespace App\Http\Controllers;

use App\Models\Bin;
use Illuminate\Contracts\View\View;

/** Detail volume satu tong untuk petugas dan nasabah (tanpa kredensial perangkat). */
class MonitoringController extends Controller
{
    public function show(Bin $bin): View
    {
        abort_unless($bin->is_active, 404);

        $logs = $bin->logs()->orderByDesc('created_at')->orderByDesc('id')->limit(48)->get();

        return view('monitoring.show', compact('bin', 'logs'));
    }
}