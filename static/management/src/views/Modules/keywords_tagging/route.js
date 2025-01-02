export default [
    {
        path: '/',
        redirect: '/module/keywords_tagging/index'
    },
    {
        path: '/module/keywords_tagging/index',
        name: 'CustomerLabelInex',
        component: () => import('@/views/Modules/keywords_tagging/index.vue')
    },
    {
        path: "/module/keywords_tagging/ruleStore",
        name: "hintKeywordsRuleStore",
        component: () => import("@/views/Modules/keywords_tagging/ruleConfig.vue"),
    },
    {
        path: "/module/keywords_tagging/details",
        name: "hintKeywordsDetails",
        component: () => import("@/views/Modules/keywords_tagging/details.vue"),
    },
]
