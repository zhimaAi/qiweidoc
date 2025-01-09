export default [
    {
        path: '/',
        redirect: '/module/user_permission/index'
    },
    {
        path: '/module/user_permission/index',
        name: 'userPermissionIndex',
        component: () => import('@/views/Modules/user_permission/index.vue')
    },
    {
        path: "/module/user_permission/config",
        name: "userPermissionConfig",
        component: () => import("@/views/Modules/user_permission/config.vue"),
    }
]
