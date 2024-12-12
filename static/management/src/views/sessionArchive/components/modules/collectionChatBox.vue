<template>
    <div class="chat-box">
        <div class="header">
            <div class="title">
                <template v-if="!chatInfo">加载中...</template>
                <template v-else-if="chatInfo?.receiver?.zm_user_type === 'GROUP_CHAT'">
                    <ChatCollection @changeCollect="changeCollectFn" :currentMsgCancelCollect="currentMsgCancelCollect"
                    :chatInfo="chatInfo" style="margin-right: 8px;" />
                    {{ props.chatInfo?.receiver.name || '未命名群聊' }}
                </template>
                <template v-else-if="chatInfo?.sender && chatInfo?.receiver">
                    <ChatCollection @changeCollect="changeCollectFn" :currentMsgCancelCollect="currentMsgCancelCollect"
                    :chatInfo="chatInfo" />
                    <ChatUser :user="chatInfo.sender"/>与
                    <ChatUser :user="chatInfo.receiver"/>的聊天
                </template>
                <template v-else>...</template>
            </div>
            <div class="operate-box">
                <!-- 一起暂时没有 -->
                <a-tooltip v-if="false" placement="topLeft" title="支持导出当前客户/群聊近3个月的会话">
                    <DownloadOutlined class="icon"/>
                </a-tooltip>
            </div>
        </div>
        <div class="header collect_reason" v-if="chatInfo?.params?.is_collect && chatInfo?.params?.collect_reason">
        <div class="collect_reason_label">收藏原因：</div>
        <div class="scroll-container">
            <!-- <div class="text" :class="{ 'text-scroll': textOverflown }">
            {{ chatInfo?.params?.collect_reason }}
            </div> -->
            <div class="text">
                <a-tooltip placement="topLeft" :overlayClassName="{'chat-box-tooltip-min': chatInfo?.params?.collect_reason.length <= 34, 'chat-box-tooltip': chatInfo?.params?.collect_reason.length > 34}" :title="chatInfo?.params?.collect_reason">
                    {{ chatInfo?.params?.collect_reason }}
                </a-tooltip>
            </div>
        </div>
        </div>
        <ZmScroll
            :key="msgCompKey"
            :loading="loading"
            :finished="finished"
            :immediate="false"
            :showLoading="list.length > 0"
            ref="listRef"
            loadType="up"
            @load="loadData"
            :class="['message-list', {opacity: opacity}]">
            <a-empty v-if="finished && list.length < 1" description="暂无数据" style="margin-top: 60px;"/>
            <template v-for="(item,index) in list">
                <div
                    v-if="item.msg_type != 'revoke'"
                    :key="item.msg_id"
                    :class="['message-item', {isSelf: item.isSelf }]"
                >
                    <div class="user-info">
                        <img class="avatar" :src="item?.from_detail?.avatar || defaultAvatar"/>
                    </div>
                    <div class="content">
                        <div class="user-name" v-if="item.isSelf">
                            <span class="msg-time">{{ item.msg_time_show }}</span>
                            <span class="ml8">{{ item.from_detail.name || '...' }}</span>
                            <template v-if="item.from_role != 'Staff'">
                        <span v-if="item?.from_detail?.corp_name"
                              class="is-corp-tag">@{{ item.from_detail.corp_name }}</span>
                                <span v-else class="is-wx-tag">@微信</span>
                            </template>
                        </div>
                        <div class="user-name" v-else>
                            <span>{{ item.from_detail.name || '...' }}</span>
                            <template v-if="item.from_role != 'Staff'">
                        <span v-if="item?.from_detail?.corp_name"
                              class="is-corp-tag">@{{ item.from_detail.corp_name }}</span>
                                <span v-else class="is-wx-tag">@微信</span>
                            </template>
                            <span class="msg-time">{{ item.msg_time_show }}</span>
                        </div>
                        <MessageRender :messageInfo="item" :isSelf="item.isSelf"/>
                    </div>
                </div>
            </template>
        </ZmScroll>
    </div>
</template>
<script setup>
import {onMounted, ref, reactive, nextTick} from 'vue';
import dayjs from 'dayjs';
import {DownloadOutlined} from '@ant-design/icons-vue';
import ZmScroll from "@/components/zmScroll.vue";
import {groupMessage, sessionMessage} from "@/api/session";
import ChatUser from "@/views/sessionArchive/components/modules/childs/chatUser.vue";
import MessageRender from "@/views/sessionArchive/components/modules/childs/messageRender.vue";
import ChatCollection from './childs/chatCollection.vue';
import {sessionRole2Text} from "@/utils/tools";

const emit = defineEmits('changeCollect')
const defaultAvatar = require('@/assets/default-avatar.png')
const props = defineProps({
    loadType: {
        type: String, // session group
        default: 'session'
    },
    chatInfo: {
        type: Object,
        default: () => {
            return {
                params: {},
                sender: {
                    name: '',
                    is_collect: '',
                    collect_reason: '',
                    conversation_id: '',
                    zm_user_type: '', //STAFF CUSTOMER GROUP_CHAT
                },
                receiver: {
                    name: '',
                    external_userid: '',
                    conversation_id: '',
                    zm_user_type: '',
                }
            }
        }
    },
    currentMsgCancelCollect: {
        type: Number
    }
})

