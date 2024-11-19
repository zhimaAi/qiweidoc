export default [
    {
        path: '/companyManagement/staff',
        name: 'companyManagementStaff',
        component: () => import('../views/companyManagement/staff.vue')
    },
    {
        path: '/companyManagement/seat',
        name: 'companyManagementSeat',
        component: () => import('../views/companyManagement/seat.vue')
    },
    {
        path: '/groupManagement/index',
        name: 'groupManagementHome',
        component: () => import('../views/groupManagement/index.vue')
    }
]
