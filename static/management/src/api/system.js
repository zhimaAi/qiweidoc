import request, {HideRequestErrorHeader} from "@/api/request";

export const getDiskUsage = () => {
    return request.get('/api/system/disk-usage', {
        headers: HideRequestErrorHeader
    })
}
