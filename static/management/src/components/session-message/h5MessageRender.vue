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
        <div v-else-if="messageInfo.msg_type == 'video'" class="message-box">[视频消息]</div>
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
                    <span class="ml8">语音通话 {{ getVoiceCallDuration }}</span>
                    <a-divider type="vertical"/>
                    <PauseCircleOutlined @click="playingVoice(messageInfo)" class="icon-btn"/>
                </template>
                <template v-else>
                    <PhoneOutlined class="voice-phone-icon"/>
                    <span class="ml8">语音通话 {{ getVoiceCallDuration }}</span>
                    <a-divider type="vertical"/>
                    <PlayCircleOutlined @click="playingVoice(messageInfo)" class="icon-btn"/>
                </template>
                <DownloadOutlined @click="downloadMsgFile" class="icon-btn ml8"/>
            </div>
        </div>
        <div v-else-if="messageInfo.msg_type == 'voiptext'"
             class="message-box">
            <div class="zm-flex-center">
                <PhoneOutlined class="voice-phone-icon"/>
                <span class="ml8">语音通话 {{ secondsToDate(messageInfo.raw_content.callduration) }}</span>
            </div>
        </div>
        <!-- 语音消息-->
        <div v-else-if="messageInfo.msg_type == 'voice'"
             class="message-box pointer">
            <div class="zm-flex-center">
                <template v-if="voicePlaying">
                    <img src="@/assets/image/icon-voice.gif" class="voice-play-icon"/>
                    <span class="ml8">语音消息 {{ formatSeconds(messageInfo.raw_content.play_length) }}</span>
                    <a-divider type="vertical"/>
                    <PauseCircleOutlined @click="playingVoice(messageInfo)" class="icon-btn"/>
                </template>
                <template v-else>
                    <img class="icon-14" src="@/assets/image/icon-voice.png"/>
                    <span class="ml8">语音消息 {{ formatSeconds(messageInfo.raw_content.play_length) }}</span>
                    <a-divider type="vertical"/>
                    <PlayCircleOutlined @click="playingVoice(messageInfo)" class="icon-btn"/>
                </template>
                <DownloadOutlined @click="downloadMsgFile" class="icon-btn ml8"/>
            </div>
        </div>
        <!-- 红包消息-->
        <div v-else-if="messageInfo.msg_type == 'external_redpacket'" class="message-box red-envelope-box">
            <div class="zm-flex-center">
                <img class="cover" src="@/assets/image/session/red-envelope-cover.png"/>
                <div class="ml8">
                    <div class="price">¥{{formatPrice(messageInfo?.raw_content?.totalamount)}}</div>
                    <div class="desc">{{messageInfo?.raw_content?.wish}}</div>
                </div>
            </div>
            <!--1 普通红包、2 拼手气群红包-->
            <div class="extra-info">
                {{RedpacketTypeMap[messageInfo?.raw_content?.type]}}
                {{messageInfo?.raw_content?.totalcnt}}个
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
import {
    downloadFile,
    formatBytes,
    secondsToDate,
    formatSeconds,
    getFileIcon,
    formatPrice,
    MessageTypeTextMap,
    RedpacketTypeMap
} from "@/utils/tools";
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
        message.error('播放失败，缺少文件！')
        return;
    }
    emit('playVoice', props.messageInfo)
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
            downloadFile(msg.msg_content, `语音消息-${msg.msg_id}.amr`)
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
        top: 0.6rem;
        border-top: 0.3rem solid transparent;
        border-left: 0.6rem solid #D4E3FC;
        border-bottom: 0.3rem solid transparent;
    }
}

.message-box {
    display: inline-block;
    background: #FFFFFF;
    padding: 0.8rem;
    max-width: 80vw;
    white-space: normal;
    word-break: break-all;
    border-radius: .2rem;
    opacity: 1;
    border: 1px solid #e6e6e6;
    position: relative;
    font-size: 1.4rem;
    font-weight: 400;
    color: #595959;

    &.warning {
        border: 1px solid #FB363F;
        background: linear-gradient(0deg, #FFEBEC 0%, #FFEBEC 100%), #FFF;
    }

    &::after {
        content: "";
        display: block;
        width: .7rem;
        height: .8rem;
        position: absolute;
        top: .6rem;
        left: -0.7rem;
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
        font-size: 1.4rem;
        font-weight: 400;
        max-width: 30rem;

        .file-icon {
            flex-shrink: 0;
            width: 4rem;
            height: 4rem;
            margin-right: .8rem;
        }

        .file-info-box {
            flex-shrink: 0;
            width: calc(100% - 4.8rem);
            display: flex;
            align-items: center;
            justify-content: space-between;

            .left-block {
                flex-shrink: 0;
                width: calc(100% - 2rem);

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
                    font-size: 1.2rem;
                    margin-top: .2rem;
                }
            }

            .right-block {
                flex-shrink: 0;
                width: 2rem;
                font-size: 1.6rem;
                color: #8C8C8C;
                text-align: right;
            }
        }
    }
    &.red-envelope-box {
        background: #FF5443;
        width: 26rem;
        &::after {
            content: '';
            background: none;
            border: none;
        }
        .cover {
            width: 4.8rem;
            height: 4.8rem;
        }
        .price {
            color: #ffeeec;
            font-size: 1.6rem;
            font-weight: 600;
            line-height: 2.4rem;
            margin-bottom: .2rem;
        }
        .desc {
            color: #ffe3e0;
            font-size: 1.4rem;
            font-weight: 400;
        }
        .extra-info {
            margin-top: 1.2rem;
            margin-left: .4rem;
            color: #ffc7c2;
            font-size: 1.2rem;
            font-weight: 400;
            line-height: 1.6rem;
            padding-top: .8rem;
            border-top: 1px solid #D9D9D9;
        }
    }
}

.voice-phone-icon {
    transform: rotate(90deg);
    font-size: 1.4rem;
}

.voice-play-icon {
    width: 1.4rem;
    height: 1.2rem;
}

.icon-btn {
    font-size: 1.6rem;
    cursor: pointer;

    &:hover {
        color: #2475FC;
    }
}
</style>
