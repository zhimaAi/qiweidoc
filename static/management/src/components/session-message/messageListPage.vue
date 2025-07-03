<template>
    <a-modal v-model:open="visible"
        :title="titleVal"
        :confirm-loading="saving"
        width="746px">
            <MessageItem ref="messageRef" @showMessage="onShowMessage"/>
        <template #footer></template>

        <!-- 聊天记录弹窗 -->
        <message-list-page v-for="(modal, idx) in modals" :key="idx" ref="modalRefs" />
    </a-modal>
</template>

<script setup>
import {ref, reactive, nextTick} from 'vue';
import MessageListPage from './messageListPages.vue';
import MessageItem from './messageItem.vue';

const messageRef = ref(null)
const visible = ref(false)
const saving = ref(false)
const messageList = ref([])
const titleVal = ref('转发合集')

// 递归弹窗管理
const modals = reactive([])
const modalRefs = ref([])

const onShowMessage = (list) => {
    modals.push({ messageListData: list })
    nextTick(() => {
        const idx = modals.length - 1
        if (modalRefs.value[idx]) {
            modalRefs.value[idx].show(list , 3)
        }
    })
}

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
