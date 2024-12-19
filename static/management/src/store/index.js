import {createStore} from 'vuex'
import {getAuthToken, getCorpInfo, getUserInfo} from "@/utils/cache";
import {getModules} from "@/api/company";
import { DEFAULT_ZH_LOGO } from '@/constants/index'
import createPersistedState from 'vuex-persistedstate';

const getState = () => {
    return {
        corp_id: '',
        agent_id: '',
        user_info: {},
        modules: {},
        corp_info: {},
        company: {
            corp_name: '',
            corp_logo: DEFAULT_ZH_LOGO
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
        getModules: state => state.modules
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
        setCorpInfo(state, corp_info) {
            state.corp_info = corp_info
            state.corp_id = corp_info.id
            state.agent_id = corp_info.agent_id
        },
        setCompany(state, info) {
            state.company = info
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
            getModules().then(res => {
                let data = res.data || []
                data.map(item => {
                    item.enable = !item.paused
                })
                commit('setModules', data)
            })
        }
    },
    modules: {},
    plugins: [createPersistedState({
        storage: window.localStorage, // 或者 sessionStorage
    })]
})
