<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdvController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CallbackController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ComparisonController;
use App\Http\Controllers\EditorImageController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\FvalueController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\NovaPoshtaController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PictureController;
use App\Http\Controllers\ProductCommentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReorderController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteProductController;
use App\Http\Controllers\SlideController;
use App\Http\Controllers\TranslationController;
use App\Http\Middleware\EnsureCartClient;
use App\Services\SiteSetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', fn () => redirect()->route('index')->with('auth_screen', 'login'))->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/register', fn () => redirect()->route('index')->with('auth_screen', 'register'))->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    Route::view('/forgot-password', 'auth.form', ['screen' => 'forgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', function (Request $request, string $token) {
        return view('auth.form', ['screen' => 'reset', 'token' => $token, 'email' => $request->query('email')]);
    })->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');

});

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth', 'auth.session'])->group(function (): void {
    Route::prefix('adminzone')->middleware('verified')->group(function (): void {
        Route::view('/', 'dashboard')->name('dashboard');

        Route::patch('/reorder/{resource}', ReorderController::class)->where('resource', '[a-z0-9_-]+')->name('reorder.update');

        Route::post('/editor-images', EditorImageController::class)->name('editor-images.store');

        Route::post('/translations', TranslationController::class)->name('translations.store');

        Route::get('/settings/seo', [SettingController::class, 'seo'])->name('settings.seo');
        Route::get('/settings/design', [SettingController::class, 'design'])->name('settings.design');
        Route::get('/settings/contacts', [SettingController::class, 'contacts'])->name('settings.contacts');
        Route::post('/settings/fields/{key}', [SettingController::class, 'saveField'])->where('key', '[a-z0-9_-]+')->name('settings.fields.save');
        Route::resource('/settings', SettingController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::patch('/slides/{slide}/publication', [SlideController::class, 'updatePublication'])->name('slides.publication.update');
        Route::resource('/slides', SlideController::class)->except('show');

        Route::resource('/advs', AdvController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('/product-comments', ProductCommentController::class)->only(['index', 'update', 'destroy']);
        Route::resource('/orders', AdminOrderController::class)->only(['index', 'show', 'update']);

        Route::resource('/brands', BrandController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::patch('/catalogs/{catalog}/publication', [CatalogController::class, 'updatePublication'])->name('catalogs.publication.update');
        Route::get('/catalog-filters', [CatalogController::class, 'filters'])->name('catalogs.filters');
        Route::get('/catalog-filters/{filter}/values', [CatalogController::class, 'filterValues'])->name('catalogs.filter-values');
        Route::get('/catalogs/{catalog}/children', [CatalogController::class, 'children'])->name('catalogs.children');
        Route::resource('/catalogs', CatalogController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);

        Route::get('/filters/{filter}/values', [FilterController::class, 'values'])->name('filters.values');
        Route::resource('/filters', FilterController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('/filters.fvalues', FvalueController::class)->only(['store', 'update', 'destroy'])->scoped();

        Route::get('/catalogs/{catalog}/product-filters', [ProductController::class, 'catalogFilters'])->name('products.catalog-filters');
        Route::resource('/products', ProductController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);

        Route::patch('/products/{product}/pictures/{picture}/rotate', [PictureController::class, 'rotate'])->scopeBindings()->name('products.pictures.rotate');
        Route::resource('/products.pictures', PictureController::class)->only(['store', 'destroy'])->scoped();

        Route::resource('/languages', LanguageController::class)->except('show');

        Route::patch('/pages/{page}/publication', [PageController::class, 'updatePublication'])->name('pages.publication.update');
        Route::resource('/pages', PageController::class)->except('show');

        Route::resource('/articles', ArticleController::class)->parameters(['articles' => 'page'])->except('show');
    });
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Route::prefix('{locale}')->name('localized.')
//     ->middleware([RedirectBaseLocale::class, SetSiteLocale::class])
//     ->where(['locale' => '[a-zA-Z]{2,3}(?:_[a-zA-Z]{2,4}|-[A-Z]{2})?'])
//     ->group(__DIR__.'/site.php');

// Route::middleware(SetSiteLocale::class)->group(__DIR__.'/site.php');

(new SiteSetLocale)->handle();

Route::group(['prefix' => config('app.url_prefix'), 'middleware' => ['redirect.base.locale', EnsureCartClient::class]], function () {
    Route::prefix('cart')->name('cart.')->group(function (): void {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::get('/products/{product}', [CartController::class, 'product'])->name('product');
        Route::post('/', [CartController::class, 'store'])->name('store');
        Route::patch('/{cart}', [CartController::class, 'update'])->name('update');
        Route::delete('/{cart}', [CartController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('delivery/nova-poshta')->name('delivery.nova_poshta.')->middleware('throttle:60,1')->group(function (): void {
        Route::get('/cities', [NovaPoshtaController::class, 'cities'])->name('cities');
        Route::get('/warehouses', [NovaPoshtaController::class, 'warehouses'])->name('warehouses');
    });

    Route::middleware('auth')->group(function (): void {
        Route::get('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
        Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');
    });
    Route::get('/product/{product:slag}', [SiteProductController::class, 'show'])->name('product');
    Route::post('/product/{product:slag}/comments', [SiteProductController::class, 'comment'])->middleware('throttle:5,10')->name('product.comments.store');
    Route::get('/comparison/state', [ComparisonController::class, 'state'])->name('comparison.state');
    Route::get('/comparison/{catalog}', [ComparisonController::class, 'show'])->name('comparison.show');
    Route::post('/comparison/products/{product}', [ComparisonController::class, 'store'])->name('comparison.store');
    Route::delete('/comparison/products/{product}', [ComparisonController::class, 'destroy'])->name('comparison.destroy');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::get('/favorites/state', [FavoriteController::class, 'state'])->name('favorites.state');
    Route::post('/favorites/{product}', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{product}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
    Route::get('/order', [OrderController::class, 'checkout'])->name('order.checkout');
    Route::get('/order/csrf-token', [OrderController::class, 'csrfToken'])->name('order.csrf-token');
    Route::post('/order', [OrderController::class, 'store'])->name('order.store');

    Route::get('/', [SiteController::class, 'index'])->name('index');
    Route::post('/callback', [CallbackController::class, 'store'])->middleware('throttle:3,10')->name('callback.store');
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->middleware('throttle:120,1')->name('search.suggestions');
    Route::get('/catalogs', [SiteController::class, 'catalogs'])->name('catalogs');
    Route::get('/catalog/{catalogSlag}', [SiteController::class, 'catalog'])->name('catalog');
    Route::get('/{blogSlag}/{articleSlag}', [SiteController::class, 'article'])->name('article');
    Route::get('/{slag}', [SiteController::class, 'page'])->name('page');
});
