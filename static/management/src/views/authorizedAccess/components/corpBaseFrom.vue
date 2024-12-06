<template>
    <div class="main-content">
        <div class="title">请先按照步骤完成自建应用的创建与配置并获取对应的信息填写到此处 <a class="ml16" href="http://zhimahuihua.com/docs/integration/" target="_blank">如何配置?</a>
        </div>
        <div class="mt24">
            <a-form
                :model="formState"
                name="basic"
                :label-col="{ span: 6 }"
                :wrapper-col="{ span: 14 }"
                autocomplete="off"
            >
                <a-form-item label="企业ID" name="corp_id">
                    <a-input v-model:value="formState.corp_id" placeholder="请输入企业ID"/>
                </a-form-item>
                <a-form-item label="自建应用AgentId" name="agent_id">
                    <a-input v-model:value="formState.agent_id" placeholder="请输入应用AgentId"/>
                </a-form-item>
                <a-form-item label="自建应用密钥" name="secret">
                    <a-input v-model:value="formState.secret" placeholder="请输入应用密钥"/>
                </a-form-item>
                <a-form-item label="可信域名文件验证" name="corp_id">
                    <a-button type="dashed" @click="fileVerifyVisible = true">上传验证文件</a-button>
                    <div v-if="existFileData" class="mt4 zm-flex-center">已上传
                        <CheckCircleFilled class="ml4" style="color: #2EC77C"/>
                    </div>
                    <div class="zm-tip-info mt4">
                        配置自建应用网页授权及JS—SDK的可信域名需完成域名归属认证，验证文件上传成功后，请在企微后台添加可信域名。
                    </div>
                </a-form-item>
                <div class="text-center">
                    <a-button type="primary" class="main-btn" :loading="saving" @click="save">确 认</a-button>
                </div>
            </a-form>
        </div>

        <a-modal v-model:open="fileVerifyVisible" @ok="saveFileData" @cancel="cancelFileData" width="600px">
            <template #title>
                上传验证文件<span class="zm-tip-info ml4">辅助信息</span>
            </template>
            <a-form
                :model="formState"
                name="basic"
                :label-col="{ span: 4 }"
                :wrapper-col="{ span: 20 }"
                autocomplete="off"
            >
                <a-form-item label="文件名" name="file_name">
                    <a-input v-model:value="formState.verify_domain_file_name" placeholder="请输入文件名"/>
                    <div class="zm-tip-info mt4">文件名WW_verify_开头，如：WW_verify_LSAreRXXX.txt（含文件后缀）</div>
                </a-form-item>
                <a-form-item label="文件内容" name="corp_id">
                    <a-input v-model:value="formState.verify_domain_file_content" placeholder="请输入文件内容"/>
                </a-form-item>
            </a-form>
        </a-modal>
    </div>
</template>

<script setup>
import {reactive, ref, computed, getCurrentInstance} from 'vue';
import {useRouter} from 'vue-router';
import {CheckCircleFilled} from '@ant-design/icons-vue';
import {saveCorpBasic} from "@/api/auth-login";

const router = useRouter()
const { proxy } = getCurrentInstance();
const fileVerifyVisible = ref(false)
const saving = ref(false)
const existFileData = ref(false)
const formState = reactive({
    corp_id: '',
    agent_id: '',
    secret: '',
    verify_domain_file_name: '',
    verify_domain_file_content: ''
})
const fileContentBack = reactive({
    verify_domain_file_name: '',
    verify_domain_file_content: ''
})

const requireFieldTextMap = {
    corp_id: '请输入企业ID',
    agent_id: '请输入应用AgentId',
    secret: '请输入应用密钥',
    verify_domain_file_name: '请上传验证文件',
    verify_domain_file_content: '请上传验证文件'
}

const saveFileData = () => {
    formState.verify_domain_file_name = formState.verify_domain_file_name.trim()
    formState.verify_domain_file_content = formState.verify_domain_file_content.trim()
    if (!formState.verify_domain_file_name) {
        return  proxy.$message.error('请输入文件名')
    }
    const isTextReg = /^[\w,\s-]+\.txt$/
    if (!isTextReg.test(formState.verify_domain_file_name)) {
        return  proxy.$message.error('请输入正确的txt文件名')
    }
    if (!formState.verify_domain_file_content) {
        return  proxy.$message.error('请输入文件内容')
    }
    fileContentBack.verify_domain_file_name = formState.verify_domain_file_name
    fileContentBack.verify_domain_file_content = formState.verify_domain_file_content
    existFileData.value = true
    fileVerifyVisible.value = false
}

const cancelFileData = () => {
    formState.verify_domain_file_name = fileContentBack.verify_domain_file_name
    formState.verify_domain_file_content = fileContentBack.verify_domain_file_content
    fileVerifyVisible.value = false
}

const save = () => {
    try {
        saving.value = true
        for (let key in formState) {
            formState[key] = formState[key].trim()
            if (!formState[key]) {
                throw requireFieldTextMap[key]
            }
        }
        let agent_id = Number(formState.agent_id)
        saveCorpBasic({
            ...formState,
            agent_id,
        }).then(res => {
            proxy.$message.success('保存完成，请登录！')
            setTimeout(() => {
                router.push({path: '/login'})
            }, 1200)
        }).finally(() => {
            saving.value = false
        })

    } catch (e) {
        saving.value = false
        proxy.$message.error(e)
    }
}
</script>

<style scoped lang="less">
.main-content {
    width: 960px;
    height: 565px;
    padding: 32px;
    border-radius: 16px;
    background: #FFF;
    box-shadow: 0 4px 32px 0 #0000001f;
    margin: 8vh auto;
    text-align: left;

    :deep(.ant-form-item) {
        margin-bottom: 16px;
    }

    .title {
        color: #242933;
        font-size: 16px;
        font-weight: 600;
        border-radius: 6px;
        padding: 16px 24px;
        background-image: url(@/assets/image/guide/nav-bg.png);
        background-size: cover;
        background-repeat: no-repeat;
    }

    .main-btn {
        width: 160px;
        margin-top: 48px;
    }
}
</style>
