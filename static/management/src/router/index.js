import {createRouter, createWebHashHistory} from 'vue-router';
import store from "@/store";
import {checkInit} from "@/api/auth-login";

const routes = []
const files = require.context(".", true, /\.js$/);
files.keys().forEach(item => {
    if (item === "./index.js") return;
    routes.push(...files(item).default)
});

const router = createRouter({
    history: createWebHashHistory(process.env.BASE_URL),
    routes
})

router.beforeEach(async (to, from, next) => {
    let login = await store.dispatch('checkLogin')
    if (!login && !to.meta.ignoreLogin) {
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
