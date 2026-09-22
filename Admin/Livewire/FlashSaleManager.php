<?php
#App\GP247\Plugins\ProductFlashSale\Admin\Livewire\FlashSaleManager.php

namespace App\GP247\Plugins\ProductFlashSale\Admin\Livewire;

use App\GP247\Plugins\ProductFlashSale\Models\PluginModel;
use GP247\Core\AdminShell\Infrastructure\ResourcePanel;
use GP247\Shop\Models\ShopProduct;
use GP247\Shop\Models\ShopProductPromotion;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Flash-sale manager — core 3.x port of the legacy AdminLTE AdminController,
 * whose form extended the removed `gp247-core::layout` and whose list paginated
 * through the removed `gp247-core::component.pagination`, driven by jQuery pjax,
 * select2 and SweetAlert (none of which the TailAdmin shell loads).
 *
 * Two-panel screen on the core ResourcePanel base: schedule/edit on the left, the
 * running sales on the right. The plugin owns the quota (this table) and writes
 * the sale price/window into gp247_shop_product_promotion, exactly as before.
 *
 * @aidlc-unit plugin-product-flash-sale
 * @aidlc-story US-product-flash-sale-core3-port
 * @aidlc-adr ADR-001, ADR-005, ADR-007
 */
class FlashSaleManager extends ResourcePanel
{
    /**
     * Human-readable label for the screen; access itself is decided by http_uri +
     * method (ADR-001 Layer-2), derived from the base route.
     *
     * @var string|null
     */
    protected ?string $permission = 'admin_product_flash_sale';

    /**
     * Keep the list page and the edited row on screen when saving, instead of
     * remounting through a redirect.
     *
     * @var bool
     */
    protected bool $keepStateOnSave = true;

    /**
     * Memoised product picker options, so a render that both validates and draws the
     * select does not query the catalogue twice.
     *
     * @var array<string, string>|null
     */
    private ?array $productOptionCache = null;

    /**
     * Quota rows, newest sales first by admin sort order.
     *
     * Scoped to the acting admin's store on multi-store / marketplace installs: a
     * store admin manages the sales of their own products only.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-visibility-parity
     */
    protected function baseQuery()
    {
        $query = PluginModel::query()->with(['product', 'promotion']);

        $adminStoreId = session('adminStoreId', GP247_STORE_ID_ROOT);
        if ($this->isStoreScopedInstall() && (string) $adminStoreId !== (string) GP247_STORE_ID_ROOT) {
            $query->whereHas('product', function ($w) use ($adminStoreId): void {
                $w->where('store_id', $adminStoreId);
            });
        }

        return $query;
    }

    /**
     * No keyword filter: a flash sale list is a short, curated set (the legacy screen
     * had no search either).
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    protected function sortableColumns(): array
    {
        return ['sort', 'stock', 'sold'];
    }

    /**
     * @return array<int, string>
     */
    protected function defaultSort(): array
    {
        return ['sort', 'asc'];
    }

    /**
     * @return string
     */
    protected function panelView(): string
    {
        return 'Plugins/ProductFlashSale::Admin.flash_sale';
    }

    /**
     * @return string
     */
    protected function pageTitle(): string
    {
        return gp247_language_render('Plugins/ProductFlashSale::lang.title');
    }

    /**
     * @return string
     */
    protected function baseRoute(): string
    {
        // Unchanged from v1 on purpose: the AdminMenu row written by
        // AppConfig::install() points at route_admin::admin_product_flash_sale.index.
        return 'admin_product_flash_sale.index';
    }

    /**
     * @return array<string, mixed>
     */
    protected function formDefaults(): array
    {
        return [
            'product_id'       => '',
            'stock'            => 1,
            'sort'             => 0,
            'price_promotion'  => '',
            'date_start'       => Carbon::now()->format('Y-m-d H:i'),
            'date_end'         => Carbon::now()->addDay()->format('Y-m-d H:i'),
            'status_promotion' => 1,
        ];
    }

