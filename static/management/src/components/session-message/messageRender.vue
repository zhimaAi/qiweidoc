<template>
    <div :class="{isSelf: isSelf}">
        <!--文本-->
        <div v-if="messageInfo.msg_type === 'text'" class="message-box text">{{ messageInfo.msg_content }}</div>
        <div v-else-if="messageInfo.msg_type === 'agree'" class="message-box">对方同意会话内容存档</div>
        <div v-else-if="messageInfo.msg_type === 'disagree'" class="message-box">对方不同意会话内容存档，你将无法继续提供服务</div>
        <!-- 笔记 -->
        <div v-else-if="messageInfo.msg_type === 'note'" class="message-box note-message-box">
            <div class="note-content">
                <div class="note-title">{{ noteContent.title }}</div>
                <div v-if="noteContent.description" class="note-description">{{ noteContent.description }}</div>
            </div>
            <button
                type="button"
                class="note-type"
                @click="onShowMessage(noteContent.items, MessageTypeTextMap[messageInfo.msg_type], true)"
            >
                <span>{{ MessageTypeTextMap[messageInfo.msg_type] }}</span>
                <RightOutlined class="icon-14"/>
            </button>
        </div>
        <!-- 接龙 -->
        <div v-else-if="messageInfo.msg_type === 'solitaire'" class="message-box solitaire-message-box">
            <div class="solitaire-content">{{ solitaireContent || '--' }}</div>
            <div class="solitaire-type">{{ MessageTypeTextMap[messageInfo.msg_type] }}</div>
        </div>
        <!-- 个人名片 -->
        <div v-else-if="messageInfo.msg_type === 'card'" class="message-box contact-card-box">
            <div class="contact-card-content">
                <div class="corp-name">{{ messageInfo?.raw_content?.corpname || '--' }}</div>
                <div class="user-name">{{ messageInfo?.raw_content?.userid || '--' }}</div>
            </div>
            <div class="contact-card-bottom">{{ MessageTypeTextMap[messageInfo.msg_type] }}</div>
        </div>
        <!--图片-->
        <template v-else-if="messageInfo.msg_type === 'image' || messageInfo.msg_type === 'emotion'">
            <a-tooltip v-if="mediaRemoved" title="图片已清除">
                <img src="@/assets/image/session/load-img-deleted.png" style="width: 120px;"/>
            </a-tooltip>
            <div v-else-if="messageInfo.msg_content " class="message-box image pointer">
                <a-image :src="messageInfo.msg_content" style="max-width: 200px;" @error="mediaLoadFailed = true"/>
            </div>
            <a-tooltip v-else-if="messageInfo.media_status === 'unavailable'" title="图片暂时无法访问，请稍后重试">
                <img src="@/assets/image/session/load-img-run.png" style="width: 120px;"/>
            </a-tooltip>
            <a-tooltip v-else title="系统正在下载中，稍后再试！">
                <img src="@/assets/image/session/load-img-run.png" style="width: 120px;"/>
            </a-tooltip>
        </template>
        <!-- 链接 -->
        <div v-else-if="messageInfo.msg_type === 'link'" class="message-box msg-link-box">
            <div class="msg-link-content">
                <div class="msg-link-avatar">
                    <img :src="messageInfo?.raw_content?.image_url" alt="">
                </div>
                <div class="msg-link-text">
                    <div class="msg-link-title">{{ messageInfo?.raw_content?.title }}</div>
                    <div v-if="messageInfo?.raw_content?.description" class="msg-link-description">
                        {{ messageInfo.raw_content.description }}
                    </div>
                </div>
            </div>
            <a class="bottom msg-link-bottom" target="_blank" :href="messageInfo.raw_content.link_url">
               <span class="msg-link-info">{{ MessageTypeTextMap[messageInfo.msg_type] }}</span>
               <RightOutlined class="msg-link-icon icon-14"/>
            </a>
        </div>
        <!-- 位置 -->
        <div v-else-if="messageInfo.msg_type === 'location'" class="message-box msg-location-box">
            <div class="msg-location-content">
                <div class="msg-location-title" :title="messageInfo?.raw_content?.title">
                    {{ messageInfo?.raw_content?.title || '--' }}
                </div>
                <div class="msg-location-address" :title="messageInfo?.raw_content?.address">
                    {{ messageInfo?.raw_content?.address || '--' }}
                </div>
            </div>
            <div class="msg-location-bottom">{{ MessageTypeTextMap[messageInfo.msg_type] }}</div>
        </div>
        <!-- 待办 -->
        <div v-else-if="messageInfo.msg_type === 'todo'" class="message-box msg-todo-box">
            <div class="msg-todo-content">
                <div class="msg-todo-title" :title="messageInfo?.raw_content?.title">
                    {{ messageInfo?.raw_content?.title || '--' }}
                </div>
                <div class="msg-todo-description">{{ messageInfo?.raw_content?.content || '--' }}</div>
            </div>
            <div class="msg-todo-bottom">{{ MessageTypeTextMap[messageInfo.msg_type] }}</div>
        </div>
        <!-- 会议 -->
        <div v-else-if="messageInfo.msg_type === 'meeting'" class="message-box msg-meeting-box">
            <div class="msg-meeting-content">
                <div class="msg-meeting-topic" :title="messageInfo?.raw_content?.topic">
                    {{ messageInfo?.raw_content?.topic || '--' }}
                </div>
                <div class="msg-meeting-time">
                    {{ formatMeetingTime(messageInfo?.raw_content?.starttime, messageInfo?.raw_content?.endtime) }}
                </div>
            </div>
            <div class="msg-meeting-address" :title="messageInfo?.raw_content?.address">
                <span class="label">地点</span>
                <span class="value">{{ messageInfo?.raw_content?.address || '--' }}</span>
            </div>
            <div class="msg-meeting-bottom">{{ MessageTypeTextMap[messageInfo.msg_type] }}</div>
        </div>
        <!-- 视频 -->
        <div v-else-if="messageInfo.msg_type == 'video'" class="message-box video-box">
            <div class="zm-flex-center">
                <template v-if="voicePlaying">
                    <img src="@/assets/image/icon-voice.gif" class="voice-play-icon"/>
                    <span class="ml8">视频消息 {{formatSeconds(messageInfo.raw_content.play_length)}}</span>
                    <a-divider type="vertical"/>
                    <a-tooltip v-if="mediaRemoved" title="文件已清除">
                        <PauseCircleOutlined class="icon-disabled"/>
                    </a-tooltip>
                    <a-tooltip v-else title="停止播放">
                        <PauseCircleOutlined @click="playingVideo(messageInfo)" class="icon-btn"/>
                    </a-tooltip>
                </template>
                <template v-else>
                    <img class="icon-14" src="@/assets/image/icon-video.png"/>
                    <span class="ml8">视频消息 {{formatSeconds(messageInfo.raw_content.play_length)}}</span>
                    <a-divider type="vertical"/>
                    <a-tooltip v-if="mediaRemoved" title="文件已清除">
                        <PlayCircleOutlined class="icon-disabled"/>
                    </a-tooltip>
                    <a-tooltip v-else title="播放视频">
                        <PlayCircleOutlined @click="playingVideo(messageInfo)" class="icon-btn"/>
                    </a-tooltip>
                </template>
                <a-tooltip v-if="mediaRemoved" title="文件已清除">
                    <DownloadOutlined class="icon-disabled ml8"/>
                </a-tooltip>
                <DownloadOutlined v-else-if="messageInfo.msg_content && messageInfo.msg_id"
                                  @click="downloadMsgFile" class="icon-btn ml8"/>
                <DownloadOutlined v-else class="icon-disabled ml8"/>
            </div>
        </div>
        <!-- 文件 -->
        <div v-else-if="messageInfo.msg_type == 'file'"
             :class="['message-box file-box', {warning: downloadFileLimit(messageInfo.content)}]">
            <img class="file-icon" :src="getFileIcon(messageInfo.raw_content.fileext)"/>
            <div class="file-info-box">
                <div class="left-block">
                    <a-tooltip
                        :title="messageInfo.raw_content.filename.length > 20 ? messageInfo.raw_content.filename : ''">
                        <div class="file-name">{{ messageInfo.raw_content.filename || '--' }}</div>
                    </a-tooltip>
                    <div class="file-size">{{ showFileSize(messageInfo.raw_content.filesize) }}</div>
                </div>
                <div class="right-block">
                    <a-tooltip v-if="mediaRemoved" title="文件已清除">
                        <DownloadOutlined style="color: #CCC;"/>
                    </a-tooltip>
                    <a v-else-if="messageInfo.msg_content" @click="downloadMsgFile">
                        <DownloadOutlined/>
                    </a>
                    <a-tooltip v-else :title="messageInfo.media_status === 'unavailable' ? '文件暂时无法访问，请稍后重试' : '系统正在下载中，稍后再试！'">
                        <DownloadOutlined style="color: #CCC;"/>
                    </a-tooltip>
                </div>
            </div>
        </div>
        <!-- 语音存档 -->
        <div v-else-if="messageInfo.msg_type == 'voip_doc_share'" class="message-box">[语音存档]</div>
        <!-- 语音通话 -->
        <div v-else-if="messageInfo.msg_type == 'meeting_voice_call'"
             class="message-box pointer">
            <div class="zm-flex-center">
                <template v-if="voicePlaying">
                    <img src="@/assets/image/icon-voice.gif" class="voice-play-icon"/>
                    <span class="ml8">语音通话 {{getVoiceCallDuration}}</span>
                    <a-divider type="vertical"/>
                    <a-tooltip v-if="mediaRemoved" title="文件已清除">
                        <PauseCircleOutlined class="icon-disabled"/>
                    </a-tooltip>
                    <a-tooltip v-else title="停止播放">
                        <PauseCircleOutlined @click="playingVoice(messageInfo)" class="icon-btn"/>
                    </a-tooltip>
                </template>
                <template v-else>
                    <PhoneOutlined class="voice-phone-icon"/>
                    <span class="ml8">语音通话 {{getVoiceCallDuration}}</span>
                    <a-divider type="vertical"/>
                    <a-tooltip v-if="mediaRemoved" title="文件已清除">
                        <PlayCircleOutlined class="icon-disabled"/>
                    </a-tooltip>
                    <a-tooltip v-else title="播放通话">
                        <PlayCircleOutlined @click="playingVoice(messageInfo)" class="icon-btn"/>
                    </a-tooltip>
                </template>
                <a-tooltip v-if="mediaRemoved" title="文件已清除">
                    <DownloadOutlined class="icon-disabled ml8"/>
                </a-tooltip>
                <DownloadOutlined v-else @click="downloadMsgFile" class="icon-btn ml8"/>
            </div>
        </div>
        <div v-else-if="messageInfo.msg_type == 'voiptext'"
             class="message-box">
            <div class="zm-flex-center">
                <PhoneOutlined class="voice-phone-icon"/>
                <span class="ml8">语音通话 {{secondsToDate(messageInfo.raw_content.callduration)}}</span>
            </div>
        </div>
        <!-- 语音消息-->
        <div v-else-if="messageInfo.msg_type == 'voice'"
             class="message-box pointer">
            <div class="zm-flex-center">
                <template v-if="voicePlaying">
                    <img src="@/assets/image/icon-voice.gif" class="voice-play-icon"/>
                    <span class="ml8">语音消息 {{formatSeconds(messageInfo.raw_content.play_length)}}</span>
                    <a-divider type="vertical"/>
                    <a-tooltip v-if="mediaRemoved" title="文件已清除">
                        <PauseCircleOutlined class="icon-disabled"/>
                    </a-tooltip>
                    <a-tooltip v-else title="停止播放">
                        <PauseCircleOutlined @click.stop="playingVoice(messageInfo)" class="icon-btn"/>
                    </a-tooltip>
                </template>
                <template v-else>
                    <img class="icon-14" src="@/assets/image/icon-voice.png"/>
                    <span class="ml8">语音消息 {{formatSeconds(messageInfo.raw_content.play_length)}}</span>
                    <a-divider type="vertical"/>
                    <a-tooltip v-if="mediaRemoved" title="文件已清除">
                        <PlayCircleOutlined class="icon-disabled"/>
                    </a-tooltip>
                    <a-tooltip v-else title="播放语音">
                        <PlayCircleOutlined @click.stop="playingVoice(messageInfo)" class="icon-btn"/>
                    </a-tooltip>
                </template>
                <a-tooltip v-if="mediaRemoved" title="文件已清除">
                    <DownloadOutlined class="icon-disabled ml8"/>
                </a-tooltip>
                <DownloadOutlined v-else @click.stop="downloadMsgFile" class="icon-btn ml8"/>
                <!--未购买时-->
                <span v-if="showPaymentTag" @click="payenmtModalShow" class="zm-payment-tag"></span>
            </div>
        </div>
        <!-- 红包消息-->
        <div v-else-if="['external_redpacket', 'redpacket'].includes(messageInfo.msg_type)"
             class="message-box red-envelope-box">
            <div class="zm-flex-center">
                <img class="cover" src="@/assets/image/session/red-envelope-cover.png"/>
                <div class="ml8">
                    <div class="price">¥{{formatPrice(messageInfo?.raw_content?.totalamount)}}</div>
                    <div class="desc">{{messageInfo?.raw_content?.wish}}</div>
                </div>
            </div>
            <div class="extra-info">
                {{RedpacketTypeMap[messageInfo?.raw_content?.type]}}
                {{messageInfo?.raw_content?.totalcnt}}个
            </div>
        </div>
       <!-- 转发消息 -->
       <div v-else-if="messageInfo.msg_type == 'chatrecord'" class="message-box msg-forwarding-box">
           <div class="title">{{ messageInfo?.raw_content?.title }}</div>
           <div class="list">
               共 {{ messageInfo?.raw_content?.item.length }} 条消息
           </div>
           <div class="bottom zm-flex-between zm-tip-info" @click="onShowMessage(messageInfo?.raw_content?.item, messageInfo?.raw_content?.title)">
               <span>{{ MessageTypeTextMap[messageInfo.msg_type] }}</span>
               <RightOutlined class="icon-14"/>
           </div>
       </div>
       <!-- 小程序 -->
       <div v-else-if="messageInfo.msg_type == 'weapp'" class="message-box msg-weapp-box">
          <div class="msg-weapp-avatar">
            <img src="@/assets/image/session/weapp-icon.png" alt="">
          </div>
          <div class="msg-weapp-content">
            <div class="title">{{ messageInfo?.raw_content?.description }}</div>
            <div class="description">{{ messageInfo?.raw_content?.title }}</div>
          </div>
       </div>
       <!-- 视频号 -->
       <div v-else-if="messageInfo.msg_type == 'sphfeed'" class="message-box msg-weapp-box">
          <div class="msg-weapp-avatar">
            <img src="@/assets/image/sphfeed-icon.png" alt="视频号">
          </div>
          <div class="msg-weapp-content">
            <div class="title">{{ messageInfo?.raw_content?.sph_name }}</div>
            <div class="description">{{ messageInfo?.raw_content?.feed_desc }}</div>
          </div>
       </div>
        <!-- 混合消息 -->
        <div v-else-if="messageInfo.msg_type == 'mixed'" class="message-box mixed-message-box">
            <template v-if="messageInfo?.raw_content?.item?.length">
                <ChatRecordItem
                    v-for="(item, index) in messageInfo.raw_content.item"
                    :key="index"
                    :item="item"
                    compact
                    @show-message="onShowMessage"
                />
            </template>
            <span v-else>[混合消息]</span>
        </div>
        <div v-else class="message-box">[{{ MessageTypeTextMap[messageInfo.msg_type] }}]</div>
        <span v-if="messageInfo.is_revoke" class="message-box" style="color: rgba(0,0,0,.25);">已撤回</span>

        <!-- 聊天记录弹窗 -->
        <MessageList ref="messageListRef" />
    </div>
