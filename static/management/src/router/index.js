import {createRouter, createWebHashHistory} from 'vue-router';
import store from "@/store";
import {checkInit} from "@/api/auth-login";
import {loginHandle} from "@/utils/tools";

const routes = []

if (process.env.VUE_APP_MODULE && process.env.VUE_APP_MODULE != 'main') {
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
    let login = await store.dispatch('checkLogin')
    if (!login && !to.meta.ignoreLogin) {
        /**
         * 携带auth_token时自动登录
         */
        if (to.query?.auth_token) {
            try {
                await loginHandle(to.query.auth_token)
                next()
                return
            } catch (e) {
                console.log('login err:',e)
            }
        }
        const {data} = await checkInit()
        if (data.init) {
            // 存在企业去登陆
            next({ path: '/login' });
        } else {
            // 企业信息不存在去绑定企业信息
            next({ path: '/authorizedAccess/index' });
        }
    } else {
        next();
    }
});

export default router
