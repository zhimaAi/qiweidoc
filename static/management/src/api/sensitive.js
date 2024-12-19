import request from "@/api/request";

export const keywordsDelete = (data) => {
    return request.post('/modules/hint_keywords/api/hint/keywords/delete', data)
}

export const keywordsRuleDelete = (data) => {
    return request.post('/modules/hint_keywords/api/hint/keywords/rule/delete', data)
}

export const ruleDetail = (data) => {
    return request.get('/modules/hint_keywords/api/hint/keywords/rule/detail', {params: data})
}

export const ruleStatistics = (data) => {
    return request.get('/modules/hint_keywords/api/hint/keywords/rule/statistics', {params: data})
}

export const noticeInfo = (data) => {
    return request.get('/modules/hint_keywords/api/hint/keywords/notice/info', {params: data})
}

export const noticeSave = (data) => {
    return request.post('/modules/hint_keywords/api/hint/keywords/notice/save', data)
}

export const changeStatus = (data) => {
    return request.post('/modules/hint_keywords/api/hint/keywords/rule/change/status', data)
}

export const keywordsRuleInfo = (data) => {
    return request.get('/modules/hint_keywords/api/hint/keywords/rule/info', {params: data})
}

export const keywordsRuleList = (data) => {
    return request.get('/modules/hint_keywords/api/hint/keywords/rule/list', {params: data})
}

export const keywordsList = (data) => {
    return request.get('/modules/hint_keywords/api/hint/keywords/list', {params: data})
}

export const keywordsRuleSave = (data) => {
    return request.post('/modules/hint_keywords/api/hint/keywords/rule/save', data)
}

export const keywordsSave = (data) => {
    return request.post('/modules/hint_keywords/api/hint/keywords/save', data)
}
