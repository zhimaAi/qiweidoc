<template>
    <div class="_main-container">
        <MainHeader/>
        <div class="main-content">
            <div class="left-box">
                <img src="@/assets/login-cover.svg" class="cover"/>
                <div class="web-logo-title-box">
                    <div class="web-logo-title">{{ company.login_page_title || '芝麻会话存档' }}</div>
                    <div class="web-logo-info">{{ company.login_page_description || '助力企业客户沟通合规管控和质量提升' }}</div>
                </div>
            </div>
            <div class="right-box" v-if="isWxLogin">
                <div class="right-icon-box">
                    <div class="login-tip-top">
                        使用密码登录
                        <img class="login-tip-top-icon" src="@/assets/svg/login-tips-triangle.svg" />
                    </div>
                    <img class="right-check" src="@/assets/user-login.svg" @click="onChangeLogin('account')" />
                </div>
                <div id="ww_login"></div>
            </div>
            <div class="right-box" v-else>
                <div class="right-icon-box">
                    <div class="login-tip-top">
                        扫码登录更便捷
                        <img class="login-tip-top-icon" src="@/assets/svg/login-tips-triangle.svg" />
                    </div>
                    <img class="right-check" src="@/assets/wx-login.svg" @click="onChangeLogin('wx')" />
                </div>
                <div class="sign-in">
                    <!-- Sign In Form -->
                    <h2 class="login-title">密码登录</h2>
                    <a-form
                        class="login-form"
                        :model="formState"
                        name="basic"
                        autocomplete="off"
                        @finish="onFinish"
                    >
                        <a-form-item
                            name="username"
                            class="usernames"
                            :rules="[{ required: true, message: '请输入账号' }]"
                        >
                            <a-input class="login-item" v-model:value="formState.username" autocomplete="off" placeholder="请输入账号">
                                <template #prefix>
                                    <img class="login-input-icon" src="@/assets/svg/user.svg" />
                                </template>
                            </a-input>
                        </a-form-item>

                        <a-form-item
                            name="password"
                            :rules="[{ required: true, message: '请输入密码' }]"
                        >
                            <a-input-password class="login-item" v-model:value="formState.password" autocomplete="off" type="password" placeholder="请输入密码">
                                <template #prefix>
                                    <img class="login-input-icon" src="@/assets/svg/password.svg" />
                                </template>
                            </a-input-password>
                        </a-form-item>

                        <a-form-item>
                            <a-button class="login-btn" type="primary" block html-type="submit">登录</a-button>
                            <div class="login-tip">暂无账号？可扫码登录后设置</div>
                        </a-form-item>
                    </a-form>
                    <!-- / Sign In Form -->
                </div>
            </div>
        </div>
        <MainFooter :copyright="company.copyright" />
    </div>
</template>

<script setup>
import {onMounted, getCurrentInstance, ref, reactive, computed} from 'vue';
import {useRouter} from 'vue-router';
import {useStore} from 'vuex';
import MainHeader from "@/components/mainHeader.vue";
import MainFooter from "@/components/mainFooter.vue";
import {checkInit, getSettings, getCurrentUser, loginByCode, loginByAccount} from "@/api/auth-login";
import {setAuthToken, setCorpInfo} from "@/utils/cache";
import { UserOutlined, LockOutlined } from '@ant-design/icons-vue';
import {setCookieAcrossSubdomain} from "@/utils/cookie";
import {loginHandle} from "@/utils/tools";

const {proxy} = getCurrentInstance();
const router = useRouter()
const store = useStore()
const company = computed(() => store.getters.getCompany)
const loading = ref(true)
const isWxLogin = ref(false)
const formState = reactive({
  username: '',
  password: '',
  remember: true
})

// const getCUrrentCorpData = () => {
//   getSettings().then((res) => {
//     if (res.status === 'success') {
//         store.commit('setCompany', res.data)
//     }
//   }).catch((e) => {
//     // 用默认的头像和企业信息
//     store.commit('setCompany', {
//       title: '',
//       logo: '',
//       navigation_bar_title: '',
//       login_page_title: '',
//       login_page_description: '',
//       copyright: ''
//     })
//   })
// }

onMounted(async () => {
  // getCUrrentCorpData()
    // console.log('ww', ww)
    // console.log('SDK_VERSION', ww.SDK_VERSION)
    // console.log('process.env.NODE_ENV', process.env.NODE_ENV)
})

const localLoginTest = () => {
    loginAfterHandle('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MjUsInVzZXJpZCI6IllvdUVyWXVhblhpYW9CYVdhbmciLCJjb3JwX2lkIjoid3c1ZjQzMmIzYTI0YTliOWYxIiwiZXhwIjoxNzM2NTA3MzcxfQ.77WBQ5tRSrBMSSiaK7p90HvTWhiPzF-5z6RJPJo7uzM')
}

