<x-mail::message>
# {{ __('Заявка на обратный звонок') }}

**{{ __('Имя') }}:** {{ $name }}

**{{ __('Телефон') }}:** {{ $phone }}

{{ config('app.name') }}
</x-mail::message>
