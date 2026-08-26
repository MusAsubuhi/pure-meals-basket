<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Seed the three services offered by Pure Meals Basket.
     *
     * These mirror the service cards on the welcome page:
     * Catering, Juices & Beverages, and Cakes & Celebration Foods.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Catering',
                'slug' => 'catering',
                'description' => 'From school meals to corporate lunches and church fellowships, we deliver fresh, well-prepared food to institutions and gatherings of all sizes across Mombasa.',
                'image' => 'assets/images/service-catering.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Juices & Beverages',
                'slug' => 'juices-beverages',
                'description' => 'Refreshing natural juices and beverages made from tropical fruits, perfect for events, meetings, and celebrations that deserve something special on the table.',
                'image' => 'assets/images/service-juice.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Cakes & Celebration Foods',
                'slug' => 'cakes-celebration-foods',
                'description' => 'Beautiful cakes and celebration catering for weddings, birthdays, graduations, and every milestone worth marking with something delicious.',
                'image' => 'assets/images/service-cakes.webp',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            ServiceCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
