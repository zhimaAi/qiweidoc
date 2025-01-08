<template>
    <div>
        <MainNavbar :title="[
           {name: '单聊统计', route: '/module/chat-statistic-single/index'},
           {name: '员工聊天明细'}
        ]"/>
        <div class="zm-main-content">
            <div class="header">
                <div class="left">
                    <div class="item zm-user-info">
                        <img class="avatar" :src="defaultAvatar"/>
                        <span class="name">{{ route.query.staff_name }}</span>
                    </div>
                    <div class="hr"></div>
                    <div class="item">统计时间：{{ statDateFormat() }}</div>
                </div>
                <div class="right filter-box">
                    <div class="filter-item ml16">
                        <a-input-search
                            v-model:value="filterData.keyword"
                            allowClear
                            placeholder="请输入客户名称进行搜索"
                            @search="search"
                            style="width: 240px;"/>
                    </div>
                </div>
            </div>
            <a-tabs v-model:activeKey="type" @change="init" class="mt8">
                <a-tab-pane v-for="tab in tabs" :key="tab.key">
                    <template #tab>
                        {{ tab.title }}<span v-if="tab.value > 0">（{{ tab.value }}）</span>
                    </template>
                </a-tab-pane>
            </a-tabs>
            <div class="table-box">
                <a-table :loading="loading"
                         :data-source="list"
                         :columns="columns"
                         :pagination="pagination"
                         :scroll="{x: 1200}"
                         @change="tableChange"
                >
                    <template #headerCell="{column}">
                        <template v-if="'rate_avg' === column.dataIndex">
                            <a-tooltip
                                :title="`${msgReplySec}分钟内回复的总轮次/单聊的总轮次之和，这里轮次是客户发起，所属员工回复算一轮`">
                                {{ msgReplySec }}分钟回复率
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                    </template>
                    <template #bodyCell="{column, text, record}">
                        <template v-if="'staff_name' === column.dataIndex">
                            <div class="zm-user-info">
                                <img class="avatar" :src="record?.external_info?.avatar || defaultAvatar"/>
                                <div class="name">{{ record?.external_info?.external_name }}</div>
                            </div>
                        </template>
                        <template v-else-if="'reply_status' === column.dataIndex">
                            <span v-if="record.reply_status == -1">--</span>
                            <div class="reply-status" v-else-if="record.reply_status==1">
                                <CheckCircleFilled/>
                                <span>已回复</span>
                            </div>
                            <div class="reply-status no" v-else>
                                <ExclamationCircleFilled/>
                                <span>未回复</span>
                            </div>
                        </template>
                        <template v-else-if="'noreply_content' === column.dataIndex">
                            <MessageRender :message-info="record.msg_info"/>
                        </template>
                        <template v-else-if="'operate' === column.dataIndex">
                            <a-button type="link"
                                      style="padding: 0;"
                                      @click="linkChat(record)">明细
                            </a-button>
                        </template>
                    </template>
                </a-table>
            </div>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref, reactive} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import dayjs from "dayjs";
import {message} from 'ant-design-vue';
import {ExclamationCircleFilled, CheckCircleFilled, QuestionCircleOutlined} from '@ant-design/icons-vue';
import MainNavbar from "@/components/mainNavbar.vue";
import {getStatDetail} from "@/api/chat_statistic_single";
import {computedRate, secondToDate, objectToQueryString} from "@/utils/tools";
import MessageRender from "@/components/session-message/messageRender.vue";

const defaultAvatar = require('@/assets/default-avatar.png');
const route = useRoute()
const router = useRouter()
const loading = ref(false)
const type = ref('all')
const msgReplySec = ref('')
const list = ref([])
const filterData = reactive({
    keyword: "",
    order: "",
})
const pagination = reactive({
    current: 1,
    page: 1,
    pageSize: 10,
    total: 0,
    pageSizeOptions: ['10', '20', '50', '100'],
    showQuickJumper: true,
    showSizeChanger: true,
})

