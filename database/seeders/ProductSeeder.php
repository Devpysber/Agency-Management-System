<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $software = ProductCategory::where('name', 'Software Licenses')->first();
        $hardware = ProductCategory::where('name', 'Hardware')->first();
        $merch = ProductCategory::where('name', 'Merchandise')->first();

        $products = [
            [
                'product_category_id' => $software?->id,
                'name' => 'CRM Pro License (Annual)',
                'sku' => 'SW-CRM-ANN',
                'description' => 'Annual subscription license for the agency CRM Pro platform.',
                'price' => 499.00,
                'stock_quantity' => 999,
            ],
            [
                'product_category_id' => $software?->id,
                'name' => 'Website Maintenance Plan',
                'sku' => 'SW-MAINT-01',
                'description' => 'Monthly software plan covering updates, backups, and security monitoring.',
                'price' => 79.00,
                'stock_quantity' => 500,
            ],
            [
                'product_category_id' => $hardware?->id,
                'name' => 'Wireless Presenter Remote',
                'sku' => 'HW-PRES-100',
                'description' => 'Bluetooth presentation clicker with laser pointer for client meetings.',
                'price' => 35.00,
                'stock_quantity' => 24,
            ],
            [
                'product_category_id' => $hardware?->id,
                'name' => 'HD Webcam',
                'sku' => 'HW-CAM-200',
                'description' => '1080p webcam with autofocus, used for client calls and video content.',
                'price' => 89.00,
                'stock_quantity' => 8,
            ],
            [
                'product_category_id' => $hardware?->id,
                'name' => 'Portable SSD 1TB',
                'sku' => 'HW-SSD-1TB',
                'description' => 'Portable external SSD used for client deliverable handoffs.',
                'price' => 129.00,
                'stock_quantity' => 15,
            ],
            [
                'product_category_id' => $merch?->id,
                'name' => 'Branded T-Shirt',
                'sku' => 'MR-TSHIRT-M',
                'description' => 'Agency-branded cotton t-shirt, unisex sizing.',
                'price' => 18.00,
                'stock_quantity' => 60,
            ],
            [
                'product_category_id' => $merch?->id,
                'name' => 'Branded Coffee Mug',
                'sku' => 'MR-MUG-01',
                'description' => 'Ceramic mug with agency logo, popular client gift item.',
                'price' => 12.00,
                'stock_quantity' => 6,
            ],
            [
                'product_category_id' => $merch?->id,
                'name' => 'Branded Notebook & Pen Set',
                'sku' => 'MR-NOTE-01',
                'description' => 'Hardcover notebook and pen set with agency branding, used as onboarding gifts.',
                'price' => 15.00,
                'stock_quantity' => 30,
            ],
        ];

        foreach ($products as $product) {
            Product::create(array_merge($product, ['status' => 'active']));
        }
    }
}
