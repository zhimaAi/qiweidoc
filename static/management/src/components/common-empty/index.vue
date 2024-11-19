<template>
  <div class="empty-box" :style="{ height: `${props.height}px` }">
    <a-empty :description="props.description" :image="simpleImage" />
  </div>
</template>

<script setup>
import { Empty } from 'ant-design-vue'
const simpleImage = Empty.PRESENTED_IMAGE_SIMPLE
const props = defineProps({
  height: {
    default: 300,
    type: Number
  },
  description: {
    default: '暂无数据',
    type: String
  }
})
</script>

<style lang="less" scoped>
.empty-box {
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