const onChangeLogin = (type) => {
    // console.log(type)
    if (type === 'wx') {
        isWxLogin.value = true
         // 验证是否完成企业初始化
        checkCorpInit()
    } else {
        isWxLogin.value = false
    }
}

const onFinish = () => {
  handleLogin()
}

const handleLogin = () => {
    loginByAccount({
      username: formState.username,
      password: formState.password
    })
    .then((res) => {
        loginAfterHandle(res.data.token)
    })
    .catch((err) => {
    //   console.log(err.message)
    })
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
                // console.log(isWeComLogin)
            },
            onLoginSuccess({code}) {
                // console.log({code})
                loginByCode({
                    corp_id: corp_id,
                    code,
                }).then(res => {
                    // console.log('res', res)
                    loginAfterHandle(res.data.token)
                })
            },
            onLoginFail(err) {
                // console.log(err)
            },
        })
    } catch (e) {
        // console.log("loginInit Err:", e)
        proxy.$message.error('初始化登录失败！')
    }
}

const loginAfterHandle = async token => {
    try {
        const {corpInfo} = await loginHandle(token)
        proxy.$message.success('登录成功，正在跳转主页')
        setTimeout(() => {
            // chat_public_key_version > 0表示已经配置会话存档
            // 否则去配置
            if (corpInfo.data?.chat_public_key_version > 0) {
                router.push({path: '/'})
            } else {
                router.push({path: '/authorizedAccess/guide'})
            }
        }, 1000)
    } catch (e) {
        // console.log('Err:', e)
        proxy.$message.error('登录失败')
    }
}
</script>

<style scoped lang="less">
._main-header {
    background: none;
    box-shadow: none;
}
._main-container {
    background: linear-gradient(254deg, #F6F9FE 6.01%, #E5EEFF 62.87%);
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
            position: relative;
            width: 480px;
            flex-shrink: 0;

            .cover {
                width: 100%;
                height: 100%;
            }

            .web-logo-title-box {
                font-family: "PingFang SC";
                position: absolute;
                top: 44px;
                left: 50%;
                transform: translateX(-50%);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 8px;

                .web-logo-title {
                    color: #262626;
                    text-align: center;
                    font-family: "PingFang SC";
                    font-size: 24px;
                    font-style: normal;
                    font-weight: 600;
                    line-height: 32px;
                }

                .web-logo-info {
                    color: #3a4559;
                    font-family: "PingFang SC";
                    font-size: 16px;
                    font-style: normal;
                    font-weight: 400;
                    line-height: 24px;
                    opacity: 0.85;
                }
            }
        }

        .right-box {
            position: relative;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;

            .right-icon-box {
                display: flex;
                align-items: center;
                position: absolute;
                right: 0;
                top: 0;
            }

            .login-tip-top {
                position: relative;
                width: 142px;
                height: 32px;
                display: inline-flex;
                padding: 4px 12px;
                justify-content: center;
                align-items: center;
                gap: 10px;
                border-radius: 6px;
                background: var(--01-, #1D5EC9);
                color: #ffffff;
                font-size: 16px;
                font-style: normal;
                font-weight: 400;
                line-height: 24px;

                .login-tip-top-icon {
                    position: absolute;
                    width: 6px;
                    right: -6px;
                    top: 50%;
                    margin-top: -6px;
                }
            }
        }

        .right-check {
            width: 60px;
            cursor: pointer;
            display: inline-block;
            margin: 8px;
        }

        .sign-in {
            padding: 20px;
            width: 382px;
            height: 334px;
            border-radius: 16px;
            padding: 0px 20px;
            font-family: "PingFang SC";

            .login-title {
                text-align: center;
                color: #262626;
                font-size: 24px;
                font-style: normal;
                font-weight: 600;
                line-height: 32px;
                margin-bottom: 56px;
            }

            .login-form {
                font-weight: 700;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .login-item {
                display: flex;
                width: 380px;
                padding: 8px 12px;
                height: 40px;
                box-sizing: border-box;
                align-items: center;
                border-radius: 6px;
                background: #FFF;
                margin-bottom: 8px;

                .login-input-icon {
                    width: 16px;
                    color: #8C8C8C;
                }
            }

            .login-btn {
                height: 40px;
                box-sizing: border-box;
                display: flex;
                width: 380px;
                padding: 8px 12px;
                align-items: center;
                justify-content: center;
                border-radius: 6px;
            }

            .login-tip {
                text-align: center;
                color: #8c8c8c;
                font-size: 14px;
                font-style: normal;
                font-weight: 400;
                line-height: 22px;
                margin-top: 10px;
            }
        }
    }

}
</style>
