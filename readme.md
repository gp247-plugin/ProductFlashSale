> 🌐 **Language:** [🇻🇳 Tiếng Việt](./readme_vi.md) · 🇬🇧 English (current)

# Product Flash Sale plugin for S-Cart (GP247)

## Introduction
This document explains how to install and use the **Product Flash Sale** plugin — a tool for time-boxed selling with a limited number of units ("only 20 units, for 3 hours"). It is written for shop owners and GP247 site administrators; no programming knowledge is required. By the end you will be able to schedule a flash sale, put it on your homepage, and understand why the system sometimes refuses an action.

## How is this different from the built-in "Promotion"?

A GP247 shop already has promotions: a price that applies over a range of dates, routinely running for weeks. That is why the shop's default "Promotion products" block has **no** countdown — a timer over a two-month campaign is a fake scarcity signal.

A flash sale is a promise you can count: **only N units, in this window**. The plugin adds exactly two things on top of the existing promotion: the **quantity the sale may sell** and its **display order**. That is what makes the countdown and the "sold / left" bar honest here.

## Requirements

- GP247 core version **3.0** or newer.
- The `gp247/shop` package must be installed. The plugin does not run without it.

## Installation

1. Place the plugin source folder at the right location on your site:

   ```
   app/GP247/Plugins/ProductFlashSale
   ```

2. Sign in to the admin panel and open **Extensions / Plugins** (Plugin manager).

3. Find the **Product Flash Sale** row, click **Install**, then click **Enable**.

   If it works, the system shows an "installed successfully" message. If it reports *not compatible*, your site is almost certainly on a core older than 3.0 — update the core first.

4. The system does three things for you: it creates the `shop_product_flash` data table (if missing), adds a menu entry under the **Catalog** group, and **places the strip at the bottom of the home page** of every store that does not have this block yet (one active row in the **Layout block** screen). The `product_flash_sale` block also shows up in the Layout block picker right away — the plugin **registers** the block with the system rather than copying a file into your template folder, so it works with every template and disappears cleanly when you remove the plugin.

   If you delete that placement or move it elsewhere, **a later plugin update will not put it back** — where it appears is your decision; the plugin only suggests a spot once, at install time.

5. If your site runs with cache enabled, clear it once:

   ```
   php artisan optimize:clear
   ```

6. Open the new **Product Flash Sale** menu. When you see the two-panel screen (form on the left, an empty list on the right), the plugin is ready.

> **Re-installing or updating the plugin does not lose data.** The table is only created when absent and the menu entry only added when missing. Only **Uninstall** drops the table.

## Scheduling a flash sale

1. Open the **Product Flash Sale** menu.

2. In the left column, in the **Select product** box, type a few letters of the product name and pick it from the suggestions.

3. Enter the **Quantity** — the maximum number of units this sale may sell, for example `20`.

4. Enter the sale **Price**. Prices are entered in the site's **base currency** (the small hint under the field tells you which unit you are typing in).

5. Pick the **Start** and **End** moments. The picker includes hours and minutes — this is what sets a flash sale apart from a plain promotion: you can run it from 20:00 to 23:00 on the same day.

6. Enter the **Sort** value (lower numbers are shown first on the storefront) and turn the **Status** switch on.

7. Click **Save**. If it works, the sale appears in the list on the right with a `0 / 20` progress bar.

8. Open the flash-sale page on your site to check (see the address below). If the window is open, you will see the product with its countdown.

To edit: click the pencil icon on that row, change it and **Save**. To stop a sale early: turn the **Status** switch off, or delete the row with the bin icon (deleting also removes the promotional price from the product).

## The flash-sale page on your site

The listing page address:

```
https://<your-domain>/plugin/product_flash_sale/index
```

If your site uses language-prefixed URLs (`GP247_SEO_LANG`), the address carries the language code, for example `/en/plugin/product_flash_sale/index`.

This page is also a **page type** in the admin **Layout block** screen, so you can attach banners, HTML blocks or static pages to it like any other page. While a sale is running, the page is contributed to `sitemap.xml` so search engines know about it.

## Putting the flash-sale strip on your homepage

The easiest way is the admin **Layout block** screen — no file editing:

1. Go to **Layout block** → **Add block layout**.

2. Set **Type** to `View`.

3. In **Position**, choose where it should appear (for example *Position Bottom*); in **Page**, choose `front_home — Home page`.

4. In **Text**, choose the block named **`product_flash_sale`**.

   This is the plugin's block, available as soon as the plugin is enabled. (The `shop_product_promotion` block next to it is the shop's built-in promotion block — a different one, with no countdown.)

5. Tick **Active**, click **Submit**, then reload the homepage. If a sale is running, you will see a horizontally scrolling strip of cards, each with the discount percentage, sold / left, and a countdown. When no sale is running the strip hides itself — your homepage is not left with an empty gap.

