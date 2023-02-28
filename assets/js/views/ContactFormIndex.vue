<template>
    <div class="page-content">
        <div class="welcome-column d-flex align-center">
            <div class="welcome-message">
                <h1>Contact us</h1>
                <h2>Contact our stores from the contact form..</h2>
                <br>
                <v-btn color="primary" href="/login" class="btn btn-primary">
                   Login
                </v-btn>  
            </div>
        </div>

        <div style="justify-content: center; display: flex; flex-grow: 1; ">
            <div>
                <div id="outer-holder" class="d-flex justify-center">
                    <div id="holder">
                        <div id="lp">
                            <span>Send us a Message..</span>                            
                        </div>
                    </div>
                </div>

                <v-card id="contactFormSection" class="contact-form-block px-5 py-3 mt-4">
                    <v-card-text>
                        <v-form v-model="contactForm" ref="contactForm">
                           
                            <v-select
                                v-model="form.store_id"
                                :items="stores"
                                item-text="text"
                                item-value="value"
                                label="Select Store"/>
                            <v-text-field
                                v-model="form.firstname"
                                :disabled="loading"
                                :error-messages="contactFormError"
                                :rules="[isRequired]"
                                label="First name"
                                validate-on-blur
                                @input="contactFormError = null"
                                @keydown.enter="contactFormSubmit"/>
                            <v-text-field
                                v-model="form.lastname"
                                :disabled="loading"
                                :error-messages="contactFormError"
                                :rules="[isRequired]"
                                label=":Last name"
                                validate-on-blur
                                @input="contactFormError = null"
                                @keydown.enter="contactFormSubmit"/>   
                            <v-text-field
                                v-model="form.email"
                                :disabled="loading"
                                :error-messages="contactFormError"
                                :rules="[isRequired]"
                                label=":Email Address"
                                validate-on-blur
                                @input="contactFormError = null"
                                @keydown.enter="contactFormSubmit"/>  
                                
                            <v-textarea
                                v-model="form.message"
                                :disabled="loading"
                                :error-messages="contactFormError"
                                :rules="[isRequired]"
                                label=":Message"
                                validate-on-blur
                                @input="contactFormError = null"
                                @keydown.enter="contactFormSubmit"/>      
                            
                        </v-form>
                    </v-card-text>
                    <div class="d-flex justify-end">
                        <v-btn :loading="loading" color="primary" text @click="contactFormSubmit">Submit</v-btn>
                    </div>
                </v-card>
            </div>
        </div>
    </div>
</template>

<script>
import {validationRulesMixin} from "~/mixins/validation-rules-mixin";
import httpClient from "~/classes/httpClient";

export default {
    name: "ContactFormIndex",
    mixins: [validationRulesMixin],
    data() {
        return {
            form: {
                firstname: '',
                lastname: '',
                email:'',
                message:'',
                store_id:'',
            },
            contactForm: true,
            loading: false,
            contactFormError: null,
            stores:[],
            }
            
    },
    mounted()
    {
        this.fetchRoles();
    },
    methods: {
        async fetchRoles() {
        try {
            const response = await httpClient.axiosClient.get('api/stores/index');
            console.log(response.data.data);
            const stores = response.data.data.map(store => ({
            value: store.id,
            text: store.name,
            }));            
            this.stores = stores;
        } catch (error) {
            console.error(error);
        }
        },
        async contactFormSubmit() {
            console.log(this.form);
            this.loading = true;
            if (!this.$refs.contactForm.validate()) {
                this.loading = false;
                return;
            }
            try {                
                await httpClient.axiosClient.post('api/contact-forms/create', this.form);
            } catch (error) {
                this.contactFormError = error.response.data.error;
            } finally {
                this.loading = false;
            }
        },
    },
}
</script>

<style lang="scss" scoped>
.page-content {
    height: 100%;
    display: flex;
    align-items: center;
}

.welcome-message {
    color: white;
    margin: 0 5rem;
    position: relative;

    h1 {
        font-weight: 400;
        font-size: 50px;
        margin-bottom: 1rem;
    }

    h2 {
        font-weight: 400;
        font-size: 30px;
    }
}

.welcome-message:before {
    content: "";
    position: absolute;
    background: inherit;
    z-index: -1;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    box-shadow: inset 0 0 2000px rgba(255, 255, 255, .5);
    filter: blur(10px);
    margin: -20px;
}

.welcome-column {
    background: #2A2342;
    width: 500px;
    height: 100%;
    font-family: "Silka";
}

.contact-form-block {
    width: 500px;
}

#holder {
    transform: scale(7);
    animation: ease-in-out zoom-out 2s forwards;
    position: relative;
    top: 0;
    width: 444px
}

#outer-holder {
    position: relative;
    height: 120px;
    padding-top: 20px;
    z-index: 3;
}

#contactFormSection {
    opacity: 1;
    z-index: 1;
}

#contactFormSection:not(.hide) {
    animation: show 2s forwards;
}

#contactFormSection.hide {
    animation: hide 120ms forwards;
}

#lp, #rp {
    width: 400px;
    overflow: hidden;
    height: 75px;
    position: relative;
    animation-duration: 600ms;
    animation-timing-function: linear;
    animation-fill-mode: forwards;
    display: inline-block;
}

#lp > span, #rp > span {
    color: #2a2342;
    font-size: 30px;
    position: absolute;
    white-space: nowrap;
    font-family: "Silka";
    animation-duration: 1150ms;
    animation-timing-function: linear;
    animation-fill-mode: forwards;
}

.action {
    #lp > span, #rp > span {
        animation-fill-mode: backwards;
    }
}

#lp > span {
    left: 0;
    animation-name: out-left;
}

#rp > span {
    //padding-right: 0px;
    right: 0;
    animation-name: out-right;
}

#lp > img, #rp > img {
    position: absolute;
    width: 37px;
    height: 75px;
}

#lp > img {
    right: 0;
}

#rp > img {
    left: 0;
}

#lp {
    animation-name: split-up;
    top: -18.75px;
}

#rp {
    animation-name: split-down;
    top: 18.75px;
}

@keyframes zoom-out {
    0% {
        transform: scale(10);
        top: 120px;
    }
    16.6% {
        transform: scale(1);
    }
    /*Waiting for the other animations to end*/
    83% {
        top: 120px;
    }
    100% {
        transform: scale(1);
        top: 0
    }
}

@keyframes split-up {
    0%, 83.3% {
        top: 0;
    }
    100% {
        top: -18.75px;
    }
}

@keyframes split-down {
    0%, 83.3% {
        top: 0;
    }
    100% {
        top: 18.75px;
    }
}

@keyframes out-left {
    0%, 65.2% {
        left: 183px;
    }
    100% {
        left: 0;
    }
}

@keyframes out-right {
    0%, 65.2% {
        right: 183px;
    }
    100% {
        right: 0;
    }
}

@keyframes show {
    0% {
        opacity: 0;
    }
    94% {
        opacity: 0;
    }
    100% {
        opacity: 1;
    }
}

@keyframes hide {
    0% {
        opacity: 1;
    }
    100% {
        opacity: 0;
    }
}
</style>
