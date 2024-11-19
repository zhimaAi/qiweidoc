export default [
    {
        path: '/customerManagement/index',
        name: 'customerManagementHome',
        component: () => import('../views/customerManagement/index.vue')
    },
    {
        path: '/customerManagement/tag',
        name: 'customerManagementTag',
        component: () => import('../views/customerManagement/tag.vue')
    },
]
