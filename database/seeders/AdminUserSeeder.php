<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('role', UserRole::Admin)->exists()) {
            return;
        }

        $password = Str::password(16);

        User::create([
            'name' => 'Canice Okwudili',
            'email' => 'admin@canicetechnologies.com',
            'password' => $password,
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $this->command?->warn("Admin account created, email: admin@canicetechnologies.com / password: {$password}");
        $this->command?->warn('Save this now, it is not stored anywhere else.');
    }
}
