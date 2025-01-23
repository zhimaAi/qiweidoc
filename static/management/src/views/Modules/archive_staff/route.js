export default [
    {
        path: '/',
        redirect: '/module/archive_staff/index'
    },
    {
        path: '/module/archive_staff/index',
        name: 'ArchiveStaffIndex',
        component: () => import('@/views/Modules/archive_staff/index.vue')
    },
]
