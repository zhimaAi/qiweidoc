<template>
    <div class="chat-box">
        <div>
            <div class="header">
                <div class="zm-flex-between">
                    <div class="title">
                        <template v-if="!chatInfo">加载中...</template>
                        <template v-else-if="chatInfo?.receiver?.zm_user_type === 'GROUP' || chatInfo?.receiver?.zm_user_type === 'GROUP_CHAT'">
                            <ChatCollection
                                @changeCollect="changeCollectFn"
                                :currentMsgCancelCollect="currentMsgCancelCollect"
                                :chatInfo="chatInfo"
                                class="mr8"/>
                            {{ props.chatInfo?.receiver.name || '未命名群聊' }}
                        </template>
                        <template v-else-if="chatInfo?.sender && chatInfo?.receiver">
                            <ChatCollection
                                v-if="!(chatInfo?.receiver?.zm_user_type === 'STAFF' && mainTab === 'LOAD_BY_STAFF')"
                                @changeCollect="changeCollectFn"
                                :currentMsgCancelCollect="currentMsgCancelCollect"
                                :chatInfo="chatInfo"/>
                            <ChatUser :user="chatInfo.sender"/>
                            与
                            <ChatUser :user="chatInfo.receiver"/>
                            的聊天
                        </template>
                        <template v-else>...</template>
                    </div>
                    <div class="operate-box">
                        <a-tooltip placement="topLeft" title="支持导出当前客户/群聊所选会话时间的会话">
                            <DownloadOutlined
                                class="icon"
                                :class="{ 'icon-disabled': exportLoading }"
                                @click="handleExport"/>
                        </a-tooltip>

                    </div>
                </div>
                <div class="filter-box mt4">
                    <a-input-search
                        v-model:value="filterData.keyword"
                        @search="init"
                        size="small"
                        allowClear
                        :disabled="filterData.msg_type !== 'all'"
                        placeholder="请输入关键词搜索"
                        style="width: 155px;"/>
                    <a-radio-group
                        v-model:value="filterData.msg_type"
                        @change="filterMsgTypeChange"
                        size="small"
                        class="ml8">
                        <a-radio-button value="all">消息</a-radio-button>
                        <a-radio-button value="file">文件</a-radio-button>
                        <a-radio-button value="image">图片</a-radio-button>
                        <a-radio-button value="voice">
                        <StaffPaymentTag :type="2">语音</StaffPaymentTag>
                        </a-radio-button>
                        <a-radio-button value="video">视频</a-radio-button>
                        <a-radio-button v-if="sessionType === 'session'" value="voiptext">音视频通话</a-radio-button>
                    </a-radio-group>
                    <a-range-picker
                        class="ml8"
                        v-model:value="filterData.dates"
                        style="width: 220px; height: 24px;"
                        @change="filterDateChange"
                        :disabled-date="disabledDate"/>
                </div>
            </div>
            <div v-if="showCollectReason" class="zm-flex-center zm-pointer collect_reason">
                <div class="collect_reason_label">收藏原因：</div>
                <div class="scroll-container">
                    <div class="text">
                        <a-tooltip placement="topLeft"
                                   :overlayClassName="{'chat-box-tooltip-min': chatInfo?.params?.collect_reason.length <= 34, 'chat-box-tooltip': chatInfo?.params?.collect_reason.length > 34}"
                                   :title="chatInfo?.params?.collect_reason">
                            {{ chatInfo?.params?.collect_reason }}
                        </a-tooltip>
                    </div>
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
            load-type="up"
            @load="loadData"
            :style="getMessageListStyle"
            :class="['message-list', { opacity: opacity}]">
            <a-empty v-if="finished && list.length < 1" description="暂无数据" style="margin-top: 60px;"/>
            <template v-for="item in list">
                <div v-if="item.msg_type != 'revoke'" :key="item.msg_id"
                     :class="['message-item', { isSelf: item.isSelf }]">
                    <div class="user-info">
                        <img class="avatar" :src="item?.from_detail?.avatar || defaultAvatar"/>
                    </div>
                    <div class="content">
                        <div class="user-name" v-if="item.isSelf">
                            <span class="msg-time">{{ item.msg_time }}</span>
                            <span class="ml8">{{ item.from_detail.name || '...' }}</span>
                            <template v-if="item.from_role !== 'Staff'">
                                <span v-if="item?.from_detail?.corp_name"
                                      class="is-corp-tag">@{{ item.from_detail.corp_name }}</span>
                                <span v-else class="is-wx-tag">@微信</span>
                            </template>
                        </div>
                        <div class="user-name" v-else>
                            <span>{{ item.from_detail.name || '...' }}</span>
                            <template v-if="item.from_role !== 'Staff'">
                                <span v-if="item?.from_detail?.corp_name"
                                      class="is-corp-tag">@{{ item.from_detail.corp_name }}</span>
                                <span v-else class="is-wx-tag">@微信</span>
                            </template>
                            <span class="msg-time">{{ item.msg_time }}</span>
                        </div>
                        <MessageRender
                            @playVoice="playVoice"
                            @playVideo="playVideo"
                            :voicePlaying="item.msg_id === currentPayVoiceKey"
                            :messageInfo="item"
                            :isSelf="item.isSelf" />
                    </div>
                </div>
            </template>
        </ZmScroll>

        <lookVideo ref="lookVideoRef"></lookVideo>

        <!-- 导出成功弹窗 -->
        <a-modal
            v-model:open="showExportSuccessModal"
            :footer="null"
            :closable="false"
            width="400px"
            centered>
            <div class="export-success-modal">
                <div class="success-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" fill="#52c41a"/>
                        <path d="M9 12l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="success-title">导出任务已提交</div>
                <div class="success-desc">导出任务已提交，请前往下载中心查看进度</div>
                <div class="modal-actions">
                    <a-button @click="showExportSuccessModal = false">取消</a-button>
                    <a-button type="primary" @click="handleGoToDownload">去查看</a-button>
                </div>
            </div>
        </a-modal>
    </div>
