export default [
    {
        path: '/',
        redirect: '/module/group-chat-stat/index'
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
    {
        path: '/module/group-chat-stat/detail',
        name: 'GroupChatStatDetail',
        component: () => import('@/views/Modules/group_chat_stat/detail.vue')
    }
]
