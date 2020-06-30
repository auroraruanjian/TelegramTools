<template>
    <div class="app-wrapper layout">
        <toolbar ></toolbar>

        <div class="container container-index">
            <el-menu
                :default-active="activeIndex"
                class="el-menu-demo"
                mode="horizontal"
                @select="handleSelect"
                background-color="#545c64"
                text-color="#fff"
                active-text-color="#ffd04b">
                <el-menu-item index="1">账户管理</el-menu-item>
                <el-menu-item index="2">任务管理</el-menu-item>
<!--                <el-menu-item index="3">信息搜集</el-menu-item>-->
                <el-menu-item index="4">报表记录</el-menu-item>
                <el-submenu index="5">
                    <template slot="title">我的工作台</template>
                    <el-menu-item index="2-1">选项1</el-menu-item>
                    <el-menu-item index="2-2">选项2</el-menu-item>
                    <el-menu-item index="2-3">选项3</el-menu-item>
                    <el-submenu index="2-4">
                        <template slot="title">选项4</template>
                        <el-menu-item index="2-4-1">选项1</el-menu-item>
                        <el-menu-item index="2-4-2">选项2</el-menu-item>
                        <el-menu-item index="2-4-3">选项3</el-menu-item>
                    </el-submenu>
                </el-submenu>
            </el-menu>

            <section class="app-main">
                <transition name="fade-transform" mode="out-in">
                    <router-view :key="key" />
                </transition>
            </section>
        </div>
    </div>
</template>

<script>
    import toolbar from './toolbar.vue';

    export default {
        name: "index",
        components: {
            toolbar,
        },
        computed: {
            key() {
                return this.$route.path
            }
        },
        data(){
            console.log(this.$router,this.$route);
            let activeIndex = "1";
            if( this.$route.path == '/telegram/task' ){
                activeIndex = "2";
            }
            return {
                activeIndex:activeIndex,
            };
        },
        methods:{
            handleSelect(key, keyPath) {
                if( key == 1 ){
                    this.$router.push({path:'/telegram/list'});
                }else if( key == 2 ){
                    this.$router.push({path:'/telegram/task'});
                }else if( key == 3 ){
                    this.$router.push({path:'/telegram/scan'});
                }
                console.log(key, keyPath);
            }
        },
        /*
        sockets:{
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
        },
        */
        created(){
            //console.log('layout');
            //this.$socket.open();
        }
    }
</script>

<style lang="scss" >
    .layout {
        min-height: 100%;
        min-width: 1240px;
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
    }
    .container {
        width: 1200px;
        margin: 0 auto;
    }
    .container-index{
        margin-top:50px;
    }
    .app-main{
        margin-top: 36px;
    }
</style>
