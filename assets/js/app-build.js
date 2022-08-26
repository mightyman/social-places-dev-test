import Vue from 'vue';
import App from './App';
import router from './router';
import Vuetify from 'vuetify';
import '../css/app-build.scss';
import vuetify from './plugins/vuetify';
import httpClient from './classes/httpClient';
import ServerBus from './classes/ServerBus';
import {loadComponent} from './helpers/build-functions';
import {DateTime} from 'luxon';
import {isDevelopment} from '~/helpers/value-helpers';
// import {GlobalNetworkService} from '~/services/app/GlobalService';
import './helpers/array-helper';
import './helpers/string-helper';
import './helpers/object-helper';
import './helpers/formdata-helper';

window.luxon = DateTime;


/**
 * Components
 */
let requireComponent = require.context(
    './components',
    true,
    /[A-Z]\w+\.(vue|js)$/,
    'lazy',
);

export const serverBus = new ServerBus();

// serverBus.enableLogging();

Vue.use(Vuetify);

requireComponent.keys().forEach(fileName => {
    loadComponent(fileName, './components/');
});


Vue.config.errorHandler = function (err, vm, info) {
    if (isDevelopment()) {
        console.error(err);
    } else {
        // GlobalNetworkService().postLogException(err, info);
    }
};
const app = new Vue({
    vuetify,
    httpClient,
    router,
    render: h => h(App),
    el: '#app',
}).$mount('#app');
