<template>
    <div id="_footer" class="_main-footer normal hide">
        <div class="copyright">{{ copyright || 'Copyright © 2021, 芝麻小事网络科技（武汉）有限公司 鄂ICP备19019997号-3' }}</div>
    </div>
</template>

<script setup>
import { onMounted } from "vue";

const props = defineProps({
    copyright: {
        type: String,
        default: ''
    }
})

onMounted(() => {
    window.addEventListener('load', adjustFooterPosition);
    window.addEventListener('resize', adjustFooterPosition);
    setTimeout(() => {
        adjustFooterPosition()
        if (document.getElementById('_footer')) {
            document.getElementById('_footer').classList.remove('hide')
        }
    }, 1000)
})

function adjustFooterPosition() {
    const contentHeight = document.body.scrollHeight;
    const viewportHeight = window.innerHeight;
    const footer = document.getElementById('_footer');
    if (!footer) {
        return
    }
    // 如果内容高度超过视口高度的，则正常显示，否则固定到底部
    if (contentHeight > viewportHeight) {
        footer.classList.add('normal');
    } else {
        footer.classList.remove('normal');
    }
}
</script>

<style scoped lang="less">
._main-footer {
    position: fixed;
    width: 100%;
    left: 0;
    bottom: 24px;
    text-align: center;

    &.hide {
        opacity: 0;
    }

    &.normal {
        position: relative;
        margin-top: 48px;
    }

    .copyright {
        color: #8c8c8c;
        font-size: 12px;
        font-weight: 400;
    }
}
</style>
