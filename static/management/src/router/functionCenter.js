export default [
    {
        path: '/plug/index',
        name: 'plugManagementHome',
        component: () => import('../views/plug/index.vue')
    },
    {
        path: "/plug/info",
        name: "plugManagementInfo",
        meta: {
            activeMenuKey: 'plugManagementHome',
            links: [
                {name: "功能插件", to: "/plug/index"},
                {name: "客户标签详情"}
            ],
        },
        component: () => import("../views/plug/info.vue"),
    },
    {
        path: '/module/customer_tag/index',
        name: 'CustomerLabelInex',
        component: () => import('../views/Modules/customer_tag/index.vue')
    },
]