> **Cannot see `product_flash_sale` in the Text box?** Check that the plugin is **Enabled** (the block only appears while it is), then run `php artisan optimize:clear` and reload the Layout block screen.

**If your site runs a custom template** (a name other than `GP247Front`): **nothing extra to do** — since 2.0.1 the plugin registers the block with the system itself, independently of the template name, so it is offered on every template.

**Want to change how the strip looks?** Do not edit the file inside the plugin folder — a plugin update overwrites it. Three ways that survive an update are described in [Customising how the strip looks](#customising-how-the-strip-looks) below.

**Manual include** (when you want the position hard-wired in your theme instead of going through Layout block): open your template's homepage file, for example

```
app/GP247/Templates/{TEMPLATE_NAME}/screen/home.blade.php
```

and insert exactly this one line where you want the strip to appear:

```blade
@include('Plugins/ProductFlashSale::blocks.product_flash_sale')
```

**To link to the flash-sale page** from a menu or anywhere in your theme:

```blade
<a href="{{ gp247_route_front('product_flash_sale.index') }}">Flash Sale</a>
```

Use `gp247_route_front(...)` rather than `route(...)`, so the address keeps the right language code on multilingual sites.

**To restyle the listing page** without touching the plugin's code, create the file

```
app/GP247/Templates/{TEMPLATE_NAME}/Plugins/ProductFlashSale/front_flash_sale.blade.php
```

The template's copy is then used instead of the plugin's default.

## Customising how the strip looks

You can change the strip's appearance **without touching the plugin's code**, and your change survives plugin updates. There are three ways — pick the lightest one that does the job.

**Option 1 — Wrap the strip (best when you only want to add something).** Keep the plugin's strip and put your own markup around it. Create the file:

```
app/GP247/Templates/{TEMPLATE_NAME}/blocks/product_flash_sale.blade.php
```

containing:

```blade
<div class="container-x pt-6">
    <h2 class="section-title">Today's golden hour</h2>
</div>

@includeIf('Plugins/ProductFlashSale::blocks.product_flash_sale')
```

Use `@includeIf`, not `@include`: if you later remove the plugin, `@includeIf` simply renders nothing, while `@include` would break the page with "View not found".

**Option 2 — Rewrite the strip entirely.** Same file path, but you write all the markup and fetch the data yourself:

```blade
@php
    $products = gp247_product_flash(10);
@endphp

@foreach ($products as $product)
    {{-- your own markup --}}
@endforeach
```

**Option 3 — Override one view inside the plugin.** When you only want to change a small piece (the product card, or the listing page) and keep the rest, create a file with the same name under:

```
resources/views/vendor/Plugins/ProductFlashSale/
```

For example `resources/views/vendor/Plugins/ProductFlashSale/partials/flash_card.blade.php` replaces the plugin's product card everywhere it appears. This works for any of the plugin's views.

> One caveat for options 2 and 3: they create a **frozen copy** — later plugin updates to that markup will not reach you. Option 1 has no such downside. To go back to the original, delete the file you created.

## How the system counts units

The sale's units are taken and given back automatically along the order lifecycle — you never adjust them by hand:

- **When a customer places an order**: the units are taken while the order is being created. Two shoppers buying at the same moment cannot oversell the sale — the second one is refused and their order is not created.
- **When an order is cancelled, deleted, or a line is removed from it**: the units are given back to the sale, exactly as the goods go back to stock.
- **When a cancelled order is re-opened**: the units are taken again. If the sale has none left, the re-open is refused — the same way the system refuses it when stock has run out.
- A product that is not part of any flash sale is never blocked by this plugin.

## Running on multi-store / marketplace sites (MultiStore / MultiVendor)

A flash sale belongs to the store that owns the product, so what is visible follows the site's usual rules:

- **Marketplace (MultiVendor)**: the shared storefront shows the sales of every active shop. If a shop is disabled, its sales disappear from the marketplace.
- **Multiple stores by domain (MultiStore)**: each store only sees its own sales.
- **In admin**: a store's administrator only sees, and can only pick, their own store's products. The root administrator sees every store's sales, and the list carries a small line with the owning store's name.
- **Together with customer-group pricing** (MultiVendor Pro): the flash-sale price is the base; a group price can only go lower, never higher. A customer buying at that lower price still uses one unit of the sale.

Note: the screen for scheduling flash sales lives in the root admin area. On a marketplace, shop staff cannot schedule their own flash sales from the vendor area — sales are scheduled by the marketplace administrator.

## Conditions & Rules (know before you act)

**When creating or editing a sale**

- **You must pick a product from the suggestion list** — the list only contains single/bundle products (not group products) belonging to your store. The system checks this again on save, so it cannot be bypassed by editing what the form submits.
- **One flash sale per product** — two sales on the same product would fight over the same promotional price, so the system refuses it up front.
- **The quantity must be at least 1** — a sale with no units has nothing to sell.
- **The quantity cannot be lower than what has already been sold** — if 4 are sold and you set it to 3, the progress bar would read over 100% and nobody could tell whether the units were sold or the number was simply edited down.
- **The end moment must be after the start moment** — otherwise the sale would never run.
- **The sale price is a number and cannot be negative**, entered in the site's base currency.

**When products appear on the storefront**

- A sale only shows while it is **inside its window** (compared to the minute, not to the day) and its **Status switch is on**.
- A **sold-out** sale (`sold = quantity`) leaves the flash-sale strip by itself.
- The product must still meet the shop's usual visibility rules: **active, approved, belonging to the store being viewed, and with a description in the current language**. If one of those is missing, the product stays hidden even though a sale exists.

**When customers buy**

- Buying more than the units left means the **order is refused**, not "sold now, sorted out later" — this is what keeps the "only N units" promise true.
- Re-opening a cancelled order is likewise refused when the sale has no units left.

## Enable, disable and uninstall

- **Disable**: both the admin screen and the flash-sale page on your site stop working. Your data stays untouched; enabling brings it all back.
- **Uninstall**: removes the plugin's config, its menu entry and the `shop_product_flash` table. The promotional prices already created on products are not removed with it. The `product_flash_sale` block disappears from the picker together with the plugin (it never left a file in your template), and any position it was placed in simply renders nothing.
- Enabling/disabling the plugin applies to the **whole site**, not to individual stores.

## Technical details

- Storefront route: `GET /plugin/product_flash_sale/index` (route name `product_flash_sale.index`).
- Admin routes: `{GP247_ADMIN_PREFIX}/product_flash_sale`, `/create`, `/edit/{id}`.
- Data table: `shop_product_flash` with `id`, `product_id`, `stock` (units offered), `sold`, `sort`.
- The price and the window live in the shop's existing promotion table: `shop_product_promotion`.
- View/translation namespace: `Plugins/ProductFlashSale`.
- The home-page block is registered into `config('gp247-config.front.layout_block_views')` (key `product_flash_sale`) from `Provider.php`; the plugin ships no template directory and copies nothing into `app/GP247/Templates`. A template's own file is looked up **first**, so your own copy always wins.
- Helpers you can reuse in a theme:

  ```php
  gp247_product_flash($limit = 8, $paginate = false);          // products currently on flash sale
  gp247_product_flash_check_over($productId, $quantity);       // are enough units left (true = yes)
  gp247_product_flash_update_stock($productId, $quantity);     // take units, returns false when short
  gp247_product_flash_release_stock($productId, $quantity);    // give units back to the sale
  ```

  Building your own listing in a theme:

  ```blade
  @php
      $products = gp247_product_flash(8);
  @endphp

  @foreach ($products as $product)
      <div>{{ $product->getName() }} — sold {{ $product->pf_sold }}/{{ $product->pf_stock }}</div>
  @endforeach
  ```

## Q&A

**Q1: I scheduled a sale but the flash-sale page is empty — why?**

→ Usually one of three reasons: the start time has not arrived or the end time has passed; the Status switch is off; or the product does not meet the shop's general visibility rules (not active, not approved, or missing a description in the language being viewed).

**Q2: Where does the countdown take its deadline from?**

→ From the **End** moment of that particular sale. Each product card has its own countdown for its own sale.

**Q3: If a customer cancels the order, are the units returned?**

→ Yes, automatically. Cancelling an order, deleting it, or removing a line from it all return the units to the sale.

**Q4: What happens when two customers click buy on the last unit?**

→ Only one of them gets it. The other receives an "over quantity" message and their order is not created — units are taken in a way that resists this race, rather than "let both through and sort it out later".

**Q5: How do I stop a sale immediately?**

→ Turn that row's **Status** switch off, or set its End moment to the past. Deleting the row also works, but that removes the product's promotional price as well.

**Q6: Does updating the plugin lose the sales I have scheduled?**

→ No. Re-installing and updating both keep your data; only **Uninstall** drops the table.

**Q7: A product has both a flash sale and customer-group pricing — which price does the customer pay?**

→ The lower one. The flash-sale price is the base and a group price can only pull it further down. Whichever price applies, the customer still uses one unit of the sale.

**Q8: On a marketplace, can a shop schedule its own flash sale?**

→ No. The scheduling screen is in the root admin area, so sales are scheduled by the marketplace administrator.

**Q9: Can I disable the plugin for just one store?**

→ No. Enabling/disabling applies to the whole site. In practice a store that schedules no sales displays nothing, so other stores are unaffected.

**Q10: The strip does not appear on my homepage even though a sale is running.**

→ The plugin never places the strip on your homepage by itself — you choose where it goes. Check that you added the `product_flash_sale` block in **Layout block** for the `front_home` page, or that your template contains the line `@include('Plugins/ProductFlashSale::blocks.product_flash_sale')`. Do not confuse it with `shop_product_promotion`, which is the shop's own promotion block.

---

<sub>📅 **Last updated:** 2026-09-22 · ✍️ **Author:** GP247</sub>
