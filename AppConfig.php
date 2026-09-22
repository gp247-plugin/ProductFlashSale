<?php
#App\GP247\Plugins\ProductFlashSale\AppConfig.php
namespace App\GP247\Plugins\ProductFlashSale;

use App\GP247\Plugins\ProductFlashSale\Models\PluginModel;
use GP247\Core\Models\AdminConfig;
use GP247\Core\Models\AdminHome;
use GP247\Core\Models\AdminMenu;
use GP247\Core\ExtensionConfigDefault;

/**
 * Extension descriptor — plugin format 2.0 (gp247/core 3.x).
 *
 * install() and update() both funnel through converge(), so the plugin ends in
 * the same working shape whether it is installed fresh, installed over a
 * half-removed state, or updated in place by the 1-click updater. Every step of
 * converge() is guarded and re-entrant, and none of them destroys data that is
 * already there (the previous version dropped the quota table on every install).
 *
 * @aidlc-unit plugin-product-flash-sale
 * @aidlc-story US-product-flash-sale-core3-port
 * @aidlc-adr plugin-product-flash-sale_install-convergence
 */
class AppConfig extends ExtensionConfigDefault
{
    /** @var string Parent admin menu the plugin screen is hung under. */
    private const MENU_PARENT_KEY = 'ADMIN_SHOP_CATALOG';

    /** @var string Storefront block name, as stored in front_layout_block.text. */
    private const BLOCK_NAME = 'product_flash_sale';

    /**
     * Read the manifest into the descriptor properties.
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function __construct()
    {
        //Read config from gp247.json
        $config = file_get_contents(__DIR__.'/gp247.json');
        $config = json_decode($config, true);
        $this->configGroup = $config['configGroup'];
        $this->configKey = $config['configKey'];
        $this->configCode = $config['configCode'];
        $this->requireCore = $config['requireCore'] ?? [];
        // WHY both spellings: the manifest keys were renamed in gp247/core 2.1.
        // Read the new names first and fall back to the old ones so this class keeps
        // working if the folder is ever paired with an older manifest.
        $this->requireComposerPackages = $config['requireComposerPackages'] ?? $config['requirePackages'] ?? [];
        $this->requireGp247Extensions = $config['requireGp247Extensions'] ?? $config['requireExtensions'] ?? [];
        //Path
        $this->appPath = $this->configGroup . '/' . $this->configKey;
        //Language
        $this->title = trans($this->appPath.'::lang.title');
        //Image logo or thumb
        $this->image = $this->appPath.'/'.$config['image'];
        //
        $this->version = $config['version'];
        $this->auth = $config['auth'];
        $this->link = $config['link'];
    }

    /**
     * Install the plugin: register it in admin_config, then converge.
     *
     * @return array{error: int, msg: string}
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function install()
    {
        $check = AdminConfig::where('key', $this->configKey)
            ->where('group', $this->configGroup)->first();
        if ($check) {
            return ['error' => 1, 'msg' =>  gp247_language_render('admin.extension.plugin_exist')];
        }

        $dataInsert = [
            [
                'group'  => $this->configGroup,
                'code'    => $this->configCode,
                'key'    => $this->configKey,
                'sort'   => 0,
                'store_id' => GP247_STORE_ID_GLOBAL,
                'value'  => self::ON,
                'detail' => $this->appPath.'::lang.title',
            ],
        ];

        try {
            AdminConfig::insert($dataInsert);
            $this->converge();
            $this->seedLayoutBlock();
            $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.install_success')];
        } catch (\Throwable $th) {
            $return = ['error' => 1, 'msg' => $th->getMessage()];
        }

        return $return;
    }

    /**
     * Run after the 1-click updater replaced this plugin's files.
     *
     * WHY the same converge() as install(): an update lands on sites in different
     * states — one may never have had the quota table, another may have lost its
     * admin menu row. Re-running the guarded steps is what leaves every site in the
     * shape this version expects, and re-running them on a healthy site is a no-op.
     *
     * @param string|null $fromVersion Version installed before the file replacement.
     * @return array{error: int, msg: string}
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     * @aidlc-adr plugin-product-flash-sale_install-convergence
     */
    public function update(?string $fromVersion = null)
    {
        try {
            $this->converge();
        } catch (\Throwable $th) {
            return ['error' => 1, 'msg' => $th->getMessage()];
        }

        return ['error' => 0, 'msg' => ''];
    }

