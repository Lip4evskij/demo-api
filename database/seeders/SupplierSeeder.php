<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::query()->firstOrCreate(
            ['slug' => 'supplier-a'],
            ['name' => 'Supplier A'],
        );

        Supplier::query()->firstOrCreate(
            ['slug' => 'supplier-b'],
            ['name' => 'Supplier B'],
        );
    }
}
