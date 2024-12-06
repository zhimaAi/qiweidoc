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

// import swiper module styles
import 'swiper/css'
// more module style...

// use swiper modules
SwiperClass.use([/* swiper modules... */])

const app = createApp(App);
app.use(store)
    .use(router)
    .use(Antd)
    .use(VueViewer)
    .use(VueAwesomeSwiper)
    .mount('#app');

app.config.globalProperties.$message = message;
