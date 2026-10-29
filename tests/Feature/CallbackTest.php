<?php

namespace Tests\Feature;

use App\Mail\CallbackRequested;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_form_queues_notification_to_admin(): void
    {
        Mail::fake();
        Setting::factory()->create(['key' => 'admin_email', 'value' => json_encode('admin@example.test')]);
        $this->postJson(route('callback.store'), ['name' => 'Customer', 'phone' => '+380501234567'])->assertOk()->assertJsonStructure(['message']);
        Mail::assertQueued(CallbackRequested::class, fn (CallbackRequested $mail): bool => $mail->hasTo('admin@example.test') && $mail->name === 'Customer' && $mail->phone === '+380501234567');
        Mail::assertNothingSent();
        $mail = new CallbackRequested('Customer', '+380501234567');
        $mail->assertSeeInHtml('Customer');
        $mail->assertSeeInHtml('+380501234567');
    }

    public function test_invalid_form_and_missing_recipient_do_not_queue_mail(): void
    {
        Mail::fake();
        $this->postJson(route('callback.store'), ['name' => '', 'phone' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'phone']);
        $this->postJson(route('callback.store'), ['name' => 'Customer', 'phone' => '+380501234567'])->assertStatus(503);
        Mail::assertNothingQueued();
    }

    public function test_homepage_has_catalog_link_and_callback_modal(): void
    {
        $this->get(route('index'))->assertOk()->assertSee('href="'.route('catalogs').'" class="btn btn-red"', false)
            ->assertSee('data-callback-open', false)->assertSee('id="callbackDialog"', false);
    }
}
