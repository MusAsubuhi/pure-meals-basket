<?php

namespace Database\Seeders;

use App\Enums\CatalogStatus;
use App\Enums\PricingType;
use App\Models\Category;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Seeds the catalogue with the reference examples from the PMB catalogue spec.
 */
class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $cakes = Category::firstOrCreate(['name' => 'Cakes'], ['slug' => 'cakes', 'sort_order' => 1]);
        $juices = Category::firstOrCreate(['name' => 'Juices & Beverages'], ['slug' => 'juices-beverages', 'sort_order' => 2]);
        $celebration = Category::firstOrCreate(['name' => 'Celebration Foods'], ['slug' => 'celebration-foods', 'sort_order' => 3]);
        $catering = Category::firstOrCreate(['name' => 'Catering'], ['slug' => 'catering', 'sort_order' => 4]);

        // Chocolate Cake — KSh 1,000/kg, 1–20kg, option modifiers
        $cake = Product::firstOrCreate(
            ['name' => 'Chocolate Cake'],
            [
                'category_id' => $cakes->id,
                'slug' => 'chocolate-cake',
                'description' => 'Rich, moist chocolate cake priced per kilogram.',
                'pricing_type' => PricingType::PER_WEIGHT,
                'base_price' => 1000,
                'unit' => 'kg',
                'minimum_quantity' => 1,
                'maximum_quantity' => 20,
                'status' => CatalogStatus::ACTIVE,
            ]
        );

        if ($cake->options()->count() === 0) {
            $frosting = ProductOption::create([
                'product_id' => $cake->id, 'name' => 'Frosting', 'is_required' => true,
            ]);
            ProductOptionValue::create(['product_option_id' => $frosting->id, 'name' => 'Buttercream', 'price_modifier' => 0, 'sort_order' => 1]);
            ProductOptionValue::create(['product_option_id' => $frosting->id, 'name' => 'Fondant', 'price_modifier' => 800, 'sort_order' => 2]);

            $decoration = ProductOption::create([
                'product_id' => $cake->id, 'name' => 'Decoration',
            ]);
            ProductOptionValue::create(['product_option_id' => $decoration->id, 'name' => 'Standard', 'price_modifier' => 0, 'sort_order' => 1]);
            ProductOptionValue::create(['product_option_id' => $decoration->id, 'name' => 'Premium', 'price_modifier' => 1000, 'sort_order' => 2]);
        }
        // Passion Juice — KSh 300/litre, min 5L
        Product::firstOrCreate(
            ['name' => 'Passion Juice'],
            [
                'category_id' => $juices->id,
                'slug' => 'passion-juice',
                'pricing_type' => PricingType::PER_VOLUME,
                'base_price' => 300,
                'unit' => 'litre',
                'minimum_quantity' => 5,
                'status' => CatalogStatus::ACTIVE,
            ]
        );

        // Samosa — KSh 50 each
        Product::firstOrCreate(
            ['name' => 'Samosa'],
            [
                'category_id' => $celebration->id,
                'slug' => 'samosa',
                'pricing_type' => PricingType::PER_UNIT,
                'base_price' => 50,
                'unit' => 'piece',
                'minimum_quantity' => 5,
                'status' => CatalogStatus::ACTIVE,
            ]
        );

        // Celebration Box — fixed price
        Product::firstOrCreate(
            ['name' => 'Celebration Box'],
            [
                'category_id' => $celebration->id,
                'slug' => 'celebration-box',
                'pricing_type' => PricingType::FIXED,
                'base_price' => 1500,
                'status' => CatalogStatus::ACTIVE,
            ]
        );

        // Catering Package A — KSh 650/person, min 30 guests, requires PMB review
        Service::firstOrCreate(
            ['name' => 'Catering Package A'],
            [
                'category_id' => $catering->id,
                'slug' => 'catering-package-a',
                'pricing_type' => PricingType::PER_PERSON,
                'base_price' => 650,
                'unit' => 'person',
                'minimum_quantity' => 30,
                'requires_review' => true,
                'status' => CatalogStatus::ACTIVE,
            ]
        );

        // Tiered catering — the spec's tier example
        $tiered = Service::firstOrCreate(
            ['name' => 'Event Catering (Tiered)'],
            [
                'category_id' => $catering->id,
                'slug' => 'event-catering-tiered',
                'pricing_type' => PricingType::TIERED,
                'unit' => 'person',
                'status' => CatalogStatus::ACTIVE,
            ]
        );

        if ($tiered->tiers()->count() === 0) {
            foreach ([[1, 50, 700], [51, 100, 650], [101, 300, 600]] as [$min, $max, $price]) {
                PriceTier::create([
                    'priceable_type' => Service::class,
                    'priceable_id' => $tiered->id,
                    'min_quantity' => $min,
                    'max_quantity' => $max,
                    'unit_price' => $price,
                ]);
            }
        }
    }
}
