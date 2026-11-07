<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Satu controller untuk CRUD petugas dan nasabah (dibedakan lewat nama route). */
class UserController extends Controller
{
    private function role(): string
    {
        return request()->routeIs('admin.petugas.*') ? User::ROLE_PETUGAS : User::ROLE_NASABAH;
    }

    private function meta(): array
    {
        $role = $this->role();

        return [
            'role' => $role,
            'label' => $role === User::ROLE_PETUGAS ? 'Petugas' : 'Nasabah',
            'prefix' => 'admin.' . $role,
        ];
    }

    private function owned(User $user): User
    {
        abort_unless($user->role === $this->role(), 404);

        return $user;
    }

    public function index()
    {
        return view('admin.users.index', $this->meta() + [
            'users' => User::where('role', $this->role())->orderBy('name')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('admin.users.form', $this->meta() + ['user' => new User(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['role'] = $this->role();
        User::create($data);

        return redirect()->route($this->meta()['prefix'] . '.index')->with('status', $this->meta()['label'] . ' berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', $this->meta() + ['user' => $this->owned($user)]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->owned($user);
        $data = $this->validated($request, $user);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route($this->meta()['prefix'] . '.index')->with('status', 'Data ' . strtolower($this->meta()['label']) . ' diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->owned($user)->delete();

        return redirect()->route($this->meta()['prefix'] . '.index')->with('status', $this->meta()['label'] . ' dihapus.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'max:30'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}