</template>
<script setup>
import {onMounted, ref, reactive, nextTick, computed} from 'vue';
import {useStore} from 'vuex';
import {useRouter} from 'vue-router';
import dayjs from 'dayjs';
import {DownloadOutlined} from '@ant-design/icons-vue';
import ZmScroll from "@/components/zmScroll.vue";
import ChatUser from "@/views/sessionArchive/components/modules/childs/chatUser.vue";
import ChatCollection from './childs/chatCollection.vue';
import MessageRender from "@/components/session-message/messageRender.vue";
import lookVideo from "@/components/common/look-video.vue";
import {groupMessage, sessionMessage, exportChatMessage} from "@/api/session";
import {VoicePlayHandle} from "@/utils/voicePlay";
import StaffPaymentTag from "@/views/sessionArchive/components/modules/staffPaymentTag.vue";
import {message} from 'ant-design-vue';

const emit = defineEmits('changeCollect')
const defaultAvatar = require('@/assets/default-avatar.png')
const props = defineProps({
    mainTab: {
        type: String,
        default: 'LOAD_BY_STAFF'
    },
    sessionType: {
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
                    department_name: '',
                    userid: '',
                    zm_user_type: '', //STAFF CUSTOMER GROUP
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
    },
    defaultParams: {
        type: Object,
        default: () => ({})
    }
})
const {play, getPlayerKey} = VoicePlayHandle()

const store = useStore()
const router = useRouter()
const list = ref([])
const oldHeight = ref(0)
const lookVideoRef = ref(null)
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
const currentPayVoiceKey = getPlayerKey()
const filterData = reactive({
    keyword: '',
    msg_type: 'all',
    dates: []
})

// 导出相关数据
const exportLoading = ref(false)
const showExportSuccessModal = ref(false)

const archiveStfModule = computed(() => {
    return store.getters.getArchiveStfInfo || []
})

const showCollectReason = computed(() => {
    return props.mainTab === 'LOAD_BY_COLLECT'
        && props.chatInfo?.params?.is_collect
        && props.chatInfo?.params?.collect_reason
})
const getMessageListStyle = computed(() => {
    let style = {}
    if (showCollectReason.value) {
        style = {
            'padding-top': '36px'
        }
    }
    return style
})

const init = () => {
    pagination.current = 1
    pagination.total = 0
    loading.value = false
    finished.value = false
    list.value = []
    loadData()
}

function changeCollectFn(item) {
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
    if (filterData.dates && filterData.dates.length) {
        params.msg_start_time = dayjs(filterData.dates[0]).format('YYYY-MM-DD 00:00:00')
        params.msg_end_time = dayjs(filterData.dates[1]).format('YYYY-MM-DD 23:59:59')
    }
    filterData.keyword = filterData.keyword.trim()
    if (filterData.keyword) {
        params.msg_content = filterData.keyword
    }
    if (filterData.msg_type !== 'all') {
        params.msg_type = filterData.msg_type
    }
    let request
    if (props.sessionType === 'session') {
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
        items.map(item => {
            item.msg_time_show = dayjs(item.msg_time).format('YY/MM/DD HH:mm')
            item.from_role = item.from_role.name
            item.from_detail = item.from_detail || {}
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
                items[findIx] && (items[findIx].is_revoke = true)
            }
        })

        oldHeight.value = listRef.value.getListDom().scrollHeight
        items.reverse()
        list.value.unshift(...items)
        pagination.current += 1
        // total = Number(total)
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
    }).catch(err => {
        console.log('Err:', err)
        loading.value = false
        finished.value = true
    })
}

