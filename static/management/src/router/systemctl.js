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
    {
        path: '/systemctl/fileExport',
        name: 'systemctlFileExport',
        component: () => import('../views/systemctl/fileExport.vue')
    },
]
