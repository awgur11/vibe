<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NovaPoshtaService
{
    public const string ENDPOINT = 'https://api.novaposhta.ua/v2.0/json/';

    /** @return list<array{ref: string, label: string}> */
    public function cities(string $query): array
    {
        $query = trim($query);

        return Cache::remember($this->cacheKey('cities', mb_strtolower($query)), now()->addMinutes(10), function () use ($query): array {
            $data = $this->call('searchSettlements', ['CityName' => $query, 'Limit' => 10, 'Page' => 1]);
            $cities = [];
            $addresses = $data[0]['Addresses'] ?? [];
            if (! is_array($addresses)) {
                throw new RuntimeException(__('Не удалось загрузить данные Новой почты. Попробуйте ещё раз.'));
            }
            foreach ($addresses as $city) {
                if (! is_array($city)) {
                    continue;
                }
                $ref = $city['Ref'] ?? null;
                $label = $city['Present'] ?? null;
                if (is_string($ref) && $ref !== '' && is_string($label) && $label !== '') {
                    $cities[] = ['ref' => $ref, 'label' => $label];
                }
                if (count($cities) === 10) {
                    break;
                }
            }

            return $cities;
        });
    }

    /** @return list<array{ref: string, label: string, city: string}> */
    public function warehouses(string $cityRef): array
    {
        return Cache::remember($this->cacheKey('warehouses', $cityRef), now()->addHour(), function () use ($cityRef): array {
            $warehouses = [];
            for ($page = 1; $page <= 20; $page++) {
                $data = $this->call('getWarehouses', ['SettlementRef' => $cityRef, 'Language' => 'UA', 'Limit' => 500, 'Page' => $page]);
                foreach ($data as $warehouse) {
                    if (! is_array($warehouse)) {
                        continue;
                    }
                    if (isset($warehouse['SettlementRef']) && $warehouse['SettlementRef'] !== $cityRef) {
                        continue;
                    }
                    $ref = $warehouse['Ref'] ?? null;
                    $label = $warehouse['Description'] ?? null;
                    $city = $warehouse['SettlementDescription'] ?? $warehouse['CityDescription'] ?? null;
                    if (is_string($ref) && $ref !== '' && is_string($label) && $label !== '' && is_string($city) && $city !== '') {
                        $warehouses[$ref] = ['ref' => $ref, 'label' => $label, 'city' => $city];
                    }
                }
                if (count($data) < 500) {
                    return array_values($warehouses);
                }
            }

            throw new RuntimeException(__('Не удалось загрузить данные Новой почты. Попробуйте ещё раз.'));
        });
    }

    /** @return array{ref: string, label: string, city: string}|null */
    public function warehouse(string $cityRef, string $warehouseRef): ?array
    {
        foreach ($this->warehouses($cityRef) as $warehouse) {
            if ($warehouse['ref'] === $warehouseRef) {
                return $warehouse;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string|int>  $properties
     * @return array<int, array<string, mixed>>
     */
    private function call(string $method, array $properties): array
    {
        $key = $this->apiKey();
        try {
            $response = Http::asJson()->acceptJson()->connectTimeout(5)->timeout(15)->post(self::ENDPOINT, [
                'apiKey' => $key,
                'modelName' => 'AddressGeneral',
                'calledMethod' => $method,
                'methodProperties' => $properties,
            ]);
        } catch (ConnectionException) {
            throw new RuntimeException(__('Не удалось загрузить данные Новой почты. Попробуйте ещё раз.'));
        }
        $data = $response->json();
        if (! $response->successful() || ! is_array($data) || ($data['success'] ?? false) !== true || ! is_array($data['data'] ?? null)) {
            throw new RuntimeException(__('Не удалось загрузить данные Новой почты. Попробуйте ещё раз.'));
        }

        return $data['data'];
    }

    private function apiKey(): string
    {
        $key = config('services.nova_poshta.key');
        if (! is_string($key) || trim($key) === '') {
            throw new RuntimeException(__('Не удалось загрузить данные Новой почты. Попробуйте ещё раз.'));
        }

        return trim($key);
    }

    private function cacheKey(string $method, string $value): string
    {
        return 'nova-poshta.'.hash('sha256', $this->apiKey()).'.'.$method.'.'.hash('sha256', $value);
    }
}
