<?php

namespace Tests\Feature;

use App\Mail\OrderPlaced;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Services\OneCService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake(config('onec.orders_disk'));
    }

    public function test_new_customer_is_logged_in_and_receives_an_eight_digit_password(): void
    {
        $this->placeOrder('New@Example.test');
        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('new@example.test', $user->email);
        $this->assertFalse($user->has_fake_email);
        $this->assertNull($user->email_verified_at);
        $this->assertSame($user->id, Order::sole()->user_id);
        $this->assertSame('+380501234567', $user->phone);
        $this->assertFalse(Schema::hasColumn('orders', 'email'));
        Mail::assertQueued(OrderPlaced::class, function (OrderPlaced $mail) use ($user): bool {
            return $mail->hasTo($user->email) && preg_match('/^[0-9]{8}$/D', $mail->password) === 1
                && Hash::check($mail->password, $user->password);
        });
    }

    public function test_existing_guest_customer_is_not_logged_in_or_given_a_new_password(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.test']);
        $password = $user->password;
        $this->placeOrder('Existing@Example.test');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($user->id, Order::sole()->user_id);
        $this->assertSame($password, $user->fresh()->password);
        Mail::assertQueued(OrderPlaced::class, fn (OrderPlaced $mail): bool => $mail->password === null);
    }

    public function test_missing_email_creates_unique_fake_users_without_logging_in_or_emailing_them(): void
    {
        $this->placeOrder(null);
        $this->placeOrder(null);
        $this->assertGuest();
        $this->assertDatabaseCount('users', 2);
        $this->assertSame(2, User::distinct()->count('email'));
        foreach (User::all() as $user) {
            $this->assertTrue($user->has_fake_email);
            $this->assertMatchesRegularExpression('/^fake.+@gm\.com$/', $user->email);
        }
        Mail::assertNotQueued(OrderPlaced::class, fn (OrderPlaced $mail): bool => ! $mail->forAdmin);
    }

    public function test_authenticated_customer_keeps_identity_and_password(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $password = $user->password;
        $this->actingAs($user);
        $this->placeOrder($other->email);
        $this->assertAuthenticatedAs($user);
        $this->assertSame($user->id, Order::sole()->user_id);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame($password, $user->fresh()->password);
        Mail::assertQueued(OrderPlaced::class, fn (OrderPlaced $mail): bool => $mail->hasTo($user->email) && $mail->password === null);
    }

    public function test_failed_export_rolls_back_new_customer_and_does_not_log_in_or_send_mail(): void
    {
        $this->mock(OneCService::class)->shouldReceive('exportOrder')->once()->andThrow(new \RuntimeException('Export failed'));
        $cart = Cart::factory()->create();
        $this->withCookie(Cart::CLIENT_COOKIE, $cart->client_id)->post(route('order.store'), [
            'name' => 'Customer', 'phone' => '+380501234567', 'email' => 'new@example.test',
            'customer_type' => 'individual', 'payment_method' => 'cash', 'delivery_method' => 'pickup',
        ])->assertStatus(500);
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertModelExists($cart);
        Mail::assertNothingOutgoing();
    }

    public function test_migration_links_legacy_orders_and_moves_customer_details_with_rollback(): void
    {
        $migration = require database_path('migrations/2026_11_03_213040_move_order_customer_details_to_users.php');
        $migration->down();
        $existing = User::factory()->create(['email' => 'existing@example.test']);
        $password = $existing->password;
        $ids = [];
        foreach (['existing@example.test', 'new@example.test', null] as $email) {
            $ids[] = DB::table('orders')->insertGetId([
                'client_id' => (string) Str::uuid(),
                'name' => 'Legacy customer', 'phone' => '+380501234567', 'email' => $email,
                'customer_type' => 'company', 'company_name' => 'Legacy company',
                'legal_address' => 'Legacy address', 'tax_id' => '123', 'kpp' => '456',
                'payment_method' => 'cash', 'total' => '100.00', 'status' => 'new',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $migration->up();
        $this->assertSame($existing->id, Order::findOrFail($ids[0])->user_id);
        $this->assertSame($password, $existing->fresh()->password);
        $this->assertSame('new@example.test', Order::findOrFail($ids[1])->user->email);
        $this->assertTrue(Order::findOrFail($ids[2])->user->has_fake_email);
        foreach (Order::all() as $order) {
            $this->assertSame('Legacy company', $order->user->company_name);
            $this->assertSame('Legacy address', $order->user->legal_address);
            $this->assertSame('123', $order->user->tax_id);
            $this->assertSame('456', $order->user->kpp);
        }
        $migration->down();
        $this->assertDatabaseHas('orders', ['id' => $ids[0], 'email' => $existing->email, 'company_name' => 'Legacy company']);
        $this->assertDatabaseHas('orders', ['id' => $ids[2], 'email' => null]);
        $migration->up();
    }

    private function placeOrder(?string $email): void
    {
        $cart = Cart::factory()->create();
        $this->withCookie(Cart::CLIENT_COOKIE, $cart->client_id)->post(route('order.store'), [
            'name' => 'Customer', 'phone' => '+380501234567', 'email' => $email,
            'customer_type' => 'individual', 'payment_method' => 'cash', 'delivery_method' => 'pickup',
        ])->assertRedirect(route('order.checkout'))->assertSessionHasNoErrors();
    }
}
