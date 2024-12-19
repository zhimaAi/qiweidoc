export default [
    {
        path: '/',
        redirect: '/index'
    },
    {
        path: '/index',
        name: 'index',
        component: () => import('../views/index.vue')
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('../views/login.vue'),
        meta: {
            ignoreLogin: true
        },
    },
    {
        path: '/authorizedAccess/index',
        name: 'authIndex',
        component: () => import('../views/authorizedAccess/index.vue'),
        meta: {
            ignoreLogin: true
        },
    },
    {
        path: '/authorizedAccess/guide',
        name: 'authGuide',
        component: () => import('../views/authorizedAccess/guide.vue')
    },
    {
        path: '/authorizedAccess/auth',
        name: 'authHome',
        component: () => import('../views/authorizedAccess/auth.vue')
    },
    {
        path: '/module/group-chat-stat/index',
        name: 'GroupChatStatIndex',
        component: () => import('@/views/Modules/group_chat_stat/index.vue')
    },
    {
        path: '/module/group-chat-stat/rule',
        name: 'GroupChatStatRule',
        component: () => import('@/views/Modules/group_chat_stat/rule.vue')
    },
]
