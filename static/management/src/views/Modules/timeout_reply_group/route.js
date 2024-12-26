export default [
    {
        path: '/',
        redirect: '/module/timeout-reply-group/index'
    },
    {
        path: '/module/timeout-reply-group/index',
        name: 'TimeoutWithoutReplyIndex',
        component: () => import('@/views/Modules/timeout_reply_group/index.vue'),
        meta: {
            ignoreLogin: true
        }
    },
    {
        path: '/module/timeout-reply-group/rule',
        name: 'TimeoutWithoutReplyRule',
        component: () => import('@/views/Modules/timeout_reply_group/rule.vue'),
        meta: {
            ignoreLogin: true
        }
    },
    {
        path: '/module/timeout-reply-group/store',
        name: 'TimeoutWithoutReplyStore',
        component: () => import('@/views/Modules/timeout_reply_group/store.vue'),
        meta: {
            ignoreLogin: true
        }
    },
    {
        path: '/module/timeout-reply-group/h5/session-message',
        name: 'TimeoutWithoutReplyH5',
        component: () => import('@/views/Modules/timeout_reply_group/h5/session-message.vue'),
        meta: {
            ignoreLogin: true
        },
    }
]
