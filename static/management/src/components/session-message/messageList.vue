<template>
    <a-modal v-model:open="visible"
        title="查看聊天记录"
        :confirm-loading="saving"
        width="746px">
            <MessageItem ref="messageItemRef" @showMessage="onShowMessage"/>
        <template #footer></template>

        <!-- 聊天记录弹窗 -->
        <MessageListPage ref="messageListPageRef" />
    </a-modal>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { jsonDecode } from "@/utils/tools";
import MessageListPage from './messageListPage.vue';
import MessageItem from './messageItem.vue';

const messageItemRef = ref(null)
const messageListPageRef = ref(null)
const visible = ref(false)
const saving = ref(false)
const messageList = ref([])

const formatMessageRecord = (item) => {
    return item.map(item => {
        item.content = jsonDecode(item.content)
        switch (item.type) {
            case "chatrecord":
                if (item.content?.item) {
                    item.content = formatMessageRecord(item.content?.item)
                }
                break
        }
        return item
    })
}

const onShowMessage = (list) => {
    nextTick(() => {
        if (messageListPageRef.value) {
            messageListPageRef.value.show(list)
        }
    })
}


const show = (messageListData) => {
    messageList.value = formatMessageRecord(messageListData)
    visible.value = true
    saving.value = false
    nextTick(() => {
        if (messageItemRef.value) {
            messageItemRef.value.show(messageListData)
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
