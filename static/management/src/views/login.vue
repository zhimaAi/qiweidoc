<template>
    <div class="_main-container">
        <MainHeader/>
        <div class="main-content">
            <div class="left-box">
                <img src="@/assets/login-cover.png" class="cover"/>
            </div>
            <div class="right-box">
                <div id="ww_login"></div>
                <!--                <div class="title">企业微信登录扫码登录</div>-->
                <!--                <div class="qrcode"></div>-->
                <!--                <div class="tip-info mt16">请使用企业微信扫描二维码登录</div>-->
            </div>
        </div>
        <MainFooter/>
    </div>
</template>

<script setup>
import {onMounted, getCurrentInstance, ref} from 'vue';
import {useRouter} from 'vue-router';
import {useStore} from 'vuex';
import MainHeader from "@/components/mainHeader.vue";
import MainFooter from "@/components/mainFooter.vue";
import {checkInit, getCurrentCorp, getCurrentUser, loginByCode} from "@/api/auth-login";
import {setAuthToken, setCorpInfo, setUserInfo} from "@/utils/cache";

const {proxy} = getCurrentInstance();
const router = useRouter()
const store = useStore()
const loading = ref(true)

onMounted(async () => {
    console.log('ww', ww)
    console.log('SDK_VERSION', ww.SDK_VERSION)
    console.log('process.env.NODE_ENV', process.env.NODE_ENV)
    // 验证是否完成企业初始化
    checkCorpInit()
})

const localLoginTest = () => {
    loginAfterHandle('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjMiLCJ1c2VyaWQiOiJMaWFvWGl1WXVhbiIsImNvcnBfaWQiOiJ3dzVmNDMyYjNhMjRhOWI5ZjEiLCJleHAiOjE3MzIyNDA0ODd9.Gg8eWqr58T02reyrlV3jkH5V295zBFvKuv2bBpt48nw')
}

const checkCorpInit = () => {
    checkInit().then(res => {
        if (res?.data?.init) {
            let {corp_id, agent_id} = res.data
            loginInit(corp_id, agent_id)
            //TODO 本地环境直接登录（本地域名无法生成登录二维码）
            process.env.NODE_ENV === 'development' && localLoginTest()
        } else {
            // 企业信息不存在
            proxy.$message.warning('企业信息不存在，请先注册企业信息！')
            setTimeout(() => {
                router.push({path: '/authorizedAccess/index'})
            }, 1000)
        }
    }).finally(() => {
        loading.value = false
    })
}

const loginInit = async (corp_id, agent_id) => {
    try {
        const wwLogin = ww.createWWLoginPanel({
            el: '#ww_login',
            params: {
                login_type: 'CorpApp',
                appid: corp_id,
                agentid: agent_id,
                redirect_uri: window.location.origin,
                state: 'loginState',
                redirect_type: 'callback',
            },
            onCheckWeComLogin({isWeComLogin}) {
                console.log(isWeComLogin)
            },
            onLoginSuccess({code}) {
                console.log({code})
                loginByCode({
                    corp_id: corp_id,
                    code,
                }).then(res => {
                    console.log('res', res)
                    loginAfterHandle(res.data.token)
                })
            },
            onLoginFail(err) {
                console.log(err)
            },
        })
    } catch (e) {
        console.log("loginInit Err:", e)
        proxy.$message.error('初始化登录失败！')
    }
}

const loginAfterHandle = async token => {
    try {
        setAuthToken(token)
        const userInfo = await getCurrentUser()
        const corpInfo = await getCurrentCorp()
        if (!userInfo.data || !corpInfo.data) {
            proxy.$message.error('登录失败')
            return
        }
        setUserInfo(userInfo.data)
        setCorpInfo(corpInfo.data)
        store.commit('setLoginInfo', userInfo.data)
        store.commit('setCorpInfo', corpInfo.data)
        proxy.$message.success('登录成功，正在跳转主页')
        setTimeout(() => {
            // chat_public_key_version > 0表示已经配置会话存档
            // 否则去配置
            if (corpInfo.data?.chat_public_key_version > 0) {
                router.push({path: '/index'})
            } else {
                router.push({path: '/authorizedAccess/guide'})
            }
        }, 1000)
    } catch (e) {
        console.log('Err:', e)
        proxy.$message.error('登录失败')
    }
}
</script>

<style scoped lang="less">
._main-container {
    background: #E6EFFF;
    min-height: 100vh;
    padding: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;

    .main-content {
        width: 960px;
        height: 565px;
        border-radius: 16px;
        background: #FFF;
        box-shadow: 0 4px 32px 0 #0000001f;
        display: flex;

        .left-box {
            width: 480px;
            flex-shrink: 0;

            .cover {
                width: 100%;
                height: 100%;
            }
        }

        .right-box {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;

            //.title {
            //    color: #262626;
            //    font-size: 24px;
            //    font-weight: 600;
            //    margin-bottom: 56px;
            //}
            //
            //.qrcode {
            //    width: 200px;
            //    height: 200px;
            //    border-radius: 10px;
            //    box-shadow: 0 3.33px 26.67px 0 #00000014;
            //    margin: auto;
            //
            //    img {
            //        width: 100%;
            //        height: 100%;
            //    }
            //}
            //
            //.tip-info {
            //    color: #8c8c8c;
            //    font-size: 14px;
            //    font-weight: 400;
            //}
        }
    }

}
</style>
