<template>
    <ZmScroll
        :finished="finished"
        :loading="loading"
        :immediate="false"
        @load="loadData"
        class="contact-box">
        <!---联系客户-->
        <div v-for="(item,i) in list"
             @click="change(item)"
             :class="['contact-item', {active: isSelected(item)}]"
             :key="i">
            <div class="left">
                <img v-if="item.avatar" class="avatar" :src="item.avatar"/>
                <img v-else class="avatar" src="@/assets/default-avatar.png"/>
            </div>
            <div class="right">
                <div class="zm-flex-between" :title="item.external_name+'@'+(item.corp_name || '微信')">
                    <div class="userinfo">
                        <span v-if="item.is_new_user == 1" class="is-new-tag">新</span>
                        <div class="user-name">
                            {{ item.external_name || '...' }}
                        </div>
                        <div :class="['corp-name ml4',{'is-wx': ! item.corp_name ||  item.corp_name === '微信'}]">
                            @{{ item.corp_name || '微信' }}
                        </div>
                    </div>
                    <img v-if="item.is_collect" class="icon-12" src="@/assets/svg/is_collect.svg" @click="onCollection(item)" />
                </div>
                <div class="zm-flex-between">
                    <div class="last-msg">
                        <img class="icon-14" src="@/assets/image/icon-message.png"/>
                        <span class="ml4">{{ item.last_msg_date }}</span>
                    </div>
                    <a-tooltip
                        v-if="showSettings?.show_is_read == 1 && item.is_read > 0"
                        title="客户的消息已读后，给客户的会话加一个已读的标识">
                       <div class="zm-flex-center">
                           <img class="icon-12" src="@/assets/image/icon-read.png"/>
                           <span class="ml4">已读</span>
                       </div>
                    </a-tooltip>
                </div>
                <a-tooltip v-if="showSettings?.show_customer_tag == 1 && item.tag_data.length > 0">
                    <template #title>
                        <a-tag v-for="tag in item.tag_data" :key="tag.id" style="color: #FFF;margin: 4px;">{{ tag.name }}</a-tag>
                    </template>
                    <div class="cst-tag-list mt4">
                        <a-tag v-for="tag in item.tag_data.slice(0, 5)" :key="tag.id">{{ tag.name }}</a-tag>
                    </div>
                </a-tooltip>
            </div>
        </div>
        <a-empty v-if="finished && list.length < 1" description="暂无数据" style="margin-top: 60px;"/>
    </ZmScroll>
</template>

<script setup>
import {onMounted, ref, reactive, watch, nextTick} from 'vue';
import dayjs from 'dayjs';
import LoadingBox from "@/components/loadingBox.vue";
import ZmScroll from "@/components/zmScroll.vue";
import {staffCstSessions, cancelCollect} from "@/api/session";
import '@/common/contacts.less';
import {formatTime} from "@/utils/tools";
import { message } from 'ant-design-vue';

const props = defineProps({
    default: {
        type: Object,
    },
    staffInfo: {
        type: Object,
    },
    filterData: {
        type: Object,
    },
    callbackData: {
        type: Object
    },
    showSettings: {
        type: Object
    }
})
const emit = defineEmits(['change', 'totalReport', 'cancelCollect'])

const loading = ref(true)
const finished = ref(false)
const selected = ref(null)
const list = ref([])
const useridMap = ref({})
const pagination = reactive({
    current: 1,
    pageSize: 20,
    total: 0,
})

function cancelCollectListFormat (obj) {
    list.value.map(item => {
        if (obj.conversation_id === item.id) {
            // 当前文件调用是取消收藏，如果是监听props传值则取值的状态
            item.is_collect = obj.is_collect || 0
            return
        }
    })
}

const onCollection = (item) => {
    let params = {
        conversation_id: item.id // 会话id
    }
    cancelCollect(params).then((res) => {
        if (res.status == 'success') {
            message.success('取消收藏成功')
            // 更新列表数据，取消收藏后当前数据没有收藏图标
            cancelCollectListFormat({ conversation_id: item.id })
            emit('cancelCollect', 0)
            return
        }
        res.error_message && message.error(res.error_message)
    })
}

watch(() => props.callbackData, (obj) => {
    // 收藏/取消收藏后要更新列表状态
    cancelCollectListFormat(obj)
})

onMounted(() => {
    init()
})

const init = () => {
    pagination.current = 1
    pagination.total = 0
    finished.value = false
    loading.value = true
    list.value = []
    loadData()
}

const loadData = () => {
    loading.value = true
    let params = {
        page: pagination.current,
        size: pagination.pageSize,
        staff_userid: props.staffInfo?.userid
    }
    if (!props.staffInfo?.userid) {
        loading.value = false
        finished.value = true
        initReport()
        return
    }
    if (props.filterData?.keyword) {
        params.keyword = props.filterData.keyword
    }
    if (props.filterData?.tag_ids && props.filterData.tag_ids.length > 0) {
        params.tag_ids = props.filterData.tag_ids
    }
    staffCstSessions(params).then(res => {
        let data = res.data || {}
        let {items, total} = data
        total = Number(total)
        if (!items || !items?.length || list.value.length === total) {
            finished.value = true
        }
        for (let item of items) {
            if (!useridMap.value[item.external_userid]) {
                if (item?.last_msg_time) {
                    item.last_msg_date = formatTime(dayjs(item.last_msg_time).unix())
                } else {
                    item.last_msg_date = '~'
                }
                list.value.push(item)
                useridMap.value[item.external_userid] = true
            }
        }

        pagination.total = total
        initReport()
        pagination.current += 1
        loading.value = false
    }).catch(() => {
        loading.value = false
        finished.value = true
    })
}

const initReport = () => {
    if (pagination.current == 1) {
        emit('totalReport', pagination.total)
        selectedHandle()
    }
}

const selectedHandle = async () => {
    if (props.default && props.default.conversation_id && props.default.receiver_type === 'Customer') {
        const find = list.value.find(item => item.id === props.default.conversation_id)
        if (find) {
            change(find)
        } else {
            let item
            try {
                const res = await staffCstSessions({
                    page: 1,
                    size: 1,
                    conversation_id: props.default.conversation_id
                })
                if (res?.data?.items && res?.data?.items.length) {
                    item = res.data.items[0]
                    list.value.unshift(item)
                    useridMap.value[item.external_userid] = true
                }
            } catch (e) {
            }
            change(item || list.value[0] || null)
        }
    } else {
        change(list.value[0] || null)
    }
}

const isSelected = item => {
    return selected.value && item?.external_userid === selected.value?.external_userid
}

const change = (item) => {
    if (isSelected(item)) {
        return
    }
    item && (item.is_read = 1)
    selected.value = item
    emit("change", item)
}
</script>

<style scoped lang="less">
.contact-box {
    .right {
        .userinfo {
            display: flex;
            align-items: center;
            overflow: hidden;
            width: 100%;

            .user-info-left {
                display: flex;
                align-items: center;
                max-width: calc(100% - 15px);

                .user-name {
                    max-width: calc(100% - 15px);
                }
                .corp-name {
                    max-width: calc(100% - 15px);
                }
            }
        }
    }

    .cst-tag-list {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        :deep(.ant-tag) {
            color: #000000a6;
            font-size: 10px;
        }
    }
}
</style>
