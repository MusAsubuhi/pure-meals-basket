<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles required by the registration flow and role middleware
        Role::firstOrCreate(['name' => 'customer']);
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_registration_creates_user_customer_and_customer_account_and_redirects_to_customer_area(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/customer');

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertFalse((bool) $user->is_superadmin);
        $this->assertTrue($user->hasRole('customer'));

        // Customer profile is created and linked to the user
        $customer = Customer::where('user_id', $user->id)->first();
        $this->assertNotNull($customer);
        $this->assertSame('active', $customer->status);

        // Customer account is created with a zero balance and a generated number
        $account = CustomerAccount::where('customer_id', $customer->id)->first();
        $this->assertNotNull($account);
        $this->assertSame('CUST-000001', $account->account_number);
        $this->assertEquals('0.00', (string) $account->balance);

        // The user was automatically logged in after registration
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_cannot_access_customer_area(): void
    {
        $response = $this->get('/customer');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_registered_customer_can_access_customer_area(): void
    {
        $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response = $this->get('/customer');

        $response->assertOk();
        $this->assertAuthenticated();
    }

    public function test_customer_is_redirected_to_customer_area_after_logging_back_in(): void
    {
        $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Log out
        $this->post('/logout');
        $this->assertGuest();

        // Log back in — e-mail was verified at registration time by the test
        // mail system, so the role check should send them to /customer
        $user = User::where('email', 'jane@example.com')->first();
        $user->forceFill(['email_verified_at' => now()])->save();

        $response = $this->post('/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/customer');
        $this->assertAuthenticatedAs($user);
    }

    public function test_non_customer_roles_are_not_sent_to_customer_area_by_guest_middleware(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/admin');
    }
}
