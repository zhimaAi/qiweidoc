/**
 * 请求contentType
 */
export const CONTENT_TYPE = 'application/x-www-form-urlencoded;charset=UTF-8'

/**
 * 请求超时时间
 */
export const REQUEST_TIMEOUT = 10 * 60 * 1000

/**
 * 刷新Token时间
 */
export const REFRESHTOKEN_TIMEOUT = 1 * 60 * 60 * 1000

// 默认的用户头像
export const DEFAULT_USER_AVATAR = new URL('@/assets/default-avatar.png', import.meta.url).href

// 中文logo
export const DEFAULT_ZH_LOGO = new URL('@/assets/logo.png', import.meta.url).href
