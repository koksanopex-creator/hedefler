<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\MasterDataTypeM;
use App\Models\MasterDataItemM;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $unitType = MasterDataTypeM::firstOrCreate(
            ['name' => 'BİRİM'],
            [
                'slug' => 'birim_' . time(),
                'parent_id' => null,
            ]
        );

        $defaultUnits = [
            'Ton',
            'Adet',
            'KG',
            'TL',
            'USD',
            'EUR',
            'Metre',
            'Koli',
            'Palet',
            'Paket',
            'Litre',
        ];

        foreach ($defaultUnits as $unit) {
            MasterDataItemM::firstOrCreate(
                [
                    'type_id' => $unitType->id,
                    'name' => $unit,
                ],
                [
                    'parent_id' => null,
                    'user_id' => null,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $unitType = MasterDataTypeM::where('name', 'BİRİM')->first();
        if ($unitType) {
            $unitType->items()->delete();
            $unitType->delete();
        }
    }
};

