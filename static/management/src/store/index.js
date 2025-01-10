import {createStore} from 'vuex'
import {getAuthToken, getCorpInfo, getUserInfo} from "@/utils/cache";
import {getModules} from "@/api/company";
import createPersistedState from 'vuex-persistedstate';
import {jsonDecode} from "@/utils/tools";
import dayjs from 'dayjs';

const getState = () => {
    return {
        document_title: '',
        corp_id: '',
        agent_id: '',
        user_info: {},
        modules: {},
        mainModuleInfo: {},
        corp_info: {},
        company: {
            title: '',
            logo: '',
            navigation_bar_title: '',
            login_page_title: '',
            login_page_description: '',
            copyright: ''
        }, // 企业
    }
}

export default createStore({
    state: getState(),
    getters: {
        getCorpId: state => state.corp_id,
        getAgentId: state => state.agent_id,
        getCorpInfo: state => state.corp_info,
        getUserInfo: state => state.user_info,
        getCompany: state => state.company,
        getModules: state => state.modules,
        getMainModule: state => state.mainModuleInfo,
    },
    mutations: {
        RESET_STATE(state) {
            Object.assign(state, getState());
        },
        setLoginInfo(state, info) {
            state.user_info = info
        },
        setModules(state, info) {
            state.modules = info
        },
        setMainModule(state, info) {
            state.mainModuleInfo = info
        },
        setCorpInfo(state, corp_info) {
            state.corp_info = corp_info
            state.corp_id = corp_info.id
            state.agent_id = corp_info.agent_id
        },
        setCompany(state, info) {
            state.company = info
            if (info.title) {
                state.document_title = info.title
                document.title = info.title
            }
        }
    },
    actions: {
        checkLogin({state, commit}) {
            if (!getAuthToken()) {
                return false
            }
            if (!state.corp_info.id) {
                let corp_info = getCorpInfo()
                let user_info = getUserInfo()
                if (user_info && corp_info) {
                    commit('setLoginInfo', user_info)
                    commit('setCorpInfo', corp_info)
                } else {
                    return false
                }
            }
            return true
        },
        updateModules({state, commit}) {
            return getModules().then(res => {
                let localMods = res?.data?.local_module_list || []
                let mainModule = res?.data?.main_local_module || {}
                let modules = res?.data?.remote_module_list || []
                let find
                const mainVersion = mainModule?.version
                const nowTime = dayjs().unix()
                modules.map(item => {
                    find = localMods.find(i => item.name === i.name)
                    item.is_install = false
                    // 系统是否启用（后台是否启用该插件）
                    item.system_enabled = (item.enabled == 1)
                    // 是否启用（用户）
                    item.is_enabled = false
                    item.local_version = ''
                    // 兼容main模块版本
                    item.compatible_main_version_list = jsonDecode(item?.latest_version?.compatible_main_version_list)
                    // 最新版本是否兼容当前main模块
                    item.is_compatible_main =  item.compatible_main_version_list.includes(mainVersion)
                    if (item?.expire_time > 0) {
                        item.is_expired = (item.expire_time < nowTime)
                        item.expire_date = dayjs(item.expire_time * 1000).format('YYYY-MM-DD HH:mm')
                    }
                    switch (Number(item.price_type)) {
                        case 1:
                            item.price_info = '免费'
                            break
                        case 2:
                            item.price_info = `¥${item.price_value}/年`
                            break
                        case 3:
                            item.price_info = item.price_value.substring(0, 12)
                            break
                    }
                    if (find) {
                        // 已安装
                        item.is_install = true
                        // 是否启用
                        item.is_enabled = !find.paused
                        // 本地版本
                        item.local_version = find.version
                        // 是否最新版本
                        item.is_last_version = (item.local_version === item?.latest_version?.version)
                        // 本地版本是否兼容当前main模块（目前不存在此情况）
                        if (find?.compatible_main_version_list) {
                            item.local_is_compatible_main = find.compatible_main_version_list.includes(mainVersion)
                        }
                    }
                })
                commit('setModules', modules)
                commit('setMainModule', mainModule)
                return Promise.resolve(modules)
            })
        }
    },
    modules: {},
    plugins: [createPersistedState({
        storage: window.localStorage, // 或者 sessionStorage
    })]
})
