<template>
    <!---拖拽拉伸盒子-改变宽度-->
    <div class="zm-drag-stretch-box">
        <div :id="domUniqueId"
             class="zm-drag-line"
             @mousedown="magnifyBoxStart"
             @mouseout="magnifyBoxEnd"></div>
        <slot></slot>
    </div>
</template>

<script setup>
import {onMounted, ref, reactive} from 'vue';

const props = defineProps({
    minWidth: {
        type: Number,
        default: 0,
    },
    maxWidth: {
        type: Number,
        default: 10000,
    }
})

const emit = defineEmits(['change'])

const domUniqueId = ref('zm-drag')
onMounted(() => {
    domUniqueId.value = "zm-drag-box-" + Math.floor(Math.random() * 10000 * Date.now())
})

const magnifyData = reactive({
    dom: null,
    dragDom: null,
    currentBoxWidth: 0,
    startX: 0,
    endX: 0,
})


function magnifyBoxStart(e) {
    let dragDom = document.getElementById(domUniqueId.value)
    if (!dragDom) {
        return
    }
    let boxDom = dragDom.parentNode
    magnifyData.currentBoxWidth = boxDom.offsetWidth
    magnifyData.maxWidth = magnifyData.slideBoxWidth - magnifyData.centerBoxWidth
    dragDom && dragDom.classList.add("large")
    magnifyData.dom = boxDom;
    magnifyData.dragDom = dragDom;
    magnifyData.startX = e.x;
    window.addEventListener('mouseup', magnifyBoxEnd)
    window.addEventListener('mouseleave', magnifyBoxEnd)
    window.addEventListener('mousemove', magnifyBoxMove)
}

// 拖动动中
function magnifyBoxMove(e) {
    magnifyData.endX = e.x
    let movingDistance = magnifyData.endX - magnifyData.startX
    let width = magnifyData.currentBoxWidth + movingDistance
    width = Math.min(props.maxWidth, width)
    width = Math.max(props.minWidth, width)
    if (magnifyData.dom) {
        magnifyData.dom.style.width = width + "px"
    }
}

//鼠标拖动结束
function magnifyBoxEnd() {
    window.removeEventListener('mousemove', magnifyBoxMove);//移除事件
    window.removeEventListener('mouseup', magnifyBoxEnd);//移除事件
    window.removeEventListener('mouseleave', magnifyBoxEnd);
    if (magnifyData.dragDom && magnifyData.dom) {
        magnifyData.dragDom.classList.remove("large")
        emit('change', magnifyData.dom.offsetWidth)
    }
}
</script>

<style scoped lang="less">
.zm-drag-stretch-box {
    position: relative;

    &:hover .zm-drag-line {
        display: block;
    }

    .zm-drag-line {
        display: none;
        position: absolute;
        right: -15px;
        top: 0;
        bottom: 0;
        width: 30px;
        z-index: 99;
        cursor: col-resize;

        &.large {
            right: -100px;
            width: 200px;
            //background: rgba(0, 0, 0, 0.5);
        }
    }
}
</style>
