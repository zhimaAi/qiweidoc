import {createStore} from 'vuex'
import {getAuthToken, getCorpInfo, getUserInfo} from "@/utils/cache";

const getState = () => {
    return {
        corp_id: '',
        agent_id: '',
        user_info: {},
        corp_info: {},
    }
}

export default createStore({
    state: getState(),
    getters: {
        getCorpId: state => state.corp_id,
        getAgentId: state => state.agent_id,
        getCorpInfo: state => state.corp_info,
        getUserInfo: state => state.user_info
    },
    mutations: {
        RESET_STATE(state) {
            Object.assign(state, getState());
        },
        setLoginInfo(state, info) {
            state.user_info = info
        },
        setCorpInfo(state, corp_info) {
            state.corp_info = corp_info
            state.corp_id = corp_info.id
            state.agent_id = corp_info.agent_id
        },
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
        }
    },
    modules: {}
})
