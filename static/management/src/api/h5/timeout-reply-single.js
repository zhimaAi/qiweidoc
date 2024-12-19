import request, {H5RequestHeader} from "@/api/request";

export const getAgentConfig = data => {
    return request.get('/modules/timeout_reply_single/api/wx/h5/get-agent-config', {
        params: data,
        headers: H5RequestHeader
    })
}

export const h5SessionMessage = data => {
    return request.get('/modules/timeout_reply_single/api/wx/h5/messages', {
        params: data,
        headers: H5RequestHeader
    })
}
