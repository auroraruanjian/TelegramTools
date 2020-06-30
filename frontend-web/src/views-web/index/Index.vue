<template>
    <div class="home">
        <img alt="Vue logo" src="../../assets/logo.png">
        <el-link type="primary" v-if="typeof username=='undefined' || username==null || username.length==0" tag="li" @click="$router.push({path:'\login'})">登录</el-link>
        <el-link type="primary" v-else @click="logout">退出</el-link>

        <div>
            <el-input v-model="message"></el-input>
            <el-button @click="sendMessage">发送消息</el-button>
        </div>
    </div>
</template>

<script>
    import { mapGetters,mapState } from 'vuex';
    import IO from "socket.io-client"

    export default {
        name: "Index",
        computed: {
            ...mapGetters(['username']),
        },
        data(){
            return {
                tg_login_status:'',
                message:'',
                updateInterval:'',
            };
        },
        methods:{
            logout(){
                this.$confirm('确认退出吗?', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    this.$store.dispatch('user/logout').then((response) => {
                        if (response.data.code == 1) {
                            this.$message(response.data.msg);
                        }
                        //this.$router.push({path:'/'});
                    }).catch((e) => {
                        console.log(e);
                    });
                }).catch(() => {});
            },
            /*
            websocket(){
                var wsServer = 'ws://frontend-api.laravel_admin.me/socket.io';
                var websocket = new WebSocket(wsServer);
                websocket.onopen = function (evt) {
                    console.log("Connected to WebSocket server.");
                };

                websocket.onclose = function (evt) {
                    console.log("Disconnected");
                };

                websocket.onmessage = function (evt) {
                    console.log(evt);
                    console.log('Retrieved data from server: ' + evt.data);
                };

                websocket.ontg_login = function (evt) {
                    console.log(evt);
                    console.log('Retrieved data from server: ' + evt.data);
                };

                websocket.onerror = function (evt, e) {
                    console.log('Error occured: ' + evt.data);
                };
            },
            */
            sendMessage(){
                if( this.tg_login_status != 'success'){
                    this.$socket.emit('telegram_login',this.message);
                }else{
                    this.$socket.emit('message',this.message);
                }
                this.message = '';
            }
        },
        created() {
            // this.websocket();
            // this.$socket.open();
            // console.log(this.$socket);

            // this.sockets.listener.subscribe('customEmit', (data) => {
            //     console.log(data);
            // });
            // this.sockets.subscribe('customEmit', (data) => {
            //     console.log(data);
            // });
        },
        sockets: {
            connect: function () {
                console.log('socket connected')
            },
            disconnect(){
                this.tg_login_status = false;
                console.log('socket 断开连接');
            },
            reconnect(){
                console.log('socket 重新连接');
            },
            telegram_login_status(data){
                this.tg_login_status = data.status;
            }
        },
        watch:{
            tg_login_status( val ){
                if( this.tg_login_status == 'success' ){
                    let _this = this;
                    this.updateInterval = setInterval(function(){
                        _this.$socket.emit('telegram_update','');
                    },30000)
                }else{
                    clearInterval(this.updateInterval );
                }
            }
        }
    }
</script>

<style scoped>

</style>
