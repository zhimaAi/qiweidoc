export default [
    {
        path: '/',
        redirect: '/module/keywords_tagging/index'
    },
    {
        path: '/module/keywords_tagging/index',
        name: 'keywordsTaggingIndex',
        component: () => import('@/views/Modules/keywords_tagging/index.vue')
    },
    {
        path: "/module/keywords_tagging/ruleStore",
        name: "keywordsTaggingRuleStore",
        component: () => import("@/views/Modules/keywords_tagging/ruleConfig.vue"),
    },
    {
        path: "/module/keywords_tagging/details",
        name: "keywordsTaggingDetails",
        component: () => import("@/views/Modules/keywords_tagging/details.vue"),
    },
]
