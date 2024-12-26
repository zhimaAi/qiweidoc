<template>
    <div class="_session-message-container">
        <NavBar :title="chatName" fixed></NavBar>
        <Empty v-if="!list.length" description="暂无消息"/>
        <ZmScroll
            :loading="loading"
            :finished="finished"
            :immediate="false"
            :showLoading="list.length > 0"
            ref="listRef"
            loadType="up"
            @load="loadData"
            :class="['message-list', { opacity: opacity }]">
            <template v-for="item in list">
                <div v-if="item.msg_type != 'revoke'"
                     :key="item.msg_id"
                     :class="['message-item', { isSelf: item.isSelf }]">
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
                        <H5MessageRender
                            :voicePlaying="item.msg_id === currentPayVoiceKey"
                            :message-info="item"
                            @playVoice="playVoice"/>
                    </div>
                </div>
            </template>
        </ZmScroll>
    </div>
</template>

<script setup>
import {onMounted, ref, reactive, nextTick} from 'vue';
import {useRoute} from 'vue-router';
import dayjs from 'dayjs';
import {NavBar, Empty} from 'vant';
import 'vant/es/nav-bar/style';
import 'vant/es/empty/style';
import defaultAvatar from "@/assets/default-avatar.png";
import H5MessageRender from "@/components/session-message/h5MessageRender.vue";
import {VoicePlayHandle} from "@/utils/voicePlay";
import ZmScroll from "@/components/zmScroll.vue";
import {setH5AuthToken} from "@/utils/cache";
import {h5GroupSessionMessage, h5PrivateSessionMessage} from "@/api/h5";

const props = defineProps({
    sessionType: {
        type: Number,
        default: 1, // 1 单聊； 2 群聊
    }
})
const route = useRoute()
const {play, getPlayerKey} = VoicePlayHandle()
const currentPayVoiceKey = getPlayerKey()
const chatName = ref('')
const listRef = ref(null)
const oldHeight = ref(0)
const loading = ref(false)
const finished = ref(false)
const opacity = ref(false)
const list = ref([])
const pagination = reactive({
    current: 1,
    pageSize: 15,
    total: 0,
})

onMounted(() => {
    if (route.query.token) {
        setH5AuthToken(route.query.token)
        loadData()
    }
})

function loadData() {
    loading.value = true
    let request = h5PrivateSessionMessage
    if (props.sessionType === 2) {
        request = h5GroupSessionMessage
    }
    request({
        page: pagination.current,
        size: pagination.pageSize
    }).then(res => {
        let data = res.data || {}
        let {items, total} = data
        chatName.value = chatNameParse(data)
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
            item.isSelf = (item.from_role === 'Staff')
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

function chatNameParse(data) {
    if (props.sessionType === 1) {
        let {from_detail, to_detail} = data
        const userFormat = user => {
            if (user?.name) {
                return `员工${user.name}`
            } else if (user?.external_name) {
                let corp = user?.corp_name ? `@${user.corp_name}` : '@微信'
                return `客户${user.external_name}${corp}`
            }
        }
        return `${userFormat(from_detail)}和${userFormat(to_detail)}的聊天`
    } else {
        return data?.group?.name || '未命名群聊'
    }
}

function playVoice(message) {
    play(message.msg_id, message.msg_content)
}
</script>

<style lang="less">
._session-message-container {
    .van-nav-bar__title {
        font-size: 1.4rem;
    }

    .message-list {
        padding-top: 50px;
        height: 100vh;
        opacity: 1;
        scroll-behavior: initial !important;

        &.opacity {
            opacity: 0;
        }
    }

    .message-item {
        display: flex;
        padding: 1rem 1.6rem 1.8rem 1.6rem;
        position: relative;

        &.isSelf {
            flex-flow: row-reverse;
            text-align: right;

            .avatar {
                margin-left: 1.6rem;
                margin-right: 0;
            }

            .user-name {
                text-align: right;
            }
        }

        .content {
            .user-name {
                font-size: 1.2rem;
                font-weight: 400;
                color: rgba(0, 0, 0, 0.65);
                margin-bottom: 4px;

                .msg-time {
                    font-size: 1.2rem;
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
            width: 3.2rem;
            height: 3.2rem;
            border-radius: .2rem;
            margin-right: .8rem;
        }
    }
}
</style>
