<?php

namespace App\GP247\Plugins\ProductFlashSale;

use App\GP247\Plugins\ProductFlashSale\Models\PluginModel;

/**
 * Sitemap URL provider (US-PLG-007, ADR seo_plugin-sitemap-extension).
 *
 * Registered by Provider.php into config('gp247-config.front.seo_sitemap_providers');
 * SeoController calls sitemapUrls() when it builds sitemap.xml and applies the
 * shared seo.sitemap_exclude_aliases filter to whatever is returned.
 *
 * @aidlc-unit plugin-product-flash-sale
 * @aidlc-story US-product-flash-sale-core3-port
 * @aidlc-adr seo_plugin-sitemap-extension
 */
class Seo
{
    /**
     * The flash-sale listing page, and only while something is actually on sale.
     *
     * WHY conditional: the products themselves are already in the sitemap as
     * product URLs, so this contributes exactly one page. Submitting it while no
     * sale is running would point crawlers at an empty listing.
     *
     * @param  mixed $storeId Store the sitemap is being built for.
     * @return array<int, array{alias:string, loc:string, changefreq?:string, priority?:string}>
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public static function sitemapUrls($storeId): array
    {
        if ((new PluginModel)->getProductFlash(1)->isEmpty()) {
            return [];
        }

        return [
            [
                'alias'      => 'product_flash_sale',
                'loc'        => gp247_route_front('product_flash_sale.index'),
                'changefreq' => 'daily',
                'priority'   => '0.7',
            ],
        ];
    }
}