    /**
     * @param PluginModel $model
     * @return array<string, mixed>
     */
    protected function fillForm($model): array
    {
        $promotion = $model->promotion;

        return [
            'product_id'       => (string) $model->product_id,
            'stock'            => (int) $model->stock,
            'sort'             => (int) $model->sort,
            'price_promotion'  => $promotion ? (string) $promotion->price_promotion : '',
            'date_start'       => $promotion && $promotion->date_start ? Carbon::parse($promotion->date_start)->format('Y-m-d H:i') : '',
            'date_end'         => $promotion && $promotion->date_end ? Carbon::parse($promotion->date_end)->format('Y-m-d H:i') : '',
            'status_promotion' => $promotion ? (int) $promotion->status_promotion : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $table = (new PluginModel)->getTable();

        // One sale per product: the quota row is unique on product_id, and two rows
        // would also fight over the single promotion row they both write.
        $unique = Rule::unique($table, 'product_id');
        if ($this->editingId !== null && $this->editingId !== '') {
            $unique = $unique->ignore($this->editingId, 'id');
        }

        return [
            // WHY Rule::in over a plain "exists": the option list is already scoped to
            // the admin's store, so this is what stops a store admin from posting
            // another store's product id straight into the form.
            'form.product_id'       => ['required', Rule::in(array_keys($this->productOptions())), $unique],
            'form.stock'            => ['required', 'numeric', 'min:1', fn ($attribute, $value, $fail) => $this->validateStockAgainstSold($value, $fail)],
            'form.sort'             => ['required', 'numeric', 'min:0'],
            'form.price_promotion'  => ['required', 'numeric', 'min:0'],
            'form.date_start'       => ['required', 'date'],
            'form.date_end'         => ['required', 'date', 'after:form.date_start'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'form.product_id.in'     => gp247_language_render('Plugins/ProductFlashSale::lang.admin.product_invalid'),
            'form.product_id.unique' => gp247_language_render('Plugins/ProductFlashSale::lang.admin.product_duplicated'),
            'form.date_end.after'    => gp247_language_render('Plugins/ProductFlashSale::lang.admin.date_end_after'),
        ];
    }

    /**
     * Refuse a quota lower than what has already been sold.
     *
     * Allowing it would leave the sale reading as more-than-sold-out, and the
     * storefront progress bar above 100%, with no way to tell whether the units were
     * sold or the number was simply edited down.
     *
     * @param mixed    $value
     * @param callable $fail
     * @return void
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-quota-integrity
     */
    protected function validateStockAgainstSold($value, callable $fail): void
    {
        if ($this->editingId === null || $this->editingId === '') {
            return;
        }

        $sold = (int) ($this->baseQuery()->whereKey($this->editingId)->value('sold') ?? 0);
        if ((int) $value < $sold) {
            $fail(gp247_language_render('Plugins/ProductFlashSale::lang.admin.stock_below_sold', ['sold' => $sold]));
        }
    }

    /**
     * Write the quota row and the product's promotion (price + window) together.
     *
     * `sold` is never written here: it belongs to the order flow (see function.php),
     * and an admin save must not silently reset what shoppers already bought.
     *
     * @param array<string, mixed> $data
     * @return void
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    protected function persist(array $data): void
    {
        $attributes = [
            'product_id' => $data['product_id'],
            'stock'      => (int) $data['stock'],
            'sort'       => (int) $data['sort'],
        ];

        if ($this->editingId !== null && $this->editingId !== '') {
            $row = PluginModel::findOrFail($this->editingId);
            $row->update($attributes);
        } else {
            $row = (new PluginModel)->create($attributes);
            // keepStateOnSave: save() re-fills the form from this id after a create.
            $this->editingId = (string) $row->id;
        }

        (new ShopProductPromotion)->updateOrCreate(
            ['product_id' => $data['product_id']],
            [
                'price_promotion'  => $data['price_promotion'],
                'date_start'       => $data['date_start'],
                'date_end'         => $data['date_end'],
                'status_promotion' => empty($data['status_promotion']) ? 0 : 1,
            ]
        );
    }

    /**
     * Delete a scheduled sale. The model's deleting hook removes the promotion row
     * with it, so the product goes back to its normal price.
     *
     * @param int|string $id
     * @return void
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    protected function deleteModel($id): void
    {
        $model = $this->baseQuery()->find($id);
        if ($model !== null) {
            $model->delete();
        }
    }

    /**
     * Products the admin may put on sale, as id => name.
     *
     * @return array<string, string>
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-visibility-parity
     */
    public function productOptions(): array
    {
        if ($this->productOptionCache === null) {
            $this->productOptionCache = (new PluginModel)->getAllProductNotGroup()->all();
        }

        return $this->productOptionCache;
    }

    /**
     * The same list shaped for <x-gp247::searchable-select>.
     *
     * @return array<int, array<string, string>>
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function productSelectOptions(): array
    {
        $options = [];
        foreach ($this->productOptions() as $id => $name) {
            $options[] = ['id' => (string) $id, 'label' => (string) $name];
        }

        return $options;
    }

    /**
     * Storefront URL of a row's product, for the list thumbnail link.
     *
     * @param PluginModel $row
     * @return string
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function productUrl($row): string
    {
        return $row->product instanceof ShopProduct ? $row->product->getUrl() : '#';
    }

    /**
     * Name of the store that owns a row's product, for the list.
     *
     * WHY only on multi-store / marketplace installs: at root admin the list spans
     * every store, and "20 units at $9" means something different depending on whose
     * shop it is. The quota row itself has no store column — a sale belongs to the
     * store that owns the product — so the label is read through that relation.
     *
     * @param PluginModel $row
     * @return string Empty on a single-store install.
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-visibility-parity
     */
    public function storeLabelFor($row): string
    {
        if (!$this->isStoreScopedInstall() || !$row->product instanceof ShopProduct) {
            return '';
        }

        $storeId = $row->product->store_id;
        if ($storeId === null || $storeId === '') {
            return '';
        }

        $titles = \GP247\Core\Models\AdminStore::getListTitle();

        return (string) ($titles[$storeId] ?? $storeId);
    }

    /**
     * Whether this install scopes products to stores (multi-store or marketplace).
     *
     * @return bool
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-visibility-parity
     */
    public function isStoreScopedInstall(): bool
    {
        return (function_exists('gp247_store_check_multi_store_installed') && gp247_store_check_multi_store_installed())
            || (function_exists('gp247_store_check_multi_partner_installed') && gp247_store_check_multi_partner_installed());
    }
}
