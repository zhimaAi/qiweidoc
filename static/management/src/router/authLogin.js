export default [
    {
        path: '/',
        redirect: '/index'
    },
    {
        path: '/index',
        name: 'index',
        component: () => import('../views/index.vue')
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('../views/login.vue'),
        meta: {
            ignoreLogin: true
        },
    },
    {
      path: '/403',
      name: 'Error403',
      component: () => import('../views/error/error-403.vue'),
      meta: {
        title: '没有权限',
        noAuth: true,
        isWhiteRouter: true
      }
    },
    {
        path: '/authorizedAccess/index',
        name: 'authIndex',
        component: () => import('../views/authorizedAccess/index.vue'),
        meta: {
            ignoreLogin: true
        },
    },
    {
        path: '/authorizedAccess/guide',
        name: 'authGuide',
        component: () => import('../views/authorizedAccess/guide.vue')
    },
    {
        path: '/authorizedAccess/auth',
        name: 'authHome',
        component: () => import('../views/authorizedAccess/auth.vue')
    },
    {
        path: '/module/chat-statistic-group/index',
        name: 'GroupChatStatIndex',
        component: () => import('@/views/Modules/chat_statistic_group/index.vue')
    },
    {
        path: '/module/chat-statistic-group/rule',
        name: 'GroupChatStatRule',
        component: () => import('@/views/Modules/chat_statistic_group/rule.vue')
    },
]
