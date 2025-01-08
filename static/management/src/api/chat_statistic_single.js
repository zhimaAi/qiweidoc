import request from "@/api/request";

export const getConfig = (data) => {
    return request.get('/modules/chat_statistic_single/api/single/chat/stat/config', {params: data})
}

export const setConfig = (data) => {
    return request.post('/modules/chat_statistic_single/api/single/chat/stat/config', data)
}

export const getStatData = (data) => {
    return request.get('/modules/chat_statistic_single/api/single/chat/stat/total', {params: data})
}

export const getStatList = (data) => {
    return request.get('/modules/chat_statistic_single/api/single/chat/stat/list', {params: data})
}

export const getStatDetail = (data) => {
    return request.get('/modules/chat_statistic_single/api/single/chat/stat/detail', {params: data})
}

export const updateStatInfo = (data) => {
    return request.get('/modules/chat_statistic_single/api/single/chat/stat/stat', {params: data})
}
