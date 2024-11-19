<template>
    <div class="main-content">
        <div class="title">使用前请按照步骤正确完成配置 <a class="ml16">如何配置?</a></div>
        <div class="content mt24">
            <a-steps :current="current" :items="steps"></a-steps>
            <a-form
                class="mt24"
                :model="formState"
                name="basic"
                :label-col="{ span: 6 }"
                :wrapper-col="{ span: 14 }"
                autocomplete="off"
            >
                <LoadingBox v-if="loading"></LoadingBox>
                <div v-else class="form-block">
                    <template v-if="current==0">
                        <div class="form-block-item">
                            <div class="form-block-title">
                                复制以下信息并填入到企业微信后台
                            </div>
                            <div class="form-block-content">
                                <!--                                <a-form-item label="IP地址">-->
                                <!--                                    <div class="c595959">复制您的IP地址</div>-->
                                <!--                                </a-form-item>-->
                                <a-form-item label="加密公钥" class="label-height-unset">
                                    <div class="c595959 zm-pointer" @click="copyText(formState.chat_public_key)">
                                        <a>点击复制</a>
                                        <pre>{{ formState.chat_public_key }}</pre>
                                    </div>
                                </a-form-item>
                                <!--                                <a-form-item label="加密私钥">-->
                                <!--                                    <div class="c595959 zm-pointer" @click="copyText(formState.chat_private_key)"><pre>{{formState.chat_private_key}}</pre></div>-->
                                <!--                                </a-form-item>-->
                            </div>
                        </div>
                        <div class="form-block-item">
                            <div class="form-block-title">将企业微信后台会话存档配置页面的信息复制粘贴到下框中</div>
                            <div class="form-block-content">
                                <a-form-item label="会话存档密钥" name="corp_id">
                                    <a-input v-model:value="formState.chat_secret" placeholder="请输入会话存档密钥"/>
                                </a-form-item>
                                <a-form-item label="公钥版本号" name="corp_id">
                                    <a-input-number
                                        style="width: 100%;"
                                        :precision="0"
                                        v-model:value="formState.chat_public_key_version"
                                        placeholder="请输入公钥版本号"/>
                                </a-form-item>
                            </div>
                        </div>
                        <div class="text-center">
                            <a-button type="primary" class="main-btn" @click="save" :loading="saving">验证配置信息
                            </a-button>
                        </div>
                    </template>
                    <template v-else-if="current==1">
                        <div class="text-center status-box">
                            <CheckCircleFilled style="color: #21A665;font-size: 60px;"/>
                            <div class="status-title mt16">验证成功</div>
                            <router-link to="/index">
                                <a-button class="mt32" type="primary">去使用</a-button>
                            </router-link>
                        </div>
                    </template>
                    <!--                    <template v-else-if="current==1">-->
                    <!--                        <template v-if="verifyStatus == 2">-->
                    <!--                            <div class="text-center status-box">-->
                    <!--                                <ExclamationCircleFilled style="color: #FF9900;font-size: 60px;"/>-->
                    <!--                                <div class="status-title mt16">失败原因：信息填写错误</div>-->
                    <!--                                <a-button class="mt32" type="primary">去使用</a-button>-->
                    <!--                            </div>-->
                    <!--                        </template>-->
                    <!--                        <template>-->
                    <!--                            <div class="form-block-item">-->
                    <!--                                <div class="form-block-title">将以下信息配置到企微后台会话存档-接收事件服务器中</div>-->
                    <!--                                <div class="form-block-content">-->
                    <!--                                    <a-form-item label="URL">-->
                    <!--                                        <div class="c595959">http://zhima.cn/get.redback</div>-->
                    <!--                                        <div class="zm-tip-info">请将上面更换成企业的服务期IP地址</div>-->
                    <!--                                    </a-form-item>-->
                    <!--                                    <a-form-item label="Token">-->
                    <!--                                        <div class="c595959">-->
                    <!--                                            MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA6GXpDa0DFugP60RTUhvX-->
                    <!--                                            K3Iy5jrySVouD4o7QFDCjapcOra1iDboo3iNlpkOr7x32CisHIAq9Z7MI1epIiJ7-->
                    <!--                                            UN3GQkb6HDnt5+d67k05bpfmd7rwcJKOwXgHG/-->
                    <!--                                        </div>-->
                    <!--                                    </a-form-item>-->
                    <!--                                    <a-form-item label="EncodingAESSKey">-->
                    <!--                                        <div class="c595959">-->
                    <!--                                            MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA6GXpDa0DFugP60RTUhvX-->
                    <!--                                            K3Iy5jrySVouD4o7QFDCjapcOra1iDboo3iNlpkOr7x32CisHIAq9Z7MI1epIiJ7-->
                    <!--                                            UN3GQkb6HDnt5+d67k05bpfmd7rwcJKOwXgHG/-->
                    <!--                                        </div>-->
                    <!--                                    </a-form-item>-->
                    <!--                                </div>-->
                    <!--                            </div>-->
                    <!--                            <div class="text-center">-->
                    <!--                                <a-button type="primary" class="main-btn">验证配置信息</a-button>-->
                    <!--                            </div>-->
                    <!--                        </template>-->
                    <!--                    </template>-->
                </div>
            </a-form>
        </div>
    </div>
