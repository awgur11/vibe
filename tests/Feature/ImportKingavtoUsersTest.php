<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportKingavtoUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preserves_hashes_skips_inactive_and_does_not_overwrite_existing_users(): void
    {
        $existing = User::factory()->create(['email' => 'existing@example.com']);
        $path = tempnam(sys_get_temp_dir(), 'kingavto-test-');
        $row = ['id' => 123, 'email' => 'IMPORTED@example.com', 'name' => 'Імпорт', 'password' => sha1('old-password'), 'admin' => 0, 'active' => 1];
        file_put_contents($path, implode("\n", array_map(fn (array $user): string => json_encode($user, JSON_THROW_ON_ERROR), [
            $row,
            array_replace($row, ['id' => 124, 'email' => $existing->email]),
            array_replace($row, ['id' => 125, 'email' => 'inactive@example.com', 'active' => 0]),
            array_replace($row, ['id' => 126, 'email' => 'invalid']),
        ])));
        try {
            $this->artisan('users:import-kingavto', ['file' => $path])->assertSuccessful();
            $this->assertDatabaseCount('users', 1);
            $this->artisan('users:import-kingavto', ['file' => $path, '--apply' => true])->assertSuccessful();
            $this->assertDatabaseCount('users', 2);
            $this->assertDatabaseHas('users', ['email' => 'imported@example.com', 'password' => $row['password'], 'is_admin' => false, 'email_verified_at' => null]);
            $this->assertSame($existing->password, $existing->fresh()->password);
            $this->artisan('users:import-kingavto', ['file' => $path, '--apply' => true])->assertSuccessful();
            $this->assertDatabaseCount('users', 2);
        } finally {
            unlink($path);
        }
    }
}
