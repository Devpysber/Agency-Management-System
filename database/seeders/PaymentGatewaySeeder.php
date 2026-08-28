<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = ['Bank Transfer', 'Stripe', 'PayPal', 'Cash', 'Cheque'];

        foreach ($gateways as $name) {
            PaymentGateway::create([
                'name' => $name,
                'is_active' => true,
            ]);
        }
    }
}
