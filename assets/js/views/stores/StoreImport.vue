<template>
    <v-container>
        <v-row>
            <v-col cols="8">
                <v-card>
                    <v-card-title>
                        Import store information
                    </v-card-title>
                    <v-card-text>
                        <media-uploader
                            v-model="files"
                            accept=".xlsx"
                            upload-folder-url="api_stores_temporary_folder"
                            upload-url="/api/stores/import/upload"
                            @uploadFolder="folder = $event">
                            <template #file-preview-new-upload-text>
                                Click or drag to upload a new import
                            </template>
                        </media-uploader>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer/>
                        <v-btn
                            :disabled="processing"
                            text
                            @click="$router.push({name: 'Stores Index'})">Cancel
                        </v-btn>
                        <v-btn
                            :loading="processing"
                            color="primary"
                            @click="process">Process
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import MediaUploader from "~/components/general/Uploader/MediaUploader";
import httpClient from "~/classes/httpClient";
export default {
    name: "StoreImport",
    components: {MediaUploader},
    data() {
        return {
            files: [],
            processing: false,
            folder: null,
        }
    },
    methods: {
        process() {
            this.processing = true;

            let file = this.files[0];
            httpClient.processExportDownload('api_store_process_import', {
                folder: this.folder,
                fileName: file.tempSrc
            }).finally(() => {this.processing = false});
        }
    }
}
</script>

<style scoped>

</style>