const textOverflown = ref(false)
const list = ref([])
const oldHeight = ref(0)
const listRef = ref(null)
const loading = ref(false)
const finished = ref(false)
const opacity = ref(false)
const msgCompKey = ref(1)
const pagination = reactive({
    current: 1,
    pageSize: 15,
    total: 0,
})

const init = () => {
    // console.log('chatInfo', props.chatInfo)
    checkTextOverflow()
    pagination.current = 1
    pagination.total = 0
    loading.value = false
    finished.value = false
    list.value = []
    loadData()
}

function changeCollectFn (item) {
  if (item.is_collect) {
    // 新增收藏要动态 收藏说明
    checkTextOverflow()
  }
  emit('changeCollect', item)
}

defineExpose({
    init,
})

const loadData = () => {
    if (!props.chatInfo || !props.chatInfo.params) {
        finished.value = true
        loading.value = false
        return
    }
    loading.value = true
    let params = {
        page: pagination.current,
        size: pagination.pageSize,
        ...props.chatInfo.params,
    }
    let request
    if (props.loadType === 'session') {
        request = sessionMessage
    } else {
        request = groupMessage
    }
    request(params).then(res => {
        let data = res.data || {}
        let {items, total} = data
        pagination.total = total
        if (!items || !items?.length || list.value.length === total) {
            finished.value = true
        }
        items.map((item, index) => {
            item.msg_time_show = dayjs(item.msg_time).format('YY/MM/DD HH:mm')
            item.from_detail = item.from_detail || {}
            item.from_role = item.from_role.name
            if (item.from_detail?.external_name) {
                item.from_detail.name = item.from_detail.external_name
            }
            item.isSelf = false
            if (props.chatInfo?.receiver?.zm_user_type === 'STAFF') {

            } else {
                item.isSelf = (item.from_role === 'Staff')
            }
            // 撤回消息
            if (item.msg_type === 'revoke') {
                let findIx = items.findIndex(i => i.msg_id === item.raw_content.pre_msgid)
                items[findIx].is_revoke = true
            }
        })
        oldHeight.value = listRef.value.getListDom().scrollHeight
        items.reverse()
        list.value.unshift(...items)
        pagination.current += 1
        total = Number(total)
        if (pagination.current === 2) {
            opacity.value = true
            setTimeout(() => {
                if (listRef.value) {
                    // 解决报错
                    listRef.value.scrollToBottom()
                }
                nextTick(() => {
                    loading.value = false
                    opacity.value = false
                })
            }, 200)
        } else {
            setTimeout(() => {
                const dom = listRef.value.getListDom()
                listRef.value.setScrollTop(dom.scrollHeight - oldHeight.value)
                nextTick(() => {
                    loading.value = false
                })
            }, 200)
        }
    }).catch(() => {
        loading.value = false
        finished.value = true
    })
}

function checkTextOverflow () {
  nextTick(() => {
    const container = document.querySelector('.scroll-container');
    const textElement = document.querySelector('.text');
    if (container && textElement) {
    //   console.log('container', container.offsetWidth)
    //   console.log('textElement', textElement.scrollWidth)
      textOverflown.value = textElement.scrollWidth > container.offsetWidth;
    }
  })
}

onMounted(() => {
  // checkTextOverflow()
})

</script>
<style scoped lang="less">
.chat-box {
    min-width: 400px;

    .header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 42px;
        border-bottom: 1px solid rgba(5, 5, 5, 0.06);
        padding: 0 12px;

        .title {
            display: flex;
            align-items: center;
            width: calc(100% - 120px);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .icon {
            font-size: 16px;
        }
    }

    .collect_reason {
        display: flex;
        height: 36px;
        background-color: #D6E6FF;
        padding: 0 16px;
        color: #7a8699;
        font-family: "PingFang SC";
        font-size: 14px;
        font-style: normal;
        font-weight: 400;
        line-height: 22px;

        .collect_reason_label {
        flex-basis: 70px;
        }
    }

    .message-list {
        height: calc(100% - 80px);
        opacity: 1;
        scroll-behavior: initial !important;

        &.opacity {
            opacity: 0;
        }
    }

    .message-item {
        display: flex;
        padding: 10px 16px 18px 16px;
        position: relative;

        &.isSelf {
            flex-flow: row-reverse;
            text-align: right;

            .avatar {
                margin-left: 16px;
                margin-right: 0;
            }

            .user-name {
                text-align: right;
            }
        }

        .content {
            .user-name {
                font-size: 12px;
                font-weight: 400;
                color: rgba(0, 0, 0, 0.65);
                margin-bottom: 4px;

                .msg-time {
                    font-size: 12px;
                    font-weight: 400;
                    color: #8C8C8C;
                    margin-left: 8px;

                    &.active {
                        color: red;
                    }
                }
            }
        }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 2px;
            margin-right: 8px;
        }
    }
}

.scroll-container {
  height: 36px;
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
  min-width: 400px;
  max-width: 100%;
}

.text {
  position: absolute;
  display: inline-block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  min-width: 400px;
  max-width: 100%;
}

@keyframes scroll-left {
  from {
    transform: translateX(0%);
  }

  to {
    transform: translateX(-100%);
  }
}

.text-scroll {
  animation: scroll-left 80s linear infinite;
  animation-play-state: running;
}
</style>

<style lang="less">
.chat-box-tooltip .ant-tooltip-inner {
    width: 200%;
}

.chat-box-tooltip-min .ant-tooltip-inner {
    width: 100%;
}
</style>
