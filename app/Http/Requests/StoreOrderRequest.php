<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_type' => ['required', 'string', Rule::in(array_keys(Order::CUSTOMER_TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^\+?[0-9][0-9\s().-]{2,39}$/D'],
            'email' => ['nullable', 'string', 'email', 'max:254'],
            'company_name' => ['exclude_unless:customer_type,company', 'required_if:customer_type,company', 'string', 'max:255'],
            'legal_address' => ['exclude_unless:customer_type,company', 'nullable', 'string', 'max:1000'],
            'tax_id' => ['exclude_unless:customer_type,company', 'nullable', 'string', 'max:32'],
            'kpp' => ['exclude_unless:customer_type,company', 'nullable', 'string', 'max:32'],
            'delivery_method' => ['required', 'string', Rule::in(array_keys(Order::DELIVERY_METHODS))],
            'delivery_city' => ['exclude_unless:delivery_method,nova_poshta', 'nullable', 'string', 'max:255'],
            'delivery_city_ref' => ['exclude_unless:delivery_method,nova_poshta', 'required_if:delivery_method,nova_poshta', 'string', 'uuid'],
            'delivery_warehouse_ref' => ['exclude_unless:delivery_method,nova_poshta', 'required_if:delivery_method,nova_poshta', 'string', 'uuid'],
            'payment_method' => ['required', 'string', Rule::in(array_keys(Order::PAYMENT_METHODS))],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => $this->input('customer_type') === 'company' ? __('ПІБ') : __('Имя'),
            'phone' => __('Телефон'),
            'email' => __('Электронная почта'),
            'company_name' => __('Название компании'),
            'legal_address' => __('Юридический адрес'),
            'tax_id' => __('ИНН'),
            'kpp' => __('КПП'),
            'delivery_method' => __('Способ доставки'),
            'delivery_city_ref' => __('Город'),
            'delivery_warehouse_ref' => __('Отделение'),
            'payment_method' => __('Способ оплаты'),
            'customer_type' => __('Тип покупателя'),
        ];
    }
}
