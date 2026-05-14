<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AutomationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryLogsController;
use App\Http\Controllers\Admin\OnboardingController;
use App\Http\Controllers\Admin\PlansController;
use App\Http\Controllers\Admin\ProvidersController;
use App\Http\Controllers\Admin\SubscriptionsController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Admin\TranslationsController;
use App\Http\Controllers\Admin\UserGuideController;
use App\Http\Controllers\Admin\WidgetSettingsController;
use App\Http\Controllers\Storefront\StorefrontApiController;
use App\Http\Controllers\Webhooks\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Plans selection — must be reachable WITHOUT an active plan so unbilled
| shops can pick one. Authenticated, but no `billable` middleware.
|--------------------------------------------------------------------------
*/
Route::middleware(['verify.shopify'])->group(function () {
    Route::get('/plans', [PlansController::class, 'index'])->name('bis.plans');
});

/*
|--------------------------------------------------------------------------
| Embedded Admin Routes (shop auth + active billing required)
|--------------------------------------------------------------------------
*/
Route::middleware(['verify.shopify', 'bis.subscribed'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('bis.dashboard');

    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('bis.onboarding');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('bis.onboarding.complete');

    Route::get('/widget',     [WidgetSettingsController::class, 'index'])->name('bis.widget');
    Route::post('/widget',    [WidgetSettingsController::class, 'update'])->name('bis.widget.update');
    Route::post('/widget/toggle', [WidgetSettingsController::class, 'toggle'])->name('bis.widget.toggle');

    Route::get('/templates',         [TemplateController::class, 'index'])->name('bis.templates');
    Route::post('/templates',        [TemplateController::class, 'store'])->name('bis.templates.store');
    Route::delete('/templates/{id}', [TemplateController::class, 'destroy'])->name('bis.templates.destroy');

    Route::get('/translations',  [TranslationsController::class, 'index'])->name('bis.translations');
    Route::post('/translations', [TranslationsController::class, 'update'])->name('bis.translations.update');

    Route::get('/subscriptions',               [SubscriptionsController::class, 'index'])->name('bis.subscriptions');
    Route::post('/subscriptions/{id}/cancel',  [SubscriptionsController::class, 'cancel'])->name('bis.subscriptions.cancel');
    Route::get('/subscriptions/export',        [SubscriptionsController::class, 'export'])->name('bis.subscriptions.export');

    Route::get('/analytics',    [AnalyticsController::class, 'index'])->name('bis.analytics');
    Route::get('/automation',   [AutomationController::class, 'index'])->name('bis.automation');
    Route::post('/automation',  [AutomationController::class, 'store'])->name('bis.automation.store');
    Route::delete('/automation/{id}', [AutomationController::class, 'destroy'])->name('bis.automation.destroy');

    Route::get('/delivery-logs', [DeliveryLogsController::class, 'index'])->name('bis.delivery_logs');

    Route::get('/providers',       [ProvidersController::class, 'index'])->name('bis.providers');
    Route::post('/providers',      [ProvidersController::class, 'update'])->name('bis.providers.update');
    Route::post('/providers/test', [ProvidersController::class, 'test'])->name('bis.providers.test');

    Route::get('/user-guide',      [UserGuideController::class, 'index'])->name('bis.user_guide');
});

/*
|--------------------------------------------------------------------------
| Public Storefront API
|--------------------------------------------------------------------------
*/
Route::prefix('api/storefront')->group(function () {
    Route::options('{any}', [StorefrontApiController::class, 'options'])->where('any', '.*');
    Route::get('/widget-config',          [StorefrontApiController::class, 'widgetConfig'])->name('bis.sf.widget_config');
    Route::get('/listing-inventory',      [StorefrontApiController::class, 'listingInventory'])->name('bis.sf.listing_inventory');
    Route::post('/subscribe',             [StorefrontApiController::class, 'subscribe'])->name('bis.sf.subscribe');
    Route::get('/unsubscribe/{token}',    [StorefrontApiController::class, 'unsubscribe'])->name('bis.unsubscribe');
});

Route::get('/bis/track-click', [StorefrontApiController::class, 'trackClick'])
    ->name('bis.track.click');

/*
|--------------------------------------------------------------------------
| Shopify Webhooks (HMAC verified by Kyon147 middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('webhook')->middleware('auth.webhook')->group(function () {
    Route::post('/products-update',      [WebhookController::class, 'productsUpdate']);
    Route::post('/inventory_levels-update', [WebhookController::class, 'inventoryLevelsUpdate']);
    Route::post('/orders-create',        [WebhookController::class, 'ordersCreate']);
    Route::post('/orders-paid',          [WebhookController::class, 'ordersCreate']);
    Route::post('/app-uninstalled',      [WebhookController::class, 'appUninstalled']);
    Route::post('/customers-data_request', [WebhookController::class, 'customersDataRequest']);
    Route::post('/customers-redact',     [WebhookController::class, 'customersRedact']);
    Route::post('/shop-redact',          [WebhookController::class, 'shopRedact']);
});
