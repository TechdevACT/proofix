<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Admin ─────────────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@proofix.com'],
            [
                'name'     => 'Admin Proofix',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'station'  => null,
            ]
        );

        // ─── Operator Contoh ───────────────────────────────────────────
        $operators = [
            ['name' => 'Budi Santoso',  'email' => 'budi@proofix.com',  'station' => 'Meja 1'],
            ['name' => 'Sari Dewi',     'email' => 'sari@proofix.com',   'station' => 'Meja 2'],
            ['name' => 'Rizky Pratama', 'email' => 'rizky@proofix.com',  'station' => 'Meja 3'],
        ];

        foreach ($operators as $op) {
            User::firstOrCreate(
                ['email' => $op['email']],
                [
                    'name'     => $op['name'],
                    'password' => Hash::make('password'),
                    'role'     => 'operator',
                    'station'  => $op['station'],
                ]
            );
        }

        $this->command->info('✓ Seeder selesai!');
        $this->command->info('  Admin    → admin@proofix.com  / password');
        $this->command->info('  Operator → budi@proofix.com   / password');
    }
}
