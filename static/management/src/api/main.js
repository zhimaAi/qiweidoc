import request from "@/api/request";

export const staffTags = (data = {}) => {
    return request.get('/api/tags/staff', {params: data})
}
