<?php
/**
 * Provides everything needed for the Extension
 */

    // Read extension config to build namespace and keys
    $config = file_get_contents(__DIR__.'/gp247.json');
    $config = json_decode($config, true);
    $extensionPath = $config['configGroup'].'/'.$config['configKey'];

    // Register translations and views using GP247 naming
    $this->loadTranslationsFrom(__DIR__.'/Lang', $extensionPath);

    if (gp247_extension_check_active($config['configGroup'], $config['configKey'])) {
        $this->loadViewsFrom(__DIR__.'/Views', $extensionPath);

        // Offer this plugin's storefront block to the LayoutBlock screen by
        // REGISTERING it, not by shipping a directory named after somebody else's
        // template. gp247_render_block() looks the template's own file up first and
        // falls back to this registry, so the block works on every template, needs
        // no writable directory, and leaves nothing behind when the plugin goes.
        //
        // Before modification 20260922T205500 this plugin added its own
        // GP247TemplatePath hint root, which meant hardcoding "GP247Front" (a custom
        // template never saw the block) and declaring itself a template source to
        // gp247:template-publish / template-prune / doctor.
        //
        // @aidlc-adr frontend-template-dev_plugin-layout-block-views
        $blockViews = config('gp247-config.front.layout_block_views', []);
        $blockViews['product_flash_sale'] = $extensionPath.'::blocks.product_flash_sale';
        config(['gp247-config.front.layout_block_views' => $blockViews]);

        if (file_exists(__DIR__.'/config.php')) {
            $this->mergeConfigFrom(__DIR__.'/config.php', $extensionPath);
        }

        // Include helper functions
        if (file_exists(__DIR__.'/function.php')) {
            require __DIR__.'/function.php';
        }

        // gp247/front is what owns both registries below. Guarded so the plugin still
        // installs and its admin screen still works on an install without the
        // storefront package.
        if (class_exists('GP247\Front\Controllers\RootFrontController')) {
            // Let an admin attach LayoutBlock blocks (banner, HTML, page…) to the
            // plugin's storefront listing page. The key MUST equal the 'layout_page'
            // value FrontController passes to view(), or a block chosen for this page
            // never renders. Stored as the i18n KEY, not a rendered string, so the
            // admin dropdown follows the viewer's locale.
            $configLayout = config('gp247-config.front.layout_page', []);
            $configLayout['product_flash_sale_index'] = $extensionPath.'::lang.layout_block_page.product_flash_sale_index';
            config(['gp247-config.front.layout_page' => $configLayout]);

            // Contribute the listing page to sitemap.xml; the SEO admin screen can
            // switch this plugin's whole contribution off by its key.
            $sitemapProviders = config('gp247-config.front.seo_sitemap_providers', []);
            $sitemapProviders[] = [
                'key' => $config['configKey'],
                'label' => $config['name'],
                'callback' => [\App\GP247\Plugins\ProductFlashSale\Seo::class, 'sitemapUrls'],
            ];
            config(['gp247-config.front.seo_sitemap_providers' => $sitemapProviders]);
        }
    }
