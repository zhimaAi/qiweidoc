export default [
    {
        path: '/',
        redirect: '/module/customer_tag/index'
    },
    {
        path: '/module/customer_tag/index',
        name: 'CustomerLabelInex',
        component: () => import('@/views/Modules/customer_tag/index.vue')
    }
]
