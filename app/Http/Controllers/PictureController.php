<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePictureRequest;
use App\Models\Picture;
use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class PictureController extends Controller
{
    public function __construct(private ImageService $imageService) {}

    public function store(StorePictureRequest $request, Product $product): JsonResponse
    {
        $path = $this->imageService->store($request->file('image') ?? $request->validated('preview_url'), directory: 'products');
        try {
            $picture = DB::transaction(function () use ($product, $path): Picture {
                Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

                return $product->pictures()->create([
                    'path' => $path,
                    'position' => ($product->pictures()->max('position') ?? -1) + 1,
                ]);
            });
        } catch (Throwable $exception) {
            $this->imageService->delete($path);
            throw $exception;
        }

        return response()->json($this->pictureData($picture), 201);
    }

    public function rotate(Product $product, Picture $picture): JsonResponse
    {
        $newPath = null;
        try {
            [$picture, $oldPath] = DB::transaction(function () use ($product, $picture, &$newPath): array {
                Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                $picture = $product->pictures()->whereKey($picture->id)->lockForUpdate()->firstOrFail();
                $oldPath = $picture->path;
                $newPath = $this->imageService->rotateClockwise($oldPath, directory: 'products')['original'];
                $picture->update(['path' => $newPath]);

                return [$picture, $oldPath];
            });
        } catch (Throwable $exception) {
            $this->imageService->delete($newPath);
            throw $exception;
        }
        $this->imageService->delete($oldPath);

        return response()->json($this->pictureData($picture));
    }

    public function destroy(Product $product, Picture $picture): JsonResponse
    {
        $path = DB::transaction(function () use ($product, $picture): string {
            Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $picture = $product->pictures()->whereKey($picture->id)->lockForUpdate()->firstOrFail();
            $path = $picture->path;
            $picture->delete();

            return $path;
        });
        $this->imageService->delete($path);

        return response()->json(['message' => __('Фото видалено.')]);
    }

    /** @return array{id: int, path: string, position: int, url: string, thumbnail_url: string, destroy_url: string, rotate_url: string} */
    private function pictureData(Picture $picture): array
    {
        $parameters = ['product' => $picture->product_id, 'picture' => $picture];

        return [
            'id' => $picture->id,
            'path' => $picture->path,
            'position' => $picture->position,
            'url' => $picture->url(),
            'thumbnail_url' => $picture->url('mini'),
            'destroy_url' => route('products.pictures.destroy', $parameters),
            'rotate_url' => route('products.pictures.rotate', $parameters),
        ];
    }
}
