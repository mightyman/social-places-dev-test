<template>
    <div>
        <span v-if="label" :style="this.internalLabelStyle" :class="this.labelClass">{{ label }}</span>
        <vue-file-agent
            ref="uploader"
            v-model="files"
            :accept="accept"
            :disabled="disabled || loading"
            :max-size="maxSize"
            :multiple="multiple"
            :theme="'list'"
            class="vue-file-agent"
            style="position: relative"
            @select="onFileSelected"
            @upload:error="onUploadError">
            <template #before-outer>
                <slot/>
            </template>
            <template #file-preview="{ fileRecord, index }">
                <upload-item
                    :disable-move="!sortable"
                    :file-record="fileRecord"
                    :include-other-name="alternativeFileNames"
                    :index="index"
                    :number-of-files="numberOfFiles"
                    :selected="multiple ? index === selectedIndex : false"
                    :show-preview="showPreviews"
                    @move:file:up="moveFile($event, $event - 1)"
                    @move:file:down="moveFile($event, $event + 1)"
                    @remove:file="removeFile"
                    @preview:file="previewFile"/>
            </template>
            <template #after-outer>
                <div>
                    <div class="drop-help-text">
                        <slot name="after-outer-drop-help-text">
                            <p>Drop here</p>
                        </slot>
                    </div>
                </div>
            </template>
            <template #file-preview-new>
                <v-card key="new" elevation="0" @click="selectFiles">
                    <v-card-subtitle>
                        <slot name="file-preview-new-subtitle"/>
                    </v-card-subtitle>
                    <v-card-text :style="{'color': uploadError ? 'var(--v-error-base)': null, 'text-align': 'center'}">
                        <slot :uploadError="uploadError" name="file-preview-new-icon">
                            <v-icon :color="uploadError ? 'error' : null" size="60px">fas fa-cloud-upload-alt</v-icon>
                        </slot>
                        <br/>
                        <slot name="file-preview-new-upload-text"></slot>
                        <div v-if="uploadError" v-html="uploadError"/>
                    </v-card-text>
                </v-card>
            </template>
        </vue-file-agent>
    </div>
</template>
<script>
import Vue from 'vue';
import VueFileAgent from 'vue-file-agent';
import UploadItem from './components/UploadItem';
import httpClient from '~/classes/httpClient';

