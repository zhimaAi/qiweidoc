<template>
    <a-modal v-model:open="visible"
        :title="titleVal"
        :confirm-loading="saving"
        width="746px">
            <MessageItem ref="messageRef" @showMessage="onShowMessage"/>
        <template #footer></template>
    </a-modal>
</template>

<script setup>
import {ref, nextTick} from 'vue';
import MessageItem from './messageItem.vue';

const messageRef = ref(null)
const visible = ref(false)
const saving = ref(false)
const messageList = ref([])
const titleVal = ref('转发合集')

const show = (messageListData) => {
    messageList.value = messageListData
    visible.value = true
    saving.value = false
    nextTick(() => {
        if (messageRef.value) {
            messageRef.value.show(messageListData)
        }
    })
}

defineExpose({ show })
</script>

<style scoped lang="less">
/deep/ .ant-form-item-label {
  line-height: 28px;
}

/deep/ .ant-form-item-control {
  line-height: 28px;
}
</style>
