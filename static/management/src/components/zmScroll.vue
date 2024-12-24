<template>
    <div :class="['zm-list-container',{'hide-scrollbar': !showScrollbar}]"
         :id="domId"
         @scroll="onScroll"
    >
        <div v-if="showLoading && loadType === 'up'" class="loading-box">
            <template v-if="loading">
                <a-spin size="small"/>
                <span class="ml4">正在加载...</span>
            </template>
            <template v-else-if="finished && hasScroll">已经到顶了</template>
        </div>
        <div class="zm-list-content">
            <slot></slot>
        </div>
        <div v-if="showLoading && loadType === 'down'" class="loading-box bottom">
            <template v-if="loading">
                <a-spin size="small"/>
                <span class="ml4">正在加载...</span>
            </template>
            <template v-else-if="finished && hasScroll">已经到底了</template>
        </div>
    </div>
</template>

<script setup>
import {onMounted, watch, nextTick, ref} from 'vue';

const props = defineProps({
    immediate: {
        type: Boolean,
        default: true,
    },
    loading: {
        type: Boolean,
    },
    loadType: {
        type: String,
        default: 'down'
    },
    finished: {
        type: Boolean
    },
    offsets: {
        type: Number,
        default: 50
    },
    showLoading: {
        type: Boolean,
        default: true
    },
    showScrollbar: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['load'])
const domId = ref('')
const hasScroll = ref(false)
const scrollLoading = ref(false)
onMounted(() => {
    domId.value = `zm-scroll-${Date.now() * Math.floor(Math.random() * 10000)}`
    props.immediate && emit('load')
})

watch(() => props.loading, () => {
    if (!props.loading && !props.finished) {
        nextTick(() => {
            // 检测当前列表是否存在滚动条，不存在则继续加载
            hasScroll.value = checkScroll()
            if (!hasScroll.value) {
                emit('load')
            }
        })
    }
})

const checkScroll = () => {
    const dom = window.document.getElementById(domId.value)
    return dom.scrollHeight > dom.offsetHeight
}

const onScroll = e => {
    // console.log('props.finished', props.finished)
    if (props.finished || props.loading || scrollLoading.value) {
        // 如果已经加载完成或者正在加载中，则不处理滚动
        return
    }
    const {scrollTop, scrollHeight, offsetHeight} = e.target
    if (props.loadType === 'down') {
        //向下加载 如果滚动到接近底部并且内容还没有加载完成
        if (scrollTop + offsetHeight >= scrollHeight - props.offsets) {
            emit('load')
        }
    } else if (props.loadType === 'up') {
        //向上加载 如果滚动接近顶部并且内容还没有加载完成
        if (scrollTop <= props.offsets) {
            emit('load')
        }
    }
}

const getListDom = () => {
    return window.document.getElementById(domId.value)
}

const scrollToBottom = () => {
    const dom = getListDom()
    scrollLoading.value = true
    dom.scrollTop = dom.scrollHeight
    setTimeout(() => {
        scrollLoading.value = false
    }, 500)
}

const setScrollTop= val => {
    const dom = getListDom()
    scrollLoading.value = true
    dom.scrollTop = val
    setTimeout(() => {
        scrollLoading.value = false
    }, 200)
}

defineExpose({
    getListDom,
    scrollToBottom,
    setScrollTop,
})
</script>

<style scoped lang="less">
.zm-list-container {
    overflow: hidden;
    overflow-y: auto;
    position: relative;
}

.loading-box {
    text-align: center;
    color: #999999;
    font-size: 12px;
    top: 8px;
    width: 100%;
    padding: 8px;

    &.bottom {
        bottom: 8px;
        top: unset;
    }
}
</style>
