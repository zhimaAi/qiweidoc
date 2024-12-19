<template>
    <ZmScroll
        @load="loadData"
        :finished="finished"
        :loading="loading"
        class="customer-box">
        <div v-for="(item,i) in list"
             @click="change(item)"
             :class="['customer-item',{active: isSelected(item)}]"
             :key="i">
            <div class="left">
                <a-tooltip placement="top" :title="item.external_name+'@'+(item.corp_name || '微信')">
                    <img v-if="item.avatar" class="avatar" :src="item.avatar"/>
                    <img v-else class="avatar" src="@/assets/default-avatar.png"/>
                </a-tooltip>
            </div>
            <div class="right">
                <div class="user-info" :title="item.external_name+'@'+(item.corp_name || '微信')">
                    <div class="user-name">{{ item.external_name }}</div>
                    <div :class="['corp-name',{'is-wx': ! item.corp_name ||  item.corp_name === '微信'}]">
                        @{{ item.corp_name || '微信' }}
                    </div>
                </div>
            </div>
        </div>
        <a-empty v-if="finished && list.length < 1" description="暂无数据" style="margin-top: 60px;"/>
    </ZmScroll>
</template>

<script setup>
import {onMounted, ref, reactive} from 'vue';
import ZmScroll from "@/components/zmScroll.vue";
import LoadingBox from "@/components/loadingBox.vue";
import {sessionCustomer} from "@/api/session";

const emit = defineEmits(['change', 'totalReport'])
const props = defineProps({
    default: {
        type: Object,
    },
    filterData: {
        type: Object
    }
})
const list = ref([])
const useridMap = ref({})
const loading = ref(false)
const finished = ref(false)
const selected = ref(null)

const pagination = reactive({
    current: 1,
    pageSize: 20,
    total: 0,
})

const init = () => {
    pagination.current = 1
    pagination.total = 0
    finished.value = false
    loading.value = false
    list.value = []
    useridMap.value = {}
    loadData()
}

const loadData = () => {
    loading.value = true
    let params = {
        has_conversation: 1,
        page: pagination.current,
        size: pagination.pageSize,
        chat_status: props.type,
    }
    if (props.filterData?.keyword) {
        params.keyword = props.filterData.keyword
    }
    sessionCustomer(params).then(res => {
        let data = res.data || {}
        let {items, total} = data
        total = Number(total)
        if (!items || !items?.length || list.value.length === total) {
            finished.value = true
        }
        for (let item of items) {
            if (!useridMap.value[item.external_userid]) {
                list.value.push(item)
                useridMap.value[item.external_userid] = true
            }
        }
        if (pagination.current == 1) {
            emit('totalReport', total)
            selectedHandle()
        }
        pagination.total = total
        pagination.current += 1
        loading.value = false
    }).catch(() => {
        loading.value = false
        finished.value = true
    })
}

const change = item => {
    if (selected.value && isSelected(item)) {
        return
    }
    selected.value = item
    emit("change", item)
}

const isSelected = item => {
    if (!selected.value) {
        return false
    }
    return item?.external_userid === selected.value?.external_userid
}

const selectedHandle = async () => {
    if (props.default && props.default.sender && props.default.sender_type === 'Customer') {
        const find = list.value.find(item => item.external_userid === props.default.sender)
        if (find) {
            change(find)
        } else {
            let item
            try {
                const res = sessionCustomer({
                    page: 1,
                    size: 1,
                    has_conversation: 1,
                    external_userid: props.default.sender,
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
    } else if (list.value.length) {
        change(list.value[0])
    }
}
</script>

<style scoped lang="less">
.customer-box {
    position: relative;

    :deep(.customer-item .ant-tooltip.ant-tooltip-placement-top) {
        top: -40px !important;
    }

    .customer-item {
        cursor: pointer;
        padding: 8px 16px;
        display: flex;
        align-items: center;
        position: relative;

        &.active {
            background: #E5EFFF;
            border-left: 2px solid #2475FC;

            .right .user-name {
                color: #2475FC;
            }
        }

        &:not(.active):hover {
            background: #F2F4F7;
        }

        .left {
            flex-shrink: 0;
            width: 32px;

            .avatar {
                width: 24px;
                height: 24px;
                border-radius: 6px;
                margin-right: 8px;
            }
        }

        .right {
            width: calc(100% - 32px);

            .wx-info {
                font-size: 12px;
                color: #595959;
            }

            .user-info {
                display: flex;
                align-items: center;
            }

            .user-name {
                font-weight: 400;
                font-size: 12px;
                color: #262626;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                max-width: 95%;
            }

            .corp-name.is-wx {
                color: #07C160;
            }

            .corp-name {
                font-size: 12px;
                font-weight: 400;
                color: #E07B00;
                max-width: 95%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        }
    }
}
</style>
