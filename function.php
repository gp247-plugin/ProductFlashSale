<?php
/**
 * Order-time and storefront hooks for the flash-sale quota.
 *
 * gp247/shop calls these by name when they exist (soft dependency, see
 * ShopOrder::addOrder / returnStockToInventory / takeStockBack), so the shop never
 * has to know this plugin exists. Each helper is also opt-out through
 * config('gp247_functions_except') like core's own helpers.
 *
 * @aidlc-unit plugin-product-flash-sale
 * @aidlc-story US-product-flash-sale-quota-integrity
 */

use App\GP247\Plugins\ProductFlashSale\Models\PluginModel;

if (!function_exists('gp247_product_flash_check_over') && !in_array('gp247_product_flash_check_over', config('gp247_functions_except', []))) {
    /**
     * Whether the flash-sale quota still allows selling this quantity.
     *
     * Advisory pre-check used before an order is written (and before a cancelled
     * order is re-opened). The binding decision is made by
     * gp247_product_flash_update_stock(), which is atomic.
     *
     * @param string $productId
     * @param int    $quantity
     * @return bool True when allowed (including when the product is not on flash sale).
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-quota-integrity
     */
    function gp247_product_flash_check_over($productId, $quantity) {
        return (new PluginModel)->canConsume($productId, $quantity);
    }
}

if (!function_exists('gp247_product_flash_update_stock') && !in_array('gp247_product_flash_update_stock', config('gp247_functions_except', []))) {
    /**
     * Consume flash-sale quota for a sold line.
     *
     * @param string $productId
     * @param int    $quantity
     * @return bool True when consumed (or nothing to consume); false when the quota
     *              ran out between the pre-check and here, so the caller can roll
     *              the order back instead of overselling the sale.
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-quota-integrity
     */
    function gp247_product_flash_update_stock($productId, $quantity) {
        return (new PluginModel)->consumeQuota($productId, $quantity);
    }
}

if (!function_exists('gp247_product_flash_release_stock') && !in_array('gp247_product_flash_release_stock', config('gp247_functions_except', []))) {
    /**
     * Return flash-sale quota when an order's goods go back to inventory.
     *
     * Without this, cancelling an order returned the product stock but kept the
     * flash-sale units spent, so a sale could read "sold out" while nothing had
     * actually been sold.
     *
     * @param string $productId
     * @param int    $quantity
     * @return void
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-quota-integrity
     */
    function gp247_product_flash_release_stock($productId, $quantity) {
        (new PluginModel)->releaseQuota($productId, $quantity);
    }
}

if (!function_exists('gp247_product_flash') && !in_array('gp247_product_flash', config('gp247_functions_except', []))) {
    /**
     * Products currently on flash sale, in admin sort order.
     *
     * @param int  $limit    Items to return (page size when paginating).
     * @param bool $paginate Return a paginator instead of a plain collection.
     * @return \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-visibility-parity
     */
    function gp247_product_flash($limit = 8, $paginate = false) {
        return (new PluginModel)->getProductFlash($limit, $paginate);
    }
}
