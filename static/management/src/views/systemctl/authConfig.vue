<template>
    <MainLayout title="会话存档配置信息">
        <div class="zm-main-box">
            <LoadingBox v-if="loading"/>
            <a-form
                v-else
                :label-col="{ span: 6 }"
                :wrapper-col="{ span: 10 }"
                autocomplete="off"
            >
                <div class="form-block">
                    <div class="form-block-item">
                        <div class="form-block-title">基础信息</div>
                        <div class="form-block-content">
                            <a-form-item label="企业ID">
                                <div class="c595959">{{ formState.id }}</div>
                            </a-form-item>
                            <a-form-item label="自建应用ID">
                                <div class="c595959">{{ formState.agent_id }}</div>
                            </a-form-item>
                            <template v-if="baseInfoEdit">
                                <a-form-item label="自建应用密钥">
                                    <a-input v-model:value="formState.agent_secret" placeholder="请输入自建应用密钥"/>
                                </a-form-item>
                                <a-form-item :wrapper-col="{ span: 10, offset: 6}">
                                    <a-button class="mr8" @click="editBaseInfoCancel">取 消</a-button>
                                    <a-button type="primary" @click="editBaseInfo" :loading="baseSaving">保 存
                                    </a-button>
                                </a-form-item>
                            </template>
                            <template v-else>
                                <a-form-item label="自建应用密钥">
                                    <div class="c595959">{{ maskStringByPercentage(formState.agent_secret) }}</div>
                                </a-form-item>
                                <a-form-item :wrapper-col="{ span: 10, offset: 6}">
                                    <a-button type="primary" @click="baseInfoEdit = true">修 改</a-button>
                                </a-form-item>
                            </template>
                        </div>
                    </div>
                    <div class="form-block-item">
                        <div class="form-block-title">将以下信息复制并填入到企业微信后台</div>
                        <div class="form-block-content">
                            <a-form-item label="加密公钥" class="label-height-unset">
                                <div class="c595959 zm-pointer" @click="copyText(formState.chat_public_key)">
                                    <div><a>点击复制</a></div>
                                    {{ maskStringByPercentage(formState.chat_public_key) }}
                                </div>
                            </a-form-item>
                            <a-form-item label="加密私钥" class="label-height-unset">
                                <div class="c595959 zm-pointer" @click="copyText(formState.chat_private_key)">
                                    <div><a>点击复制</a></div>
                                    {{ maskStringByPercentage(formState.chat_private_key, 80) }}
                                </div>
                            </a-form-item>
                        </div>
                    </div>
                    <div class="form-block-item">
                        <div class="form-block-title">将以下信息复制到企微后台-应用管理-接收消息服务器中</div>
                        <div class="form-block-content">
                            <a-form-item label="URL" class="label-height-unset">
                                <div class="zm-pointer" @click="copyText(formState.url)">
                                    <div><a>点击复制</a></div>
                                    {{ formState.url }}
                                </div>
                            </a-form-item>
                            <a-form-item label="token" class="label-height-unset">
                                <div class="zm-pointer" @click="copyText(formState.callback_event_token)">
                                    <div><a>点击复制</a></div>
                                    {{ maskStringByPercentage(formState.callback_event_token) }}
                                </div>
                            </a-form-item>
                            <a-form-item label="encodingAESSKey" class="label-height-unset">
                                <div class="zm-pointer" @click="copyText(formState.callback_event_aes_key)">
                                    <div><a>点击复制</a></div>
                                    {{ maskStringByPercentage(formState.callback_event_aes_key) }}
                                </div>
                            </a-form-item>
                        </div>
                    </div>
                    <div class="form-block-item">
                        <div class="form-block-title">将企业微信后台会话存档配置页面的信息复制粘贴到下框中</div>
                        <div class="form-block-content">
                            <template v-if="sessionInfoEdit">
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
                                <a-form-item :wrapper-col="{ span: 10, offset: 6}">
                                    <a-button class="mr8" @click="editSessionInfoCancel">取 消</a-button>
                                    <a-button type="primary" @click="editSessionInfo" :loading="sessionSaving">保 存
                                    </a-button>
                                </a-form-item>
                            </template>
                            <template v-else>
                                <a-form-item label="会话存档密钥" name="corp_id">
                                    <div class="c595959">{{ maskStringByPercentage(formState.chat_secret) }}</div>
                                </a-form-item>
                                <a-form-item label="公钥版本号" name="corp_id">
                                    <div class="c595959">{{ formState.chat_public_key_version }}</div>
                                </a-form-item>
                                <a-form-item :wrapper-col="{ span: 10, offset: 6}">
                                    <a-button type="primary" @click="sessionInfoEdit = true">修 改</a-button>
                                </a-form-item>
                            </template>
                        </div>
                    </div>
                </div>
            </a-form>
        </div>
    </MainLayout>
