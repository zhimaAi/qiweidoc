import {createRouter, createWebHashHistory} from 'vue-router';
import store from "@/store";
import {checkInit} from "@/api/auth-login";
import {loginHandle, logoutClearData} from "@/utils/tools";
import {getCookieUserInfo} from '@/utils/cookie';

const routes = []

if (process.env.VUE_APP_MODULE && process.env.VUE_APP_MODULE != 'main') {
    routes.push({
        path: '/login',
        name: 'login',
        component: () => import('../views/login.vue'),
        meta: {
            ignoreLogin: true
        },
    })
    const files = require.context('@/views/Modules/' + process.env.VUE_APP_MODULE, true, /route\.js$/);
    files.keys().forEach(item => {
        routes.push(...files(item).default)
    });
} else {
    const files = require.context(".", true, /\.js$/);
    files.keys().forEach(item => {
        if (item === "./index.js") return;
        routes.push(...files(item).default)
    });
}

const router = createRouter({
    history: createWebHashHistory(process.env.BASE_URL),
    routes
})

router.beforeEach(async (to, from, next) => {
    if (!to.meta.ignoreLogin) {
        let login = await store.dispatch('checkLogin')
        if (to.query?.auth_token) {
            // 携带auth_token时自动登录
            try {
                await loginHandle(to.query.auth_token)
            } catch (e) {
                console.log('login err:',e)
            }
        } else if (login) {
            if (!getCookieUserInfo()) {
                // 已登录但官网退出登录时（网退出会清除cookie）
                // 官网和demo登录态同步
                logoutClearData()
                next({ path: '/login' });
                return
            }
        } else {
            // 未登录时
            // 检测是否存在企业信息
            const {data} = await checkInit()
            if (data.init) {
                // 存在企业去登陆
                next({ path: '/login' });
            } else {
                // 企业信息不存在去绑定企业信息
                next({ path: '/authorizedAccess/index' });
            }
            return
        }
    }
    next();
});

export default router
