<template>
    <div :class="['message-item', {compact}]">
        <div v-if="!compact" class="avatar">
            <img src="@/assets/image/default-avatar.png" alt="">
        </div>
        <div class="message-main">
            <div v-if="!compact" class="message-time">{{ statDateFormat(item.msgtime) }}</div>
            <div v-if="normalizedType === 'text'" class="text-content">{{ content.content || '--' }}</div>
            <div v-else-if="normalizedType === 'agree'" class="message-box">对方同意会话内容存档</div>
            <div v-else-if="normalizedType === 'disagree'" class="message-box">对方不同意会话内容存档，你将无法继续提供服务</div>

            <div v-else-if="normalizedType === 'image'" class="message-box image-box">
                <img v-if="mediaRemoved" class="media-placeholder"
                     src="@/assets/image/session/load-img-deleted.png" alt="图片已清除">
                <a-image v-else-if="content.download_url" :src="content.download_url" :width="180"
                         @error="mediaLoadFailed = true"/>
                <img v-else class="media-placeholder" src="@/assets/image/session/load-img-run.png" alt="图片下载中">
            </div>

            <div v-else-if="normalizedType === 'file'" class="message-box file-box">
                <img class="file-icon" :src="getFileIcon(content.fileext)" alt="">
                <div class="file-info">
                    <div class="file-name">{{ content.filename || '未知文件' }}</div>
                    <div class="file-size">{{ showFileSize(content.filesize) }}</div>
                </div>
                <a v-if="content.download_url && !mediaRemoved" @click="downloadMediaFile">下载</a>
                <span v-else class="disabled">{{ mediaStatusText }}</span>
            </div>

            <div v-else-if="normalizedType === 'video'" class="message-box video-box">
                <video v-if="content.download_url && !mediaRemoved" :src="content.download_url" controls
                       preload="metadata" @error="mediaLoadFailed = true"/>
                <span v-else>{{ mediaRemoved ? '视频已清除' : '视频下载中' }}</span>
            </div>

            <div v-else-if="normalizedType === 'link'" class="message-box link-box">
                <img v-if="content.image_url" :src="content.image_url" alt="">
                <div>
                    <div class="link-title">{{ content.title || '链接消息' }}</div>
                    <div class="description">{{ content.description }}</div>
                    <a v-if="safeLink" :href="safeLink" target="_blank" rel="noopener noreferrer">{{ MessageTypeTextMap[normalizedType] }}</a>
                </div>
            </div>

            <div v-else-if="normalizedType === 'location'" class="message-box location-box">
                <EnvironmentOutlined/>
                <div>
                    <div class="location-title">{{ content.title || '位置消息' }}</div>
                    <div>{{ content.address || `${content.latitude || '--'}, ${content.longitude || '--'}` }}</div>
                </div>
            </div>

            <div v-else-if="normalizedType === 'mixed'" class="message-box mixed-box">
                <ChatRecordItem
                    v-for="(child, index) in depth < 3 ? mixedItems : []"
                    :key="index"
                    :item="child"
                    :allow-open="allowOpen"
                    :depth="depth + 1"
                    compact
                    @show-message="onShowMessage"
                />
                <span v-if="mixedItems.length === 0">[混合消息]</span>
                <span v-else-if="depth >= 3">[内容层级过深]</span>
            </div>

            <div v-else-if="normalizedType === 'chatrecord'" class="message-box forwarding-box">
                <div class="title">{{ content.title || '转发合集' }}</div>
                <div class="count">共 {{ recordItems.length }} 条消息</div>
                <button v-if="allowOpen && recordItems.length" type="button" class="detail"
                        @click="onShowMessage(recordItems, content.title)">
                    <span>{{ MessageTypeTextMap[normalizedType] }}</span>
                    <RightOutlined/>
                </button>
            </div>

            <div v-else class="message-box">[暂不支持的消息类型：{{ item.type || 'unknown' }}]</div>
        </div>
    </div>
</template>

