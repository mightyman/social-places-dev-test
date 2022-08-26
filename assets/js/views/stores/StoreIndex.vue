<template>
    <filter-table
        filter-url="api_stores_filters"
        id="stores"
        ref="storesTable"
        entity-label="Store"
        :export-button-array="[{
                    id: 'excelExport',
                    iconOnly: true,
                    iconClass: 'fas fa-file-excel',
                    url: 'api_store_export',
                    name: 'Excel',
                    tooltipBottom: true,
                }]"
        create-route-label="Import Stores"
        create-route="Stores Import"
        table-api-url="api_stores">
        <template #item.status="{item}">
            <basic-select
                :items="item.statuses"
                :row-item-id="item.id"
                :value="item.status"
                field="status"
                item="stores"
                url="/admin/api/{item}/{id}/{field}"
                table-mode
                @change="onFieldChange($event, 'status')"
                @error="onFieldError"/>
        </template>
    </filter-table>
</template>

<script>
import FilterTable from "~/components/layout/filter-table/FilterTable";
import BasicSelect from "~/components/general/BasicSelect";

export default {
    name: "StoreIndex",
    components: {BasicSelect, FilterTable},
    methods: {
        onFieldError(event) {
            console.log(event);
            this.$refs.storesTable.resetTableRow(event);
        },
        onFieldChange(event, field) {
            console.log(event, field);
            if (event.hasOwnProperties(['id', field])) {
                this.$refs.storesTable.setTableRow(event);
            }
        },
    }
}
</script>

<style scoped>

</style>
