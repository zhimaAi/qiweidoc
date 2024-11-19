<template>
  <div class="cu-scroll-box" ref="scrollableDiv" @scroll="handleScroll">
    <slot></slot>
  </div>
</template>

<script setup>
import { ref } from 'vue'
const SafeHeight = 30
const emit = defineEmits(['isScrollBottom'])
const scrollableDiv = ref(null)
const handleScroll = () => {
  if (scrollableDiv.value) {
    const { scrollTop, clientHeight, scrollHeight } = scrollableDiv.value
    // 判断是否滚动到底部
    if (scrollTop + clientHeight >= scrollHeight - SafeHeight) {
      emit('isScrollBottom')
    }
  }
}
</script>

<style lang="less" scoped>
.cu-scroll-box {
  height: 100%;
  overflow: auto;
}
.cu-scroll-box::-webkit-scrollbar {
  width: 4px; /*  设置纵轴（y轴）轴滚动条 */
  height: 4px; /*  设置横轴（x轴）轴滚动条 */
}
/* 滚动条滑块（里面小方块） */
.cu-scroll-box::-webkit-scrollbar-thumb {
  border-radius: 4px;
  background: transparent;
}
/* 滚动条轨道 */
.cu-scroll-box::-webkit-scrollbar-track {
  border-radius: 4px;
  background: transparent;
}

/* hover时显色 */
.cu-scroll-box:hover::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
}
.cu-scroll-box:hover::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.1);
}
</style>
