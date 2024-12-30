import request, {HideRequestErrorHeader} from "@/api/request";

export const getRegionData = () => {
    return request.get('/api/cloud-storage-settings/provider-regions')
}

export const getSettings = () => {
    return request.get('/api/cloud-storage-settings')
}

export const savSettings = (data) => {
    return request.put('/api/cloud-storage-settings', data, {
        headers: HideRequestErrorHeader
    })
}