<script setup>
import {computed, ref} from 'vue';
import dayjs from 'dayjs';
import {message} from 'ant-design-vue';
import {EnvironmentOutlined, RightOutlined} from '@ant-design/icons-vue';
import {downloadFile, formatBytes, getFileIcon, jsonDecode, MessageTypeTextMap} from '@/utils/tools';

const props = defineProps({
    item: {type: Object, default: () => ({})},
    allowOpen: {type: Boolean, default: true},
    depth: {type: Number, default: 1},
    compact: {type: Boolean, default: false},
})
const emit = defineEmits(['showMessage'])
const mediaLoadFailed = ref(false)

const TYPE_MAP = {
    ChatRecordText: 'text',
    ChatRecordImage: 'image',
    ChatRecordFile: 'file',
    ChatRecordVideo: 'video',
    ChatRecordLink: 'link',
    ChatRecordLocation: 'location',
    ChatRecordMixed: 'mixed',
    ChatRecord: 'chatrecord',
}

const itemType = computed(() => props.item.type || props.item.msg_type || '')
const normalizedType = computed(() => TYPE_MAP[itemType.value] || String(itemType.value).toLowerCase())
const content = computed(() => {
    if (props.item.content && typeof props.item.content === 'object') {
        return props.item.content
    }
    return jsonDecode(props.item.content, {})
})
const recordItems = computed(() => Array.isArray(content.value.item) ? content.value.item : [])
const mixedItems = computed(() => Array.isArray(content.value.item) ? content.value.item : [])
const safeLink = computed(() => /^https?:\/\//i.test(content.value.link_url || '') ? content.value.link_url : '')
const mediaRemoved = computed(() => content.value.media_status === 'removed' || mediaLoadFailed.value)
const mediaStatusText = computed(() => {
    if (mediaRemoved.value) return '已清除'
    return content.value.media_status === 'unavailable' ? '暂时无法访问' : '下载中'
})

const onShowMessage = (list, title) => emit('showMessage', list, title)
const downloadMediaFile = () => downloadFile(content.value.download_url, content.value.filename, status => {
    if (status === 404) {
        mediaLoadFailed.value = true
        message.warning('文件已清除')
        return
    }
    message.error('文件暂时无法访问，请稍后重试')
})
const statDateFormat = time => time ? dayjs(Number(time) * 1000).format('MM-DD HH:mm') : '--'
const showFileSize = size => size ? formatBytes(size) : ''
</script>

<style scoped lang="less">
.message-item { display: flex; gap: 12px; padding: 16px 0; border-bottom: 1px solid #f0f0f0; background: #fff; }
.message-item.compact { padding: 8px 0; border-bottom: 0; background: transparent; }
.avatar, .avatar img { width: 40px; height: 40px; border-radius: 12px; }
.message-main { min-width: 0; flex: 1; }
.message-time, .description, .file-size, .disabled { color: #8c8c8c; font-size: 12px; }
.text-content { margin-top: 8px; white-space: pre-wrap; word-break: break-word; }
.message-box { box-sizing: border-box; max-width: 520px; margin-top: 8px; padding: 12px; border: 1px solid #e6e6e6; border-radius: 8px; color: #595959; word-break: break-word; }
.media-placeholder { width: 120px; }
.file-box, .link-box, .location-box { display: flex; align-items: center; gap: 12px; }
.file-icon { width: 36px; }
.file-info { min-width: 0; flex: 1; }
.file-name, .link-title, .location-title, .title { color: #262626; font-weight: 500; }
.link-box img { width: 56px; height: 56px; border-radius: 4px; object-fit: cover; }
.video-box video { width: 320px; max-width: 100%; }
.mixed-box { padding: 0 12px; }
.mixed-box :deep(.message-item:last-child) { border-bottom: 0; }
.forwarding-box { padding: 0; }
.forwarding-box .title, .forwarding-box .count { padding: 10px 12px 0; }
.forwarding-box .count { padding-bottom: 10px; }
.detail { display: flex; width: 100%; align-items: center; justify-content: space-between; padding: 8px 12px; border: 0; border-top: 1px solid #d9d9d9; background: transparent; color: #8c8c8c; cursor: pointer; }
</style>
