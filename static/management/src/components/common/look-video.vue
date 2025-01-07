<template>
  <a-modal title="预览视频" v-model:open="videoShow" :footer="null" width="1000px" @cancel="close">
    <div class="video">
      <video-player
        :src="videoSrc"
        :options="playerOptions"
        :volume="0.6"
      />
    </div>
  </a-modal>
</template>

<script setup>
import { ref, reactive } from 'vue'

const videoShow = ref(false)

// 视频链接地址
const videoSrc = ref('https://stream7.iqilu.com/10339/upload_transcode/202002/18/20200218093206z8V1JuPlpe.mp4');

let playerOptions = reactive({
  // height: 200,
  // width: document.documentElement.clientWidth, //播放器宽度
  playbackRates: [0.7, 1.0, 1.5, 2.0], // 播放速度
  autoplay: 'any', // 如果true,浏览器准备好时开始回放。
  muted: false, // 默认情况下将会消除任何音频。
  loop: false, // 导致视频一结束就重新开始。
  preload: 'auto', // 建议浏览器在<video>加载元素后是否应该开始下载视频数据。auto浏览器选择最佳行为,立即开始加载视频（如果浏览器支持）
  language: 'zh-CN',
  aspectRatio: '16:9', // 将播放器置于流畅模式，并在计算播放器的动态大小时使用该值。值应该代表一个比例 - 用冒号分隔的两个数字（例如"16:9"或"4:3"）
  fluid: true, // 当true时，Video.js player将拥有流体大小。换句话说，它将按比例缩放以适应其容器。
  notSupportedMessage: '此视频暂无法播放，请稍后再试', // 允许覆盖Video.js无法播放媒体源时显示的默认信息。
  controls: true
//   controlBar: {
//     timeDivider: true,
//     durationDisplay: true,
//     remainingTimeDisplay: false,
//     fullscreenToggle: true // 全屏按钮
//   }
})

const show = (url) => {
  // console.log('show', url)
  videoShow.value = true
  videoSrc.value = url
  // playerOptions.sources[0].src = url;
}
const close = () => {
  videoShow.value = false;
}

defineExpose({
  show
})
</script>

<style lang="less" scoped>
.dialog {
  z-index: 1001;
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  overflow: auto;
  margin: 0;
  background-color: rgba(43, 48, 59, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;

  .video {
    width: 1080px;
    height: 600px;
    position: relative;
  }
}
</style>
