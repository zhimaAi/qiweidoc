<template>
    <a-modal title="联系客服" width="408px" v-model:open="visible" :footer="false" centered>
        <div class="text-center container" style="padding-top: 10px">
            <div class="code-box">
                <img src="https://zmwk.cn/static/image/home/qrcode.jpg?v=20231117"/>
            </div>
            <span class="message">{{ tip }}</span>
        </div>
    </a-modal>
</template>

<script setup>
import {ref} from 'vue';

const visible = ref(false)
const props = defineProps({
    tip: {
        type: String,
        default: '微信扫码咨询'
    }
})

const show = () => {
    visible.value = true
}

defineExpose({
    show,
})
</script>

<style scoped lang="less">
.container {
    display: flex;
    flex-direction: column;
    align-items: center;

    .code-box {
        border-radius: 2px;
        border: 1px solid rgba(0, 0, 0, 0.15);
        display: inline-block;

        img {
            width: 135px;
            height: 135px;
            margin: 12px;
        }
    }

    .message {
        margin-top: 8px;
        font-size: 14px;
        font-weight: 400;
        color: rgba(0, 0, 0, 0.45);
    }
}
</style>
