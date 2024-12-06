<template>
    <MainLayout title="账号管理">
        <div class="zm-main-content account">
            <div class="title">
                请设置登录账号及密码
            </div>
            <a-form class="mt24" ref="ruleFormRef" :model="ruleForm" :rules="rules">
                <a-form-item name="login" label="登录账号">
                    <a-input placeholder="请输入登录账号" v-model:value="ruleForm.login" allowClear />
                </a-form-item>
                <a-form-item name="pass" :label="his_id != '' ? '新密码' : '设置密码'">
                    <a-input-password  autocomplete="" :placeholder="his_id != '' ? '请输入新密码' : '请输入设置密码'" v-model:value="ruleForm.pass" allowClear />
                </a-form-item>
                <a-form-item name="checkPass" :label="his_id != '' ? '确认新密码' : '确认密码'">
                    <a-input-password  autocomplete="" :placeholder="his_id != '' ? '再次输入新密码' : '再次输入设置密码'" v-model:value="ruleForm.checkPass" allowClear />
                </a-form-item>

                <a-form-item>
                    <a-button type="primary" @click="submitForm()" style="margin-top: 24px; margin-left: 138px;">
                        保存
                        <!-- 确认修改 -->
                    </a-button>
                </a-form-item>
            </a-form>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from "@/components/mainLayout.vue";
import { message } from 'ant-design-vue';
import { getCurrentUser, saveAccount } from "@/api/auth-login";

const router = useRouter()
const ruleFormRef = ref(null)

let validateLogin = (rule, value, callback) => {
    if (value === '') {
        callback(new Error('账号不能为空'))
    } else if (!/^[a-zA-Z0-9]+$/.test(value)) {
        callback(new Error('账号只能包含英文和数字'))
    } else if (value.length < 6) {
        callback(new Error('账号最少6个字符'))
    } else if (value.length > 32) {
        callback(new Error('账号最多32个字符'))
    } else {
        if (ruleForm.checkPass !== '') {
            ruleFormRef.value.validateFields('checkSetPass')
        }
        callback()
    }
}
let validatePass = (rule, value, callback) => {
    if (value === '') {
        callback(new Error('密码不能为空'))
    } else if (!/^[a-zA-Z0-9_`~!@#$%^&*()\-=_+\[\]{}|;':",.<>\/?]+$/.test(value)) {
        callback(new Error('密码只能包含英文、数字、下划线以及键盘上的特殊符号'))
    } else if (value.length < 6) {
        callback(new Error('密码最少6个字符'))
    } else if (value.length > 32) {
        callback(new Error('密码最多32个字符'))
    } else {
        ruleFormRef.value.validateFields('checkSetPass')
        callback()
    }
}
let cvalidateCheckPass = (rule, value, callback) => {
    if (value === '') {
        callback(new Error('密码不能为空'))
    } else if (!/^[a-zA-Z0-9_`~!@#$%^&*()\-=_+\[\]{}|;':",.<>\/?]+$/.test(value)) {
        callback(new Error('密码只能包含英文、数字、下划线以及键盘上的特殊符号'))
    } else if (value.length < 6) {
        callback(new Error('密码最少6个字符'))
    } else if (value.length > 32) {
        callback(new Error('密码最多32个字符'))
    } else if (value !== ruleForm.pass) {
        callback(new Error('两次密码不一致'))
    } else {
        callback()
    }
}

const ruleForm = reactive({
    login: '',
    pass: '',
    checkPass: ''
})

const rules = reactive({
    login: [{ required: true, validator: validateLogin, trigger: 'change' }],
    pass: [{ required: true, validator: validatePass, trigger: 'change' }],
    checkPass: [{ required: true, validator: cvalidateCheckPass, trigger: 'change' }]
})

const layout = reactive({
    labelCol: { span: 2 },
    wrapperCol: { span: 7 }
})

const his_login_name = ref('')

const his_id = computed(() => {
    if (!his_login_name.value) {
        return ''
    }
    if (ruleForm.login != his_login_name.value) {
        return ''
    }
    return 1
})

onMounted(() => {
    getLoginAccount()
})

const getLoginAccount = () => {
    getCurrentUser().then((res) => {
        if (res.data) {
            ruleForm.login = res.data.account
            his_login_name.value = JSON.parse(JSON.stringify(res.data.account))
        }
    })
}

const submitForm = () => {
    ruleFormRef.value.validate().then((valid) => {
        if (valid) {
            let { login, pass, checkPass } = ruleForm
            let params = {
                username: login,
                password: pass,
                repeat_password: checkPass
            }
            saveAccount(params).then((res) => {
                if (res.status == 'success') {
                    message.success('操作成功')
                    // 刷新当前页面
                    ruleForm.pass = ''
                    ruleForm.checkPass = ''
                    // router.go(0)
                    // return
                }
                res.error_message && message.error(res.error_message)
            })
        } else {
            return false
        }
    })
}

</script>

<style scoped lang="less">
.account {
    min-height: calc(100vh - 128px);
    background: #fff;
    padding: 0;
    font-family: "PingFang SC";

    .title {
        display: flex;
        width: 100%;
        padding: 12px var(---0, 0) 12px var(---24, 24px);
        flex-direction: column;
        align-items: flex-start;
        border-radius: 2px 2px var(---0, 0) var(---0, 0);
        border-bottom: 1px solid var(--07, #F0F0F0);
        background: #FFF;
        color: #262626;
        font-size: 14px;
        font-style: normal;
        font-weight: 600;
        line-height: 22px;
    }

    :deep(.ant-input-affix-wrapper) {
        width: 360px;
        border-radius: 2px;
    }

    :deep(.ant-form-item-label) {
        min-width: 138px;
    }


    :deep(.ant-form-item-required::before) {
        display: none !important;
    }
}
</style>
