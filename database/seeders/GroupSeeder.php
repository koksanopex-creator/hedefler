<?php

namespace Database\Seeders;

use App\Models\GroupM;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        GroupM::updateOrCreate(['name' => 'Grup A'], ['description' => 'Örnek veri giriş grubu A', 'is_active' => true]);
        GroupM::updateOrCreate(['name' => 'Grup B'], ['description' => 'Örnek veri giriş grubu B', 'is_active' => true]);
    }
}
