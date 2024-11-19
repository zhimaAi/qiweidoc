/*
 * @Description:
 * @Author: chris
 * @Date: 2021-11-24 11:48:52
 * @LastEditTime: 2022-02-21 12:18:00
 * @LastEditors: chris
 */

const USER_AUTH_TOKEN = 'zm:session:archive:login:token';
const USER_INFO_KEY = 'zm:session:archive:login:user';
const CORP_INFO_KEY = 'zm:session:archive:login:corp';

export function setAuthToken(token) {
    set(USER_AUTH_TOKEN, {token: token})
}

export function setUserInfo(data) {
    set(USER_INFO_KEY, data)
}

export function getUserInfo() {
   return get(USER_INFO_KEY)
}

export function getAuthToken() {
   return get(USER_AUTH_TOKEN)?.token || ''
}

export function setCorpInfo(data) {
    set(CORP_INFO_KEY, data)
}

export function getCorpInfo(data) {
    return get(CORP_INFO_KEY)
}

/**
 * 设置浏览器缓存方法
 * @param key
 * @param value
 * @param time 当time不传入时默认为-1 永久保存
 */
export function set(key, value, time = -1) {
  if (Array.isArray(value) || typeof (value) === 'string' || typeof (value) === 'number' || typeof (value) === 'boolean') { //兼容数组、字符串类型缓存写法
    let data = value;
    value = {};
    value[time] = data;
  }
  value.cacheTimestamp = time;
  return localStorage.setItem(key, JSON.stringify(value));
}

/**
 * 获取浏览器缓存方法
 * @param key
 */
export function get(key) {
  let data = localStorage.getItem(key)
  if (data) {
    try {
      let obj = JSON.parse(data);
      if (Object.keys(obj).length > 0) { //此处是对象缓存
        let now = new Date().getTime() / 1000;
        if (obj.cacheTimestamp && obj.cacheTimestamp > 0 && obj.cacheTimestamp < now) {
          del(key);
          return false
        }
        if (obj.cacheTimestamp && Array.isArray(obj[obj.cacheTimestamp]) || typeof (obj[obj.cacheTimestamp]) === 'string' || typeof (obj[obj.cacheTimestamp]) === 'number' || typeof (value) === 'boolean') { //兼容数组类型、數字、字符串缓存
          return obj[obj.cacheTimestamp];
        }
        return obj;
      }
      return data;
    } catch (e) {
      console.log(e,99999);
      return data;
    }
  } else {
    return false;
  }
}

/**
 * 删除浏览器缓存方法
 * @param key
 */
export function del(key) {
  return localStorage.removeItem(key);
}

export default {
  set,
  get,
  del
}
