define([
    "pim/datagrid-view-fetcher",
    "pim/base-fetcher",
    "pim/attribute-group-fetcher",
    "pim/attribute-fetcher",
    "pim/locale-fetcher",
    "pim/variant-group-fetcher",
    "pim/product-fetcher"
], function(DatagridViewFetcher, BaseFetcher, AttributeGroupFetcher, AttributeFetcher, LocaleFetcher, VariantGroupFetcher, ProductFetcher) {
    return {
        "pim/datagrid-view-fetcher": DatagridViewFetcher,
        "pim/base-fetcher": BaseFetcher,
        "pim/attribute-group-fetcher": AttributeGroupFetcher,
        "pim/attribute-fetcher": AttributeFetcher,
        "pim/locale-fetcher": LocaleFetcher,
        "pim/variant-group-fetcher": VariantGroupFetcher,
        "pim/product-fetcher": ProductFetcher
    }
})