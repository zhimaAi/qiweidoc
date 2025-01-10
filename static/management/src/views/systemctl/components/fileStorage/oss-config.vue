<template>
    <div>
        <a-alert type="info"
                 class="alert-box"
                 message="配置OSS地址后，聊天记录的文件将存储在OSS服务中，同时本地默认保存7天，7天后自动清除"
                 show-icon/>
        <LoadingBox v-if="loading"/>
        <div v-else class="w1200">
            <a-form class="mt24"
                    ref="formRef"
                    :labelCol="{span: 3}"
                    :wrapperCol="{span: 10}">
                <template v-if="configFinished && !configEdit">
                    <a-form-item label="云服务商" name="provider">
                        {{ formState.provider }}
                    </a-form-item>
                    <a-form-item label="地区">
                        <div class="config-info">{{ formState.region }}</div>
                        <div class="config-info">endpoint：{{ formState.endpoint }}</div>
                    </a-form-item>
                    <a-form-item label="Bucket">
                        <div class="config-info">{{ maskStringByPercentage(formState.bucket) }}</div>
                    </a-form-item>
                    <a-form-item label="Access Key">
                        <div class="config-info">{{ maskStringByPercentage(formState.access_key) }}</div>
                    </a-form-item>
                    <a-form-item label="Secret Key">
                        <div class="config-info">{{ maskStringByPercentage(formState.secret_key) }}</div>
                    </a-form-item>
                    <a-form-item label="本地文件保存时间">
                        <span v-if="formState.local_session_file_retention_days === 0">永久保存</span>
                        <span v-else>保存{{ localSessionDays }}天，{{ localSessionDays }}天后自动清除</span>
                    </a-form-item>
                    <a-form-item :wrapper-col="{ offset: 3, span: 10 }">
                        <div class="mt24">
                            <a-button @click="configEdit = true">修 改</a-button>
                        </div>
                    </a-form-item>
                </template>
                <template v-else>
                    <a-form-item label="云服务商" name="provider">
                        <a-select v-model:value="formState.provider"
                                  @change="providerChange"
                                  placeholder="请选择云服务商">
                            <a-select-option v-for="(_, key) in regionMap" :key="key">{{ key }}</a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item label="地区" name="region">
                        <a-select
                            v-model:value="formState.region"
                            @change="regionChange"
                            placeholder="请选择地区">
                            <a-select-option v-for="(item, key) in regionData" :key="key">
                                {{ item.name }}
                            </a-select-option>
                        </a-select>
                        <a-form-item label="Endpoint" name="endpoint" class="mt8">
                            <a-input v-model:value="formState.endpoint" placeholder="请输入Endpoint"/>
                        </a-form-item>
                    </a-form-item>
                    <a-form-item label="Bucket" name="bucket">
                        <a-input v-model:value="formState.bucket" placeholder="请输入Bucket"/>
                    </a-form-item>
                    <a-form-item label="Access Key" name="access_key">
                        <a-input v-model:value="formState.access_key" placeholder="请输入Access Key"/>
                    </a-form-item>
                    <a-form-item label="Secret Key" name="secret_key">
                        <a-input v-model:value="formState.secret_key" placeholder="Secret Key"/>
                    </a-form-item>
                    <a-form-item label="本地文件保存时间" name="local_session_file_retention_days">
                        <div class="zm-flex-center">
                            <a-input-number
                                v-model:value="formState.local_session_file_retention_days"
                                :precision="0"
                                :min="0"
                                style="width: 100px;"
                                placeholder="请输入"/>
                            <span class="ml8">天</span>
                        </div>
                        <div class="zm-tip-info mt8">
                        <span v-if="formState.local_session_file_retention_days > 0">
                            保存{{ localSessionDays }}天，{{ localSessionDays }}天后自动清除；
                        </span>
                            保存时间为0，则表示永久保存
                        </div>
                    </a-form-item>
                    <a-form-item :wrapper-col="{ offset: 3, span: 10 }">
                        <div class="mt24">
                            <a-button v-if="configEdit" @click="loadConfig" class="mr16">取 消</a-button>
                            <a-button type="primary" @click="save">配置完成</a-button>
                        </div>
                    </a-form-item>
                </template>
            </a-form>
        </div>

        <a-modal v-model:open="checkModal.visible"
                 :footer="null"
                 :maskClosable="false"
                 :closable="false">
            <div class="check-modal-box">
                <div v-if="checkModal.status == 1" class="text-center">
                    <a-spin :indicator="indicator"/>
                    <div class="tit">正在验证配置，请勿关闭</div>
                </div>

                <div v-else-if="checkModal.status == 2" class="text-center">
                    <CheckCircleFilled style="color: #21A665;font-size: 60px;"/>
                    <div class="tit">验证成功</div>
                    <a-button class="mt16" type="primary" @click="checkOk">知道了</a-button>
                </div>

                <div v-else class="text-center">
                    <ExclamationCircleFilled style="color: #FF9900;font-size: 60px;"/>
                    <div class="tit">验证失败</div>
                    <div class="zm-tip-info mt4">{{ checkModal.error || '未知错误' }}</div>
                    <a-button class="mt16" type="primary" @click="hideCheckModal">修改重新验证</a-button>
                </div>
            </div>
        </a-modal>
    </div>
