<template>
    <div v-loading="dialogs_loading">
        <el-page-header @back="$router.back()" content="消息列表" style="margin-bottom: 20px;"></el-page-header>
        <el-row :gutter="20" >
            <el-col :span="8" >
                <el-card class="box-card">
                    <div slot="header" class="clearfix">
                        <span>消息列表</span>
<!--                        <el-button style="float: right; padding: 3px 0" type="text">操作按钮</el-button>-->
                        <el-dropdown style="float: right;color: #409eff;cursor: pointer;" trigger="click" @command="leftMenuChange">
                            <span class="el-dropdown-link" >
                                操作<i class="el-icon-arrow-down el-icon--right"></i>
                            </span>
                            <el-dropdown-menu slot="dropdown" >
                                <el-dropdown-item command="seachGroups">查找群</el-dropdown-item>
                                <el-dropdown-item command="collectUser" @click="collect_user_model=true">用户抓取</el-dropdown-item>
                            </el-dropdown-menu>
                        </el-dropdown>
                    </div>
                    <el-row style="text-align: right;background: #ececec;padding: 5px 6px;border-radius: 5px;margin: 0 auto;" v-if="collect_user_model">
                        <el-checkbox style="float: left;margin-top: 7px;" :indeterminate="isIndeterminate" v-model="checkAll" @change="handleCheckAllChange">{{ !checkAll?'全选':'反选' }}</el-checkbox>
                        <el-button type="success" size="mini" @click="collectUser">确定</el-button>
                        <el-button type="danger" size="mini" @click="collect_user_model=false">取消</el-button>
                    </el-row>
                    <el-scrollbar>
<!--                        <el-checkbox-group v-model="collect_user" @change="handleCollectUserChange" >-->
                            <div v-for="(item,key) in dialogs" :key="key" @click="getMessages(item)" class="text item" v-if="!(typeof item.dialog_detail.Chat != 'undefined' && item.dialog_detail.Chat.deactivated)">
                                <template v-if="item.peer._=='peerUser'">
                                    <el-col :span="18" class="group_name" >
                                        {{ ((typeof (item.dialog_detail.User.first_name) != 'underfine' && item.dialog_detail.User.first_name != null) ? item.dialog_detail.User.first_name : '')  }} .
                                        {{ ((typeof (item.dialog_detail.User.last_name) != 'underfine' && item.dialog_detail.User.last_name != null) ? item.dialog_detail.User.last_name : '') }}
                                    </el-col>
<!--                                    <el-col :span="4" style="text-align: right;" v-if="item.unread_count>0"></el-col>-->
                                    <el-tag type="danger" v-if="item.unread_count>0"  style="float: right;margin-top: 3px;display: inline-block;">{{ item.unread_count }}</el-tag>
                                </template>
                                <template v-else-if="item.peer._=='peerChannel' || item.peer._== 'peerChat' ">
                                    <el-checkbox  v-if="collect_user_model" @click.stop style="float: left;width: 20px;" v-model="item.peer.status"></el-checkbox>
<!--                                    <input type="checkbox" :value="item.peer.channel_id" @click.stop></input>-->
                                    <el-col :span="18" class="group_name" style="padding-left: 0px;" >{{ item.dialog_detail.Chat.title }}</el-col>
<!--                                    <el-col :span="4" style="text-align: right;" v-if="item.unread_count>0"></el-col>-->
                                    <el-tag type="danger" v-if="item.unread_count>0" style="float: right;margin-top: 3px;display: inline-block;">{{ item.unread_count }}</el-tag>
                                </template>
                            </div>
<!--                        </el-checkbox-group>-->
                    </el-scrollbar>
                </el-card>
            </el-col>
            <el-col :span="16">
                <el-card shadow="always" v-loading="message_loading">
                    <div slot="header" class="clearfix" style="text-align: center;">
                        <template v-if="JSON.stringify(current_dialog) != '{}'">
                            <span v-if="current_dialog.peer._=='peerUser'">
                                {{ ((typeof (current_dialog.dialog_detail.User.first_name) != 'underfine' && current_dialog.dialog_detail.User.first_name != null) ? current_dialog.dialog_detail.User.first_name : '')  }} .
                                {{ ((typeof (current_dialog.dialog_detail.User.last_name) != 'underfine' && current_dialog.dialog_detail.User.last_name != null) ? current_dialog.dialog_detail.User.last_name : '') }}
                            </span>
                            <span v-else> {{ current_dialog.dialog_detail.Chat.title }}</span>
                        </template>
                        <!-- <el-button style="float: right; padding: 3px 0" type="text">操作按钮</el-button>-->
                        <el-dropdown style="float: right;color: #409eff;cursor: pointer;" trigger="click" @command="leftMenuChange">
                            <span class="el-dropdown-link" >
                                操作<i class="el-icon-arrow-down el-icon--right"></i>
                            </span>
                            <el-dropdown-menu slot="dropdown" >
