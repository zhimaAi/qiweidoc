<template>
    <ZmScroll
        :finished="finished"
        :loading="loading"
        @load="loadData"
        class="group-box">
        <div v-for="(item,i) in list"
             :key="i"
             @click="change(item)"
             :class="['group-item',{active: isSelected(item)}]">
            <div class="left">
                <img v-if="item.avatar" class="avatar" :src="item.avatar"/>
                <img v-else class="avatar" src="@/assets/default-group-avatar.png"/>
            </div>
            <div class="right">
                <div class="top" :title="item.name">
                    <div class="user-name">{{ item.name || '未命名群聊' }}</div>
                </div>
            </div>
        </div>
        <a-empty v-if="finished && list.length < 1" description="暂无数据" style="margin-top: 60px;"/>
    </ZmScroll>
</template>

<script setup>
import {onMounted, ref, reactive, watch} from 'vue';
import ZmScroll from "@/components/zmScroll.vue";
import {groupsList} from "@/api/company";

const props = defineProps({
    default: {
        type: Object,
    },
    filterData: {
        type: Object,
    },
})
const emit = defineEmits(['change', 'totalReport'])
const loading = ref(true)
const finished = ref(false)
const selected = ref(null)
const list = ref([])
const chatKeyMap = ref({})
const pagination = reactive({
    current: 1,
    pageSize: 20,
    total: 0,
})

const init = () => {
    pagination.current = 1
    pagination.total = 0
    finished.value = false
    loading.value = true
    list.value = []
    loadData()
}

defineExpose({
    init,
})

const loadData = () => {
    loading.value = true
    let params = {
        has_conversation: 1,
        page: pagination.current,
        size: pagination.pageSize,
    }
    if (props.filterData?.keyword) {
        params.keyword = props.filterData.keyword
    }
    groupsList(params).then(res => {
        let data = res.data || {}
        let {items, total} = data
        total = Number(total)
        if (!items || !items?.length || list.value.length === total) {
            finished.value = true
        }
        for (let item of items) {
            if (!chatKeyMap.value[item.chat_id]) {
                list.value.push(item)
                chatKeyMap.value[item.chat_id] = true
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

const selectedHandle = async () => {
    if (props.default && props.default.group_chat_id) {
        const find = list.value.find(item => item.chat_id === props.default.group_chat_id)
        if (find) {
            change(find)
        } else {
            let item
            try {
                const res = await groupsList({
                    page: 1,
                    size: 1,
                    has_conversation: 1,
                    chat_id: props.default.group_chat_id,
                })
                if (res?.data?.items && res?.data?.items.length) {
                    item = res.data.items[0]
                    list.value.unshift(item)
                    chatKeyMap.value[item.chat_id] = true
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
    if (!selected.value) {
        return false
    }
    return item?.chat_id === selected.value.chat_id
}

const change = (item) => {
    if (selected.value && isSelected(item)) {
        return
    }
    selected.value = item
    emit("change", item)
}
</script>

<style scoped lang="less">
.group-box {
    .group-item {
        cursor: pointer;
        width: 100%;
        padding: 8px 16px;
        display: flex;
        align-items: center;

        &.active {
            background: #E5EFFF;
            border-left: 2px solid #2475FC;
            padding-left: 14px;

            .right .top .user-name {
                color: #2475FC;
            }
        }

        &:not(.active):hover {
            background: #F2F4F7;
        }

        .left {
            flex-shrink: 0;
            width: 32px;
            padding-top: 4px;

            .avatar {
                width: 24px;
                height: 24px;
                border-radius: 6px;
            }
        }

        .right {
            flex-shrink: 0;
            width: calc(100% - 40px);

            .top {
                display: flex;
                align-items: center;

                .user-name {
                    font-size: 12px;
                    font-weight: 400;
                    color: #262626;
                    max-width: 95%;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
            }

            .wx-info {
                font-size: 12px;
                color: #595959;
            }

            .bottom {
                //margin-top: 4px;

                .last-msg label {
                    font-size: 12px;
                    font-weight: 400;
                    color: #8C8C8C;
                }

                .last-msg span {
                    font-size: 12px;
                    font-weight: 400;
                    color: #8C8C8C;
                }
            }
        }
    }
}
</style>
