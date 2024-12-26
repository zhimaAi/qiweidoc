export default [
    {
        path: '/tools/h5/openChat',
        name: 'ToolsH5OpenChat',
        component: () => import('@/views/tools/h5/openChat.vue'),
        meta: {
            ignoreLogin: true
        },
    },
]
