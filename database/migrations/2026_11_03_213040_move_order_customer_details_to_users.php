<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('customer_type', 20)->default('individual');
            $table->string('phone', 40)->nullable();
            $table->string('company_name')->nullable();
            $table->text('legal_address')->nullable();
            $table->string('tax_id', 32)->nullable();
            $table->string('kpp', 32)->nullable();
            $table->boolean('has_fake_email')->default(false);
        });

        DB::table('orders')->orderBy('id')->chunkById(100, function ($orders): void {
            foreach ($orders as $order) {
                $email = filled($order->email) ? Str::lower($order->email) : null;
                $user = $order->user_id ? DB::table('users')->find($order->user_id) : null;
                $user ??= $email !== null ? DB::table('users')->whereRaw('LOWER(email) = ?', [$email])->first() : null;
                $customer = [
                    'customer_type' => $order->customer_type,
                    'name' => $order->name,
                    'phone' => $order->phone,
                    'company_name' => $order->company_name,
                    'legal_address' => $order->legal_address,
                    'tax_id' => $order->tax_id,
                    'kpp' => $order->kpp,
                    'updated_at' => now(),
                ];
                if ($user === null) {
                    $userId = DB::table('users')->insertGetId([
                        ...$customer,
                        'email' => $email ?? 'fake'.Str::uuid().'@gm.com',
                        'has_fake_email' => $email === null,
                        'password' => Hash::make(Str::random(64)),
                        'created_at' => $order->created_at,
                    ]);
                } else {
                    $userId = $user->id;
                    DB::table('users')->where('id', $userId)->update($customer);
                }
                DB::table('orders')->where('id', $order->id)->update(['user_id' => $userId]);
            }
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['customer_type', 'name', 'phone', 'email', 'company_name', 'legal_address', 'tax_id', 'kpp']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('customer_type', 20)->default('individual');
            $table->string('name')->default('');
            $table->string('phone', 40)->default('');
            $table->string('email', 254)->nullable();
            $table->string('company_name')->nullable();
            $table->text('legal_address')->nullable();
            $table->string('tax_id', 32)->nullable();
            $table->string('kpp', 32)->nullable();
        });
        DB::table('orders')->orderBy('id')->chunkById(100, function ($orders): void {
            foreach ($orders as $order) {
                $user = DB::table('users')->find($order->user_id);
                if ($user === null) {
                    continue;
                }
                DB::table('orders')->where('id', $order->id)->update([
                    'customer_type' => $user->customer_type,
                    'name' => $user->name,
                    'phone' => $user->phone ?? '',
                    'email' => $user->has_fake_email ? null : $user->email,
                    'company_name' => $user->company_name,
                    'legal_address' => $user->legal_address,
                    'tax_id' => $user->tax_id,
                    'kpp' => $user->kpp,
                ]);
            }
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['customer_type', 'phone', 'company_name', 'legal_address', 'tax_id', 'kpp', 'has_fake_email']);
        });
    }
};