Vue.use(VueFileAgent);
export default {
    name: 'MediaUploader',
    components: {UploadItem},
    props: {
        value: {
            required: true,
            type: Array | Object,
        },
        multiple: {
            type: Boolean,
            default: false,
        },
        sortable: {
            type: Boolean,
            default: false,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        accept: {
            type: String,
            default: '',
        },
        alternativeFileNames: {
            type: Boolean,
            default: false,
        },
        folder: {
            type: String | null,
            default: undefined,
        },
        showPreviews: {
            type: Boolean,
            default: false,
        },
        platform: {
            type: String | Number,
            default: null,
        },
        contentType: {
            type: String | Number,
            default: null,
        },
        mediaType: {
            type: String | Number,
            default: null,
        },
        maxSize: {
            type: String,
            default: null,
        },
        minDimension: {
            type: String,
            default: null,
        },
        maxDimension: {
            type: String,
            default: null,
        },
        aspectRatio: {
            type: String,
            default: null,
        },
        minSize: {
            type: String,
            default: null,
        },
        noMedia: {
            type: Boolean,
            default: false,
        },
        label: {
            type: String,
        },
        labelClass: {
            type: String,
        },
        labelStyle: {
            type: Object,
            default: () => {
            }
        },
        uploadFolderUrl: {
            type: String | null,
            required: true
        },
        uploadUrl: {
            type: String
        }

    },
    async created() {
        if (this.folder === undefined) {
            this.loading = true;
            const {data: {folder}} = await httpClient.get(this.uploadFolderUrl);
            this.uploadFolder = folder;
            this.loading = false;
        }
    },
    data() {
        return {
            loading: false,
            uploadFolder: this.folder ?? null,
            uploadError: null,
            selectedIndex: null,
            files: this.value,
            fileRecordsForUpload: [],
        };
    },
    watch: {
        folder() {
            this.uploadFolder = this.folder;
            this.loading = false;
        },
        uploadFolder() {
            this.$emit('uploadFolder', this.uploadFolder);
        },
        value(newValue) {
            this.files = newValue;
        },
    },
    computed: {
        numberOfFiles() {
            return this.multiple ? this.files.length : 1;
        },
        uploadHeaders() {
            let headers = {};
            if (this.platform) {
                headers['X-Platform'] = this.platform;
            }
            if (this.contentType) {
                headers['X-Content-Type'] = this.contentType;
            }
            if (this.mediaType) {
                headers['X-Media-Type'] = this.mediaType;
            }
            headers['X-Folder'] = this.uploadFolder;
            return headers;
        },
        internalMinDimension() {
            return this.convertDimension(this.minDimension);
        },
        internalMaxDimension() {
            return this.convertDimension(this.maxDimension);
        },
        internalAspectRatio() {
            return this.calculateAspectRatio(this.aspectRatio, ':');
        },
        internalMinSize() {
            return this.minSize ? this.getSizeParsed(this.minSize) : null;
        },
        internalLabelStyle() {
            if (this.labelStyle) {
                return this.labelStyle;
            }

            return {
                'color': this.$vuetify.theme.dark ? '#FFFFFF99' : '#00000099',
                'font-size': '12px',
                'margin-left': '8px'
            };
        }
    },
    methods: {
        convertDimension(input) {
            if (!input) {
                return null;
            }

            let items = input.toLowerCase().replace(/px/g, '').split('x');
            if (items.length > 1) {
                return {
                    width: parseInt(items[0]),
                    height: parseInt(items[1]),
                }
            }

            return {
                width: parseInt(items[0]),
                height: parseInt(items[0]),
            }
        },
        calculateAspectRatio(input, sep = 'x') {
            if (!input) {
                return null;
            }

            let items = [];
            if (typeof input === 'object' && !Array.isArray(input)) {
                items = [
                    input.width,
                    input.height,
                ];
            } else {
                if (typeof input === 'string') {
                    items = input.split(sep);
                }
            }
            if (items.length === 1) {
                return parseFloat(items[0]);
            }

            return parseFloat(items[0]) / parseFloat(items[1]);
        },
        selectFiles() {
            this.$emit('upload:select');
            this.$refs.uploader.$refs.fileInput.click();
        },
        removeFile(index) {
            this.$emit('file:delete', index);
            if (this.multiple) {
                this.files.splice(index, 1);
            } else {
                this.files = null;
            }
            if (this.selectedIndex === index) {
                this.selectedIndex = null;
            }
            this.$emit('input', this.files);
        },
        previewFile({src, isImage, index}) {
            this.$emit('file:preview', {src, isImage, index});
            this.selectedIndex = index;
        },
        moveFile(index, toIndex) {
            this.$emit('file:move', {index, toIndex});
            if (toIndex < 0 || toIndex > (this.files.length - 1)) {
                return;
            }

            let item = this.files[index];
            this.files.splice(index, 1);
            this.files.splice(toIndex, 0, item);
            if (this.selectedIndex === index) {
                this.selectedIndex = toIndex;
            }
            this.$emit('input', this.files);
        },
        uploadFiles() {
            if (this.fileRecordsForUpload.length === 0) {
                return;
            }
            this.$refs.uploader.upload(this.uploadUrl, this.uploadHeaders, this.fileRecordsForUpload).then(response => {
                    let inc = 0;
                    for (let fileRecord of this.fileRecordsForUpload) {
                        fileRecord.tempSrc = response[inc++].data.uploadedFiles[0].tempName;
                    }
                    this.fileRecordsForUpload = [];
                    const changedFields = [];
                    let fileRecords = this.$refs.uploader.fileRecords;
                    for (let fileRecord of fileRecords) {
                        changedFields.push({
                            ...fileRecord,
                            tempSrc: fileRecord?.raw?.tempSrc,
                            url: fileRecord?.urlValue ?? '',
                            mimeType: fileRecord?.raw?.type,
                            originalFileName: fileRecord.name(),
                        });
                    }
                    this.$emit('upload:inProgress', false);
                    this.$emit('upload:complete');
                    this.$emit('input', changedFields);
                },
            );
        },
        onFileSelected(fileRecordsNewlySelected) {
            const validateFile = (file) => {
                if (file.hasOwnProperty('dimensions')) {
                    if (this.internalMinDimension !== null) {
                        if (this.internalMinDimension.width > file.dimensions.width || this.internalMinDimension.height > file.dimensions.height) {
                            return false;
                        }
                    }
                    if (this.internalMaxDimension !== null) {
                        if (this.internalMaxDimension.width < file.dimensions.width || this.internalMaxDimension.height < file.dimensions.height) {
                            return false;
                        }
                    }
                    if (this.internalAspectRatio !== null) {
                        const tolerance = 0.05; // 5%
                        let deviation = this.internalAspectRatio * (tolerance / 2);
                        let fileAspectRatio = this.calculateAspectRatio(file.dimensions);

                        if (Math.abs(this.internalAspectRatio - fileAspectRatio) > deviation) {

                        }
                    }
                }

                if (this.internalMinSize > file.size) {
                    return false;
                }
                if (!file.error) {
                    return true;
                }
                return !Object.keys(file.error).some(key => file.error[key] === true);
            };
            const validFileRecords = fileRecordsNewlySelected.filter(validateFile);
            this.fileRecordsForUpload = this.fileRecordsForUpload.concat(
                validFileRecords,
            );

            let difference = Math.abs(validFileRecords.length - fileRecordsNewlySelected.length);
            if (difference !== 0) {
                if (difference > 1) {
                    // Snackbar().error('Unable to upload some your files');
                } else {
                    // Snackbar().error('Unable to upload your file');
                }
                if (Array.isArray(this.files)) {
                    this.files = this.files.filter(validateFile);
                }
            }
            this.$emit('upload:inProgress', true);
            if (validFileRecords.length > 0) {
                let firstFile = validFileRecords[0];
                this.previewFile({src: firstFile?.src(), isImage: firstFile.file.type.indexOf('image/') === 0, index: 0});
                this.$emit('upload:valid-files', validFileRecords);
                this.uploadFiles();
            } else {
                this.$emit('upload:valid-files', null);
            }
        },
        onUploadError(errors) {
            this.$emit('upload:inProgress', false);
            this.$emit('upload:error', errors);
            if (errors.length === 0) {
                return;
            }
            const error = errors[0];
            // displayErrorMessages(error);
        },
        validate() {
            return true;
        },
        getSizeParsed(size) {
            size = ('' + size).toUpperCase();
            let matches = size.match(/([\d|.]+?)\s*?([A-Z]+)/);
            let sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            if (!matches) {
                return parseFloat(size);
            }
            let i = sizes.indexOf(matches[2]);
            if (i === -1) {
                return parseFloat(size);
            }
            return parseFloat(matches[1]) * Math.pow(1024, i);
        }
    },
};
</script>
<style scoped>
.vue-file-agent ::v-deep .file-input {
    display: none
}

.drop-help-text {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    margin: 2px;
    background: rgba(255, 255, 255, 0.75);
    z-index: 1200;
    font-size: 32px;
    font-weight: bold;
    color: #888888;
    align-items: center;
    justify-content: center;
    display: none;
}

.theme--dark .drop-help-text {
    background: rgba(18, 18, 18, 0.75);
    color: white;
}

.is-drag-over .drop-help-text {
    display: flex;
}

.theme--dark.v-card ::v-deep .v-card__subtitle {
    color: rgba(255, 255, 255, 0.7);
}

.theme--light.v-card ::v-deep .v-card__subtitle {
    color: rgba(0, 0, 0, 0.6);
}
</style>
