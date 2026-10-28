@extends('layouts.dashboard')
@section('title', __('Личный кабинет'))
@section('content')
    <h1>{{ __('Здравствуйте, :name!', ['name' => auth()->user()->name]) }}</h1>
    <p>{{ __('Вы вошли в аккаунт.') }}</p>
    <div class="details">{{ auth()->user()->email }}</div>
@endsection
