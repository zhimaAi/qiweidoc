<template>
    <ZmScroll
        :finished="finished"
        :loading="loading"
        :immediate="false"
        @load="loadData"
        class="contact-box">
        <!---客户联系员工-->
        <div v-for="(item,i) in list"
             @click="change(item)"
             :class="['contact-item', {active: isSelected(item)}]"
             :key="i">
            <div class="left">
                <img v-if="item.avatar" class="avatar" :src="item.avatar"/>
                <img v-else class="avatar" src="@/assets/default-avatar.png"/>
            </div>
            <div class="right">
                <div class="flex-between" :title="item.name || ''">
                    <div class="userinfo">
                        <div class="user-name">{{ item.name || '...' }}</div>
                    </div>
                </div>
                <div class="wx-info" :title="item.name">
                    <span>部门：</span>
                    <span>{{ item.department_name || '--' }}</span>
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
import {onMounted, ref, reactive} from 'vue';
import dayjs from 'dayjs';
import LoadingBox from "@/components/loadingBox.vue";
import ZmScroll from "@/components/zmScroll.vue";
import {cstSessions} from "@/api/session";
import '@/common/contacts.less';
import {formatTime} from "@/utils/tools";

const props = defineProps({
    default: {
        type: Object,
    },
    cstInfo: {
        type: Object,
    },
    filterData: {
        type: Object,
    },
})
const emit = defineEmits(['change', 'totalReport'])

const loading = ref(false)
const finished = ref(false)
const selected = ref(null)
const list = ref([])
const useridMap = ref({})
const pagination = reactive({
    current: 1,
    pageSize: 20,
    total: 0,
})

onMounted(() => {
    init()
})

// watch(() => props.cstInfo, (n, o) => {
//     init()
// }, {deep: true})

const init = () => {
    pagination.current = 1
    pagination.total = 0
    finished.value = false
    loading.value = false
    list.value = []
    loadData()
}

const loadData = () => {
    if (!props.cstInfo || !props.cstInfo?.external_userid) {
        finished.value = true
        initReport()
        return
    }
    loading.value = true
    let params = {
        page: pagination.current,
        size: pagination.pageSize,
        external_userid: props.cstInfo?.external_userid
    }
    if (props.filterData?.keyword) {
        params.keyword = props.filterData.keyword
    }
    cstSessions(params).then(res => {
        let data = res.data || {}
        let {items, total} = data
        total = Number(total)
        if (!items || !items?.length || list.value.length === total) {
            finished.value = true
        }
        for (let item of items) {
            if (!useridMap.value[item.userid]) {
                if (item?.last_msg_time) {
                    item.last_msg_date = formatTime(dayjs(item.last_msg_time).unix())
                } else {
                    item.last_msg_date = '~'
                }
                list.value.push(item)
                useridMap.value[item.userid] = true
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
    if (props.default && props.default.conversation_id) {
        const find = list.value.find(item => item.id === props.default.conversation_id)
        if (find) {
            change(find)
        } else {
            let item
            try {
                const res = await cstSessions({
                    page: 1,
                    size: 1,
                    conversation_id: props.default.conversation_id,
                })
                if (res?.data?.items && res?.data?.items.length) {
                    item = res.data.items[0]
                    list.value.unshift(item)
                    useridMap.value[item.userid] = true
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
    return selected.value && item?.userid === selected.value?.userid
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
    height: calc(100% - 42px);
}
</style>
