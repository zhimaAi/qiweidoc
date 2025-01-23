import request from "@/api/request";

export const deleteRole = data => {
    return request.post("/modules/user_permission/api/delete", data)
}

export const changeRole = data => {
    return request.post("/modules/user_permission/api/change/role", data)
}

export const permissionSave = data => {
    return request.post("/modules/user_permission/api/save", data)
}

export const permissionList = data => {
    return request.get("/modules/user_permission/api/permission/list", {params: data})
}

export const roleUserList = data => {
    return request.get("/modules/user_permission/api/user/list", {params: data})
}

export const roleList = data => {
    return request.get("/modules/user_permission/api/list", {params: data})
}

export const taskLogList = data => {
    return request.get("/modules/keywords_tagging/api/task/log/list", {params: data})
}

export const taskStatistics = data => {
    return request.get("/modules/keywords_tagging/api/task/statistics", {params: data})
}

export const taskDelete = (data = {}) => {
    return request.put('/modules/keywords_tagging/api/task/delete', data)
}

export const taskChangeSwitch = (data = {}) => {
    return request.put('/modules/keywords_tagging/api/task/change/switch', data)
}

export const taskSave = (data) => {
    return request.post("/modules/keywords_tagging/api/task/save", data)
}

export const taskInfo = data => {
    return request.get("/modules/keywords_tagging/api/task/info", {params: data})
}

export const taskList = data => {
    return request.get("/modules/keywords_tagging/api/task/list", {params: data})
}

export const conversationList = data => {
    return request.get("/api/chats/by/collect/customer/conversation/list", {params: data})
}

export const conversationGroupList = data => {
    return request.get("/api/chats/by/collect/room/conversation/list", {params: data})
}

export const joinCollect = (data = {}) => {
    return request.put('/api/chats/join/collect', data)
}

export const cancelCollect = (data = {}) => {
    return request.put('/api/chats/cancel/collect', data)
}

export const staffCstSessions = data => {
    return request.get("/api/chats/by/staff/customer/conversation/list", {params: data})
}

export const staffSessions = data => {
    return request.get('/api/chats/by/staff/staff/conversation/list', {params: data})
}

export const groupSessions = data => {
    return request.get('/api/chats/by/staff/room/conversation/list', {params: data})
}

export const cstSessions = data => {
    return request.get('/api/chats/by/customer/staff/conversation/list', {params: data})
}

export const sessionMessage = data => {
    return request.get('/api/chats/by/conversation/message/list', {params: data})
}

export const groupMessage = data => {
    return request.get('/api/chats/by/group/message/list', {params: data})
}

export const sessionCustomer = data => {
    return request.get('/api/customers/has-conversation/list', {params: data})
}

export const searchSession = data => {
    return request.get('/api/chats/search', {params: data})
}

export const chatCollect = (data) => {
    return request.get("/sessionArchive/chat/chat-collect", {params: data})
}

export const chatUnCollect = (data) => {
    return request.get("/sessionArchive/chat/chat-un-collect", {params: data})
}

export const getChatConfig = (data) => {
    return request.get("/api/chats/config/info", {params: data})
}

export const getArchiveMaxStf = (data) => {
    return request.get("/api/staff/archive/max-num", {params: data})
}

export const setChatConfig = (data) => {
    return request.post("/api/chats/config/save", data)
}

export const setSessionStaffs = (data) => {
    return request.post("/api/staff/archive/enable", data)
}
