{{--
    Flash-sale listing page (storefront).

    WHY the plugin ships its own screen instead of reusing the template's
    shop_product_list: in GP247Front that screen ignores the $products it is handed
    and renders @livewire('gp247-shop-front::product-filter'), which builds its own
    catalogue query (RISK-TECH-livewire-grid-drops-page-context). The v1 plugin
    passed its flash-sale products into it, so the page showed the whole catalogue.

    A template may override this page at
    GP247TemplatePath::<Template>.Plugins.ProductFlashSale.front_flash_sale
    (gp247_plugin_process_view resolves the template copy first) — the same
    override path the News plugin's pages use.

    Overrides block_main_content_center, not block_main, so the page keeps the
    layout's container, breadcrumb and admin-configurable sidebar blocks
    (gp247_render_block with layout_page = 'product_flash_sale_index').

    Variables: $products (paginator of ShopProduct with pf_sold / pf_stock).

    @aidlc-unit plugin-product-flash-sale
    @aidlc-story US-product-flash-sale-core3-port
    @aidlc-adr ADR-014
--}}
@extends($GP247TemplatePath.'.layout')

@section('block_main_content_center')
<div class="lg:col-span-12 w-full">
    @if ($products->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($products as $product)
                @include('Plugins/ProductFlashSale::partials.flash_card', ['product' => $product])
            @endforeach
        </div>

        @include($GP247TemplatePath.'.common.pagination', ['items' => $products])
    @else
        <p class="text-center text-ink-400 py-12">{{ gp247_language_render('front.no_item') }}</p>
    @endif
</div>
@endsection
