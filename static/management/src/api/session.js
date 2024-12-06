import request from "@/api/request";

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
