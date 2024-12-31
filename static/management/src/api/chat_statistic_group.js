import request from "@/api/request";

export const getConfig = (data) => {
    return request.get('/modules/chat_statistic_group/api/config', {params: data})
}

export const setConfig = (data) => {
    return request.post('/modules/chat_statistic_group/api/config', data)
}

export const getStatList = (data) => {
    return request.get('/modules/chat_statistic_group/api/list', {params: data})
}

export const getStatDetail = (data) => {
    return request.get('/modules/chat_statistic_group/api/detail', {params: data})
}

export const updateStatInfo = (data) => {
    return request.get('/modules/chat_statistic_group/api/stat', {params: data})
}
