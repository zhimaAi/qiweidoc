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
                <div class="flex-between" :title="item.external_name+'@'+(item.corp_name || '微信')">
                    <div class="userinfo">
                        <div class="user-info-left">
                            <span v-if="item.is_new_user == 1" class="is-new-tag">新</span>
                            <div class="user-name">
                                {{ item.external_name || '...' }}
                            </div>
                            <div :class="['corp-name ml4',{'is-wx': ! item.corp_name ||  item.corp_name === '微信'}]">
                                @{{ item.corp_name || '微信' }}
                            </div>
                        </div>
                        <div class="collection" v-if="item.is_collect">
                            <img class="chat-collection-icon" :src="require('@/assets/svg/is_collect.svg')" @click="onCollection(item)" />
                        </div>
                    </div>
                </div>
                <div class="flex-between">
                    <div class="last-msg">
                        <img class="icon-14" src="@/assets/image/icon-message.png"/>
                        <span class="ml4">{{ item.last_msg_date }}</span>
                    </div>
                </div>
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
            justify-content: space-between;
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
}
.collection {
    width: 12px;
    margin-right: 8px;
}
</style>
