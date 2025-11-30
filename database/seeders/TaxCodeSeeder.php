<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxCode;

class TaxCodeSeeder extends Seeder
{
    public function run()
    {
        $items = [
            [
                'code' => 'V0',
                'description' => 'Input VAT 16%',
                'tax_type' => 'INPUT_VAT',
                'rate' => 16.00,
                'country' => 'KE'
                'created_by' => ,
                'company_id' => ,
            ],
            [
                'code' => 'V1',
                'description' => 'Input VAT 0% – Zero Rated',
                'tax_type' => 'INPUT_VAT',
                'rate' => 0.00,
                'country' => 'KE'
            ],
            [
                'code' => 'A0',
                'description' => 'Output VAT 16%',
                'tax_type' => 'OUTPUT_VAT',
                'rate' => 16.00,
                'country' => 'KE'
            ],
            [
                'code' => 'W1',
                'description' => 'Withholding Tax 5% – Services',
                'tax_type' => 'WITHHOLDING',
                'rate' => 5.00,
                'country' => 'KE'
            ],
            [
                'code' => 'ZR',
                'description' => 'Zero Rated – Exempt',
                'tax_type' => 'EXEMPT',
                'rate' => 0.00,
                'country' => 'KE',
                'created_by' => ,
                'company_id' => ,
            ]
        ];

        foreach ($items as $item) {
            TaxCode::create($item);
        }
    }
}
