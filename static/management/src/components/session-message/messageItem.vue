<template>
    <div class="message-list-box">
        <div class="message-item-box" v-for="(item, index) in messageList" :key="index">
            <!-- 文本 -->
            <div v-if="item.type === 'ChatRecordText'" class="message-item">
                <div class="avatar">
                    <img src="@/assets/image/default-avatar.png" alt="">
                </div>
                <div class="message-main">
                    <div class="message-time">
                        {{ statDateFormat(item.msgtime) }}
                    </div>
                    <div class="message-content">
                        {{ item.content.content }}
                    </div>
                </div>
            </div>
            <!-- 链接 -->
            <div v-else-if="item.type === 'link'" class="message-box msg-link-box">
                <div class="msg-link-content">
                    <div class="msg-link-avatar">
                        <img :src="messageInfo?.raw_content?.image_url" alt="">
                    </div>
                    <div class="msg-link-title">{{ messageInfo.raw_content.title }}</div>
                </div>
                <a class="bottom msg-link-bottom" target="_blank" :href="messageInfo.raw_content.link_url">
                <span class="msg-link-info">详情</span>
                <RightOutlined class="msg-link-icon icon-14"/>
                </a>
            </div>
            <!-- 聊天记录 -->
            <div v-else-if="item.type === 'chatrecord'" class="message-item">
                <div class="avatar">
                    <img src="@/assets/image/default-avatar.png" alt="">
                </div>
                <div class="message-main">
                    <div class="message-time">
                        {{ statDateFormat(item.msgtime) }}
                    </div>
                    <div class="message-content message-box msg-forwarding-box">
                        <!-- 转发消息 -->
                        <div class="message-box msg-forwarding-box">
                            <div class="title">{{ titleVal }}</div>
                            <div class="list">
                                共 {{ item?.content?.length }} 条消息
                            </div>
                            <div v-if="showMore" class="bottom zm-flex-between zm-tip-info" @click="onShowMessage(item?.content)">
                                <span>详情</span>
                                <RightOutlined class="icon-14"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { copyObj } from "@/utils/tools";
import { RightOutlined } from '@ant-design/icons-vue';
import dayjs from "dayjs";

const emit = defineEmits(['showMessage'])
const messageList = ref([])
const titleVal = ref('转发合集')
const showMore = ref(true)

const onShowMessage = (list) => {
    const newList = copyObj(list)
    emit('showMessage', newList)
}

const show = (messageListData, type) => {
    if (type && type == 3) {
        showMore.value = false
    }
    messageList.value = messageListData
}

function statDateFormat(time) {
    return dayjs(time * 1000).format("MM-DD HH:mm")
}

defineExpose({ show })
</script>

<style scoped lang="less">
.message-list-box {
    max-height: 500px;
    overflow-y: auto;

    .message-item {
        display: flex;
        border-bottom: 1px solid #F0F0F0;
        background: #FFF;
        padding: 16px 0;
        gap: 12px;

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 12px;

            img {
                width: 100%;
                height: 100%;
                border-radius: 12px;
            }
        }

        .message-main {
            display: flex;
            flex-direction: column;
            gap: 8px;

            .message-time {
                color: #8c8c8c;
                font-size: 14px;
                font-style: normal;
                font-weight: 400;
                line-height: 22px;
            }

            .message-content {
                color: #262626;
                font-size: 14px;
                font-style: normal;
                font-weight: 400;
                line-height: 22px;
            }
        }
    }
}

.message-box {
    display: inline-block;
    background: #FFFFFF;
    padding: 8px;
    max-width: 40vw;
    white-space: normal;
    word-break: break-all;
    border-radius: 8px;
    opacity: 1;
    border: 1px solid #e6e6e6;
    position: relative;
    font-size: 12px;
    font-weight: 400;
    color: #595959;

    &::after {
        content: "";
        display: block;
        width: 7px;
        height: 8px;
        position: absolute;
        top: 6px;
        left: -7px;
        background: url(@/assets/image/icon-arrow-left.png) 0 0 no-repeat;
    }

    &.msg-forwarding-box {
        padding: 0;
        .title {
            color: #262626;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
            padding: 12px 12px 0;
        }
        .list {
            padding: 4px 12px 12px;
            color: #595959;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
        }
        .bottom {
            font-size: 12px;
            padding: 8px 12px;
            border-top: 1px solid #D9D9D9;
            cursor: pointer;
        }
    }
}
</style>
