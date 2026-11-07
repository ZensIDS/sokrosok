<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // AKUN DEV. Ganti kata sandi sebelum deploy produksi.
        $akun = [
            ['Admin Sokrosok', 'admin@sokrosok.test', User::ROLE_ADMIN],
            ['Petugas Demo', 'petugas@sokrosok.test', User::ROLE_PETUGAS],
            ['Nasabah Demo', 'nasabah@sokrosok.test', User::ROLE_NASABAH],
        ];

        foreach ($akun as [$name, $email, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password', 'role' => $role, 'is_active' => true]
            );
        }
    }
}