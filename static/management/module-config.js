const ModuleConfig = {
    /**
    模块KEY: {
        buildOutputPath: 编译输出目录
    },
    **/
    main: {
        buildOutputPath: '/php/modules/main/public/build/',
    },
    hint_keywords: {
        buildOutputPath: '/php/modules/hint_keywords/public/build/',
    },
    single_chat_stat: {
        buildOutputPath: '/php/modules/single_chat_stat/public/build/',
    },
    chat_statistic_group: {
        buildOutputPath: '/php/modules/chat_statistic_group/public/build/',
    },
    timeout_reply_single: {
        buildOutputPath: '/php/modules/timeout_reply_single/public/build/',
    },
    timeout_reply_group: {
        buildOutputPath: '/php/modules/timeout_reply_group/public/build/',
    },
}

module.exports = ModuleConfig;
