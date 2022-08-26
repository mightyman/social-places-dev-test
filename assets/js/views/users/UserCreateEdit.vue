<template>
    <v-container>
        <v-row>
            <v-col cols="8">
                <v-card :loading="loading">
                    <v-card-title>
                        {{ isEditRoute ? 'Edit' : 'Create' }} User
                    </v-card-title>
                    <v-card-text>
                        <v-form>
                            <span class="text-subtitle-2">Contact Details</span>
                            <v-row>
                                <v-col cols="12" md="6">
                                    <u-text-field
                                        v-model="user.username"
                                        :item-id="userIdentifier"
                                        :readonly="loading || saving"
                                        :rules="[isRequired, isEmail]"
                                        check-url="api_user_unique_email"
                                        dense
                                        label="Email"
                                        validate-on-blur
                                        @paste="pastePlainText"/>
                                </v-col>
                            </v-row>
                            <v-row>
                                <v-col cols="12" lg="6">
                                    <v-text-field
                                        v-model="user.name"
                                        :error-messages="errors.name"
                                        :readonly="loading || saving"
                                        color="primary"
                                        dense
                                        label="Name"
                                        @input="delete errors.name"
                                        @paste="pastePlainText"/>
                                </v-col>
                                <v-col cols="12" lg="6">
                                    <v-text-field
                                        v-model="user.surname"
                                        :error-messages="errors.surname"
                                        :readonly="loading || saving"
                                        color="primary"
                                        dense
                                        label="Surname"
                                        @input="delete errors.surname"
                                        @paste="pastePlainText"/>
                                </v-col>
                            </v-row>
                            <span class="text-subtitle-2">Access & Affiliation</span>
                            <v-row>
                                <v-col cols="12" md="6">
                                    <url-autocomplete
                                        url="api_users_primary_roles"
                                        label="Primary Role"
                                        :rules="[isRequired]"
                                        :loading.sync="loadingItems.primaryRole"
                                        dense
                                        v-model="user.primaryRole"/>
                                </v-col>
                            </v-row>
                        </v-form>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer/>
                        <v-btn
                            :disabled="loading || saving"
                            text
                            @click="$router.push({name: 'Users Index'})">Cancel
                        </v-btn>
                        <v-btn
                            :disabled="loading"
                            :loading="saving"
                            color="primary"
                            @click="save">Save
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import UTextField from "~/components/general/UTextField";
import {validationRulesMixin} from "~/mixins/validation-rules-mixin";
import {pastePlainTextMixin} from "~/mixins/paste-plain-text-mixin";
import httpClient from "~/classes/httpClient";
import UrlAutocomplete from "~/components/general/UrlAutocomplete";

export default {
    name: "UserCreateEdit",
    components: {UrlAutocomplete, UTextField},
    mixins: [validationRulesMixin, pastePlainTextMixin],
    data() {
        return {
            user: {},
            errors: {},
            saving: false,
            loading: false,
            primaryRoles: [],
            loadingItems: {
                primaryRole: false
            }
        }
    },
    computed: {
        isEditRoute() {
            return this.$route.path.includes('edit');
        },
        userIdentifier() {
            return this.$route.params.id ?? null;
        },
    },
    mounted() {
        this.init();
    },
    watch: {
    },
    methods: {
        init() {
            this.loading = true;
            httpClient.get('api_user_information', this.getIdentifier('default')).then(({data: {user}}) => {
                this.user = user;
            }).finally(() => {
                this.loading = false;
            });


        },
        save() {
            this.saving = true;
            httpClient.post(this.userIdentifier ? 'api_user_edit' : 'api_user_create', this.user, {params: this.getIdentifier()}).then(({data: {user}}) => {
                if (!this.isEditRoute) {
                    this.$router.push({name: 'Users Edit', params: {id: user.id}})
                }
            }).finally(() => {
                this.saving = false;
            });
        },
        getIdentifier(defaultValue = null) {
            if (this.userIdentifier) {
                return {user: this.userIdentifier};
            }
            if (defaultValue) {
                return {user: defaultValue};
            }
            return {};
        },

    },
}
</script>

<style scoped>

</style>
