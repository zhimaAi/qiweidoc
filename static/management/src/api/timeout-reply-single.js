import request from "@/api/request";

export const getBaseRule = data => {
    return request.get('/modules/timeout_reply_single/api/reply-rules', {params: data})
}

export const setBaseRule = data => {
    return request.post('/modules/timeout_reply_single/api/reply-rules', data)
}

export const getRules = data => {
    return request.get('/modules/timeout_reply_single/api/rules', {params: data})
}

export const setRule = data => {
    return request.post('/modules/timeout_reply_single/api/rules', data)
}

export const getRuleInfo = id => {
    return request.get(`/modules/timeout_reply_single/api/rules/${id}`)
}

export const delRuleInfo = id => {
    return request.delete(`/modules/timeout_reply_single/api/rules/${id}`)
}

export const updateRule = (id, data) => {
    return request.put(`/modules/timeout_reply_single/api/rules/${id}`, data)
}

export const enableRule = id => {
    return request.put(`/modules/timeout_reply_single/api/rules/${id}/enable`)
}

export const disabledRule = id => {
    return request.put(`/modules/timeout_reply_single/api/rules/${id}/disable`)
}
