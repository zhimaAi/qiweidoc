<template>
    <div class="staff-box">
        <div class="search-box">
            <a-input-search
                placeholder="请输入员工昵称搜索"
                allowClear
                size="small"
                v-model:value="filterData.keyword"
                @search="search"/>
        </div>
        <ZmScroll class="staff-list"
                  @load="loadData"
                  :finished="finished"
                  :loading="loading">
            <div v-for="(item,i) in list"
                 :key="i"
                 @click="change(item)"
                 :class="['staff-item',{active: isSelected(item)}]">
                <div class="left">
                    <a-popover overlayClassName="zm-tooltip-popover" placement="top" :content="item.name">
                        <img class="avatar" :src="item.avatar || defaultAvatar"/>
                    </a-popover>
                </div>
                <div class="right">
                    <div class="staff-name" :title="item.name">
                        {{ item.name }}<span v-if="showChatNo">（{{ item.chat_no || 0 }}）</span>
                    </div>
                </div>
            </div>
            <a-empty v-if="finished && list.length < 1" description="暂无数据" style="margin-top: 60px;"/>
        </ZmScroll>
    </div>
</template>

<script setup>
import {reactive, ref, onMounted, watch} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import {useStore} from 'vuex';
import ZmScroll from "@/components/zmScroll.vue";
import {staffList} from "@/api/company";

const route = useRoute()
const router = useRouter()
const store = useStore()
const defaultAvatar = require('@/assets/default-avatar.png')

const props = defineProps({
    type: {
        type: Number,// 1在会话存档中 2历史
        required: true
    },
    showChatNo: {
        type: Boolean,
        default: false
    },
    default: {
        type: Object,
    }
})

const emit = defineEmits(['change', 'totalReport'])
const list = ref([])
const useridMap = ref({})
const loading = ref(false)
const finished = ref(false)
const selected = ref(null)

const filterData = reactive({
    keyword: '',
})

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
}

const loadData = () => {
    if (loading.value) {
        return
    }
    loading.value = true
    let params = {
        page: pagination.current,
        size: pagination.pageSize,
        chat_status: props.type,
    }
    if (params.chat_status === 1) { // 会话存档中
        params.enable_archive = 1
    }
    if (params.chat_status === 2) { // 历史员工
        params.has_conversation = 1
    }
    filterData.keyword = filterData.keyword.trim()
    if (filterData.keyword) {
        params.keyword = filterData.keyword
    }
    staffList(params).then(res => {
        let data = res.data || {}
        let {items, total} = data
        total = Number(total)

        // 添加新数据到列表
        let addedCount = 0
        if (items && items.length) {
            for (let item of items) {
                if (!useridMap.value[item.userid]) {
                    list.value.push(item)
                    useridMap.value[item.userid] = true
                    addedCount++
                }
            }
        }

        // 判断是否已加载完所有数据
        if (!items || !items.length || addedCount === 0 || list.value.length >= total) {
            finished.value = true
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

// 监听 props.default 的变化
watch(() => props.default, (newDefault) => {
    if (newDefault && list.value.length > 0) {
        selectedHandle()
    }
}, { deep: true })

const selectedHandle = () => {
    if (props.default && props.default?.chat_status === props.type && props.default.userid) {
        const find = list.value.find(item => item.userid === props.default.userid)
        if (find) {
            change(find)
        } else {
            useridMap.value[props.default.userid] = true
            list.value.unshift(props.default)
        }
    } else {
        change(list.value[0] || null)
    }
}

const isSelected = item => {
    return selected.value && selected.value.userid === item?.userid
}

const change = (item) => {
    if (item && isSelected(item)) {
        return
    }
    selected.value = item
    emit("change", item)
}

onMounted(() => {
    // ZmScroll组件会自动触发初始加载，无需手动调用loadData
})

const search = () => {
    init()
    loadData()
}
</script>

<style scoped lang="less">
.search-box {
    padding: 9px 16px;
}

.staff-list {
    height: calc(100% - 42px);
}

.staff-box {
    .staff-item.active {
        background: #E5EFFF;
        border-left: 2px solid #2475FC;

        .right {
            .staff-name {
                color: #2475FC;
            }
        }
    }

    .staff-item:not(.active):hover {
        background: #F2F4F7;
    }

    .staff-item {
        cursor: pointer;
        width: 100%;
        padding: 8px 16px;
        display: flex;
        align-items: center;
        margin-bottom: 2px;
        //border-bottom: 1px solid rgba(0, 0, 0, 0.04);

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

            .staff-name {
                font-size: 12px;
                font-weight: 400;
                color: #262626;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        }
    }
}
</style>
