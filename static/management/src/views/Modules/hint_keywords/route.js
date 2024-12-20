export default [
    {
        path: '/',
        redirect: '/module/hint_keywords/index'
    },
    {
        path: '/module/hint_keywords/index',
        name: 'CustomerLabelInex',
        component: () => import('@/views/Modules/hint_keywords/index.vue')
    },
    {
        path: "/module/hint_keywords/ruleStore",
        name: "hintKeywordsRuleStore",
        component: () => import("@/views/Modules/hint_keywords/ruleConfig.vue"),
    },
]
