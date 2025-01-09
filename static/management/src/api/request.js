import axios from 'axios';
import {message} from 'ant-design-vue';
import {getAuthToken, getH5AuthToken} from "@/utils/cache";
import {logoutHandle} from "@/utils/tools";

let resErrorAbort = false

// 避免出现多条提示
// 如登录过期时
const showGlobalError = (content, type = 'error') => {
    if (!resErrorAbort) {
        resErrorAbort = true
        setTimeout(() => {
            resErrorAbort = false
        }, 2000)
        message[type](content)
    }
}

export const H5RequestHeader = {'H5-Special-Request': true}
export const HideRequestErrorHeader = {'Custom-Handle-Error': true}

const request = axios.create({
    headers: {
        'Content-Type': 'application/json',
    },
    timeout: 60000000, // 请求超时时间
    transformRequest: [function (data) {
        data = JSON.stringify(data)
        return data
    }]
});

// request拦截器
request.interceptors.request.use(
    config => {
        // h5请求
        let tokenFn = config.headers['H5-Special-Request'] ? getH5AuthToken : getAuthToken
        // 获取 token
        const token = tokenFn();
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config; // 返回修改后的 config
    },
    error => {
        // 处理请求错误
        return Promise.reject(error);
    }
);


// respone拦截器
request.interceptors.response.use(
    response => {
        const status = response.status
        const res = response.data
        if (response.config.notice === false) {//如果不需要提醒时，直接返回
            return Promise.resolve(res)
        }
        switch (status) {
            case 200:
            case 201:
                if (res.status === 'success') {
                    return Promise.resolve(res)
                } else {
                    if (!res?.error_message) {
                        res.error_message = '当前业务操作发生异常，请联系客服！'
                    }
                    message.error(res.error_message);
                    return Promise.reject(res)
                }
                break
            default:
                // 写入错误信息
                showGlobalError(res?.error_message || ('网络连接出错, error_code: ' + status))
                return Promise.reject(res)
        }
    },
    error => {
        const res = error?.response?.data
        switch (error?.response?.status) {
            case 401:
                showGlobalError(res?.error_message || '登录过期，请重新登录！', 'warn');
                logoutHandle()
                break
            case 403:
                window.location.href = "/#/403"
                break
            default:
                if (!error?.config?.headers['Custom-Handle-Error']) {
                    showGlobalError(res?.error_message || '网络连接出错');
                }
                break
        }
        return Promise.reject(res)
    }
)
export default request
