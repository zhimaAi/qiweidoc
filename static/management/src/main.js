import { createApp } from 'vue';
import App from './App.vue';
import router from './router/index';
import store from './store';
import Antd from 'ant-design-vue';
import 'ant-design-vue/dist/reset.css';
import './common/common.less';
import { message } from 'ant-design-vue';
import VueViewer from 'v-viewer';
import SwiperClass, { /* swiper modules... */ } from 'swiper'
import VueAwesomeSwiper from 'vue-awesome-swiper'
import { registGlobalComponent } from './components'
// 引入指令入口文件
import directive from "@/directive/index";

//导入视频播放组件
import VueVideoPlayer from '@videojs-player/vue'
import 'video.js/dist/video-js.css'

// import swiper module styles
import 'swiper/css'
// more module style...

// use swiper modules
SwiperClass.use([/* swiper modules... */])
const setupAll = async () => {
const app = createApp(App);

// 挂载
directive(app);
app.config.globalProperties.$message = message;
app.use(store)
    .use(router)
    .use(Antd)
    .use(VueViewer)
    .use(VueVideoPlayer)
    .use(VueAwesomeSwiper)

  registGlobalComponent(app)

  app.mount('#app');
}

setupAll()
