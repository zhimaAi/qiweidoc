import request from "@/api/request";

export const customerTags = (data = {}) => {
    return request.get('/api/tags/customer', {params: data})
}

export const staffTags = (data = {}) => {
    return request.get('/api/tags/staff', {params: data})
}
