import request from "@/api/request";

export const getArchiveStaffSettings = (params) => {
    return request.get('/modules/archive_staff/api/settings', {params: params})
}

export const setArchiveStaffSettings = (params) => {
    return request.put('/modules/archive_staff/api/settings', params)
}
