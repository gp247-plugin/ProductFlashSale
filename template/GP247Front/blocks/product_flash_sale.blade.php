{{--
    Flash-sale strip, offered to the "Layout block" admin screen.

    WHY it sits under template/<TemplateName>/blocks and not in Views/: a template's
    files come from every hint path registered under the GP247TemplatePath view
    namespace, and both the renderer (gp247_render_block) and the block picker
    (gp247_template_files -> TemplateSourceAudit::roots) walk exactly those hints.
    Provider.php registers this directory as one more hint, so the block is listed
    and rendered straight from the plugin — nothing is ever copied into
    app/GP247/Templates. That is what makes the block work on a read-only
    deployment, disappear cleanly when the plugin is removed, and follow plugin
    updates without anyone re-publishing a file.

    The view key is GP247TemplatePath::<Template>.blocks.<name>, so the template
    name is a path segment: this file serves GP247Front. A site running its own
    template adds a one-line file of its own (see readme) — the same limitation
    gp247/shop's blocks have.

    WHY a one-line include instead of the markup: the strip itself belongs with the
    rest of the plugin's storefront views.

    @aidlc-unit plugin-product-flash-sale
    @aidlc-story US-product-flash-sale-core3-port
--}}
@include('Plugins/ProductFlashSale::blocks.product_flash_sale')
