<?php
/**
 * Routes for the Product Flash Sale plugin.
 *
 * Admin screens are Livewire full-page components (core 3.x TailAdmin shell); the
 * v1 AdminController and its pjax/select2 view are gone, but the route NAMES are
 * unchanged because the AdminMenu row written by AppConfig::install() points at
 * `route_admin::admin_product_flash_sale.index`, and existing links/bookmarks use
 * the same names.
 *
 * Both groups sit inside the active check, so disabling the plugin takes its
 * screens and its storefront page off the site instead of leaving routes that
 * resolve into a disabled extension.
 *
 * @aidlc-unit plugin-product-flash-sale
 * @aidlc-story US-product-flash-sale-core3-port
 */

use App\GP247\Plugins\ProductFlashSale\Admin\Livewire\FlashSaleManager;
use Illuminate\Support\Facades\Route;

$config = file_get_contents(__DIR__.'/gp247.json');
$config = json_decode($config, true);

if (gp247_extension_check_active($config['configGroup'], $config['configKey'])) {

    $langUrl = GP247_SEO_LANG ? '{lang?}/' : '';

    // Storefront: the plugin's own flash-sale listing page.
    Route::group(
        [
            'middleware' => GP247_FRONT_MIDDLEWARE,
            'prefix'    => $langUrl.'plugin/product_flash_sale',
            'namespace' => 'App\\GP247\\Plugins\\ProductFlashSale\\Controllers',
        ],
        function () {
            Route::get('index', 'FrontController@index')
                ->name('product_flash_sale.index');
        }
    );

    // Admin: one component drives list + create + edit (two-panel).
    Route::group(
        [
            'prefix' => GP247_ADMIN_PREFIX.'/product_flash_sale',
            'middleware' => GP247_ADMIN_MIDDLEWARE,
        ],
        function () {
            Route::get('/', FlashSaleManager::class)
                ->name('admin_product_flash_sale.index');
            Route::get('/create', FlashSaleManager::class)
                ->name('admin_product_flash_sale.create');
            Route::get('/edit/{id}', FlashSaleManager::class)
                ->name('admin_product_flash_sale.edit');
        }
    );
}
