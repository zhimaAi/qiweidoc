export default [
    {
        path: '/',
        redirect: '/module/chat-statistic-single/index'
    },
    {
        path: '/module/chat-statistic-single/index',
        name: 'PrivateChatStatIndex',
        component: () => import('@/views/Modules/chat_statistic_single/index.vue')
    },
    {
        path: '/module/chat-statistic-single/rule',
        name: 'PrivateChatStatRule',
        component: () => import('@/views/Modules/chat_statistic_single/rule.vue')
    },
    {
        path: '/module/chat-statistic-single/detail',
        name: 'PrivateChatStatDetail',
        component: () => import('@/views/Modules/chat_statistic_single/detail.vue')
    }
]