</template>

<script setup>
import {onMounted, reactive, ref} from 'vue';
import {ExclamationCircleFilled, CheckCircleFilled} from '@ant-design/icons-vue';
import {message} from 'ant-design-vue';
import {getPublicKey, saveCorpConfig} from "@/api/auth-login";
import {copyText} from "@/utils/tools";
import LoadingBox from "@/components/loadingBox.vue";

const current = ref(0)
const verifyStatus = ref(1)
const loading = ref(false)
const saving = ref(false)
const steps = ref([
    {title: '企业信息',},
    // {title: '配置接收事件服务器',},
    {title: '完成',},
])

const formState = reactive({
    chat_public_key: '',
    chat_private_key: '',
    chat_public_key_version: '',
    chat_secret: '',
})

onMounted(() => {
    loading.value = true
    getPublicKey().then(res => {
        formState.chat_public_key = res.data.public_key
        formState.chat_private_key = res.data.private_key
    }).finally(() => {
        loading.value = false
    })
})

const save = () => {
    try {
        saving.value = true
        formState.chat_secret = formState.chat_secret.trim()
        if (!formState.chat_public_key || !formState.chat_private_key) {
            throw '缺少密钥信息'
        }
        if (!formState.chat_secret) {
            throw '请输入会话存档密钥'
        }
        if (!formState.chat_public_key) {
            throw '请输入公钥版本号'
        }
        saveCorpConfig({
            ...formState,
        }).then(() => {
            current.value = 1
            verifyStatus.value = 1
        }).finally(() => {
            saving.value = false
        })
    } catch (e) {
        message.error(e)
        saving.value = false
    }
}
</script>

<style scoped lang="less">
.main-content {
    width: 960px;
    min-height: 700px;
    padding: 32px;
    border-radius: 16px;
    background: #FFF;
    box-shadow: 0 4px 32px 0 #0000001f;
    margin: 8vh auto;
    text-align: left;

    :deep(.ant-form-item) {
        margin-bottom: 16px;
    }

    :deep(.label-height-unset .ant-form-item-label >label ) {
        height: unset;
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

    .content {
        padding: 0 118px;
    }

    .form-block {
        .form-block-title {
            color: #262626;
            font-size: 14px;
            font-weight: 600;
            padding: 12px 24px;
            border-bottom: 1px solid #F0F0F0;
            margin-bottom: 16px;
        }
    }

    .main-btn {
        width: 160px;
        margin-top: 24px;
    }

    .status-box {
        margin-top: 70px;

        .status-title {
            color: #000000;
            font-size: 16px;
            font-weight: 600;
        }
    }
}

.c595959 {
    color: #595959;
}

.mt32 {
    margin-top: 32px;
}

pre {
    margin-bottom: 0;
    overflow: hidden;
}
</style>