<!--                                <el-dropdown-item command="seachGroups">用户</el-dropdown-item>-->
                                <!--<el-dropdown-item disabled>查找好友</el-dropdown-item>-->
                            </el-dropdown-menu>
                        </el-dropdown>
                    </div>
                    <el-scrollbar ref="chat_window">
                        <ul class="infinite-list" style="overflow:auto"  v-if="JSON.stringify(messages_list) != '{}'">
                            <li v-for="(item,key) in messages_list.messages" :key="key" class="infinite-list-item" @click="messageClick" :class="getCurrentMessageClass(item)">
                                <p v-html='item.message'></p>
                                <el-button size='mini' class="add_all_group" type="danger" @click.stop="addAllGroup(item)" v-if="hasGroupLink(item)">批量加群</el-button>
                            </li>
                        </ul>
                        <span v-else>点击左侧选择对应对话</span>
                    </el-scrollbar>
                    <el-form :inline="true" :model="messageForm" class="demo-form-inline" size="medium" style="height: 35px;margin-top: 15px;text-align: right;">
                        <el-form-item label="">
                            <el-input v-model="messageForm.message" placeholder="消息内容" style="width: 400px;" @keyup.enter.native="sendMessage" :disabled="send_form_disable"></el-input>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" @click="sendMessage" :loading="send_btn_loading" :disabled="send_form_disable">发送</el-button>
                        </el-form-item>
                    </el-form>
                </el-card>
            </el-col>
        </el-row>
    </div>
</template>

