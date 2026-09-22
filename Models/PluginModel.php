<?php
#App\GP247\Plugins\ProductFlashSale\Models\PluginModel.php
namespace App\GP247\Plugins\ProductFlashSale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use GP247\Shop\Models\ShopProduct;
use GP247\Shop\Models\ShopProductPromotion;

/**
 * Time-boxed selling ledger: how many units of a promoted product may be sold
 * while its promotion window is open, and how many already were.
 *
 * The price and the window itself live in gp247_shop_product_promotion (owned by
 * gp247/shop); this table adds only the quota and the display order. That split
 * is why a plain price promotion shows no timer on the storefront while a flash
 * sale does — see the "Promotion products" block in gp247/shop.
 *
 * @aidlc-unit plugin-product-flash-sale
 * @aidlc-story US-product-flash-sale-quota-integrity
 * @aidlc-adr plugin-product-flash-sale_quota-atomic-ledger
 */
class PluginModel extends Model
{
    use \GP247\Core\Models\UuidTrait;

    public $timestamps    = false;
    public $table = GP247_DB_PREFIX.'shop_product_flash';
    protected $connection = GP247_DB_CONNECTION;
    protected $guarded    = [];

    /**
     * The product this quota row belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function product()
    {
        return $this->belongsTo(ShopProduct::class, 'product_id', 'id');
    }

    /**
     * The promotion row carrying this product's sale price and window.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function promotion()
    {
        return $this->hasOne(ShopProductPromotion::class, 'product_id', 'product_id');
    }

    /**
     * Drop the ledger. Called only from AppConfig::uninstall().
     *
     * @return array{error: int, msg: string}
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function uninstallExtension()
    {
        Schema::dropIfExists($this->table);

        return ['error' => 0, 'msg' => 'uninstall success'];
    }

    /**
     * Create the ledger when it is missing ("Pattern A": Schema here rather than a
     * migrations folder, because install/uninstall and 1-click update delete and
     * recreate the plugin directory, which would leave a migrations ledger pointing
     * at files that are gone).
     *
     * WHY it no longer drops first: the previous version called uninstallExtension()
     * at the top of this method, so re-installing the plugin — or updating a site
     * whose table was already there — silently destroyed every scheduled sale and
     * its sold counters. Creating only when absent makes install/update re-entrant.
     *
     * @return array{error: int, msg: string}
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     * @aidlc-adr plugin-product-flash-sale_install-convergence
     */
    public function installExtension()
    {
        if (!Schema::hasTable($this->table)) {
            Schema::create($this->table, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('product_id')->unique();
                $table->integer('stock')->default(0);
                $table->integer('sold')->default(0);
                $table->integer('sort')->default(0);
            });
        }

