<template>
    <MainLayout title="会话搜索">
        <div class="zm-main-content">
            <a-alert show-icon message="功能说明：支持通过关键词、单聊/群聊、会话时间等搜索条件查询会话内容"></a-alert>
            <div class="mt16">
                <a-input-search
                    v-model:value="filterData.keyword"
                    style="width: 600px;"
                    size="large"
                    placeholder="请输入会话内容关键词搜索"
                    allowClear
                    @search="search">
                    <template #enterButton>
                        <a-button :icon="h(SearchOutlined)" type="primary">搜 索</a-button>
                    </template>
                </a-input-search>
            </div>
            <div class="zm-filter-box">
                <div class="zm-filter-item">
                    <span class="zm-filter-label">会话类型：</span>
                    <div>
                        <a-select v-model:value="filterData.session_type"
                                  style="width: 220px;"
                                  @change="search"
                                  placeholder="请选择会话类型"
                                  allowClear>
                            <a-select-option :value="1">单聊</a-select-option>
                            <a-select-option :value="2">群聊</a-select-option>
                        </a-select>
                    </div>
                </div>
                <div class="zm-filter-item">
                    <span class="zm-filter-label">发送时间：</span>
                    <div>
                        <a-range-picker
                            v-model:value="filterData.dates"
                            style="width: 220px;"
                            @change="filterDateChange"
                            :disabled-date="disabledDate"/>
                    </div>
                </div>
                <div class="zm-filter-item">
                    <span class="zm-filter-label">发送人：</span>
                    <div>
                        <a-input-group compact>
                            <a-select
                                v-model:value="filterData.from_type"
                                style="width: 80px;"
                                @change="search"
                                placeholder="请选择会话类型"
                            >
                                <a-select-option :value="0">全部</a-select-option>
                                <a-select-option :value="1">客户</a-select-option>
                                <a-select-option :value="2">员工</a-select-option>
                            </a-select>
                            <a-input v-model:value="filterData.from"
                                     style="width: 220px;"
                                     placeholder="选择输入发送人名称"/>
                        </a-input-group>
                    </div>
                </div>
                <!--                <div class="zm-filter-item">-->
                <!--                    <span class="zm-filter-label">发送群聊：</span>-->
                <!--                    <div>-->
                <!--                        <a-input v-model:value="filterData.group"-->
                <!--                                 style="width: 220px;"-->
                <!--                                 placeholder="选择输入群聊名称"/>-->
                <!--                    </div>-->
                <!--                </div>-->
            </div>
        </div>
        <div class="zm-main-content mt16" style="min-height: 60vh;">
            <a-table
                v-if="list.length > 0"
                :loading="loading"
                :data-source="list"
                :columns="columns"
                :pagination="pagination"
                @resizeColumn="handleResizeColumn"
                @change="tableChange"
            >
                <template #bodyCell="{column, text, record}">
                    <template v-if="column.dataIndex === 'msg_content'">
                        <div class="msg-content-box" v-html="record.msg_content"></div>
                    </template>
                    <template v-else-if="column.dataIndex === 'sender_info'">
                        <div class="zm-user-info">
                            <img class="avatar" :src="record?.sender_info?.avatar || defaultAvatar"/>
                            <span class="name">{{ record?.sender_info?.name }}</span>
                        </div>
                    </template>
                    <template v-else-if="column.dataIndex === 'receiver_info'">
                        <div v-if="record?.receiver_info" class="zm-user-info">
                            <img class="avatar" :src="record?.receiver_info?.avatar || defaultAvatar"/>
                            <span class="name">{{ record?.receiver_info?.name }}</span>
                        </div>
                    </template>
                    <template v-else-if="column.dataIndex === 'group_info'">
                        <div v-if="record.group_info">
                            {{ record.group_info?.group_name || '未命名群聊' }}
                        </div>
                    </template>
                    <template v-else-if="column.dataIndex === 'id'">
                        <a @click="openDetail(record)">查看明细</a>
                    </template>
                </template>
            </a-table>
            <a-empty v-else
                     style="margin-top: 88px;"
                     :description="filterData.keyword ? '暂无匹配数据' : '输入内容后搜索查看'">
                <template #image>
                    <img src="@/assets/image/data-empty.png"/>
                </template>
            </a-empty>
        </div>
    </MainLayout>
</template>

