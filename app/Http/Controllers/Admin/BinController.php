<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BinController extends Controller
{
    public function index(Request $request)
    {
        $bins = Bin::orderBy('code')->paginate(15);

        // Permintaan AJAX (auto-update): kirim potongan HTML saja, halaman tidak di-reload.
        if ($request->expectsJson()) {
            return response()->json([
                'rows' => view('admin.bins._rows', ['bins' => $bins])->render(),
                'pager' => (string) $bins->links(),
                'updated_at' => now()->format('H:i:s'),
            ]);
        }

        return view('admin.bins.index', ['bins' => $bins]);
    }

    public function create()
    {
        return view('admin.bins.form', ['bin' => new Bin(['height_cm' => 60, 'sleep_interval_sec' => 14400, 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $bin = Bin::create($this->validated($request));

        return redirect()->route('admin.bins.show', $bin)->with('status', 'Tempat sampah berhasil ditambahkan. Salin API key di bawah ke firmware.');
    }

    public function show(Bin $bin)
    {
        return view('admin.bins.show', [
            'bin' => $bin,
            'logs' => $bin->logs()->latest('created_at')->limit(20)->get(),
        ]);
    }

    public function edit(Bin $bin)
    {
        return view('admin.bins.form', ['bin' => $bin]);
    }

    public function update(Request $request, Bin $bin): RedirectResponse
    {
        $bin->update($this->validated($request, $bin));

        return redirect()->route('admin.bins.show', $bin)->with('status', 'Data tempat sampah diperbarui.');
    }

    public function destroy(Bin $bin): RedirectResponse
    {
        $bin->delete();

        return redirect()->route('admin.bins.index')->with('status', 'Tempat sampah dihapus.');
    }

    public function regenerateKey(Bin $bin): RedirectResponse
    {
        $bin->forceFill(['api_key' => Bin::newKey()])->save();

        return redirect()->route('admin.bins.show', $bin)->with('status', 'API key baru dibuat. Perangkat harus di-flash ulang dengan key ini.');
    }

    private function validated(Request $request, ?Bin $bin = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code' => ['required', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('bins', 'code')->ignore($bin)],
            'name' => ['required', 'max:100'],
            'location' => ['nullable', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'height_cm' => ['required', 'integer', 'between:20,400'],
            'sleep_interval_sec' => ['required', 'integer', 'between:60,86400'],
        ], [
            'code.regex' => 'Kode hanya boleh huruf besar, angka, minus, dan garis bawah (contoh: BIN-001).',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}