<script>
    import { mapGetters,mapState } from 'vuex';
    import { getDialogs,getMessage } from "../../api/telegram";

    export default {
        name: "telegram_manage",
        data(){
            return {
                id:this.$route.params.id,
                self_account_info:{},
                dialogs_loading:false,
                dialogs:[],
                current_dialog:{},
                message_loading:false,
                messages_list:{},
                tg_login_status:false,
                //operate:'seach_group',// seach_group:查找群  chat:聊天
                send_btn_loading:false,
                send_form_disable:false,
                messageForm:{
                    message:'',
                },
                checkAll:false,
                isIndeterminate:false,
                collect_user_model:false,
            };
        },
        computed: {
            ...mapGetters(['user_id']),
        },
        methods:{
            /**
             * 登录
             */
            login(){
                this.dialogs_loading = true;
                this.$socket.emit('telegram_login',{user_id:this.user_id,account_id:this.id});
            },
            /**
             * 获取所有会话
             */
            getDialogs(){
                //this.dialogs_loading = true;
                this.$socket.emit('telegram_get_dialogs',{account_id:this.id,get_photo:0});
                /*
                getDialogs({account_id:this.id,get_phone:0}).then(response=>{
                    this.dialogs_loading = false;
                    if( response.data.code == 1 ){
                        this.dialogs = response.data.data;
                    }else{
                        this.$message.error(response.data.msg);
                    }
                });
                */
            },
            /**
             * 获取会话最近100条消息内容
             */
            getMessages( item ){
                console.log(item);
                this.message_loading = true;
                this.current_dialog  = item;

                let limit = this.current_dialog.top_message - this.current_dialog.read_inbox_max_id;
                if( limit < 100 ){limit = 100;}
                limit = 100;

                this.$socket.emit('telegram_get_dialog_messages',{account_id:this.id,peer:item.peer,max_id:this.current_dialog.top_message,limit:limit});//dialog_detail.InputPeer
                /*
                getMessage({account_id:this.id,input_peer:item.dialog_detail.InputPeer}).then(response=>{
                    this.message_loading = false;
                    if( response.data.code == 1 ){
                        this.messages_list = response.data.data;
                    }else{
                        this.$message.error(response.data.msg);
                    }
                });
                */
            },
            /**
             * 搜索群
             */
            leftMenuChange( command ){
                console.log(command);
                // 查找群
                if( command == 'seachGroups' ) {
                    this.messages_list = {};
                    for( let i in this.dialogs){
                        if( this.dialogs[i].peer._ == 'peerUser' ){
                            if( this.dialogs[i].peer.user_id == '401234709' ){
                                this.getMessages(this.dialogs[i]);break;
                            }
                        }
                    }
                // 收集用户
                }else if( command == 'collectUser' ){
                    this.collect_user_model = true;
                    //
                }
            },
            /**
             */
            collectUser(){
                //console.log(this.collect_user,this.dialogs);
                let data = [];
                for(let i in this.dialogs){
                    if( this.dialogs[i].peer.status ){
                        data.push(this.dialogs[i].peer);
                    }
                }
                this.dialogs_loading = true;
                this.$socket.emit('telegram_collect_user',data);
            },
            /**
             * 加群
             */
            addAllGroup( item ){
                if( this.current_dialog.peer.user_id != 401234709 ) return;
                let groupList = [];
                var regexp = /:\/\/t.me\//;
                for(let i in item.entities){
                    if( typeof item.entities[i].url != 'undefined' && item.entities[i].url != null && regexp.test(item.entities[i].url) ){
                        groupList.push(item.entities[i].url);
                    }
                }
                this.$socket.emit('telegram_add_all_group',groupList);
                // console.log(item);
                // console.log(groupList);
            },
            /**
             * 是否有群链接添加批量加群按钮
             */
            hasGroupLink( item ){
                //console.log(item);
                var regexp = /:\/\/t.me\//;
                for(let i in item.entities){
                    if( typeof item.entities[i].url != 'undefined' && item.entities[i].url != null && regexp.test(item.entities[i].url)){
                        return true;
                    }
                }
                return false;
            },
            /**
             * 搜集群组所有用户
             */
            getGroupUsers(){
                // 活跃度，群组列表

            },
            /**
             * 发送消息
             */
            sendMessage(){
                console.log(this.current_dialog,this.current_dialog != {});
                if( JSON.stringify(this.current_dialog) == '{}' ){
                    let search_robot = '@hao1234bot';//@SuperIndex_Bot

                    this.$socket.emit('telegram_send_messages',{'peer':search_robot,'message':this.messageForm.message});
                }else{
                    this.$socket.emit('telegram_send_messages',{'peer':this.current_dialog.peer,'message':this.messageForm.message});
                }
                this.send_btn_loading = true;
                this.send_form_disable = true;
            },
            /**
             * 消息内容被点击
             */
            messageClick(event){
                console.log(event.target,event.target.dataset.link);
                if (event.target.nodeName === 'a' ) {
                    // 获取触发事件对象的属性
                }
            },
            getCurrentMessageClass(item){
                if( this.self_account_info.id == item.from_id ){
                    return 'right';
                }
            },
            /**
             * 聊天消息自动滑动到消息末尾
             */
            scrollDown() {
                setTimeout(()=>{
                    if( typeof this.$refs['chat_window'] != 'undefined' ){
                        this.$refs['chat_window'].wrap.scrollTop = this.$refs['chat_window'].wrap.scrollHeight
                    }
                },0);
            },
            handleCheckAllChange(val) {
                //console.log(val);
                for( let i in this.dialogs ){
                    if( this.dialogs[i].peer._ == 'peerChannel' ){
                        this.dialogs[i].peer.status = val;
                    }
                }
                this.isIndeterminate = false;
            },
            /*
            handleCollectUserChange(value) {
                console.log(this.dialogs,value);
                let checkedCount = value.length;
                let group_length = 0;
                for( let i in this.dialogs ){
                    if( this.dialogs[i].peer._ == 'peerChannel' ){
                        group_length++;
                    }
                }
                console.log(group_length);
                this.checkAll = checkedCount === group_length;
                this.isIndeterminate = checkedCount > 0 && checkedCount < group_length;
            }
            */
        },
        created() {
            this.$socket.io.opts.query = {
                user_id : this.user_id,
            };
            console.log(this.$socket);
            this.$socket.open();
        },
        sockets:{
            connect: function () {
                console.log('socket connected')
                this.login();
            },
            disconnect(){
                this.tg_login_status = false;
                this.self_account_info = {};
                console.log('socket 断开连接');
            },
            reconnect(){
                console.log('socket 重新连接');
            },
            telegram_login_status(data){
                this.tg_login_status = data.status;

                if( this.tg_login_status ){
                    this.self_account_info = data.self
                    this.getDialogs();
                }else{
                    //this.$socket.disconnect();
                }
            },
            get_dialogs(data){
                for(let i in data){
                    data[i].peer.status = false;
                }
                this.dialogs = data.reverse();
                this.dialogs_loading = false;
            },
            telegram_get_dialog_messages(data){
                this.send_btn_loading = false;
                this.message_loading = false;
                this.send_form_disable = false;
                this.messageForm.message = '';

                //console.log('running');
                for(let i in data.messages){
                    let cur_message = data.messages[i];
                    let after_replace = cur_message.message;

                    let replace_time = {};
                    for( let x in cur_message.entities ){
                        let cur_entities = cur_message.entities[x];

                        let need_replace = cur_message.message.substring(cur_entities.offset,cur_entities.offset+cur_entities.length+1);

                        if( typeof replace_time[need_replace] == 'undefined' ) replace_time[need_replace] = 0;
                        switch ( cur_entities._ ) {
                            case "messageEntityUrl":
                                after_replace = after_replace.replace(need_replace,'<a href="javascript:;" data-link="'+need_replace+'">'+need_replace+'</a>');
                                break;
                            case "messageEntityBold":
                                after_replace = after_replace.replace(need_replace,'<b>'+need_replace+'</b>');
                                break;
                            case "messageEntityTextUrl":
                                after_replace = after_replace.replaceTime(need_replace,'<a href="javascript:;" data-link="'+cur_entities.url+'">'+need_replace+'</a>',replace_time[need_replace]);
                                replace_time[need_replace]++;
                                break;
                        }
                    }
                    data.messages[i].message = after_replace.replace(/[\r\n]/g,'<br/>');
                    //console.log(data.messages[i].message);

                }
                data.messages = data.messages.reverse();
                this.messages_list = data;
                this.scrollDown();
            },
            telegram_send_messages(data){
                if( data.status ){
                    // 发送按钮状态
                    this.send_btn_loading = false;
                    // 表单可用
                    this.send_form_disable = false;
                    // 清空消息
                    this.messageForm.message = '';

                    for(let i in data.updates ){
                        // 更新消息
                        if( data.updates[i]._ == "updateNewMessage" ){
                            this.messages_list.messages.push(data.updates[i].message);
                        // 更新消息ID
                        }else if(data.updates[i]._ == "updateReadHistoryOutbox"){
                            this.current_dialog.top_message = data.updates[i].max_id;
                        }
                    }
                    this.scrollDown();

                    setTimeout(()=>{
                        this.$socket.emit('telegram_update',{});
                    },5000)
                }
                console.log(data);
            },
            telegram_collect_user( data ){
                //this.dialogs_loading = true;
                if( data.status ){
                    this.dialogs_loading = false;
                    this.$message({
                        message: '数据已经抓取成功，请在报表记录中查看以及导出数据！',
                        type: 'success'
                    });
                }
            }
        },
        destroyed() {
            this.$socket.close();
        }
    }
