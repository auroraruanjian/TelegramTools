import Vue from 'vue'
import router from './router/web.js'
import store from './store'

require('@/utils/String.js');

window.router = router;

import ElementUI from 'element-ui';
// import 'element-ui/lib/theme-chalk/index.css';
Vue.use(ElementUI);

import VueSocketIO from 'vue-socket.io'
import SocketIO from "socket.io-client"

Vue.use(new VueSocketIO({
    debug: true,
    connection: SocketIO('ws://frontend-api.laravel_admin.me',{
        path : '/socket.io',
        transports : ['websocket'],//'polling',
        autoConnect : false,
        // query:{
        //
        // }
    }),
    vuex: {
        store,
        actionPrefix: 'SOCKET_',
        mutationPrefix: 'SOCKET_'
    },
    /*
    options: {
        path : '/socket.io',
        transports : ['websocket'],//'polling',
        autoConnect : false
    }
    */
}))


import App from './views-web/App.vue'

Vue.config.productionTip = false

new Vue({
    router,
    store,
    render: h => h(App)
}).$mount('#app')
