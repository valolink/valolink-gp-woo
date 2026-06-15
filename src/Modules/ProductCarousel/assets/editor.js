/*
 * Product Carousel — editor registration. No build step: plain JS against the wp.* globals
 * (createElement, not JSX). Settings live in the inspector sidebar; the canvas shows a live
 * ServerSideRender preview of the same PHP render_callback used on the front end.
 */
(function (blocks, element, blockEditor, components, serverSideRender, i18n) {
    var el = element.createElement;
    var Fragment = element.Fragment;
    var __ = i18n.__;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelColorSettings = blockEditor.PanelColorSettings;
    var useBlockProps = blockEditor.useBlockProps;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var RangeControl = components.RangeControl;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var ServerSideRender = serverSideRender;

    var SOURCES = [
        { label: __('Newest', 'valolink-gp-woo'), value: 'newest' },
        { label: __('On sale', 'valolink-gp-woo'), value: 'sale' },
        { label: __('Featured', 'valolink-gp-woo'), value: 'featured' },
        { label: __('Best selling', 'valolink-gp-woo'), value: 'best_selling' },
        { label: __('Top rated', 'valolink-gp-woo'), value: 'top_rated' },
        { label: __('Category', 'valolink-gp-woo'), value: 'category' }
    ];

    blocks.registerBlockType('valolink-gp-woo/product-carousel', {
        apiVersion: 3,
        title: __('Product Carousel', 'valolink-gp-woo'),
        description: __('A scrollable row of product cards, using the shared product-card template.', 'valolink-gp-woo'),
        icon: 'slides',
        category: 'woocommerce',
        keywords: [__('products', 'valolink-gp-woo'), __('carousel', 'valolink-gp-woo'), __('slider', 'valolink-gp-woo')],
        supports: { align: ['wide', 'full'], html: false },
        attributes: {
            source: { type: 'string', default: 'newest' },
            category: { type: 'string', default: '' },
            count: { type: 'number', default: 10 },
            columns: { type: 'number', default: 4 },
            cardMinWidth: { type: 'number', default: 220 },
            showArrows: { type: 'boolean', default: true },
            arrowSize: { type: 'number', default: 40 },
            arrowColor: { type: 'string', default: '' }
        },

        edit: function (props) {
            var a = props.attributes;
            var set = props.setAttributes;
            var blockProps = useBlockProps();

            var controls = [
                el(SelectControl, {
                    key: 'source',
                    label: __('Source', 'valolink-gp-woo'),
                    value: a.source,
                    options: SOURCES,
                    onChange: function (v) { set({ source: v }); }
                })
            ];

            if (a.source === 'category') {
                controls.push(el(TextControl, {
                    key: 'category',
                    label: __('Category slug', 'valolink-gp-woo'),
                    help: __('The product_cat slug, e.g. "sale-items".', 'valolink-gp-woo'),
                    value: a.category,
                    onChange: function (v) { set({ category: v }); }
                }));
            }

            controls.push(el(RangeControl, {
                key: 'count',
                label: __('Number of products', 'valolink-gp-woo'),
                min: 1, max: 24,
                value: a.count,
                onChange: function (v) { set({ count: v }); }
            }));

            controls.push(el(RangeControl, {
                key: 'columns',
                label: __('Visible columns', 'valolink-gp-woo'),
                help: __('How many cards fit across the track (desktop).', 'valolink-gp-woo'),
                min: 1, max: 6,
                value: a.columns,
                onChange: function (v) { set({ columns: v }); }
            }));

            controls.push(el(RangeControl, {
                key: 'cardMinWidth',
                label: __('Mobile card min width (px)', 'valolink-gp-woo'),
                help: __('Cards never shrink below this on phones — they scroll instead.', 'valolink-gp-woo'),
                min: 120, max: 400, step: 10,
                value: a.cardMinWidth,
                onChange: function (v) { set({ cardMinWidth: v }); }
            }));

            controls.push(el(ToggleControl, {
                key: 'arrows',
                label: __('Show arrows', 'valolink-gp-woo'),
                checked: a.showArrows,
                onChange: function (v) { set({ showArrows: v }); }
            }));

            var arrowPanel = (a.showArrows && PanelColorSettings)
                ? el(PanelColorSettings, {
                    title: __('Arrows', 'valolink-gp-woo'),
                    initialOpen: false,
                    colorSettings: [{
                        value: a.arrowColor,
                        label: __('Arrow color', 'valolink-gp-woo'),
                        onChange: function (v) { set({ arrowColor: v || '' }); }
                    }]
                },
                    el(RangeControl, {
                        key: 'arrowSize',
                        label: __('Arrow size (px)', 'valolink-gp-woo'),
                        min: 16, max: 80,
                        value: a.arrowSize,
                        onChange: function (v) { set({ arrowSize: v }); }
                    })
                )
                : null;

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Listing', 'valolink-gp-woo'), initialOpen: true }, controls),
                    arrowPanel
                ),
                el('div', blockProps,
                    el(ServerSideRender, {
                        block: 'valolink-gp-woo/product-carousel',
                        attributes: a
                    })
                )
            );
        },

        save: function () {
            return null; // dynamic block — rendered in PHP
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.serverSideRender,
    window.wp.i18n
);