</template>

<script setup>
import {onMounted, ref, reactive, computed} from 'vue';
import {useStore} from 'vuex';
import {message} from 'ant-design-vue';
import MainLayout from "@/components/mainLayout.vue";
import {getCurrentCorp, getEventToken, setCurrentCorp} from "@/api/auth-login";
import {copyObj, copyText, maskStringByPercentage} from "@/utils/tools";
import LoadingBox from "@/components/loadingBox.vue";

const store = useStore()
const loading = ref(true)
const baseInfoEdit = ref(false)
const sessionInfoEdit = ref(false)
const baseSaving = ref(false)
const sessionSaving = ref(false)
const formStateDefault = ref({})
const formState = ref({
    id: '',
    agent_id: '',
    agent_secret: '',
    chat_public_key: '',
    chat_private_key: '',
    chat_public_key_version: '',
    chat_secret: '',
    callback_event_token: '',
    callback_event_aes_key: '',
    url: '',
})

const corpId = computed(() => {
    return store.getters.getCorpId
})

onMounted(() => {
    init()
})

const init = async () => {
    loading.value = true
    try {
        await loadCorpInfo()
    } catch (e) {
        // console.log('Err:', e)
    }
    loading.value = false
}

const loadCorpInfo = async () => {
    const {data} = await getCurrentCorp({
        show_config: true
    })
    formState.value = data || {}
    formState.value.url = window.location.origin + '/openpush/qw/' + corpId.value
    formStateDefault.value = copyObj(formState.value)
}

const editBaseInfo = () => {
    if (baseInfoEdit.value) {
        try {
            baseSaving.value = true
            formState.value.agent_secret = formState.value.agent_secret.trim()
            if (!formState.value.agent_secret) {
                throw '请输入自建应用密钥'
            }
            setCurrentCorp({
                agent_secret: formState.value.agent_secret,
            }).then(() => {
                message.success('已保存')
                baseInfoEdit.value = !baseInfoEdit.value
                formStateDefault.value.agent_secret = formState.value.agent_secret
            }).finally(() => {
                baseSaving.value = false
            })
        } catch (e) {
            baseSaving.value = false
            message.error(e)
        }
    }
}

const editBaseInfoCancel = () => {
    baseInfoEdit.value = false
    formState.value = copyObj(formStateDefault.value)
}

const editSessionInfo = () => {
    if (sessionInfoEdit.value) {
        try {
            sessionSaving.value = true
            formState.value.chat_secret = formState.value.chat_secret.trim()
            if (!formState.value.chat_secret) {
                throw '请输入会话存档密钥'
            }
            if (!formState.value.chat_public_key_version) {
                throw '请输入公钥版本号'
            }
            setCurrentCorp({
                chat_secret: formState.value.chat_secret,
                chat_public_key_version: formState.value.chat_public_key_version
            }).then(() => {
                message.success('已保存')
                sessionInfoEdit.value = !sessionInfoEdit.value
                formStateDefault.value.chat_secret = formState.value.chat_secret
                formStateDefault.value.chat_public_key_version = formState.value.chat_public_key_version
            }).finally(() => {
                sessionSaving.value = false
            })
        } catch (e) {
            sessionSaving.value = false
            message.error(e)
        }
    }
}

const editSessionInfoCancel = () => {
    sessionInfoEdit.value = false
    formState.value = copyObj(formStateDefault.value)
}
</script>

<style scoped lang="less">
.zm-main-box {
    background: #FFF;
    padding-bottom: 40px;
    min-height: calc(100vh - 120px);

    :deep(.label-height-unset .ant-form-item-label >label ) {
        height: unset;
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

    .c595959 {
        color: #595959;
        word-break: break-all;
    }
}
</style>
