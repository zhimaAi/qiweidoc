const {defineConfig} = require('@vue/cli-service')
const dev_host = process.env.VUE_APP_HOST || '/';
const path = require("path");
const webpack = require('webpack');
module.exports = defineConfig({
    transpileDependencies: true,
    devServer: {
        open: true,
        proxy: {
            "/": {
                target: dev_host,
                changeOrigin: true, // 是否跨域
                pathRewrite: {
                    "^/": "/",
                },
            },
        },
    },
    configureWebpack: {
        resolve: {
            //配置路径别名
            alias: {
                "@image": "@/assets/image",
                "@style": "@/assets/style",
                "@components": "@/components",
                "@views": "@/views",
                "@api": "@/api",
                "@icons": "@/icons",
            },
        },
        plugins: [
            new webpack.DefinePlugin({
                __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false',
            })
        ],
    },
    lintOnSave: false,
})