function filterMsgTypeChange() {
    if (filterData.msg_type !== 'all') {
        filterData.keyword = ''
    }
    init()
}

function playVoice(message) {
    play(message.msg_id, message.msg_content)
}

function playVideo(message) {
    lookVideoRef.value.show(message.msg_content)
}

const filterDateChange = () => {
    if (filterData.dates && filterData.dates.length > 0) {
        let time = filterData.dates[1] - filterData.dates[0]
        if ((time / 86400000) > 30) {
            filterData.dates = []
            message.warning("发送时间跨度不得超过30天")
        }
    }
    init()
}

const disabledDate = current => {
    return current && current > dayjs().endOf('day')
}

// 处理导出
const handleExport = async () => {
    if (exportLoading.value) return

    if (!props.chatInfo || !props.chatInfo.params) {
        message.warning('请先选择要导出的会话')
        return
    }

    exportLoading.value = true

    try {
        let params = {
            conversation_id: props.chatInfo.params.conversation_id
        }

        // 群聊时添加group_chat_id
        if (props.chatInfo.receiver.zm_user_type === 'GROUP' || props.chatInfo.receiver.zm_user_type === 'GROUP_CHAT') {
            params.group_chat_id = props.chatInfo.params.group_chat_id || props.chatInfo.receiver.chat_id
        }

        // 添加时间范围参数
        if (filterData.dates && filterData.dates.length > 0) {
            // 优先使用筛选器中的时间范围
            params.msg_start_time = dayjs(filterData.dates[0]).format('YYYY-MM-DD 00:00:00')
            params.msg_end_time = dayjs(filterData.dates[1]).format('YYYY-MM-DD 23:59:59')
        } else if (props.defaultParams?.start_date && props.defaultParams?.end_date) {
            // 如果筛选器中没有时间，则使用默认参数中的时间
            params.msg_start_time = dayjs(props.defaultParams.start_date).format('YYYY-MM-DD 00:00:00')
            params.msg_end_time = dayjs(props.defaultParams.end_date).format('YYYY-MM-DD 23:59:59')
        }

        await exportChatMessage(params)

        // 显示成功弹窗
        showExportSuccessModal.value = true

    } catch (error) {
        console.error('导出失败:', error)
        message.error('导出失败，请重试')
    } finally {
        exportLoading.value = false
    }
}

// 去查看导出记录
const handleGoToDownload = () => {
    showExportSuccessModal.value = false
    // 使用vue-router在新窗口打开文件导出记录页面
    const routeData = router.resolve({ path: '/systemctl/fileExport' })
    window.open(routeData.href, '_blank')
}

</script>
<style scoped lang="less">
.chat-box {
    min-width: 400px;
    position: relative;
    display: flex;
    flex-direction: column;

    .header {
        border: 1px solid rgba(5, 5, 5, 0.06);
        border-right: none;
        border-left: none;
        padding: 12px;

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
            cursor: pointer;
            transition: color 0.3s ease;

            &:hover {
                color: #1890ff;
            }

            &.icon-disabled {
                color: #d9d9d9;
                cursor: not-allowed;

                &:hover {
                    color: #d9d9d9;
                }
            }
        }

        .filter-box {
            display: flex;
            align-items: center;

            :deep(.ant-input-search .ant-input) {
                min-height: 22px;
            }

            :deep(.ant-radio-button-wrapper) {
                color: #595959;
                font-size: 12px;

                &.ant-radio-button-wrapper-checked:not(.ant-radio-button-wrapper-disabled) {
                    color: #1677ff;
                }
            }
        }
    }


    .collect_reason {
        position: absolute;
        width: 100%;
        z-index: 99;
        display: flex;
        height: 36px;
        background-color: #D6E6FF;
        padding: 0 16px;
        color: #7a8699;
        font-size: 14px;
        font-style: normal;
        font-weight: 400;
        line-height: 22px;

        .collect_reason_label {
            flex-basis: 70px;
        }
    }

    .message-list {
        flex: 1;
        opacity: 1;
        scroll-behavior: initial !important;

        &.opacity {
            opacity: 0;
        }
    }

    .message-list-tips {
        height: calc(100vh - 248px);
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
            border-radius: 6px;
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

// 导出成功弹窗样式
.export-success-modal {
    text-align: center;
    padding: 20px 0;

    .success-icon {
        margin-bottom: 16px;
        display: flex;
        justify-content: center;
    }

    .success-title {
        font-size: 16px;
        font-weight: 500;
        color: #262626;
        margin-bottom: 8px;
    }

    .success-desc {
        font-size: 14px;
        color: #8c8c8c;
        margin-bottom: 24px;
        line-height: 1.5;
    }

    .modal-actions {
        display: flex;
        justify-content: center;
        gap: 12px;

        .ant-btn {
            min-width: 80px;
        }
    }
}
</style>
