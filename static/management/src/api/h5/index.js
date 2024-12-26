import request, {H5RequestHeader} from "@/api/request";

export const getAgentConfig = data => {
    return request.get('/api/wx/h5/get-agent-config', {
        params: data,
        headers: H5RequestHeader
    })
}

export const h5PrivateSessionMessage = data => {
    return request.get('/api/wx/h5/conversation/messages', {
        params: data,
        headers: H5RequestHeader
    })
}

export const h5GroupSessionMessage = data => {
    return request.get('/api/wx/h5/group/messages', {
        params: data,
        headers: H5RequestHeader
    })
}
