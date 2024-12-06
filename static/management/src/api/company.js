import request from "@/api/request";

export const deleteGroupTags = (data) => {
    return request.delete(`/modules/customer_tag/api/tags/group/${data.group_id}`)
}

export const deleteTags = (data) => {
    return request.delete(`/modules/customer_tag/api/tags/${data.tag_id}`)
}

export const updateTags = (data) => {
    return request.post('/modules/customer_tag/api/tags', data)
}

export const apiTags = (data) => {
    return request.get('/modules/customer_tag/api/tags', {params: data})
}

export const getModulesInfo = (data) => {
    return request.get(`/api/modules/${data.name}`)
}

export const enableModules = (data) => {
    return request.put(`/api/modules/${data.name}/enable`)
}

export const disableModules = (data) => {
    return request.put(`/api/modules/${data.name}/disable`)
}

export const getModules = (data) => {
    return request.get('/api/modules', {params: data})
}

export const syncDepartmentStaff = () => {
    return request.get('/api/department/sync')
}

export const departmentList = () => {
    return request.get('/api/department/list')
}

export const staffList = (data) => {
    return request.get('/api/staff/list', {params: data})
}

export const groupsList = (data) => {
    return request.get('/api/groups/list', {params: data})
}

export const groupsSync = (data) => {
    return request.get('/api/groups/sync', {params: data})
}

export const customersList = (data) => {
    return request.get('/api/customers/list', {params: data})
}

export const customersSync = (data) => {
    return request.get('/api/customers/sync', {params: data})
}
