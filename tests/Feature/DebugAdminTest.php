<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Enums\PricingType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        $category = \App\Models\Category::create(['name' => 'Cakes']);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Chocolate Cake',
            'pricing_type' => PricingType::PER_WEIGHT,
            'base_price' => 1000, 'unit' => 'kg', 'status' => 'active',
        ]);

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin = \App\Models\User::factory()->create(['is_superadmin' => true]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/admin/catalogue/products');
        fwrite(STDERR, "STATUS: " . $response->getStatusCode() . "\n");
        fwrite(STDERR, "EXC: " . ($response->exception ? $response->exception->getMessage() : 'none') . "\n");
        fwrite(STDERR, substr($response->getContent(), 0, 800) . "\n");
        $this->assertTrue(true);
    }
}
