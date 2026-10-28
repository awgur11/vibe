<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ReorderController extends Controller
{
    public function __invoke(ReorderRequest $request, string $resource): JsonResponse
    {
        $definition = config('reordering.resources.'.$resource);
        abort_unless(is_array($definition), 404);

        $model = new $definition['model'];
        $column = $definition['column'];
        $ids = array_map(intval(...), $request->validated('ids'));
        $model->getConnection()->transaction(function () use ($request, $model, $column, $definition, $ids): void {
            $query = $model->newQuery()->where($definition['where'] ?? []);
            foreach (array_keys($definition['scope'] ?? []) as $scope) {
                $query->where($scope, $request->validated($scope));
            }
            $records = (clone $query)->orderBy($column)->orderByDesc($model->getKeyName())
                ->lockForUpdate()->get([$model->getKeyName(), $column]);
            $originalIds = $records->modelKeys();
            if (count(array_intersect($ids, $originalIds)) !== count($ids)) {
                throw ValidationException::withMessages(['ids' => __('Список змінився. Оновіть сторінку та спробуйте ще раз.')]);
            }

            $selectedIds = array_fill_keys($ids, true);
            $nextIndex = 0;
            foreach ($originalIds as $position => $id) {
                $orderedId = isset($selectedIds[$id]) ? $ids[$nextIndex++] : $id;
                (clone $query)->whereKey($orderedId)->update([$column => $position]);
            }
        });

        return response()->json(['message' => __('Порядок збережено.')]);
    }
}
