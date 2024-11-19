import request from "@/api/request";

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
