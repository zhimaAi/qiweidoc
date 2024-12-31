export default [
    {
        path: '/',
        redirect: '/module/chat-statistic-group/index'
    },
    {
        path: '/module/chat-statistic-group/index',
        name: 'GroupChatStatIndex',
        component: () => import('@/views/Modules/chat_statistic_group/index.vue')
    },
    {
        path: '/module/chat-statistic-group/rule',
        name: 'GroupChatStatRule',
        component: () => import('@/views/Modules/chat_statistic_group/rule.vue')
    },
    {
        path: '/module/chat-statistic-group/detail',
        name: 'GroupChatStatDetail',
        component: () => import('@/views/Modules/chat_statistic_group/detail.vue')
    }
]
