<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    private $isTestBilling;

    function __construct()
    {
        $this->isTestBilling = env('SHOPIFY_TEST_BILLING', true);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plans')->insert([
            [
                'type' => 'RECURRING',
                'name' => 'Basic',
                'price' => 3.99,
                'interval' => 'EVERY_30_DAYS',
                'capped_amount' => null,
                'terms' => '3-day free trial',
                'trial_days' => 3,
                'test' => $this->isTestBilling,
                'on_install' => true,
                'features' => json_encode([
                    'Unlimited back-in-stock alerts',
                        'Price-drop notifications',
                        'Email + SMS via your own provider',
                        'Customizable widget & templates',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'RECURRING',
                'name' => 'Basic',
                'price' => 35.00,
                'interval' => 'ANNUAL',
                'capped_amount' => null,
                'terms' => '3-day free trial',
                'trial_days' => 3,
                'test' => $this->isTestBilling,
                'on_install' => true,
                'features' => json_encode([
                   'Unlimited back-in-stock alerts',
                        'Price-drop notifications',
                        'Email + SMS via your own provider',
                        'Customizable widget & templates',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
