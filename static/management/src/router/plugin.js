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
        },
        component: () => import("../views/plug/info.vue"),
    },
    {
        path: "/plug/render",
        name: "plugManagementRender",
        component: () => import("../views/plug/render.vue"),
    },
    {
        path: "/plug/shopping",
        name: "plugShopping",
        component: () => import("../views/plug/shopping.vue"),
    },
]

