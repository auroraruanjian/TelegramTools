<template>
    <div>
        <el-table
            ref="multipleTable"
            :data="tableData"
            tooltip-effect="dark"
            :loading="accounts_list_loading"
            style="width: 100%"
            @selection-change="handleSelectionChange">
            <el-table-column
                type="selection"
                width="55">
            </el-table-column>
            <el-table-column
                prop="phone"
                label="号码"
                width="300">
            </el-table-column>
            <el-table-column
                prop="nickname"
                label="昵称"
                show-overflow-tooltip
                width="400">
            </el-table-column>
            <el-table-column
                prop="status"
                label="状态">
                <template slot-scope="scope">
                    <el-tag type="success" v-if="scope.row.status==1">在线</el-tag>
                    <el-tag type="info" v-else-if="scope.row.status==2">离线</el-tag>
                    <el-tag type="warning" v-else-if="scope.row.status==3">失效</el-tag>
                    <el-tag type="danger" v-else-if="scope.row.status==4">异常</el-tag>
                </template>
            </el-table-column>
            <el-table-column
                label="操作"
                width="250"
            >
                <template slot-scope="scope">
                    <el-button
                        v-if="scope.row.status!=1"
                        size="mini"
                        type="success"
                        @click="handleLogin(scope.$index, scope.row)">登录</el-button>
                    <el-button
                        v-if="scope.row.status==1"
                        size="mini"
                        type="primary"
                        @click="$router.push({path:`/telegram/manage/${scope.row.id}`})">管理</el-button>
                    <el-button
                        size="mini"
                        type="danger"
                        @click="handleDelete(scope.$index, scope.row)">删除</el-button>
                </template>
            </el-table-column>
        </el-table>
        <div style="margin-top: 20px">
            <el-button @click="createNew" size="medium ">新增</el-button>
        </div>

        <el-dialog title="新增账户" :visible.sync="newVisible" width="500px">
            <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="75px" class="">
                <el-form-item label="手机号码" prop="phone_number" v-if="ruleForm.step==1">
                    <el-input v-model="ruleForm.phone_number"></el-input>
                </el-form-item>
                <el-form-item label="验证码" prop="code" v-else-if="ruleForm.step==2">
                    <el-input v-model="ruleForm.code"></el-input>
                </el-form-item>
                <el-form-item label="短信验证码" prop="code" v-else-if="ruleForm.step==3">
                    <el-input v-model="ruleForm.phone_code"></el-input>
                </el-form-item>
            </el-form>
            <el-row >
                <el-button type="primary" @click="submitForm('ruleForm')" size="small" :loading="ruleForm.loading">提交</el-button>
                <el-button @click="resetForm('ruleForm')"  size="small">重置</el-button>
            </el-row>
        </el-dialog>
    </div>
</template>

<script>
    import { getAccounts,postAuth } from "../../api/telegram";

    const defaultForm = {
        step: 1,
        phone_number: '',
        type : 'automatic',
        code: '',
        phone_code: '',
        loading:false,
    };

    export default {
        name: "telegram_index",
        data(){
            return {
                accounts_list_loading:false,
                tableData: [],
                multipleSelection: [],
                newVisible:false,
                ruleForm: Object.assign({}, defaultForm),
                rules:{
                }
            }
        },
        methods:{
            getAccounts(){
                this.accounts_list_loading = true;
                getAccounts().then( response => {
                    this.accounts_list_loading = false;
                    this.tableData = [];
                    for( let i in response.data.data.accounts ){
                        let _item = response.data.data.accounts[i];
                        this.tableData.push(_item);
                    }
                });
            },
            createNew(){
                this.newVisible = true;
            },
            async handleLogin( index , item ){
                await this.createNew();
                this.ruleForm.phone_number = item.phone;
                this.submitForm('ruleForm');
            },
            submitForm(formName) {
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        let post_data = {
                            phone_number:this.ruleForm.phone_number,
                        };
                        if( this.ruleForm.step == 2 ){
                            post_data.code = this.ruleForm.code;
                        }else if(this.ruleForm.step == 3){
                            post_data.phone_code = this.ruleForm.phone_code;
                        }

                        this.ruleForm.loading = true;
                        postAuth( post_data ).then( response =>{
                            this.ruleForm.loading = false;
                            // 输入验证码
                            if( response.data.code == -4 ){
                                this.$message.error(response.data.msg);
                                this.ruleForm.step = 2;
                            // 验证码发送失败
                            }else if( response.data.code == -12 || response.data.code == -13 ){
                                this.$message.error(response.data.msg);
                                this.newVisible = false;
                                // this.$alert(response.data.message, '提示', {
                                //     confirmButtonText: '确定',
                                //     callback: action => {
                                //
                                //     }
                                // });
                            // 手机确认验证码
                            }else if( response.data.code == -8 ){
                                this.$message.error(response.data.msg);
                                this.ruleForm.step = 3;
                                // 验证码发送失败
                            }else if( response.data.code == 1 ){
                                this.$message.success(response.data.msg);
                                this.ruleForm.step = 1;
                                this.newVisible = false;
                            }
                            console.log(response.data);
                        });
                    } else {
                        console.log('error submit!!');
                        return false;
                    }
                });
            },
            resetForm(formName) {
                this.$refs[formName].resetFields();
                this.newVisible = flase;
            },
            handleSelectionChange(val) {
                this.multipleSelection = val;
            }
        },
        created() {
            this.getAccounts();
        },
        watch:{
            newVisible( val ){
                if( !val ){
                    this.ruleForm.step = 1;
                    this.ruleForm.phone_number = '';
                    this.ruleForm.code = '';
                    this.ruleForm.phone_code = '';
                }
            }
        }
    }
</script>

<style scoped>

</style>
