export default [
    {
        path: '/',
        redirect: '/module/timeout-reply-single/index'
    },
    {
        path: '/module/timeout-reply-single/index',
        name: 'TimeoutWithoutReplyIndex',
        component: () => import('@/views/Modules/timeout_reply_single/index.vue'),
    },
    {
        path: '/module/timeout-reply-single/rule',
        name: 'TimeoutWithoutReplyRule',
        component: () => import('@/views/Modules/timeout_reply_single/rule.vue'),
    },
    {
        path: '/module/timeout-reply-single/store',
        name: 'TimeoutWithoutReplyStore',
        component: () => import('@/views/Modules/timeout_reply_single/store.vue'),
    },
    {
        path: '/module/timeout-reply-single/h5/session-message',
        name: 'TimeoutWithoutReplyH5',
        component: () => import('@/views/Modules/timeout_reply_single/h5/session-message.vue'),
        meta: {
            ignoreLogin: true
        },
    }
]
