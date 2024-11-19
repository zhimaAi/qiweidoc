import { defineConfig } from 'vitepress'
import { withMermaid } from 'vitepress-plugin-mermaid'

// https://vitepress.dev/reference/site-config
export default withMermaid(
    defineConfig({
        base: '/docs',
        title: "芝麻会话存档帮助文档",
        description: "企微会话存档服务",
        themeConfig: {
            // https://vitepress.dev/reference/default-theme-config
            nav: [
                { text: '体验', link: '/management#/' },
                { text: '芝麻微客-会话存档', link: 'https://huihuacundang.zmwk.cn' },
            ],

            sidebar: [
                {
                    text: '帮助文档',
                    items: [
                        { text: '介绍', link: '/index' },
                        { text: '技术架构', link: '/tech' },
                        { text: '部署指导', link: '/deploy' },
                        { text: '接入流程', link: '/integration' }
                    ]
                }
            ],

            socialLinks: [
                { icon: 'github', link: 'https://github.com/zhimaAi/qw-session-archive' }
            ]
        },

        // Mermaid 配置
        mermaid: {
            // 自定义主题配置
            theme: 'default',
            // 其他 Mermaid 初始化配置
            defaultDiagram: 'flowchart',
            darkMode: true,
        },
    })
)
