<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class InventoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        $accounts = [
            'qc@mail.com' => ['QC', 'qc'],
            'gudang@mail.com' => ['Warehouse Admin', 'warehouse'],
            'cs@mail.com' => ['Customer Service', 'cs'],
        ];

        foreach ($accounts as $email => [$name, $role]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'role' => $role,
                'password' => 'admin',
            ]);
        }
    }
}