        return ['error' => 0, 'msg' => 'install success'];
    }

    /**
     * Products an admin may attach a flash sale to, as id => name.
     *
     * Scoped to the acting admin's store on multi-store / marketplace installs so a
     * store admin cannot schedule a sale on another store's product. Status and
     * approval are deliberately NOT filtered: scheduling a sale for a product that
     * goes live later is a normal thing to do.
     *
     * @return \Illuminate\Support\Collection<string, string>
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-visibility-parity
     */
    public function getAllProductNotGroup()
    {
        $tableProduct = (new ShopProduct)->getTable();
        $tableDescription = GP247_DB_PREFIX . 'shop_product_description';

        $query = (new ShopProduct)
            ->leftJoin($tableDescription, $tableDescription.'.product_id', $tableProduct.'.id')
            ->where($tableDescription.'.lang', gp247_get_locale())
            ->whereIn($tableProduct.'.kind', [GP247_PRODUCT_SINGLE, GP247_PRODUCT_BUILD]);

        $adminStoreId = session('adminStoreId', GP247_STORE_ID_ROOT);
        if ($this->isStoreScopedInstall() && (string) $adminStoreId !== (string) GP247_STORE_ID_ROOT) {
            $query = $query->where($tableProduct.'.store_id', $adminStoreId);
        }

        return $query
            ->select($tableProduct.'.id', $tableDescription.'.name')
            ->get()
            ->pluck('name', 'id');
    }

    /**
     * Flash-sale products currently on sale, in admin sort order.
     *
     * Which products are allowed to be SEEN is decided by the shop's own product
     * builder (ShopProduct::buildQuery) rather than re-implemented here: it applies
     * status, approval, the store scope of a marketplace / multi-store install and
     * the "hide out of stock" setting. The previous version queried the tables
     * directly and therefore published unapproved products and products belonging to
     * other stores.
     *
     * @param int  $limit    Items to return (page size when paginating).
     * @param bool $paginate Return a paginator instead of a plain collection.
     * @return \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-visibility-parity
     */
    public function getProductFlash($limit = 8, $paginate = false)
    {
        $perPage = max(1, (int) $limit);
        $quotaRows = $this->activeQuotaRows();

        if ($quotaRows->isEmpty()) {
            return $paginate ? $this->emptyPaginator($perPage) : collect();
        }

        $ids = $quotaRows->keys()->all();
        $visible = (new ShopProduct)->start()
            ->getProductFromListID($ids)
            ->setLimit(count($ids))
            ->getData()
            ->keyBy('id');

        if ($visible->isEmpty()) {
            return $paginate ? $this->emptyPaginator($perPage) : collect();
        }

        // The storefront countdown reads promotionPrice.date_end; load it once for the
        // whole strip instead of one query per card.
        $visible->load('promotionPrice');

        $ordered = collect($ids)
            ->filter(fn ($id) => $visible->has($id))
            ->map(function ($id) use ($visible, $quotaRows) {
                $product = $visible->get($id);
                $quota = $quotaRows->get($id);
                // Exposed as pf_* so views can draw the "sold / stock" progress bar
                // without a second query per card (same names as the previous version).
                $product->pf_stock = (int) $quota->stock;
                $product->pf_sold = (int) $quota->sold;

                return $product;
            })
            ->values();

        if (!$paginate) {
            return $ordered->take($perPage)->values();
        }

        $page = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $ordered->forPage($page, $perPage)->values(),
            $ordered->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()]
        );
    }

    /**
     * Whether the quota allows selling $quantity more units right now.
     *
     * A product with no quota row, or whose promotion window is not open, is not a
     * flash-sale product at all and is never blocked by this plugin.
     *
     * @param string $productId
     * @param int    $quantity
     * @return bool
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-quota-integrity
     */
    public function canConsume($productId, $quantity): bool
    {
        $quantity = (int) $quantity;
        if ($quantity <= 0) {
            return true;
        }

        $row = $this->activeQuotaQuery()
            ->where($this->table.'.product_id', $productId)
            ->first();

        if ($row === null) {
            return true;
        }

        return ((int) $row->stock - (int) $row->sold) >= $quantity;
    }

    /**
     * Take $quantity units out of the quota, atomically.
     *
     * WHY a conditional UPDATE instead of read-modify-write: this runs inside the
     * order transaction, next to ShopProduct::updateStock() which core made an
     * atomic conditional update for exactly this reason. Reading `sold`, adding to
     * it in PHP and saving lets two simultaneous checkouts both pass the pre-check
     * and oversell the sale — the one failure mode a quota exists to prevent.
     *
     * @param string $productId
     * @param int    $quantity
     * @return bool True when the units were taken, or when the product has no open
     *              quota to take from; false when the quota is too low.
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-quota-integrity
     * @aidlc-adr plugin-product-flash-sale_quota-atomic-ledger
     */
    public function consumeQuota($productId, $quantity): bool
    {
        $quantity = (int) $quantity;
        if ($quantity <= 0) {
            return true;
        }

        $tablePromotion = (new ShopProductPromotion)->getTable();
        $now = gp247_time_now();

        $affected = DB::connection(GP247_DB_CONNECTION)->update(
            'UPDATE '.$this->table.' SET sold = sold + ?'
            .' WHERE product_id = ? AND sold + ? <= stock'
            .' AND EXISTS (SELECT 1 FROM '.$tablePromotion.' p'
            .' WHERE p.product_id = '.$this->table.'.product_id'
            .' AND p.status_promotion = 1'
            .' AND (p.date_start IS NULL OR p.date_start <= ?)'
            .' AND (p.date_end IS NULL OR p.date_end >= ?))',
            [$quantity, $productId, $quantity, $now, $now]
        );

        if ($affected > 0) {
            return true;
        }

        // Nothing was written: either this product has no open quota (nothing to
        // consume — allow the sale) or the quota is exhausted (refuse it).
        return $this->canConsume($productId, $quantity);
    }

    /**
     * Give $quantity units back to the quota.
     *
     * Deliberately NOT limited to an open promotion window: an order cancelled after
     * the sale ended must still return the units, otherwise the quota of the next
     * sale on that product starts already spent.
     *
     * @param string $productId
     * @param int    $quantity
     * @return void
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-quota-integrity
     * @aidlc-adr plugin-product-flash-sale_quota-atomic-ledger
     */
    public function releaseQuota($productId, $quantity): void
    {
        $quantity = (int) $quantity;
        if ($quantity <= 0) {
            return;
        }

        // GREATEST floors the counter at zero so a double release (two code paths
        // both returning the same order's goods) cannot drive `sold` negative and
        // hand out more units than the sale ever offered.
        DB::connection(GP247_DB_CONNECTION)->update(
            'UPDATE '.$this->table.' SET sold = GREATEST(sold - ?, 0) WHERE product_id = ?',
            [$quantity, $productId]
        );
    }

    /**
     * Quota rows whose promotion window is open, keyed by product id, in sort order.
     *
     * @return \Illuminate\Support\Collection<string, static>
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-visibility-parity
     */
    protected function activeQuotaRows()
    {
        return $this->activeQuotaQuery()
            ->whereColumn($this->table.'.sold', '<', $this->table.'.stock')
            ->orderBy($this->table.'.sort', 'asc')
            ->select($this->table.'.*')
            ->get()
            ->keyBy('product_id');
    }

    /**
     * Base query for quota rows joined to an enabled, currently-open promotion.
     *
     * The window is compared against the full timestamp, not the date: a sale that
     * starts at 18:00 must not be sellable at 09:00 the same day, which is the whole
     * point of time-boxed selling.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-quota-integrity
     */
    protected function activeQuotaQuery()
    {
        $tablePromotion = (new ShopProductPromotion)->getTable();
        $now = gp247_time_now();

        return $this->newQuery()
            ->join($tablePromotion, $tablePromotion.'.product_id', $this->table.'.product_id')
            ->where($tablePromotion.'.status_promotion', 1)
            ->where(function ($query) use ($tablePromotion, $now): void {
                $query->whereNull($tablePromotion.'.date_start')
                    ->orWhere($tablePromotion.'.date_start', '<=', $now);
            })
            ->where(function ($query) use ($tablePromotion, $now): void {
                $query->whereNull($tablePromotion.'.date_end')
                    ->orWhere($tablePromotion.'.date_end', '>=', $now);
            });
    }

    /**
     * Whether this install scopes products to stores (multi-store or marketplace).
     *
     * @return bool
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-visibility-parity
     */
    protected function isStoreScopedInstall(): bool
    {
        return (function_exists('gp247_store_check_multi_store_installed') && gp247_store_check_multi_store_installed())
            || (function_exists('gp247_store_check_multi_partner_installed') && gp247_store_check_multi_partner_installed());
    }

    /**
     * An empty paginator, so a caller expecting one can render its pagination links
     * even when nothing is on sale.
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    protected function emptyPaginator(int $perPage)
    {
        return new LengthAwarePaginator(
            collect(),
            0,
            $perPage,
            1,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()]
        );
    }

    /**
     * Model hooks: cascade the promotion row on delete, and mint the row id.
     *
     * @return void
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($item) {
            // Removing a flash sale removes the sale price with it: the plugin is what
            // created that promotion row, and leaving it behind would keep the product
            // discounted with nothing on screen to explain why.
            (new ShopProductPromotion)->where('product_id', $item->product_id)->delete();
        });

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = gp247_generate_id(prefix: 'FS');
            }
        });
    }
}
