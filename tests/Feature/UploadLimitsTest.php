<?php

namespace Tests\Feature;

use App\Services\UploadLimits;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UploadLimitsTest extends TestCase
{
    public function test_effective_limit_uses_the_smallest_finite_php_limit(): void
    {
        foreach ([
            ['2M', '8M', 2 * 1024 ** 2],
            ['64M', '8M', 8 * 1024 ** 2],
            ['512K', '1G', 512 * 1024],
            ['16M', '0', 16 * 1024 ** 2],
            ['0', '32M', 32 * 1024 ** 2],
            ['0', '0', null],
        ] as [$uploadLimit, $postLimit, $expected]) {
            $this->assertSame($expected, UploadLimits::fromIniValues($uploadLimit, $postLimit));
        }
    }

    public function test_runtime_validation_rule_matches_php_ini(): void
    {
        $expected = UploadLimits::fromIniValues(ini_get('upload_max_filesize'), ini_get('post_max_size'));
        $this->assertSame($expected, UploadLimits::bytes());
        $this->assertSame($expected === null ? [] : ['max:'.($expected / 1024)], UploadLimits::rules());
        $this->assertNotEmpty(UploadLimits::label());
    }

    public function test_oversized_image_message_is_translated_and_uses_file_size(): void
    {
        $limit = UploadLimits::bytes();
        if ($limit === null) {
            $this->markTestSkipped('PHP does not impose an upload size limit.');
        }
        $image = UploadedFile::fake()->createWithContent('image.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ))->size((int) ceil($limit / 1024) + 1);
        $validator = Validator::make(['preview' => $image], ['preview' => ['file', ...UploadLimits::rules()]]);

        $this->assertTrue($validator->fails());
        $message = $validator->errors()->first('preview');
        $this->assertStringContainsString('Розмір файлу', $message);
        $this->assertStringContainsString('КБ', $message);
        $this->assertStringNotContainsString('validation.', $message);
        $this->assertStringNotContainsString('символ', $message);
    }

    public function test_image_url_and_integer_validation_errors_are_translated(): void
    {
        $validator = Validator::make(
            ['preview_url' => 'invalid', 'position' => 'invalid', 'preview' => 'invalid'],
            ['preview_url' => ['url:http,https'], 'position' => ['integer'], 'preview' => ['file']],
        );
        $this->assertTrue($validator->fails());
        foreach ($validator->errors()->all() as $message) {
            $this->assertStringNotContainsString('validation.', $message);
            $this->assertStringStartsWith('Поле ', $message);
        }
    }
}
