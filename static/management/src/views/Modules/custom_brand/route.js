export default [
    {
        path: '/',
        redirect: '/module/custom_brand/index'
    },
    {
        path: '/module/custom_brand/index',
        name: 'CustomerLabelInex',
        component: () => import('@/views/Modules/custom_brand/index.vue')
    }
]
