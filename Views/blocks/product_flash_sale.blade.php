{{--
    Home "Flash Sale" strip — the markup itself.

    HOW IT REACHES A PAGE: the admin picks it in the Layout block screen as
    `product_flash_sale`. That entry comes from template/GP247Front/blocks/ in this
    plugin, which Provider.php registers as one more GP247TemplatePath source root —
    both the picker (gp247_template_files) and the renderer (gp247_render_block)
    read those hints, so nothing is ever copied into app/GP247/Templates. A theme
    can also mount the strip directly with one line:

        @include('Plugins/ProductFlashSale::blocks.product_flash_sale')

    This is the time-boxed sibling of gp247/shop's own promotion block
    (`shop_product_promotion`): that one lists every active price promotion and
    deliberately shows no timer, because promotions routinely run for weeks. The
    strip below is driven by this plugin's quota ledger, so a countdown and a
    "sold / left" bar are honest here.

    @aidlc-unit plugin-product-flash-sale
    @aidlc-story US-product-flash-sale-core3-port
    @aidlc-adr ADR-014
--}}
@php
    $appPath = 'Plugins/ProductFlashSale';
    // gp247/shop is a hard requirement of this plugin, but the helper is only
    // defined while the plugin is active — guard so a template that keeps the
    // include after disabling the plugin renders nothing instead of failing.
    $flashSaleProducts = function_exists('gp247_product_flash') ? gp247_product_flash(10) : collect();
@endphp
@if (count($flashSaleProducts))
<section class="container-x py-6" data-testid="front-flash-sale-block">
    <div class="flex items-center justify-between mb-4">
        <h2 class="section-title">{{ gp247_language_render($appPath.'::lang.front.flash_title') }}</h2>
        <a href="{{ gp247_route_front('product_flash_sale.index') }}" class="nav-link">{{ gp247_language_quickly('front.view_all', 'View all') }}</a>
    </div>
    <div class="flex gap-4 overflow-x-auto no-scrollbar snap-x pb-2">
        @foreach ($flashSaleProducts as $product)
            <div class="snap-start shrink-0 w-[46%] sm:w-[31%] lg:w-[19%]">
                @include('Plugins/ProductFlashSale::partials.flash_card', ['product' => $product])
            </div>
        @endforeach
    </div>
</section>
@endif
