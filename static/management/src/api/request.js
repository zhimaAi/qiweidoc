import axios from 'axios';
import Qs from 'qs';
import {message, Modal} from 'ant-design-vue';
import {getAuthToken} from "@/utils/cache";
import {logoutHandle} from "@/utils/tools";

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
        // 获取 token
        const token = getAuthToken();
        if (token) {
            config.headers.Authorization = `Bearer ${getAuthToken()}`;
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
                message.error(res?.error_message || ('网络连接出错, error_code: ' + status))
                return Promise.reject(res)
        }
    },
    error => {
        const res = error.response.data
        if (error.response && error.response.status >= 400 && error.response.status < 500) {
            switch (error.response.status) {
                case 401:
                    message.warn(res?.error_message || '登录过期，请重新登录！');
                    logoutHandle()
                    return
                default:
            }
        }
        message.error(res?.error_message || '网络连接出错')
        return Promise.reject(error)
    }
)
export default request
