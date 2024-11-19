import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
    base: '/docs',
    title: "芝麻会话存档",
    description: "企微会话存档服务",
    themeConfig: {
        // https://vitepress.dev/reference/default-theme-config
        nav: [
            { text: '首页', link: '/' },
            { text: '文档', link: '/docs' },
            { text: '体验', link: 'http://hhdev1.xiaokefu.cn/management#/' },
            { text: '芝麻小客服', link: 'https://www.xiaokefu.com.cn' },
        ],

        sidebar: [
            {
                text: '帮助文档',
                items: [
                    { text: '介绍', link: '/docs' },
                    { text: '部署', link: '/deploy' }
                ]
            }
        ],

        socialLinks: [
            { icon: 'github', link: 'https://github.com/zhimaAi/qw-session-archive' }
        ]
    }
})
