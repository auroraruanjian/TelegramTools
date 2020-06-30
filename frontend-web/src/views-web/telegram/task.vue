<template>
    <div class="task">
        <el-button @click="createNew" size="medium ">新增任务</el-button>
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

        <el-dialog title="新增任务" :visible.sync="newVisible" width="600px">
            <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="">
                <!--
                <el-form-item label="手机号码" prop="phone_number" v-if="ruleForm.step==1">
                    <el-input v-model="ruleForm.phone_number"></el-input>
                </el-form-item>
                <el-form-item label="验证码" prop="code" v-else-if="ruleForm.step==2">
                    <el-input v-model="ruleForm.code"></el-input>
                </el-form-item>
                <el-form-item label="短信验证码" prop="code" v-else-if="ruleForm.step==3">
                    <el-input v-model="ruleForm.phone_code"></el-input>
                </el-form-item>

                任务周期  间隔定时，指定时间  （当天，每天）
                任务内容  发送消息（私聊，群聊），收集资料

                群发（每个账户10分钟，100个群 限制）
                私聊（每个账户限制私聊100个用户）
                -->
                <el-form-item label="任务类型">
                    <el-select v-model="ruleForm.task_type" placeholder="请选择活动区域">
                        <el-option label="数据搜集" value="1"></el-option>
                        <el-option label="定时消息" value="2"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="抓取数据来源">
                    <el-select v-model="ruleForm.data_source" multiple placeholder="请选择">
                        <el-option
                            v-for="item in data_source"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                        </el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="执行时间">
                    <el-radio v-model="ruleForm.run_data_type" label="1">定时
                        <el-date-picker
                            v-model="ruleForm.date"
                            type="datetime"
                            placeholder="选择日期时间">
                        </el-date-picker>
                    </el-radio>
                </el-form-item>
                <el-form-item label="">
                    <el-radio v-model="ruleForm.run_data_type" label="2">间隔
                        <el-select v-model="ruleForm.interval" placeholder="" style="width: 80px;">
                            <el-option :key="key" :value="key" v-for="key in 60"></el-option>
                        </el-select>
                        <el-select v-model="ruleForm.interval" placeholder="" style="width: 100px;margin-left: 10px;">
                            <el-option value="1" label="分钟"></el-option>
                            <el-option value="2" label="小时"></el-option>
                        </el-select>

                    </el-radio>
                </el-form-item>
                <el-form-item label="消息内容">
                    <el-input type="textarea" v-model="ruleForm.message"></el-input>
                </el-form-item>

            </el-form>
            <el-row >
                <el-button type="primary" @click="submitForm('ruleForm')" size="small" :loading="formLoading">提交</el-button>
                <el-button @click="resetForm('ruleForm')"  size="small">重置</el-button>
            </el-row>
        </el-dialog>
    </div>
</template>

<script>
    export default {
        name: "telegram_task",
        data(){
            return {
                newVisible:false,
                formLoading:false,
                data_source:[

                ],
                ruleForm:{
                    task_type:'',
                    data_source:'',
                    run_data_type:'',
                    interval:'1',
                    date:'',
                    message:"",
                },
                rules:{

                },
                tableData: [],
            }
        },
        methods:{
            createNew(){
                this.newVisible = true;
            },
            submitForm( formName ){
                this.$refs[formName].validate((valid) => {
                    if (valid) {

                    } else {
                        console.log('error submit!!');
                        return false;
                    }
                });
            },
            resetForm( formName ){
                this.$refs[formName].resetFields();
                this.newVisible = flase;
            }
        }
    }
</script>

<style scoped>

</style>