<script setup>
import {h, reactive, ref, onMounted} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import dayjs from 'dayjs';
import {SearchOutlined} from '@ant-design/icons-vue';
import {message} from 'ant-design-vue';
import MainLayout from "@/components/mainLayout.vue";
import {searchSession} from "@/api/session";
import {getRegex, sessionRole2Text} from "@/utils/tools";

const router = useRouter()
const route = useRoute()
const defaultAvatar = require('@/assets/default-avatar.png');
const columns = [
    {
        title: "会话内容",
        dataIndex: "msg_content",
        width: 260,
    },
    {
        title: "会话类型",
        dataIndex: "session_type_text",
        width: 160,
    },
    {
        title: "群聊名称",
        dataIndex: "group_info",
        width: 160,
    },
    {
        title: "发送人",
        dataIndex: "sender_info",
        width: 160,
    },
    {
        title: "接收人",
        dataIndex: "receiver_info",
        width: 160,
    },
    {
        title: "发送时间",
        dataIndex: "msg_time",
        width: 160,
    },
    {
        title: "操作",
        dataIndex: "id",
        width: 120,
    }
]
const handleResizeColumn = (w, col) => {
    col.width = w;
}
const loading = ref(false)
const list = ref([])
const pagination = reactive({
    pageSize: 50,
    current: 1,
    total: 0
})
const filterData = reactive({
    keyword: '',
    session_type: undefined,
    from_type: 0,
    dates: [],
    from: '',
})

const search = () => {
    list.value = []
    pagination.current = 1
    pagination.total = 0
    loadData()
}

const loadData = (type) => {
    loading.value = true
    let params = {
        page: pagination.current,
        size: pagination.pageSize
    }
    filterData.keyword = filterData.keyword.trim()
    filterData.from = filterData.from.trim()
    if (!filterData.keyword && !type) {
        message.warn('请输入搜索内容')
        return
    }
    params.keyword = filterData.keyword
    if (filterData.session_type) {
        params.session_type = filterData.session_type
    }
    if (filterData.from) {
        params.from = filterData.from
        if (filterData.from_type > 0) {
            params.from_type = filterData.from_type
        }
    }
    if (filterData.dates.length) {
        params.start_time = filterData.dates[0].format('YYYY-MM-DD 00:00:00')
        params.stop_time = filterData.dates[1].format('YYYY-MM-DD 23:59:59')
    }
    if (type && type == 'init') {
        params.init = true
    }
    searchSession(params).then(res => {
        let data = res.data || {}
        let {items, total} = data
        items.map(item => {
            item.msg_content = item.msg_content.replace(getRegex(filterData.keyword), `<span class="search-keyword-text">${filterData.keyword}</span>`)
            item.session_type_text = item.group_info ? '群聊' : '单聊'
            item.msg_time = dayjs(item.msg_time).format('YY/MM/DD HH:mm')
            item.from_role = sessionRole2Text(item.from_role)
        })
        list.value = items
        pagination.total = Number(total)
    }).finally(() => {
        loading.value = false
    })
}

const tableChange = p => {
    pagination.current = p.current
    pagination.pageSize = p.pageSize
    search()
}

const filterDateChange = () => {
    if (filterData.dates.length > 0) {
        let time = filterData.dates[1] - filterData.dates[0]
        if ((time / 86400000) > 30) {
            filterData.dates = []
            message.warning("发送时间跨度不得超过30天")
        }
    }
    search()
}

const openDetail = record => {
    let params = {}
    if (record.group_info) {
        params.group_chat_id = record.roomid
    } else {
        params.conversation_id = record.conversation_id
        params.sender = record.from
        params.receiver = record?.to_list[0] || ''
        params.sender_type = record.from_role
        params.receiver_type = record.to_role
    }
    let href = router.resolve({
        path: '/sessionArchive/index',
        query: params
    }).href
    window.open(href)
}

const disabledDate = current => {
    return current && current > dayjs().endOf('day')
}

const onInit = () => {
    list.value = []
    pagination.current = 1
    pagination.total = 0
    loadData('init')
}

onMounted(() => {
    // 为了权限验证
    onInit()
})
</script>

<style scoped lang="less">
.zm-filter-box {
    flex-wrap: wrap;

    .zm-filter-item {
        margin-top: 12px;
        margin-right: 12px;
    }
}

.zm-main-content {
    :deep(.ant-empty-image) {
        height: 200px;
        margin-bottom: 0;
    }

    :deep(.msg-content-box) {
        word-break: break-all;

        .search-keyword-text {
            color: red;
        }
    }
}
</style>
