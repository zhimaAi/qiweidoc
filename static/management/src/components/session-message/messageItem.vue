<template>
    <div class="message-list-box">
        <ChatRecordItem
            v-for="(item, index) in messageList"
            :key="index"
            :item="item"
            :allow-open="showMore"
            :compact="compactMode"
            @show-message="onShowMessage"
        />
    </div>
</template>

<script setup>
import {ref} from 'vue';
import {copyObj} from '@/utils/tools';
import ChatRecordItem from './chatRecordItem.vue';

const emit = defineEmits(['showMessage'])
const messageList = ref([])
const showMore = ref(true)
const compactMode = ref(false)

const onShowMessage = (list, title) => {
    emit('showMessage', copyObj(list), title)
}

const show = (messageListData, type, compact = false) => {
    showMore.value = type != 3
    compactMode.value = compact
    messageList.value = Array.isArray(messageListData) ? messageListData : []
}

defineExpose({show})
</script>

<style scoped lang="less">
.message-list-box {
    max-height: 500px;
    overflow-y: auto;
}
</style>
