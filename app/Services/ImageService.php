<?php

namespace App\Services;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Image\Image as ImageValue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ImageService
{
    public const array THUMB_WIDTHS = [
        'fhd' => 1920,
        'one' => 1280,
        'two' => 1024,
        'three' => 768,
        'four' => 640,
        'five' => 480,
        'mini' => 320,
    ];

    public function store(UploadedFile|string|null $source, ?string $currentPath = null, bool $remove = false, string $directory = 'images', string $disk = 'public'): ?string
    {
        if ($source instanceof UploadedFile) {
            return $this->save($source, directory: $directory, disk: $disk)['original'];
        }

        if ($remove) {
            return null;
        }

        if (is_string($source) && $source !== '') {
            return $this->saveFromUrl($source, directory: $directory, disk: $disk)['original'];
        }

        return $currentPath;
    }

    public function delete(?string $path, string $disk = 'public'): void
    {
        $paths = self::paths($path);
        if ($paths !== []) {
            Storage::disk($disk)->delete($paths);
        }
    }

    /** @return list<string> */
    public static function paths(?string $path): array
    {
        if ($path === null || ! preg_match('~^[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*/img-[0-9]+\.(?:jpe?g|png|gif|webp)$~', $path)) {
            return [];
        }

        $paths = [$path];
        foreach (array_keys(self::THUMB_WIDTHS) as $name) {
            $paths[] = self::thumbnailPath($path, $name);
        }

        return $paths;
    }

    /**
     * @param  list<string>|null  $thumbs
     * @return array{original: string, thumbs: array<string, string>}
     */
    public function save(UploadedFile $image, ?array $thumbs = null, bool $webp = true, string $directory = 'images', string $disk = 'public'): array
    {
        Validator::make(['image' => $image], [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', ...UploadLimits::rules()],
        ])->validate();

        return $this->saveImage(Image::fromUpload($image), $thumbs, $webp, $directory, $disk);
    }

    /** @return array{original: string, thumbs: array<string, string>} */
    public function rotateClockwise(string $path, string $directory = 'images', string $disk = 'public'): array
    {
        return $this->saveImage(Image::fromStorage($path, $disk)->rotate(90), null, true, $directory, $disk);
    }

    /**
     * @param  list<string>|null  $thumbs
     * @return array{original: string, thumbs: array<string, string>}
     */
    private function saveImage(ImageValue $source, ?array $thumbs, bool $webp, string $directory, string $disk): array
    {
        $thumbs ??= array_keys(self::THUMB_WIDTHS);

        foreach ($thumbs as $name) {
            if (! is_string($name) || ! array_key_exists($name, self::THUMB_WIDTHS)) {
                throw new InvalidArgumentException('Unknown thumbnail name. Allowed names: '.implode(', ', array_keys(self::THUMB_WIDTHS)).'.');
            }
        }

        $processed = $source->orient();

        if ($webp) {
            $processed = $processed->toWebp();
        }

        $original = $webp ? $processed : $source;
        $extension = $webp ? 'webp' : $source->extension();
        $identifier = (string) random_int(100000000000000000, 999999999999999999);
        $directory = trim($directory, '/');
        $directory = ltrim($directory.'/'.now()->format('dmY').'/'.random_int(0, 99), '/');
        $originalPath = ltrim($directory.'/img-'.$identifier.'.'.$extension, '/');
        $paths = [];
        $thumbnails = [];

        try {
            $paths[] = $originalPath;
            if ($original->storeAs('', $originalPath, $disk) === false) {
                throw new RuntimeException('Unable to store the original image.');
            }

            foreach (array_unique($thumbs) as $name) {
                $thumbnailPath = self::thumbnailPath($originalPath, $name);
                $paths[] = $thumbnailPath;

                if ($processed->scale(width: self::THUMB_WIDTHS[$name])->storeAs('', $thumbnailPath, $disk) === false) {
                    throw new RuntimeException('Unable to store an image thumbnail.');
                }

                $thumbnails[$name] = $thumbnailPath;
            }
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($paths);

            throw $exception;
        }

        return ['original' => $originalPath, 'thumbs' => $thumbnails];
    }

    /**
     * @param  list<string>|null  $thumbs
     * @return array{original: string, thumbs: array<string, string>}
     */
    public function saveFromUrl(string $url, ?array $thumbs = null, bool $webp = true, string $directory = 'images', string $disk = 'public'): array
    {
        $maximumBytes = UploadLimits::bytes();
        $temporaryPath = tempnam(sys_get_temp_dir(), 'page-image-');
        if ($temporaryPath === false) {
            throw ValidationException::withMessages(['preview_url' => __('Не вдалося завантажити зображення.')]);
        }

        try {
            for ($redirect = 0; $redirect <= 3; $redirect++) {
                $parts = parse_url($url);
                if (! is_array($parts) || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)
                    || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
                    throw new RuntimeException('Invalid image URL.');
                }
                $host = trim($parts['host'], '[]');
                $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
                if ($addresses === []) {
                    throw new RuntimeException('Unable to resolve image host.');
                }
                foreach ($addresses as $address) {
                    if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        throw new RuntimeException('Image host must have a public address.');
                    }
                }
                $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);
                $address = str_contains($addresses[0], ':') ? '['.$addresses[0].']' : $addresses[0];
                $response = Http::connectTimeout(5)->timeout(20)->withOptions([
                    'allow_redirects' => false,
                    'proxy' => '',
                    'sink' => $temporaryPath,
                    'curl' => [CURLOPT_RESOLVE => [$host.':'.$port.':'.$address]],
                    'progress' => function (float $total, float $downloaded, float $uploadTotal, float $uploaded) use ($maximumBytes): void {
                        if ($maximumBytes !== null && ($total > $maximumBytes || $downloaded > $maximumBytes)) {
                            throw new RuntimeException('Image exceeds the maximum size.');
                        }
                    },
                ])->get($url);

                if ($response->redirect() && $response->header('Location') !== '') {
                    $url = (string) UriResolver::resolve(new Uri($url), new Uri($response->header('Location')));
                    continue;
                }
                if (! $response->successful()) {
                    throw new RuntimeException('Unable to download image.');
                }
                clearstatcache(true, $temporaryPath);
                $image = new UploadedFile($temporaryPath, 'remote-image', test: true);
                Validator::make(['preview_url' => $image], [
                    'preview_url' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', ...UploadLimits::rules()],
                ])->validate();

                return $this->save($image, $thumbs, $webp, $directory, $disk);
            }

            throw new RuntimeException('Too many image redirects.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages(['preview_url' => __('validation.remote_image', ['size' => UploadLimits::label()])]);
        } finally {
            unlink($temporaryPath);
        }
    }

    public static function thumbnailPath(string $original, string $name): string
    {
        if (! array_key_exists($name, self::THUMB_WIDTHS)) {
            throw new InvalidArgumentException('Unknown thumbnail name: '.$name);
        }

        return preg_replace('~(^|/)img-([0-9]+)(\.[a-zA-Z0-9]+)$~', '$1img-'.$name.'-$2$3', $original);
    }
}