</template>

<script setup>
import {ref, computed} from 'vue';
import {useStore} from 'vuex';
import {useRouter} from 'vue-router';
import dayjs from 'dayjs';
import {Modal, message} from 'ant-design-vue';
import {DownloadOutlined, PlayCircleOutlined, PauseCircleOutlined, PhoneOutlined, RightOutlined} from '@ant-design/icons-vue';
import {
    downloadFile,
    formatBytes,
    secondsToDate,
    formatSeconds,
    getFileIcon,
    MessageTypeTextMap,
    RedpacketTypeMap,
    copyObj, getPluginRouteParams, getNoteDisplayContent, getSolitaireDisplayContent
} from "@/utils/tools";
import BenzAMRRecorder from 'benz-amr-recorder';
import {formatPrice} from "@/utils/tools";
import MessageList from './messageList.vue'
import ChatRecordItem from './chatRecordItem.vue'

const messageListRef = ref(null)
const props = defineProps({
    messageInfo: {
        type: Object,
        default: () => {
            return {}
        }
    },
    isSelf: {
        type: Boolean,
        default: false
    },
    voicePlaying: {
        // 播放语音
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['playVoice'])
const router = useRouter()
const store = useStore()
const amrPlayer = ref(null)
const mediaLoadFailed = ref(false)
const totalStorage = ref(10)
const noteContent = computed(() => getNoteDisplayContent(props.messageInfo))
const solitaireContent = computed(() => getSolitaireDisplayContent(props.messageInfo))
const mediaRemoved = computed(() => props.messageInfo.file_is_remove
    || props.messageInfo.media_status === 'removed'
    || mediaLoadFailed.value)

const archiveStfModule = computed(() => {
    return store.getters.getArchiveStfInfo || {}
})

const archiveStfSetting = computed(() => {
    return store.getters.getArchiveStfSetting || {}
})

const showPaymentTag = computed(() => {
    const val = archiveStfModule.value
    return !val.is_install || val.is_expired || val.has_bought != 1
})

const getVoiceCallDuration = computed(() => {
    const msg = props.messageInfo
    if (msg.msg_type === 'meeting_voice_call') {
        try {
            let endtime = dayjs(msg?.raw_content?.endtime * 1000)
            let msgtime = dayjs(msg?.msg_time)
            let diffInSeconds = endtime.diff(msgtime, 'second'); // 差异的秒数
            return secondsToDate(diffInSeconds)
        } catch
            (e) {
            console.log('Err:', e)
        }
    }
    return '00:00'
})

const onShowMessage = (list, title, compact = false) => {
    if (messageListRef.value) {
        const newList = copyObj(list)
        messageListRef.value.show(newList, title, compact)
    }
}

const payenmtModalShow = () => {
    Modal.confirm({
        title: '语音播放插件需购买开启后使用',
        content: '确认去购买吗？',
        okText: '去购买',
        onOk: () => {
            const link = router.resolve({
                path: '/plug/index'
            })
            window.open(link.href)
        }
    })
}

const existArchivePlug = () => {
    if (!archiveStfModule.value.is_install || archiveStfModule.value.is_expired) {
        payenmtModalShow()
        return false
    }
    if (archiveStfSetting.value.enable_voice_play != 1) {
        Modal.confirm({
            title: '提示',
            content: '语音播放下载已禁用，如需使用需启用插件「存档消息管理」设置',
            okText: '去设置',
            onOk: () => router.push(getPluginRouteParams({name: 'archive_staff'}))
        })
        return false
    }
    return true
}

const playingVoice = (msg) => {
    if (mediaRemoved.value) {
        message.warning('语音文件已清除')
        return
    }
    if (msg.media_status === 'unavailable') {
        message.error('语音文件暂时无法访问，请稍后重试')
        return
    }
    if (!existArchivePlug()) {
        return
    }
    if (!msg.msg_content) {
        message.error('播放失败，文件正在下载中！')
        return;
    }
    emit('playVoice', props.messageInfo)
}

const playingVideo = (msg) => {
    if (mediaRemoved.value) {
        message.warning('视频文件已清除')
        return
    }
    if (msg.media_status === 'unavailable') {
        message.error('视频文件暂时无法访问，请稍后重试')
        return
    }
    if (!msg.msg_content) {
        message.error('播放失败，文件正在下载中！')
        return;
    }
    emit('playVideo', props.messageInfo)
}

const getTotalStorageSizeTitle = () => {
    return "当前文件存储已超过" + totalStorage.value + "G，无法下载"
}

const downloadMsgFile = () => {
    const msg = props.messageInfo
    if (mediaRemoved.value) {
        message.warning('文件已清除')
        return
    }
    if (msg.media_status === 'unavailable') {
        message.error('文件暂时无法访问，请稍后重试')
        return
    }
    if (!msg.msg_content) {
        message.warning('文件正在下载中，请稍后再试')
        return
    }
    if (!existArchivePlug()) {
        return
    }
    switch (msg.msg_type) {
        case 'file':
            downloadFile(msg.msg_content, msg.raw_content.filename, handleDownloadError)
            break
        case 'meeting_voice_call':
            downloadFile(msg.msg_content, `语音通话-${msg.msg_id}.amr`, handleDownloadError)
            break
        case 'voice':
            downloadFile(msg.msg_content, `语音消息-${msg.msg_id}.mp3`, handleDownloadError)
            break
        case 'video':
            downloadFile(msg.msg_content, `视频消息-${msg.msg_id}.mp4`, handleDownloadError)
            break
    }
}

const handleDownloadError = status => {
    if (status === 404) {
        mediaLoadFailed.value = true
        message.warning('文件已清除')
        return
    }
    message.error('文件暂时无法访问，请稍后重试')
}

const downloadFileLimit = fileCont => {

}

const showFileSize = size => {
    if (!size) {
        return ''
    }
    return formatBytes(size)
}

const formatMeetingTime = (startTimestamp, endTimestamp) => {
    const start = dayjs(Number(startTimestamp) * 1000)
    const end = dayjs(Number(endTimestamp) * 1000)
    if (!startTimestamp || !endTimestamp || !start.isValid() || !end.isValid()) {
        return '--'
    }
    const todayText = start.isSame(dayjs(), 'day') ? '今天 ' : ''
    return `${todayText}${start.format('M月D日 HH:mm')} - ${end.format('HH:mm')}`
}

const showBuyFileStorage = () => {
    Modal.confirm({
        title: `文件存储不足`,
        okText: `去购买`,
        cancelText: '取 消',
        width: '500px',
        centered: true,
    })
}
</script>

<style scoped lang="less">
.isSelf {
    .message-box {
        background: #D4E3FC;
        text-align: left;
        border: none;
    }

    .message-box:after {
        content: "";
        position: absolute;
        left: 100%;
        top: 6px;
        border-top: 3px solid transparent;
        border-left: 6px solid #D4E3FC;
        border-bottom: 3px solid transparent;
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

    &.warning {
        border: 1px solid #FB363F;
        background: linear-gradient(0deg, #FFEBEC 0%, #FFEBEC 100%), #FFF;
    }

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

    &.text {
        display: inline-block;
    }

    &.mixed-message-box {
        min-width: 220px;
        padding: 4px 12px;
    }

    &.note-message-box {
        box-sizing: border-box;
        width: 240px;
        padding: 0;

        .note-content {
            padding: 12px 16px;
        }

        .note-title {
            font-size: 16px;
            font-weight: 500;
            line-height: 24px;
        }

        .note-description {
            margin-top: 4px;
            display: -webkit-box;
            overflow: hidden;
            color: #9A9AA1;
            font-size: 14px;
            line-height: 22px;
            white-space: pre-wrap;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
        }

        .note-type {
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            padding: 8px 16px;
            border-top: 1px solid #D9D9D9;
            border-right: 0;
            border-bottom: 0;
            border-left: 0;
            background: transparent;
            color: #8C8C8C;
            font-size: 14px;
            line-height: 22px;
            cursor: pointer;
        }
    }

    &.solitaire-message-box {
        box-sizing: border-box;
        width: 240px;
        padding: 0;

        .solitaire-content {
            padding: 12px 16px;
            color: #262626;
            font-size: 14px;
            line-height: 22px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .solitaire-type {
            padding: 8px 16px;
            border-top: 1px solid #D9D9D9;
            color: #8C8C8C;
            font-size: 14px;
            line-height: 22px;
        }
    }

    &.contact-card-box {
        box-sizing: border-box;
        width: 240px;
        padding: 0;
        overflow: hidden;

        .contact-card-content {
            padding: 14px 16px;
        }

        .corp-name {
            color: #262626;
            font-size: 18px;
            font-weight: 500;
            line-height: 26px;
        }

        .user-name {
            margin-top: 2px;
            color: #8c8c8c;
            font-size: 14px;
            line-height: 22px;
        }

        .contact-card-bottom {
            padding: 8px 16px;
            border-top: 1px solid #D9D9D9;
            color: #8c8c8c;
            font-size: 14px;
            line-height: 22px;
        }
    }

    &.voice {
        div {
            display: flex;
            align-items: center;
        }
    }

    &.file-box {
        display: flex;
        align-items: center;
        color: #262626;
        font-size: 14px;
        font-weight: 400;
        max-width: 300px;

        .file-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            margin-right: 8px;
        }

        .file-info-box {
            flex-shrink: 0;
            width: calc(100% - 48px);
            display: flex;
            align-items: center;
            justify-content: space-between;

            .left-block {
                flex-shrink: 0;
                width: calc(100% - 20px);

                .file-name {
                    word-break: break-all;
                    text-overflow: ellipsis;
                    overflow: hidden;
                    display: -webkit-box;
                    -webkit-line-clamp: 1;
                    -webkit-box-orient: vertical;
                }

                .file-size {
                    color: #8c8c8c;
                    font-size: 12px;
                    margin-top: 2px;
                }
            }

            .right-block {
                flex-shrink: 0;
                width: 20px;
                font-size: 16px;
                color: #8C8C8C;
                text-align: right;
            }
        }
    }
    &.red-envelope-box {
        background: #FF5443;
        width: 260px;
        &::after {
            content: '';
            background: none;
            border: none;
        }
        .cover {
            width: 48px;
            height: 48px;
        }
        .price {
            color: #ffeeec;
            font-size: 16px;
            font-weight: 600;
            line-height: 24px;
            margin-bottom: 2px;
        }
        .desc {
            color: #ffe3e0;
            font-size: 14px;
            font-weight: 400;
        }
        .extra-info {
            margin-top: 12px;
            margin-left: 4px;
            color: #ffc7c2;
            font-size: 12px;
            font-weight: 400;
            line-height: 16px;
            padding-top: 8px;
            border-top: 1px solid #D9D9D9;
        }
    }
    &.msg-link-box {
        padding: 0;
        border-radius: 6px;
        border: 1px solid #F0F0F0;
        background: #FFF;
        display: flex;
        flex-direction: column;

        .msg-link-content {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;

            .msg-link-avatar {
                flex-shrink: 0;
                width: 48px;
                height: 48px;
                border-radius: 4px;

                img {
                    width: 48px;
                    height: 48px;
                    object-fit: cover;
                }
            }

            .msg-link-text {
                min-width: 0;
                flex: 1;
            }

            .msg-link-title,
            .msg-link-description {
                display: -webkit-box;
                width: 100%;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 1;
                overflow: hidden;
                text-overflow: ellipsis;
                font-style: normal;
                font-weight: 400;
            }

            .msg-link-title {
                color: #262626;
                font-size: 14px;
                line-height: 22px;
            }

            .msg-link-description {
                margin-top: 2px;
                color: #8c8c8c;
                font-size: 12px;
                line-height: 20px;
            }
        }

        .bottom {
            font-size: 12px;
            padding: 8px 12px;
            border-top: 1px solid #D9D9D9;
            cursor: pointer;
        }

        .msg-link-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
        }

        .msg-link-info {
            color: #8c8c8c;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
        }

        .msg-link-icon {
            color: #8c8c8c;
        }
    }
    &.msg-location-box {
        box-sizing: border-box;
        width: 280px;
        padding: 0;
        overflow: hidden;
        border-radius: 6px;
        border: 1px solid #F0F0F0;
        background: #FFF;

        .msg-location-content {
            padding: 12px;
        }

        .msg-location-title,
        .msg-location-address {
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .msg-location-title {
            color: #262626;
            font-size: 14px;
            line-height: 22px;
        }

        .msg-location-address {
            margin-top: 4px;
            color: #8c8c8c;
            font-size: 12px;
            line-height: 20px;
        }

        .msg-location-bottom {
            padding: 8px 12px;
            border-top: 1px solid #D9D9D9;
            color: #8c8c8c;
            font-size: 14px;
            line-height: 22px;
        }
    }
    &.msg-meeting-box {
        box-sizing: border-box;
        width: 280px;
        padding: 0;
        overflow: hidden;
        border-radius: 6px;
        border: 1px solid #F0F0F0;
        background: #FFF;

        .msg-meeting-content {
            padding: 12px;
            background: linear-gradient(135deg, #f4f8ff 0%, #eaf3ff 100%);
        }

        .msg-meeting-topic,
        .msg-meeting-time,
        .msg-meeting-address .value {
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .msg-meeting-topic {
            color: #262626;
            font-size: 16px;
            font-weight: 500;
            line-height: 24px;
        }

        .msg-meeting-time {
            margin-top: 4px;
            color: #262626;
            font-size: 14px;
            line-height: 22px;
        }

        .msg-meeting-address {
            display: flex;
            padding: 10px 12px;
            font-size: 14px;
            line-height: 22px;

            .label {
                flex-shrink: 0;
                margin-right: 12px;
                color: #8c8c8c;
            }

            .value {
                min-width: 0;
                color: #595959;
            }
        }

        .msg-meeting-bottom {
            padding: 8px 12px;
            border-top: 1px solid #D9D9D9;
            color: #8c8c8c;
            font-size: 14px;
            line-height: 22px;
        }
    }
    &.msg-todo-box {
        box-sizing: border-box;
        width: 280px;
        padding: 0;
        overflow: hidden;
        border-radius: 6px;
        border: 1px solid #F0F0F0;
        background: #FFF;

        .msg-todo-content {
            padding: 12px;
        }

        .msg-todo-title {
            overflow: hidden;
            color: #262626;
            font-size: 16px;
            font-weight: 500;
            line-height: 24px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .msg-todo-description {
            margin-top: 4px;
            color: #8C8C8C;
            font-size: 14px;
            line-height: 22px;
            overflow-wrap: anywhere;
            white-space: pre-line;
        }

        .msg-todo-bottom {
            padding: 8px 12px;
            border-top: 1px solid #D9D9D9;
            color: #8C8C8C;
            font-size: 14px;
            line-height: 22px;
        }
    }
    &.msg-weapp-box {
        box-sizing: border-box;
        width: 280px;
        overflow: hidden;
        border-radius: 6px;
        border: 1px solid #F0F0F0;
        background: #FFF;
        display: flex;
        gap: 12px;

        .msg-weapp-avatar {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            overflow: hidden;
            border-radius: 4px;

            img {
                width: 48px;
                height: 48px;
                object-fit: cover;
            }
        }

        .msg-weapp-content {
            display: flex;
            min-width: 0;
            flex: 1;
            flex-direction: column;
            gap: 4px;

            .title {
                display: -webkit-box;
                width: 100%;
                max-width: 100%;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 1;
                overflow: hidden;
                color: #262626;
                text-overflow: ellipsis;
                font-family: "PingFang SC";
                font-size: 14px;
                font-style: normal;
                font-weight: 400;
                line-height: 22px;
            }

            .description {
                display: -webkit-box;
                width: 100%;
                max-width: 100%;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 1;
                overflow: hidden;
                color: #8c8c8c;
                text-overflow: ellipsis;
                font-family: "PingFang SC";
                font-size: 12px;
                font-style: normal;
                font-weight: 400;
                line-height: 20px;
            }
        }
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
.voice-phone-icon {
    transform: rotate(90deg);
    font-size: 14px;
}
.voice-play-icon {
    width: 14px;
    height: 12px;
}
.icon-btn {
    font-size: 16px;
    cursor: pointer;
    &:hover {
        color: #2475FC;
    }
}
.icon-disabled {
    font-size: 16px;
    color: #999;
    cursor: no-drop;
}
</style>
