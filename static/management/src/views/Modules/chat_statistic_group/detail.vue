<template>
    <div>
        <MainNavbar :title="[
            {name: '群聊统计', route: '/module/chat-statistic-group/index'},
            {name: '员工群聊明细'}
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
                            placeholder="请输入群聊名称进行搜索"
                            @change="search"
                            style="width: 240px;"/>
                    </div>
                </div>
            </div>
            <a-tabs v-model:activeKey="activeTab" @change="tabChange">
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
                        <template v-else-if="'at_rate_avg' === column.dataIndex">
                            @后{{atMsgReplySec}}分钟回复率
                        </template>
                    </template>

                    <template #bodyCell="{column, text,record}">
                        <template v-if="'reply_status' === column.dataIndex">
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
                        <template v-else-if="'operate' === column.dataIndex">
                            <a @click="linkChat(record)">明细</a>
                        </template>
                    </template>
                </a-table>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref, reactive, onMounted} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import dayjs from "dayjs";
import {message} from 'ant-design-vue';
import {ExclamationCircleFilled, CheckCircleFilled, QuestionCircleOutlined} from '@ant-design/icons-vue';
import MainNavbar from "@/components/mainNavbar.vue";
import {getConfig, getStatDetail} from "@/api/chat_statistic_group";
import {computedRate, objectToQueryString, secondToDate} from "@/utils/tools";

const defaultAvatar = require('@/assets/default-avatar.png');
const route = useRoute()
const router = useRouter()
const loading = ref(false)
const staffInfo = ref({
    name: '',
    avatar: '',
})
const activeTab = ref('all')
const msgReplySec = ref('')
const atMsgReplySec = ref('')
const pagination = reactive({
    current: 1,
    page: 1,
    pageSize: 10,
    total: 0,
    pageSizeOptions: ['10', '20', '50', '100'],
    showQuickJumper: true,
    showSizeChanger: true,
})
const filterData = reactive({
    keyword: ""
})
const list = ref([])
const columns = ref([])
const columnsMap = {
    chat_name: {
        title: "群聊名称",
        dataIndex: "chat_name",
        width: 300,
    },
    owner_name: {
        title: "群主",
        dataIndex: "owner_name",
        width: 120,
    },
    msg_no_day: {
        title: "总消息数",
        dataIndex: "msg_no_day",
        width: 120,
    },
    staff_msg_no_day: {
        title: "员工总消息数",
        dataIndex: "staff_msg_no_day",
        width: 120,
    },
    staff_self_msg_num: {
        title: "当前员工发送",
        dataIndex: "staff_self_msg_num",
        width: 120,
    },
    cst_msg_no_day: {
        title: "客户消息数",
        dataIndex: "cst_msg_no_day",
        width: 120,
    },
    at_rate_avg: {
        // title: "@分钟回复率",
        dataIndex: "at_rate_avg",
        width: 120,
    },
    at_round_no: {
        title: "@回复轮次",
        dataIndex: "at_round_no",
        width: 120,
    },
    rate_avg: {
        // title: "分钟回复率",
        dataIndex: "rate_avg",
        width: 120,
    },
    reply_status: {
        title: "回复状态",
        dataIndex: "reply_status",
        width: 120,
    },
    noreply_content: {
        title: "未回复消息内容",
        dataIndex: "noreply_content",
        width: 200,
    },
    promoter_type_text: {
        title: "发起人",
        dataIndex: "promoter_type_text",
        width: 100,
    },
    operate: {
        title: "操作",
        dataIndex: "operate",
        width: 120,
    },
}

const normalColumns = ['chat_name', 'owner_name', 'msg_no_day', 'staff_msg_no_day', 'staff_self_msg_num', 'cst_msg_no_day', 'rate_avg', 'reply_status', 'promoter_type_text', 'operate']
const noReplyColumns = ['chat_name', 'owner_name', 'msg_no_day', 'staff_msg_no_day', 'staff_self_msg_num', 'cst_msg_no_day', 'reply_status', 'promoter_type_text', 'operate']
const replyRateColumns = ['chat_name', 'owner_name', 'at_rate_avg', 'at_round_no', 'rate_avg', 'promoter_type_text', 'operate']
const tabs = ref([
    {key: 'all', title: '全部群聊', value: '', columns: normalColumns},
    {key: 'new_room', title: '今日新增群聊', value: '', columns: normalColumns},
    {key: 'active_room', title: '活跃群聊', value: '', columns: normalColumns},
    {key: 'replay', title: '已回复群聊', value: '', columns: normalColumns},
    {key: 'no_replay', title: '未回复群聊', value: '', columns: noReplyColumns},
    {key: 'rate_avg', title: '回复率', value: '', columns: replyRateColumns},
])

onMounted(() => {
    activeTab.value = route.query.type
    loadConfig()
    init()
})

function init() {
    columnsInit()
    loadData()
    loadOtherTotal()
}

function columnsInit() {
    const tab = tabs.value.find(i => i.key === activeTab.value)
    columns.value = []
    tab.columns.map(i => {
        columns.value.push(columnsMap[i])
    })
}

function loadConfig() {
    getConfig().then(res => {
        try {
            let data = res?.data || {}
            msgReplySec.value = data.msg_reply_sec
            atMsgReplySec.value = data.at_msg_reply_sec
        } catch (e) {
            console.error("statRuleDetail error:", e)
        }
    })
}

function reload() {
    pagination.current = 1
    pagination.total = 0
    list.value = []
    loadData()
}

function search() {
    reload()
    loadOtherTotal()
}

function tabChange() {
    columnsInit()
    reload()
}

function statDateFormat() {
    return dayjs(route.query.stat_time * 1000).format("YYYY-MM-DD")
}

function getFilterParams() {
    let params = {
        page: pagination.current,
        size: pagination.pageSize,
        staff_userid: route.query.staff_userid,
        stat_time: route.query.stat_time,
    }
    filterData.keyword = filterData.keyword.trim()
    if (filterData.keyword) {
        params.keyword = filterData.keyword
    }
    if (!['all'].includes(activeTab.value)) {
        params.type = activeTab.value
    }
    return params
}

function loadData() {
    loading.value = true
    let params = getFilterParams()
    getStatDetail(params).then(res => {
        let _list = res.data.list || []
        _list.map(item => {
            item.chat_name = item?.chat_info?.name || '未命名群聊'
            item.owner_name = item?.owner_info?.name || '--'
            item.promoter_type_text = item?.promoter_type == 1 ?  '客户' : '员工'
            item.rate_avg = computedRate(item.rate_avg, 1)
            item.at_rate_avg = computedRate(item.at_rate_avg, 1)
        })
        list.value = _list
        pagination.total = Number(res.data.count)
        let type = params.type || ''
        let index = tabs.value.findIndex(i => i.key === type)
        if (index > -1) {
            tabs.value[index].value = pagination.total
        }
    }).finally(() => {
        loading.value = false
    })
}

function loadOtherTotal() {
    let params = getFilterParams()
    params.page = 1
    params.size = 1
    tabs.value.map(item => {
        if (!['all'].includes(item.key)) {
            params.type = item.key
        } else {
            delete params.type
        }
        getStatDetail(params).then(res => {
            item.value = Number(res?.data?.count || 0)
        })
    })
}

function tableChange(p) {
    Object.assign(pagination, p)
    loadData()
}

function linkChat(record) {
    const query = {
        group_chat_id: record?.chat_info?.chat_id || ''
    }
    window.open(`/#/sessionArchive/index?${objectToQueryString(query)}`)
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
</style>
