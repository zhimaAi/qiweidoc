export default [
    {
        path: '/',
        redirect: '/module/single-chat-stat/index'
    },
    {
        path: '/module/private-chat-stat/index',
        name: 'PrivateChatStatIndex',
        component: () => import('@/views/Modules/single_chat_stat/index.vue')
    },
    {
        path: '/module/private-chat-stat/rule',
        name: 'PrivateChatStatRule',
        component: () => import('@/views/Modules/single_chat_stat/rule.vue')
    },
    {
        path: '/module/private-chat-stat/detail',
        name: 'PrivateChatStatDetail',
        component: () => import('@/views/Modules/single_chat_stat/detail.vue')
    }
]