</template>

<script setup>
import {ref, reactive, h, onMounted, computed} from 'vue';
import {message} from 'ant-design-vue';
import {LoadingOutlined, CheckCircleFilled, ExclamationCircleFilled} from '@ant-design/icons-vue';
import {getRegionData, getSettings, savSettings} from "@/api/file-storage";
import {maskStringByPercentage} from "@/utils/tools";
import LoadingBox from "@/components/loadingBox.vue";

const formRef = ref(null)
const loading = ref(true)
const configFinished = ref(false)
const configEdit = ref(false)
const regionMap = reactive({
    '阿里云': {
        "cn-hangzhou": {
            "name": "华东1（杭州）",
            "endpoint": "https://oss-cn-hangzhou.aliyuncs.com"
        },
    },
    '腾讯云': {
        "ap-beijing-1": {
            "name": "北京一区",
            "endpoint": "https://cos.ap-beijing-1.myqcloud.com"
        },
    },
})
const formState = reactive({
    provider: '阿里云',
    access_key: '',
    secret_key: '',
    bucket: '',
    region: undefined,
    endpoint: '',
    local_session_file_retention_days: 7,
})
const rules = {
    access_key: {message: '请输入Access Key'},
    secret_key: {message: '请输入Secret Key'},
    bucket: {message: '请输入Bucket'},
    region: {message: '请选择地区'},
    endpoint: {message: '请选择地区Endpoint'},
    local_session_file_retention_days: {message: '请输入本地文件保存时间'},
}
const checkModal = reactive({
    visible: false,
    status: 1, // 1 验证中； 2 完成； 3 失败
    error: ''
})
const indicator = h(LoadingOutlined, {
    style: {
        fontSize: '28px',
    },
    spin: true,
});

const regionData = computed(() => {
    return regionMap[formState.provider] || {}
})

const localSessionDays = computed(() => {
    return formState.local_session_file_retention_days > -1 ? formState.local_session_file_retention_days : 7
})

onMounted(() => {
    loadConfig()
    loadRegionData()
})

function loadConfig () {
    try {
        loading.value = true
        getSettings().then(res => {
            let data = res?.data || {}
            if (data.access_key && data.secret_key) {
                configFinished.value = true
                configEdit.value = false
                Object.assign(formState, data)
            }
        }).finally(() => {
            loading.value = false
        })
    } catch (e) {

    }
}

function loadRegionData() {
    getRegionData().then(res => {
        let data = res?.data || {}
        Object.assign(regionMap, data)
    })
}

function regionChange() {
    if (formState.region) {
        formState.endpoint = regionData?.value?.[formState.region].endpoint || ''
    } else {
        formState.endpoint = ''
    }
}

function providerChange() {
    formState.region = undefined
    formState.endpoint = ''
}

function save() {
    if (formState.local_session_file_retention_days === '') {
        formState.local_session_file_retention_days = 7
    }
    for (let key in formState) {
        if (typeof formState[key] === 'string') {
            formState[key] = formState[key].trim()
        }
        if (!formState[key] && formState[key] !== 0) {
            return message.error(rules[key]?.message)
        }
    }
    const checkFunc = data => {
        setTimeout(() => {
            if (data.status === 'success') {
                checkModal.status = 2
            } else {
                checkModal.status = 3
                checkModal.error = data.error_message
            }
        }, 2000)
    }
    checkModal.status = 1
    checkModal.visible = true
    savSettings(formState).then(checkFunc).catch(checkFunc)
}

function checkOk() {
    configFinished.value = true
    configEdit.value = false
    checkModal.visible = false
}

function hideCheckModal() {
    checkModal.visible = false
}
</script>

<style scoped lang="less">
.zm-main-content {
    position: relative;
    min-height: calc(100vh - 126px);

    .config-info {
        color: #595959;
    }

    .ant-form-item:last-child {
        margin-bottom: 0;
    }
}

.check-modal-box {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 300px;

    .tit {
        color: #262626;
        font-size: 16px;
        font-weight: 600;
        margin-top: 12px;
    }
}

.w1200 {
    width: 1200px;
}
</style>
