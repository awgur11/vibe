<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'index'])->name('index');
Route::get('/{blogSlag}/{articleSlag}', [SiteController::class, 'article'])->name('article');
Route::get('/{slag}', [SiteController::class, 'page'])->name('page');
