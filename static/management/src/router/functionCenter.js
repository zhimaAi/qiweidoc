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
        path: '/module/hint_keywords/index',
        name: 'hintKeywordsHome',
        component: () => import('../views/Modules/hint_keywords/index.vue')
    },
    {
        path: "/module/hint_keywords/ruleStore",
        name: "hintKeywordsRuleStore",
        meta: {
            activeMenuKey: 'hintKeywordsHome',
            links: [
                {name: "敏感词提醒", to: "/module/hint_keywords/index"},
                {name: "规则"}
            ],
        },
        component: () => import("../views/Modules/hint_keywords/ruleConfig.vue"),
    },
    {
        path: '/module/customer_tag/index',
        name: 'CustomerLabelInex',
        component: () => import('../views/Modules/customer_tag/index.vue')
    },
]