    /**
     * Bring the install up to the shape this version needs, without data loss.
     *
     * @return void
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    protected function converge(): void
    {
        (new PluginModel)->installExtension();
        $this->installMenu();
    }

    /**
     * Place the storefront strip on the home page of every store that can render
     * it — once, at install time, and only when the store has no such block yet.
     *
     * WHY at install and NOT in converge(): a layout block is site CONTENT, not
     * plugin structure. An admin who removes the strip from their home page has
     * made a decision, and re-creating it on the next plugin update would quietly
     * overrule them. The table and the admin menu are different — those are the
     * plugin's own scaffolding, so converge() keeps repairing them.
     *
     * WHY the template column matters: FrontLayoutBlock::getLayout() filters by
     * store AND template, so a row carrying the wrong template renders nowhere.
     *
     * Seeded for EVERY store since the block moved to the layout_block_views
     * registry (modification 20260922T205500): the strip no longer depends on a
     * file inside some template's directory, so there is no longer a template it
     * cannot render on.
     *
     * @return void
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    protected function seedLayoutBlock(): void
    {
        if (!class_exists(\GP247\Front\Models\FrontLayoutBlock::class)) {
            return; // storefront package absent: nothing renders blocks
        }

        try {
            $stores = \GP247\Core\Models\AdminStore::query()->get(['id', 'template']);
        } catch (\Throwable $e) {
            return; // store table unreadable this early — skip, never fail the install
        }

        foreach ($stores as $store) {
            $template = (string) $store->template;
            if (preg_match('/^[A-Za-z0-9_-]+$/', $template) !== 1) {
                continue; // the name becomes a path segment in the view key
            }

            $taken = \GP247\Front\Models\FrontLayoutBlock::where('store_id', $store->id)
                ->where('text', self::BLOCK_NAME)
                ->exists();
            if ($taken) {
                continue;
            }

            \GP247\Front\Models\FrontLayoutBlock::insert([
                'id'       => (string) \Illuminate\Support\Str::orderedUuid(),
                'name'     => 'Flash Sale (ProductFlashSale)',
                'position' => 'bottom',
                'page'     => 'front_home',
                'text'     => self::BLOCK_NAME,
                'type'     => 'view',
                // Blocks at a position render by descending sort, and the shop's own
                // "Product Home" block seeds at 10: a sale that ends today deserves to
                // sit above the evergreen product list.
                'sort'     => 20,
                'status'   => 1,
                'template' => $template,
                'store_id' => $store->id,
            ]);
        }
    }

    /**
     * Add the admin menu row when it is missing.
     *
     * Guarded on the plugin key so installing over an existing state, or updating,
     * never leaves the sidebar with the same entry twice — duplicates are
     * indistinguishable to the admin looking at the menu.
     *
     * @return void
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    protected function installMenu(): void
    {
        if (AdminMenu::where('key', $this->configKey)->exists()) {
            return;
        }

        $parent = AdminMenu::where('key', self::MENU_PARENT_KEY)->first();
        if (!$parent) {
            return;
        }

        AdminMenu::insert([
            'sort' => 100,
            'parent_id' => $parent->id,
            'title' => $this->appPath.'::lang.title',
            'icon' => 'fa fa-bolt',
            'uri' => 'route_admin::admin_product_flash_sale.index',
            'key' => $this->configKey,
        ]);
    }

    /**
     * Remove everything install() created, including the quota table.
     *
     * @return array{error: int, msg: string}
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function uninstall()
    {
        try {
            //Delete config
            (new AdminConfig)
                ->where('key', $this->configKey)
                ->orWhere('code', $this->configKey.'_config')
                ->delete();
            //Delete home
            AdminHome::where('extension', $this->appPath)->delete();
            //Delete menu
            AdminMenu::where('key', $this->configKey)->delete();
            (new PluginModel)->uninstallExtension();
            $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.uninstall_success')];
        } catch (\Throwable $e) {
            $return = ['error' => 1, 'msg' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * Enable the plugin.
     *
     * @return array{error: int, msg: string}
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function enable()
    {
        $process = (new AdminConfig)
            ->where('group', $this->configGroup)
            ->where('key', $this->configKey)
            ->update(['value' => self::ON]);

        AdminHome::where('extension', $this->appPath)->update(['status' => 1]);

        if (!$process) {
            $return = ['error' => 1, 'msg' => gp247_language_render('admin.extension.action_error', ['action' => 'Enable'])];
        }
        $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.enable_success')];
        return $return;
    }

    /**
     * Disable the plugin.
     *
     * @return array{error: int, msg: string}
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function disable()
    {
        $process = (new AdminConfig)
            ->where('group', $this->configGroup)
            ->where('key', $this->configKey)
            ->update(['value' => self::OFF]);
        if (!$process) {
            $return = ['error' => 1, 'msg' => gp247_language_render('admin.extension.action_error', ['action' => 'Disable'])];
        }
        $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.disable_success')];
        AdminHome::where('extension', $this->appPath)->update(['status' => 0]);

        return $return;
    }

    /**
     * Nothing to tear down per store: the quota ledger is not store-scoped.
     *
     * @param int|string|null $storeId
     * @return void
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function removeStore($storeId = null)
    {
        // Intentionally empty — see Models/PluginModel for the ledger's shape.
    }

    /**
     * Nothing to set up per store: the quota ledger is not store-scoped.
     *
     * @param int|string|null $storeId
     * @return void
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function setupStore($storeId = null)
    {
        // Intentionally empty — see Models/PluginModel for the ledger's shape.
    }

    /**
     * Open the plugin's admin screen when its card is clicked in Plugin manager.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function clickApp()
    {
        return redirect(gp247_route_admin('admin_product_flash_sale.index'));
    }

    /**
     * Descriptor for the Plugin manager card.
     *
     * @return array<string, mixed>
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function getData()
    {
        return $this->getInfo();
    }

    /**
     * Hook called after an order is completed. Unused by this plugin: the quota is
     * consumed at order creation through the gp247_product_flash_* helpers.
     *
     * @param array<string, mixed> $data
     * @return void
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-quota-integrity
     */
    public function endApp($data = []) {
        // Intentionally empty — see function.php for the order-time hooks.
    }

    /**
     * Descriptor read by core (Plugin manager, extension list).
     *
     * @return array<string, mixed>
     *
     * @aidlc-unit plugin-product-flash-sale
     * @aidlc-story US-product-flash-sale-core3-port
     */
    public function getInfo()
    {
        $arrData = [
            'title'      => $this->title,
            'key'        => $this->configKey,
            'code'       => $this->configCode,
            'image'      => $this->image,
            'permission' => self::ALLOW,
            'version'    => $this->version,
            'auth'       => $this->auth,
            'link'       => $this->link,
            'value'      => 0,
            'appPath'    => $this->appPath
        ];

        return $arrData;
    }
}
