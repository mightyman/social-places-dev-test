<template>
    <v-card
        :elevation="flat ? 0 : (selected ? 10 : 2)"
        class="mb-2 elevation-changes">
        <v-card-text style="display: flex; width: 100%">
            <div class="mr-3" style="max-width: 86px; height: 62px; cursor: pointer;">
                <div :style="{'background': fileRecord.icon().color}" class="d-flex icon-holder">
                    <v-scale-transition origin="center">
                        <div
                            v-if="fileRecord.progressInternal !== 0 && fileRecord.progressInternal !== 100"
                            class="loading-holder">
                            <v-progress-circular
                                v-model="fileRecord.progressInternal"
                                color="white"
                                size="40"/>
                        </div>
                    </v-scale-transition>
                    <svg v-if="!showPreview || !isImage" style="width: 45px; fill: white" viewBox="0 0 100 100">
                        <template v-for="(d, index) in fileRecord.icon().paths">
                            <path v-if="d" :key="index" :d="d"/>
                        </template>
                    </svg>
                    <v-img v-if="showPreview && isImage" :src="fileRecord.src()" width="45px"/>
                </div>
            </div>
            <div class="d-flex" style="flex-direction: column; width: 100%" :style="{'justify-content': includeOtherName ? '': 'center'}">
                <span style="text-align: left; word-break: break-word">{{ fileRecord.name() }}</span>
                <v-text-field
                    v-if="includeOtherName"
                    v-model="fileRecord.raw.otherName"
                    :label="`File ${index + 1} Name`"
                    :placeholder="fileRecord.name().replace('.' + fileRecord.ext(), '').toLowerCase().ucwords()"
                    class="mt-4"
                    color="primary"
                    dense
                    hide-details
                    persistent-placeholder/>
            </div>
            <v-row no-gutters>
                <v-col :cols="disableMove ? 12 : 6">
                </v-col>
                <v-col cols="6" v-if="!disableMove">
                    <v-btn
                        :disabled="index === 0"
                        color="accent"
                        icon
                        style="width: 100%; height: 100%"
                        tile
                        x-small
                        @click="$emit('move:file:up',index)">
                        <v-icon>fas fa-chevron-up</v-icon>
                    </v-btn>
                </v-col>
                <v-col :cols="disableMove ? 12 : 6">
                    <v-btn
                        color="error"
                        icon
                        style="width: 100%; height: 100%"
                        tile
                        x-small
                        @click="$emit('remove:file',index)">
                        <v-icon>fas fa-trash</v-icon>
                    </v-btn>
                </v-col>
                <v-col cols="6" v-if="!disableMove">
                    <v-btn
                        :disabled="(numberOfFiles - 1) === index"
                        color="accent"
                        icon
                        style="width: 100%; height: 100%"
                        tile
                        x-small
                        @click="$emit('move:file:down',index)">
                        <v-icon>fas fa-chevron-down</v-icon>
                    </v-btn>
                </v-col>
            </v-row>
        </v-card-text>
    </v-card>
</template>

<script>

import httpClient from "~/classes/httpClient";

export default {
    name: "UploadItem",
    props: {
        index: {
            type: Number,
            required: true
        },
        selected: {
            type: Boolean,
            default: false
        },
        numberOfFiles: {
            type: Number,
            required: true
        },
        fileRecord: {
            type: Object,
            validator: (v) => {
                return v.hasOwnProperties(['raw', 'file']);
            }
        },
        disableMove: {
            type: Boolean,
            default: false
        },
        includeOtherName: {
            type: Boolean,
            default: true
        },
        flat: {
            type: Boolean,
            default: false
        },
        showPreview: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            isDownloading: false,
        }
    },
    computed: {
        isImage() {
            return this.fileRecord?.file?.type?.indexOf('image/') === 0;
        }
    },
}
</script>

<style scoped>
.icon-holder {
    position:relative;;
    width: 62px;
    height: 100%;
    justify-content: center;
    border-radius: 6px;
    overflow:hidden;
}

.elevation-changes {
    transition: box-shadow 450ms;
}

.loading-holder {
    position: absolute;
    display: flex;
    justify-content: center;
    align-items: center;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    overflow: hidden;
    background: rgba(0, 0, 0, 0.4);
    z-index: 100;
}
</style>
