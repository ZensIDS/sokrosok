<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BinDeviceController extends Controller
{
    /** GET /api/bins/config?bin_code=BIN-001 */
    public function config(Request $request): JsonResponse
    {
        /** @var Bin $bin */
        $bin = $request->attributes->get('bin');

        // Perintah reset WiFi dikirim sekali saja: setelah diambil perangkat, ditandai delivered.
        $wifiReset = $bin->wifi_reset_requested_at && ! $bin->wifi_reset_delivered_at;

        if ($wifiReset) {
            $bin->forceFill(['wifi_reset_delivered_at' => now()])->save();
        }

        return response()->json([
            'config' => [
                // Firmware memakai capacity_liters sebagai tinggi tong (cm).
                'capacity_liters' => $bin->height_cm,
                'sleep_interval_sec' => $bin->sleep_interval_sec,
                'wifi_reset' => (bool) $wifiReset,
            ],
        ]);
    }

    /** POST /api/bins/status-update  {bin_code, fill_level_percent, [distance_cm], [sensor_error]} */
    public function statusUpdate(Request $request): JsonResponse
    {
        /** @var Bin $bin */
        $bin = $request->attributes->get('bin');

        $validator = Validator::make($request->all(), [
            'sensor_error' => ['sometimes', 'boolean'],
            'fill_level_percent' => ['required_without:sensor_error', 'nullable', 'numeric', 'between:0,100'],
            'distance_cm' => ['nullable', 'numeric', 'between:0,1000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data tidak valid', 'errors' => $validator->errors()], 422);
        }

        $sensorError = $request->boolean('sensor_error');
        $percent = $sensorError || $request->input('fill_level_percent') === null
            ? null
            : (int) round((float) $request->input('fill_level_percent'));

        if ($sensorError) {
            // Sensor rusak: jangan timpa volume terakhir dengan angka palsu.
            $bin->forceFill(['sensor_error' => true, 'last_reported_at' => now()])->save();
            $status = 'error';
        } else {
            $bin->forceFill(['fill_level_percent' => $percent, 'sensor_error' => false, 'last_reported_at' => now()])->save();
            $status = Bin::statusFor($percent);
        }

        $bin->logs()->create([
            'fill_level_percent' => $percent,
            'distance_cm' => $request->input('distance_cm'),
            'status' => $status,
            'sensor_error' => $sensorError,
        ]);

        return response()->json([
            'message' => 'OK',
            'fill_level_percent' => $percent,
            'status' => $status,
        ], 201);
    }
}