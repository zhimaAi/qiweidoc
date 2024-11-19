import request from "@/api/request";

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

export const searchSession = data => {
    return request.get('/api/chats/search', {params: data})
}
