import SvgIcon from './svg-icon/index.vue'
import CuScroll from './cu-scroll/index.vue'
import CommonEmpty from './common-empty/index.vue'
export const registGlobalComponent = (app) => {
  // 全局注册组件
  app.component('SvgIcon', SvgIcon)
  app.component('CuScroll', CuScroll)
  app.component('CommonEmpty', CommonEmpty)
}
