<?php
#App\GP247\Plugins\ProductFlashSale\Controllers\FrontController.php
namespace App\GP247\Plugins\ProductFlashSale\Controllers;

use App\GP247\Plugins\ProductFlashSale\AppConfig;
use GP247\Front\Controllers\RootFrontController;

/**
 * Storefront controller: the public flash-sale listing page.
 *
 * @aidlc-unit plugin-product-flash-sale
 * @aidlc-story US-product-flash-sale-core3-port
 */
class FrontController extends RootFrontController
{
    /** @var AppConfig Extension descriptor (paths, language keys). */
    public $plugin;

    /**
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function __construct()
    {
        parent::__construct();
        $this->plugin = new AppConfig;
    }

    /**
     * Flash-sale listing page.
     *
     * @param mixed ...$params Route parameters ({lang?} when GP247_SEO_LANG is on).
     * @return \Illuminate\Contracts\View\View
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function index(...$params)
    {
        if (GP247_SEO_LANG) {
            $lang = $params[0] ?? '';
            gp247_lang_switch($lang);
        }
        return $this->_flashSaleProcess();
    }

    /**
     * Render the products currently on flash sale.
     *
     * WHY the plugin's own view instead of the template's product-list screen: that
     * screen hands its grid to a Livewire component which builds its own catalogue
     * query and ignores the products passed in, so the v1 page listed the whole
     * catalogue instead of the sale. gp247_plugin_process_view still lets a template
     * override this page with its own copy.
     *
     * @return \Illuminate\Contracts\View\View
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-visibility-parity
     */
    private function _flashSaleProcess()
    {
        $perPage = (int) (gp247_config('item_list') ?: 12);
        $products = function_exists('gp247_product_flash')
            ? gp247_product_flash($perPage, $paginate = true)
            : collect();

        $view = gp247_plugin_process_view($this->plugin->appPath, $this->GP247TemplatePath, 'front_flash_sale');
        gp247_check_view($view);

        return view(
            $view,
            [
                'title' => gp247_language_render($this->plugin->appPath.'::lang.front.flash_title'),
                'products' => $products,
                // Page-type token registered in Provider.php, so an admin can attach
                // LayoutBlock blocks to this page. It must stay equal to the key
                // registered there or the blocks never render.
                'layout_page' => 'product_flash_sale_index',
            ]
        );
    }
}
