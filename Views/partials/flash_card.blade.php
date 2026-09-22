{{--
    One flash-sale card: the shop's shared product card, plus what makes a flash
    sale different from a plain price promotion — the discount ribbon, how much of
    the quota is gone, and the countdown to the end of the window.

    WHY every class here already exists elsewhere in the template: the storefront
    CSS is compiled ahead of time and its content globs cover gp247/front,
    gp247/shop and app/GP247/Templates — NOT app/GP247/Plugins. A class used only
    by this plugin would have no rule behind it and the element would silently fall
    back to unstyled (rule gp247.md §3b). The countdown likewise reuses the Alpine
    factory gp247frontCountdown() already shipped in the template's app.js instead
    of loading a jQuery plugin from a CDN, as the v1 block did.

    Variables: $product (ShopProduct with pf_sold / pf_stock and promotionPrice).

    @aidlc-unit plugin-product-flash-sale
    @aidlc-story US-product-flash-sale-core3-port
    @aidlc-adr ADR-014
--}}
@php
    $appPath = 'Plugins/ProductFlashSale';
    $quota = (int) ($product->pf_stock ?? 0);
    $sold = (int) ($product->pf_sold ?? 0);
    $percent = $quota > 0 ? min(100, (int) round($sold / $quota * 100)) : 0;
    $endsAt = $product->promotionPrice->date_end ?? null;
@endphp
<div class="relative" data-testid="front-flash-sale-card">
    <span class="badge-brand absolute top-2 start-2 z-10">-{{ $product->getPercentDiscount() }}%</span>
    @livewire('gp247-shop-front::product-card', ['productId' => $product->id], key('flash-product-card-'.$product->id))

    <div class="mt-2">
        <div class="flex items-center justify-between text-xs text-ink-500">
            <span>{{ gp247_language_render($appPath.'::lang.front.flash_sold') }}: {{ $sold }}</span>
            <span>{{ gp247_language_render($appPath.'::lang.front.flash_stock') }}: {{ max(0, $quota - $sold) }}</span>
        </div>
        <div class="mt-1 h-2.5 w-full overflow-hidden rounded-full bg-ink-50">
            <div class="h-full rounded-full bg-brand-600" style="width: {{ $percent }}%"></div>
        </div>

        @if ($endsAt)
            <div x-data="gp247frontCountdown({{ \Illuminate\Support\Carbon::parse($endsAt)->getTimestamp() * 1000 }})"
                class="mt-2 flex items-center justify-center gap-1 text-sm font-mono bg-ink-900 text-white rounded-lg px-3 py-1"
                aria-label="{{ gp247_language_render($appPath.'::lang.front.flash_ends_in') }}">
                <span x-text="h"></span>:<span x-text="m"></span>:<span x-text="s"></span>
            </div>
        @endif
    </div>
</div>
