<template>
    <filter-table
        id="user"
        create-route="Users Create"
        entity-label="User"
        filter-url="api_users_filters"
        table-api-url="api_users">
        <template #item.fullname="{item}">
            <div class="d-flex flex-column">
                <span>{{ item.fullname }}</span>
                <a :href="`mailto:${item.username}`" style="font-size:smaller; font-style: italic; text-decoration: none">{{ item.username }}</a>
            </div>
        </template>
        <template #item.actions="{item}">
            <bit-btn
                :tooltip="`Edit ${item.fullname}`"
                color="primary"
                icon="fas fa-edit"
                @click="$router.push({name: 'Users Edit', params: {id: item.id}})"/>
        </template>
    </filter-table>
</template>

<script>
import FilterTable from "~/components/layout/filter-table/FilterTable";
import BitBtn from "~/components/general/BitBtn";

export default {
    name: "UserIndex",
    components: {BitBtn, FilterTable},
}
</script>

<style scoped>

</style>
