<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use JsonException;

#[Signature('users:import-kingavto {file : JSON Lines export} {--apply : Persist users; otherwise preview only}')]
#[Description('Import active Kingavto users while preserving legacy password hashes')]
class ImportKingavtoUsers extends Command
{
    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_readable($path)) {
            $this->error('Export file is not readable.');

            return self::FAILURE;
        }

        $rows = [];
        $skipped = [];
        $existing = 0;
        $emails = [];
        try {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $user = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                $email = mb_strtolower(trim($user['email'] ?? ''));
                if (! $user['active'] || ! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255
                    || ! preg_match('/\A[0-9a-f]{40}\z/', $user['password'] ?? '')) {
                    $skipped[] = $user['id'];

                    continue;
                }
                if (isset($emails[$email])) {
                    $this->error('Duplicate source email detected; import aborted.');

                    return self::FAILURE;
                }
                $emails[$email] = true;
                if (DB::table('users')->where('email', $email)->exists()) {
                    $existing++;

                    continue;
                }
                $rows[] = [
                    'name' => mb_substr(trim($user['name'] ?? '') ?: $email, 0, 255),
                    'email' => $email,
                    'password' => $user['password'],
                    'is_admin' => (bool) $user['admin'],
                    'email_verified_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        } catch (JsonException $exception) {
            $this->error('Invalid JSON export; nothing imported.');

            return self::FAILURE;
        }

        if ($this->option('apply')) {
            DB::transaction(function () use ($rows): void {
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('users')->insert($chunk);
                }
            });
        }
        $this->info(($this->option('apply') ? 'Imported: ' : 'Ready: ').count($rows));
        $this->line('Existing (unchanged): '.$existing);
        $this->line('Skipped: '.count($skipped).' (source IDs: '.implode(', ', $skipped).')');

        return self::SUCCESS;
    }
}
