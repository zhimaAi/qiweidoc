const {defineConfig} = require('@vue/cli-service')
const dev_host = process.env.VUE_APP_HOST || 'http://hhdev4.xiaokefu.cn';
const path = require("path");
const webpack = require('webpack');
const ModuleConfig = require('./module-config');
const RootPath = path.resolve(__dirname, '../../');
let outputDir = "../dist"
const Module = process.env.VUE_APP_MODULE || 'main'
if (Module && ModuleConfig[Module]) {
    outputDir = RootPath + ModuleConfig[Module].buildOutputPath
}
let publicPath
if (process.env.NODE_ENV === 'development') {
    publicPath = '/'
} else {
    publicPath = ModuleConfig[Module].buildOutputPath.replace('/php', '')
    publicPath = publicPath.replace('/public', '')
}

module.exports = defineConfig({
    outputDir: outputDir,
    publicPath: publicPath,
    transpileDependencies: true,
    productionSourceMap: false,
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
