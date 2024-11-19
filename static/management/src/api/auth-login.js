import request from "@/api/request";

export const checkInit = (data = {}) => {
    return request.post('/api/corps/init/check', data)
}

export const getCurrentCorp = (data = {}) => {
    return request.get('/api/corps/current', {params: data})
}

export const setCurrentCorp = (data = {}) => {
    return request.put('/api/corps/current', data)
}

export const getCurrentUser = (data = {}) => {
    return request.get('/api/users/current', {params: data})
}

export const saveCorpBasic = (data = {}) => {
    return request.post('/api/corps/basic', data)
}

export const loginByCode = (data = {}) => {
    return request.post('/api/auth/code/login', data)
}

export const saveCorpConfig = (data = {}) => {
    return request.post('/api/corps/session/saveConfig', data)
}

export const getPublicKey = (data = {}) => {
    return request.get('/api/corps/session/publicKey', {params: data})
}