const columnsMap = {
    staff_name: {
        title: "客户信息",
        dataIndex: "staff_name",
        width: 180,
        fixed: 'left',
    },
    add_time: {
        title: "添加时间",
        dataIndex: "add_time",
        width: 150,
    },
    promoter_type_text: {
        title: "发起人",
        dataIndex: "promoter_type_text",
        width: 100,
    },
    staff_msg_no_day: {
        title: "员工发送消息数",
        dataIndex: "staff_msg_no_day",
        width: 140,
        sorter: true,
    },
    cst_msg_no_day: {
        title: "客户发送消息数",
        dataIndex: "cst_msg_no_day",
        width: 140,
        sorter: true,
    },
    reply_status: {
        title: "回复状态",
        dataIndex: "reply_status",
        width: 116,
    },
    noreply_content: {
        title: "未回复消息内容",
        dataIndex: "noreply_content",
        width: 200,
    },
    first_recover_time: {
        title: "首次回复时长",
        dataIndex: "first_recover_time",
        width: 140,
    },
    rate_avg: {
        title: "分钟回复率",
        dataIndex: "rate_avg",
        width: 140,
    },
    send_msg_ime: {
        title: "发送时间",
        dataIndex: "send_msg_ime",
        width: 140,
    },
    operate: {
        title: "操作",
        dataIndex: "operate",
        width: 60,
        fixed: 'right',
    }
}

const allColumnMap = {
    time_st_avg: ['staff_name', 'add_time', 'first_recover_time', 'staff_msg_no_day', 'cst_msg_no_day', 'rate_avg', 'reply_status', 'promoter_type_text', 'operate'],
    rate_avg: ['staff_name', 'add_time', 'rate_avg', 'staff_msg_no_day', 'cst_msg_no_day', 'first_recover_time', 'reply_status', 'promoter_type_text', 'operate'],
}
const normalColumns = ['staff_name', 'add_time', 'staff_msg_no_day', 'cst_msg_no_day', 'rate_avg', 'first_recover_time', 'reply_status', 'promoter_type_text', 'operate'];
const promoterColumns = ['staff_name', 'add_time', 'promoter_type_text', 'staff_msg_no_day', 'cst_msg_no_day', 'rate_avg', 'first_recover_time', 'reply_status', 'operate']
const validPromoterColumns = ['staff_name', 'add_time', 'promoter_type_text', 'staff_msg_no_day', 'cst_msg_no_day', 'reply_status', 'operate'];
const unReplyColumns = ['staff_name', 'add_time', 'promoter_type_text', 'noreply_content', 'send_msg_ime', 'operate']
const columns = ref([])
const tabKeyIsAllIndex = 1
const tabs = ref([
    {key: 'is_new_user', title: '今日新增', value: '', columns: normalColumns},
    {key: 'all', title: '单聊数', value: '', columns: normalColumns},
    {key: 'promoter_stf_no', title: '主动沟通', value: '', columns: promoterColumns,},
    {key: 'promoter_cst_no', title: '被动沟通', value: '', columns: promoterColumns},
    {key: 'promoter_stf_no_valid', title: '主动有效沟通', value: '', columns: validPromoterColumns},
    {key: 'promoter_cst_no_valid', title: '被动有效沟通', value: '', columns: validPromoterColumns},
    {key: 'no_replay', title: '未回复', value: '', columns: unReplyColumns},
])

onMounted(() => {
    msgReplySec.value = route.query.msg_reply_sec
    type.value = route.query.type
    if (['rate_avg', 'time_st_avg'].includes(type.value)) {
        tabs.value[tabKeyIsAllIndex].key = type.value
        tabs.value[tabKeyIsAllIndex].columns = allColumnMap[type.value]
    }
    init()
})

function init() {
    columnsInit()
    search()
    loadOtherTotal()
}

function search() {
    pagination.current = 1
    pagination.total = 0
    list.value = []
    loadData()
}

function columnsInit() {
    const tab = tabs.value.find(i => i.key === type.value)
    columns.value = []
    tab.columns.map(i => {
        columns.value.push(columnsMap[i])
    })
}

function statDateFormat() {
    return dayjs(route.query.stat_time * 1000).format("YYYY-MM-DD")
}

