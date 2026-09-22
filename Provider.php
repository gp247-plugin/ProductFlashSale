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

        // Offer this plugin's storefront block to the active template as one more
        // template SOURCE ROOT, instead of copying a file into app/GP247/Templates.
        //
        // WHY this works: a template's files are served through the GP247TemplatePath
        // namespace, one hint path per source, and BOTH sides read exactly those hints
        // — gp247_render_block() resolves GP247TemplatePath::<Template>.blocks.<name>,
        // and the admin block picker lists what gp247_template_files() finds across
        // TemplateSourceAudit::roots(), which is the hint list itself. Registering here
        // therefore makes the block appear in the picker and render, with no file copy:
        // it needs no writable template directory (shared hosting), leaves nothing
        // behind when the plugin is removed, and follows plugin updates by itself.
        //
        // Registered from a booted() callback, NOT with loadViewsFrom() here: hints are
        // appended in registration order, and a plugin's Provider.php runs before
        // FrontServiceProvider adds its own. Registering inline would put this plugin
        // AHEAD of app/GP247/Templates and let it shadow a file the site published to
        // edit — the one thing the override order exists to prevent. Deferring to
        // booted() puts it last, after every package root.
        $pluginTemplateRoot = __DIR__.'/template';
        $this->app->booted(function () use ($pluginTemplateRoot): void {
            \Illuminate\Support\Facades\View::addNamespace('GP247TemplatePath', $pluginTemplateRoot);
        });

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
