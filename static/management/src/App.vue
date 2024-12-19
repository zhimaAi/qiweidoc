<template>
    <a-config-provider :locale="zhCN">
        <router-view/>
    </a-config-provider>
</template>

<script setup>
import {onMounted} from 'vue';
import zhCN from 'ant-design-vue/es/locale/zh_CN';
import dayjs from 'dayjs';
import 'dayjs/locale/zh-cn';
import {updateUserInfo} from "@/utils/tools";

dayjs.locale('zh-cn');

onMounted(() => {
    updateUserInfo()
})
</script>
<script >
// 解决ERROR ResizeObserver loop completed with undelivered notifications.
//问题的
const debounce = (fn, delay) => {
  let timer = null;
  return function () {
    let context = this;
    let args = arguments;
    clearTimeout(timer);
    timer = setTimeout(function () {
      fn.apply(context, args);
    }, delay);
  };
};
// 解决ERROR ResizeObserver loop completed with undelivered notifications.
const _ResizeObserver = window.ResizeObserver;
window.ResizeObserver = class ResizeObserver extends _ResizeObserver {
  constructor(callback) {
    callback = debounce(callback, 16);
    super(callback);
  }
};
</script>

<style lang="less">
body {
    background-color: #f0f2f5;
}

#app {
    font-size: 14px;
    font-family: Avenir, Helvetica, Arial, sans-serif;
    color: #595959;
}

nav {
    padding: 30px;

    a {
        font-weight: bold;
        color: #2c3e50;

        &.router-link-exact-active {
            color: #42b983;
        }
    }
}
</style>