</script>

<style lang="scss" scoped>
    .text {
        font-size: 14px;
    }
    .clearfix:before,
    .clearfix:after {
        display: table;
        content: "";
    }
    .clearfix:after {
        clear: both
    }
    .box-card {
        .item{
            line-height: 40px;
            height: 40px;
            margin-bottom: 0px;
            padding: 6px;
            &:hover{
                background: #efefef;
                border-radius: 5px;
                cursor: pointer;
            }
        }
    }
    ::v-deep.el-page-header{
        .el-page-header__content{
            font-size: 16px;
        }
    }
    .infinite-list-item{
        line-height: 35px;
    }
    ::v-deep.el-scrollbar{
        height: 450px;
        .el-scrollbar__wrap{
            overflow-x: hidden;
            .infinite-list{
                .infinite-list-item{
                    font-size: 14px;
                    line-height: 20px;
                    background: #ececec;
                    border-radius: 5px;
                    padding: 10px 12px;
                    width: 80%;
                    margin: 10px 0px;
                    float: left;
                    position: relative;

                    &.right{
                        float: right;
                        margin-right: 15px;
                        background: #6de471;
                    }
                    p{
                        >a{
                            color: #007eff;
                            text-decoration: none;
                            word-break: break-word;
                        }
                    }
                    .add_all_group{
                        position: absolute;
                        bottom: 10px;
                        right: 10px;
                    }
                }
            }
        }
    }
    .group_name{
        overflow: hidden;
        white-space: nowrap;
    }
</style>