function getFilterParams() {
    let params = {
        page: pagination.current,
        size: pagination.pageSize,
        staff_userid: route.query.staff_userid,
        stat_time: route.query.stat_time
    }
    if (type.value !== 'all') {
        params.type = type.value
    }
    filterData.keyword = filterData.keyword.trim()
    if (filterData.keyword) {
        params.keyword = filterData.keyword
    }
    if (filterData.order) {
        params.order = filterData.order
    }
    return params
}

function loadData() {
    loading.value = true
    let params = getFilterParams()
    getStatDetail(params).then(res => {
        let _list = res.data.list || []
        _list.map(item => {
            item.add_time = dayjs(item?.external_info?.add_time).format("YY-MM-DD HH:mm")
            item.promoter_type_text = item?.promoter_type == 1 ? '客户' : '员工'
            item.first_recover_time = secondToDate(item.first_recover_time)
            item.rate_avg = computedRate(item.rate_avg, 1)
            if (item?.msg_info && item?.msg_info?.msg_time) {
                item.send_msg_ime = item.msg_info.msg_time.substring(2, 16)
            }
        })
        list.value = _list
        pagination.total = Number(res.data.count)
    }).finally(() => {
        loading.value = false
    })
}

function loadOtherTotal() {
    let params = getFilterParams()
    params.page = 1
    params.size = 1
    tabs.value.map(item => {
        if (item.key !== 'all') {
            params.type = item.key
        } else {
            delete params.type
        }
        getStatDetail(params).then(res => {
            item.value = Number(res?.data?.count || 0)
        })
    })
}

function linkChat(record) {
    const query = {
        sender: record.staff_user_id,
        receiver: record.external_userid,
        sender_type: 'Staff',
        receiver_type: 'Customer',
        conversation_id: record.conversation_id
    }
    window.open(`/#/sessionArchive/index?${objectToQueryString(query)}`)
}

function tableChange(p, _, sorter) {
    if (sorter.field && sorter.order) {
        columns.value.map(item => {
            if (item.dataIndex === sorter.field) {
                item.sortOrder = sorter.order
            } else {
                item.sortOrder = false
            }
        })
        filterData.order = `${sorter.field} ${sorter.order == 'ascend' ? 'ASC' : 'DESC'}`
    } else {
        columns.value.map(item => item.sortOrder = false)
        filterData.order = ""
    }
    Object.assign(pagination, p)
    loadData()
}
</script>

<style scoped lang="less">
.zm-main-content {
    min-height: 86vh;
}

.header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    .left {
        display: flex;
        align-items: center;
        font-size: 14px;
        font-weight: 400;
        color: #595959;

        .item {
            display: flex;
            align-items: center;
        }

        .hr {
            background: #D9D9D9;
            width: 1px;
            height: 12px;
            margin: 0 16px;
        }
    }
}

.reply-status {
    display: inline-block;
    font-size: 14px;
    font-weight: 600;
    color: #21A665;
    padding: 2px 6px;
    border-radius: 2px;
    background: #E8FCF3;

    span {
        margin-left: 4px;
    }

    &.no {
        background: #FAEBE6;
        color: #ED744A;
    }
}

.filter-box {
    display: flex;
    flex-wrap: wrap;

    .filter-item:last-child {
        margin-right: 0;
    }

    .filter-item {
        display: flex;
        align-items: center;

        .item-label {
            font-size: 14px;
            font-weight: 400;
            color: #262626;
            width: 70px;
            flex-shrink: 0;
        }

        .item-body {
            flex: 1;
        }
    }
}

.msg-content-box {
    display: flex;
    align-items: flex-start;
}

.last-sender-info {
    font-size: 14px;
    font-weight: 400;
    color: rgba(0, 0, 0, 0.65);
    display: flex;
    align-items: center;

    .name {
        white-space: nowrap;
    }

    .avatar {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        border-radius: 2px;
        margin-right: 8px;
    }
}

.session-content {
    height: 40px;
    overflow: hidden;
}

.table-box {
    :deep(.message-box) {
        border: none;
        padding: 0;

        &::after {
            display: none;
        }
    }
}
</style>
