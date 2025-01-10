<template>
    <div class="open-chat-box">
        <Empty v-if="failMessage" image="error" :description="failMessage"/>
    </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {useRoute} from 'vue-router';
import {jwtDecode} from 'jwt-decode';
import {showLoadingToast, Empty} from 'vant';
import 'vant/es/toast/style';
import 'vant/es/empty/style';
import {setH5AuthToken} from "@/utils/cache";
import {getAgentConfig} from "@/api/h5/index";
import {isWorkWxEnv} from "@/utils/tools";

const route = useRoute()
const loading = ref(true)
const tokenData = ref(null)
const agentConfig = ref(null)
const agentConfigFinished = ref(false)
const failMessage = ref('')
onMounted(() => {
    if (process.env.NODE_ENV === 'production' && !isWorkWxEnv()) {
        failMessage.value = '请在企业微信浏览器或企业微信手机客户端打开！'
        return
    }
    init()
})

async function init() {
    loading.value = true
    if (!route.query.token) {
        failMessage.value = '缺少会话信息'
        return
    }
    tokenData.value = jwtDecode(route.query.token);
    setH5AuthToken(route.query.token)
    const loadingToast = showLoadingToast({
        message: '加载中...',
        duration: 0,
    });
    try {
        loading.value = true
        const {data} = await getAgentConfig({current_url: window.location.href.split("#")[0]})
        agentConfig.value = data || {}
        agentConfig.value.success = function () {
            loadingToast.message = '正在打开会话'
            agentConfigFinished.value = true;
            wx.checkJsApi({
                jsApiList: ['openExistedChatWithMsg', 'openEnterpriseChat'],
                success: (res) => {
                    console.log("res:" + JSON.stringify(res))
                },
            })
            let params = {
                // 参数不能不传，不需要即传空值
                // 否则在某些环境打开会报错invalid param
                userIds: '',
                chatId: '',
                groupName: '',
                externalUserIds: '',
            }
            if (tokenData.value?.group_chat_id) {
                // 群聊
                params.chatId = tokenData.value.group_chat_id
            } else if (tokenData.value?.external_userid) {
                // 单聊
                params.externalUserIds = tokenData.value.external_userid
            } else {
                failMessage.value = '缺少会话信息'
                return
            }
            wx.openEnterpriseChat({
                ...params,
                success: (res) => {
                    console.log(res)
                },
                fail: (err) => {
                    failMessage.value = '无法打开会话！Error:' + JSON.stringify(err)
                },
                complete: () => {
                    loadingToast.close()
                }
            })
        };
        agentConfig.value.fail = function (res) {
            loadingToast.close()
            if (res.errMsg.indexOf('function not exist') > -1) {
                failMessage.value = '微信版本过低请升级'
            } else {
                failMessage.value = 'JsSdk初始化失败！AgentConfig Error:' + JSON.stringify(res)
            }
        };
        try {
            wx.agentConfig(agentConfig.value);
        } catch (e) {
            failMessage.value = 'JsSdk初始化失败！Error: ' + JSON.stringify({error: e})
            loadingToast.close()
        }
    } catch (e) {
        failMessage.value = '数据加载失败！Error: ' + JSON.stringify({error: e})
        loading.value = false
        loadingToast.close()
    }
}

</script>

<style scoped lang="less">
.open-chat-box {
    :deep(.van-empty__description) {
        text-align: center;
        max-width: 100vw;
        word-break: break-all;
        padding: 0 24px;
    }
}
</style>
