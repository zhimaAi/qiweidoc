export default [
    {
        path: '/sessionArchive/index',
        name: 'sessionArchiveHome',
        component: () => import('../views/sessionArchive/index.vue')
    },
    {
        path: '/sessionArchive/search',
        name: 'sessionArchiveSearch',
        component: () => import('../views/sessionArchive/search.vue')
    }
]
