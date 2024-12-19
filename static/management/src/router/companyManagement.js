export default [
    {
        path: '/companyManagement/staff',
        name: 'companyManagementStaff',
        component: () => import('../views/companyManagement/staff.vue')
    },
    {
        //暂未使用
        path: '/companyManagement/seat',
        name: 'companyManagementSeat',
        component: () => import('../views/companyManagement/seat.vue')
    },
    {
        path: '/companyManagement/accounts',
        name: 'companyManagementAccounts',
        component: () => import('../views/companyManagement/accounts.vue')
    }
]
