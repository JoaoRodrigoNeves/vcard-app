import axios from 'axios'
import { io } from 'socket.io-client'
import Toast from 'primevue/toast';
import ToastService from 'primevue/toastservice';
import './assets/generics.css'
import "primeflex/primeflex.css";
import "primevue/resources/themes/lara-light-teal/theme.css";
import "primevue/resources/primevue.min.css";
import "primeicons/primeicons.css";
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config';

import App from './App.vue'
import router from './router'
import Tooltip from 'primevue/tooltip';
import ConfirmationService from 'primevue/confirmationservice';
import ConfirmDialog from 'primevue/confirmdialog';

const app = createApp(App)

const apiDomain = import.meta.env.VITE_API_DOMAIN
const wsConnection = import.meta.env.VITE_WS_CONNECTION

app.use(createPinia())
app.use(router)
app.use(PrimeVue);
app.use(ToastService);

app.component('Toast', Toast);
app.component('ConfirmDialog', ConfirmDialog);

app.directive('tooltip', Tooltip);
app.use(ConfirmationService);

app.provide('serverBaseUrl', apiDomain)
app.provide('socket', io(wsConnection))
app.provide(
    'axios',
    axios.create({
        baseURL: apiDomain + '/api',
        headers: {
            'Content-type': 'application/json'
        }
    })
)

app.mount('#app')
