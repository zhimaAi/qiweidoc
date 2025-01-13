export default [
    {
        path: '/systemctl/authConfig',
        name: 'systemctlAuthConfig',
        component: () => import('../views/systemctl/authConfig.vue')
    },
    {
        path: '/systemctl/account',
        name: 'systemctlAccount',
        component: () => import('../views/systemctl/account.vue')
    },
    // {
    //     path: '/systemctl/firm',
    //     name: 'systemctlFirm',
    //     component: () => import('../views/systemctl/firm.vue')
    // },
    {
        path: '/systemctl/fileStorage',
        name: 'systemctlFileStorage',
        component: () => import('../views/systemctl/fileStorage.vue')
    },
    {
        path: '/systemctl/fileExport',
        name: 'systemctlFileExport',
        component: () => import('../views/systemctl/fileExport.vue')
    },
]
