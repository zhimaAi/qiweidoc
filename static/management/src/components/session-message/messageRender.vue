<template>
    <div :class="{isSelf: isSelf}">
        <!--文本-->
        <div v-if="messageInfo.msg_type === 'text'" class="message-box text">{{ messageInfo.msg_content }}</div>
        <!--图片-->
        <template v-else-if="messageInfo.msg_type === 'image' || messageInfo.msg_type === 'emotion'">
            <a-tooltip v-if="messageInfo.file_is_remove" title="图片已清除">
                <img src="@/assets/image/session/load-img-deleted.png" style="width: 120px;"/>
            </a-tooltip>
            <div v-else-if="messageInfo.msg_content " class="message-box image pointer">
                <a-image :src="messageInfo.msg_content" style="max-width: 200px;"/>
            </div>
            <a-tooltip v-else title="系统正在下载中，稍后再试！">
                <img src="@/assets/image/session/load-img-run.png" style="width: 120px;"/>
            </a-tooltip>
        </template>
        <!-- 链接 -->
        <div v-else-if="messageInfo.msg_type === 'link'" class="message-box">
            <span v-if="messageInfo.hasOwnProperty('link_url')">
                [链接]<a target="_blank" :href="messageInfo.link_url">{{ messageInfo.link_title }}</a>
            </span>
            <span v-else>
                [链接]<a target="_blank" :href="messageInfo.link">{{ messageInfo.link }}</a>
            </span>
        </div>
        <!-- 视频 -->
        <div v-else-if="messageInfo.msg_type == 'video'" class="message-box video-box">
            <div class="zm-flex-center">
                <template v-if="voicePlaying">
                    <img src="@/assets/image/icon-voice.gif" class="voice-play-icon"/>
                    <span class="ml8">视频消息 {{formatSeconds(messageInfo.raw_content.play_length)}}</span>
                    <a-divider type="vertical"/>
                    <a-tooltip title="停止播放">
                        <PauseCircleOutlined @click="playingVideo(messageInfo)" class="icon-btn"/>
                    </a-tooltip>
                </template>
                <template v-else>
                    <img class="icon-14" src="@/assets/image/icon-video.png"/>
                    <span class="ml8">视频消息 {{formatSeconds(messageInfo.raw_content.play_length)}}</span>
                    <a-divider type="vertical"/>
                    <a-tooltip title="播放视频">
                        <PlayCircleOutlined @click="playingVideo(messageInfo)" class="icon-btn"/>
                    </a-tooltip>
                </template>
                <DownloadOutlined v-if="messageInfo.msg_content && messageInfo.msg_id" @click="downloadMsgFile" class="icon-btn ml8"/>
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
                    <a-tooltip v-if="messageInfo.file_is_remove" title="文件已清除">
                        <DownloadOutlined style="color: #CCC;"/>
                    </a-tooltip>
                    <a v-else-if="messageInfo.msg_content" @click="downloadMsgFile">
                        <DownloadOutlined/>
                    </a>
                    <a-tooltip v-else title="系统正在下载中，稍后再试！">
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
                    <a-tooltip title="停止播放">
                        <PauseCircleOutlined @click="playingVoice(messageInfo)" class="icon-btn"/>
                    </a-tooltip>
                </template>
                <template v-else>
                    <PhoneOutlined class="voice-phone-icon"/>
                    <span class="ml8">语音通话 {{getVoiceCallDuration}}</span>
                    <a-divider type="vertical"/>
                    <a-tooltip title="播放通话">
                        <PlayCircleOutlined @click="playingVoice(messageInfo)" class="icon-btn"/>
                    </a-tooltip>
                </template>
                <DownloadOutlined @click="downloadMsgFile" class="icon-btn ml8"/>
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
                    <a-tooltip title="停止播放">
                        <PauseCircleOutlined @click="playingVoice(messageInfo)" class="icon-btn"/>
                    </a-tooltip>
                </template>
                <template v-else>
                    <img class="icon-14" src="@/assets/image/icon-voice.png"/>
                    <span class="ml8">语音消息 {{formatSeconds(messageInfo.raw_content.play_length)}}</span>
                    <a-divider type="vertical"/>
                    <a-tooltip title="播放语音">
                        <PlayCircleOutlined @click="playingVoice(messageInfo)" class="icon-btn"/>
                    </a-tooltip>
                </template>
                <DownloadOutlined @click="downloadMsgFile" class="icon-btn ml8"/>
            </div>
        </div>
        <!-- 混合消息 -->
        <div v-else class="message-box">[{{ MessageTypeTextMap[messageInfo.msg_type] }}]</div>
        <span v-if="messageInfo.is_revoke" class="message-box" style="color: rgba(0,0,0,.25);">已撤回</span>
    </div>
</template>

<script setup>
import {ref, computed} from 'vue';
import dayjs from 'dayjs';
import {Modal, message} from 'ant-design-vue';
import {DownloadOutlined, PlayCircleOutlined, PauseCircleOutlined, PhoneOutlined} from '@ant-design/icons-vue';
import {downloadFile, formatBytes, secondsToDate, formatSeconds, getFileIcon, MessageTypeTextMap} from "@/utils/tools";
import BenzAMRRecorder from 'benz-amr-recorder';

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
const amrPlayer = ref(null)
const totalStorage = ref(10)

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

const playingVoice = (msg) => {
    if (!msg.msg_content) {
        message.error('播放失败，文件正在下载中！')
        return;
    }
    emit('playVoice', props.messageInfo)
}

const playingVideo = (msg) => {
    if (!msg.msg_content) {
        message.error('播放失败，文件正在下载中！')
        return;
    }
    emit('playVideo', props.messageInfo)
}

const getTotalStorageSizeTitle = () => {
    return "当前文件存储已超过" + totalStorage + "G，无法下载"
}

const downloadMsgFile = () => {
    const msg = props.messageInfo
    switch (msg.msg_type) {
        case 'file':
            downloadFile(msg.msg_content, msg.raw_content.filename)
            break
        case 'meeting_voice_call':
            downloadFile(msg.msg_content, `语音通话-${msg.msg_id}.amr`)
            break
        case 'voice':
            downloadFile(msg.msg_content, `语音消息-${msg.msg_id}.mp3`)
            break
        case 'video':
            downloadFile(msg.msg_content, `视频消息-${msg.msg_id}.mp4`)
            break
    }
}

const downloadFileLimit = fileCont => {

}

const showFileSize = size => {
    if (!size) {
        return ''
    }
    return formatBytes(size)
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
    border-radius: 2px;
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
