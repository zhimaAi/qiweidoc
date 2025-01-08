<template>
    <div>
        <MainNav active="index"/>
        <div class="zm-main-content">
            <a-alert type="info"
                     style="margin-bottom: 16px"
                     v-if="showConfigTip">
                <template #message>
                    温馨提示：未回复单聊、未回复占比数据需提前设置规则，如果还未配置，可前往配置
                    <router-link to="/workQualityStat/rules?navKey=workQualityStatRule" target="_blank"><a>去配置</a>
                    </router-link>
                </template>
            </a-alert>
            <div class="filter-box">
                <div class="filter-item">
                    <span class="item-label">选择时间：</span>
                    <div class="item-body">
                        <a-select v-model:value="filterData.year"
                                  @change="changeDefaultValue"
                                  style="width: 80px;">
                            <a-select-option :value="1">今年</a-select-option>
                            <a-select-option :value="2">去年</a-select-option>
                        </a-select>
                        <a-select v-model:value="filterData.month"
                                  @change="changeDefaultValue"
                                  style="width: 80px;">
                            <a-select-option
                                v-for="i in 12"
                                :value="i"
                                :key="i">{{ i }}月
                            </a-select-option>
                        </a-select>
                    </div>
                </div>
                <div class="filter-item">
                    <span class="item-label">选择日期：</span>
                    <div class="item-body">
                        <a-radio-group
                            v-model:value="filterData.dateType"
                            @change="dateTypeChange">
                            <a-radio-button :value="1">今天</a-radio-button>
                            <a-radio-button :value="2">昨天</a-radio-button>
                            <a-radio-button :value="0">自定义</a-radio-button>
                        </a-radio-group>
                        <a-date-picker
                            ref="customDateRef"
                            style="width: 160px;"
                            class="ml8"
                            v-model:value="filterData.date"
                            @change="dateChange"
                            :disabled-date="disabledDate"
                            format="YYYY-MM-DD"></a-date-picker>
                    </div>
                </div>
                <div class="filter-item">
                    <span class="item-label">所属员工：</span>
                    <div class="item-body">
                        <ASelectStaff :maxTagCount="1" width="200px" @change="filterStaffChange"/>
                    </div>
                </div>
                <div class="filter-item">
                    <a-button type="primary" @click="search">搜 索</a-button>
                    <a-button @click="reset" class="ml4">重 置</a-button>
                </div>
            </div>
            <div class="zm-flex-between">
                <div class="filter-box">
                    <div class="filter-item" v-if="filterData.dateType == 1">
                        <span class="item-label" style="width: 100px;">统计截止时间：</span>
                        <div class="item-body">
                            {{ config.last_stat_time > 0 ? config.last_stat_date : "--" }}
                            <a class="ml16" @click="updateStatData">
                                <RedoOutlined/>
                                更新
                            </a>
                        </div>
                    </div>
                </div>
                <div class="filter-box">
                    <div class="filter-item">
                        <a-button @click="exportData" :loading="exporting">导出</a-button>
                    </div>
                </div>
            </div>
            <div class="overview-box">
                <div v-for="(item,index) in overviewItems"
                     :key="index"
                     @click="item.link ? $goRouter(item.link) : ''"
                     :class="['overview-item',{link: item.link}]">
                    <div class="item-title">
                        <a-tooltip :title="item.desc">
                            {{ item.title }}
                            <QuestionCircleOutlined/>
                        </a-tooltip>
                    </div>
                    <div class="item-value">{{ item.value }}</div>
                </div>
            </div>
            <div class="table-box mt16">
                <a-table :loading="loading"
                         :data-source="list"
                         :columns="columns"
                         :scroll="{x:1200}"
                         :pagination="pagination"
                         @change="tableChange"
                >
                    <template #headerCell="{column}">
                        <template v-if="'cst_total' === column.dataIndex">
                            <a-tooltip title="截止到统计时间，员工累计添加的客户总数">
                                总客户
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'new_cst_total' === column.dataIndex">
                            <a-tooltip title="统计时间内，成为好友的人数">
                                新客户
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'chat_total' === column.dataIndex">
                            <a-tooltip title="统计时间内，产生过会话的单聊数量（员工或客户发送了消息都算）">
                                单聊数
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'promoter_stf_no' === column.dataIndex">
                            <a-tooltip>
                                <template #title>
                                    <div>主动沟通：由员工主动发起的单聊总数</div>
                                    <div>被动沟通：由客户主动发起的单聊总数</div>
                                </template>
                                主动 / 被动沟通
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'promoter_cst_no' === column.dataIndex">
                            <a-tooltip>
                                <template #title>
                                    <div>主动有效沟通：由员工主动发起客户回复过消息的单聊总数</div>
                                    <div>被动有效沟通：由客户主动发起员工回复过消息的单聊总数</div>
                                </template>
                                主动 / 被动有效沟通
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'eft_chat_no' === column.dataIndex">
                            <a-tooltip title="主动有效沟通 + 被动有效沟通">
                                总有效沟通
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'staff_msg_no_day' === column.dataIndex">
                            <a-tooltip title="单聊中，员工发送的消息总数">
                                员工消息数
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'msg_no_day' === column.dataIndex">
                            <a-tooltip title="单聊中，员工和客户发送的消息的总数量">
                                总消息数
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'no_reply_chat_no' === column.dataIndex">
                            <a-tooltip>
                                <template #title>
                                    <div>未回复：员工单聊中未回复的客户数量</div>
                                    <div>未回复率：员工未回复的单聊数/有效会话数</div>
                                    <div>有效会话数：工作时间内，客户发送过消息（包含客户主动发送和回复员工的消息）
                                    </div>
                                </template>
                                未回复 / 未回复率
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'rate_avg' === column.dataIndex">
                            <a-tooltip
                                :title="`${config.msg_reply_sec}分钟内回复的总轮次/单聊的总轮次之和，这里轮次是客户发起，所属员工回复算一轮`">
                                {{ config.msg_reply_sec }}分钟回复率
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'time_st_avg' === column.dataIndex">
                            <a-tooltip
                                title="客户先发消息至员工回复之间的时长，为首次回复时长。所有的首次回复总时长/已回复单聊总数，即为平均首次回复时长。">
                                平均首响
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                    </template>

                    <template #bodyCell="{column, text, record}">
                        <template v-if="'staff_name'=== column.dataIndex">
                            <div class="zm-user-info">
                                <img class="avatar" :src="record.avatar || defaultAvatar"/>
                                <span class="name">{{ record.staff_name }}</span>
                            </div>
                        </template>
                        <template v-else-if="'new_cst_total' === column.dataIndex">
                            <a @click="linkDetail('is_new_user', record)">{{ text }}</a>
                        </template>
                        <template v-else-if="['new_cst_total', 'chat_total'].includes(column.dataIndex)">
                            <a @click="linkDetail('all', record)">{{ text }}</a>
                        </template>
                        <template v-else-if="'promoter_stf_no'=== column.dataIndex">
                            <a @click="linkDetail('promoter_stf_no',record)">{{ text }}</a>
                            /
                            <a @click="linkDetail('promoter_cst_no',record)">{{ record.promoter_cst_no }}</a>
                        </template>
                        <template v-else-if="'promoter_cst_no'=== column.dataIndex">
                            <a @click="linkDetail('promoter_stf_no_valid',record)">
                                {{ record.promoter_stf_no_valid }}
                            </a>
                            /
                            <a @click="linkDetail('promoter_cst_no_valid',record)">
                                {{ record.promoter_cst_no_valid }}
                            </a>
                        </template>
                        <template v-else-if="'no_reply_chat_no'=== column.dataIndex">
                            <div @mouseenter="showEftChatNo = true"
                                 @mouseleave="showEftChatNo = false">
                                <a @click="linkDetail('no_replay',record)">{{ text }}</a>
                                /
                                <span>{{ record.noreply_rate }}</span>
                                <div v-show="showEftChatNo" class="eft-chat-no">
                                    有效会话数：{{ record.eft_chat_no }}
                                </div>
                            </div>
                        </template>
                        <template v-else-if="'rate_avg'=== column.dataIndex">
                            <a @click="linkDetail('rate_avg',record)">{{ text }}</a>
                        </template>
                        <template v-else-if="'time_st_avg'=== column.dataIndex">
                            <a @click="linkDetail('time_st_avg',record)">{{ text }}</a>
                        </template>
                    </template>
                </a-table>
            </div>
        </div>
    </div>

</template>

<script setup>
import {ref, reactive, onMounted} from 'vue';
import {useRouter} from 'vue-router';
import dayjs from 'dayjs';
import {message} from 'ant-design-vue';
import {RedoOutlined, QuestionCircleOutlined} from '@ant-design/icons-vue';
import MainNav from "@/views/Modules/chat_statistic_single/components/mainNav.vue";
import {getConfig, getStatData, getStatList, updateStatInfo} from "@/api/chat_statistic_single";
import ASelectStaff from "@/components/common/aselect-staff.vue";
import tableToExcel, {computedRate, openPluginRouteLink, secondToDate} from "@/utils/tools";

const defaultAvatar = require('@/assets/default-avatar.png');
const router = useRouter()
const columns = ref([
    {
        title: "员工昵称",
        dataIndex: "staff_name",
        width: 140,
        fixed: "left",
    },
    {
        //title: "总客户",
        dataIndex: "cst_total",
        width: 100,
    },
    {
        //title: "新增客户",
        dataIndex: "new_cst_total",
        width: 100,
    },
    {
        //title: "单聊总数",
        dataIndex: "chat_total",
        sorter: true,
        width: 100,
    },
    {
        //title: "主动/被动沟通",
        dataIndex: "promoter_stf_no",
        sorter: true,
        width: 150,
    },
    {
        //title: "主动/被动有效沟通",
        dataIndex: "promoter_cst_no",
        sorter: true,
        width: 180,
    },
    {
        //title: "总有效沟通",
        dataIndex: "eft_chat_no",
        sorter: true,
        width: 130,
    },
    {
        //title: "员工发送消息数",
        dataIndex: "staff_msg_no_day",
        sorter: true,
        width: 130,
    },
    {
        //title: "总消息数",
        dataIndex: "msg_no_day",
        sorter: true,
        width: 120,
    },
    {
        //title: "未回复单聊",
        dataIndex: "no_reply_chat_no",
        sorter: true,
        width: 160,
    },
    {
        //title: "3分钟回复率",
        dataIndex: "rate_avg",
        sorter: true,
        width: 140,
    },
    {
        //title: "平均首响",
        dataIndex: "time_st_avg",
        sorter: true,
        width: 120,
    },
])
const filterDataDefault = {
    year: 1,
    month: 1,
    dateType: 1,
    date: undefined,
    staff_userid: [],
    order: ""
}
const customDateRef = ref(null)
const loading = ref(false)
const updating = ref(false)
const exporting = ref(false)
const showConfigTip = ref(false)
const showEftChatNo = ref(false)
const list = ref([])
const filterData = reactive({
    ...filterDataDefault
})
const overviewItems = ref([
    {
        title: "新客户数",
        desc: "查询范围内所有的新客户",
        value: "",
        key: "new_cst_total"
    },
    {
        title: "单聊数",
        desc: "查询范围内所有的单聊数",
        value: "",
        key: "chat_total"
    },
    {
        title: "发送消息数",
        desc: "查询范围内所有的发送消息数",
        value: "",
        key: "msg_no_day"
    },
    {
        title: "未回复率",
        desc: "查询范围内所有员工的平均未回复率",
        value: "",
        key: "noreply_rate"
    },
    {
        title: "分钟回复率",
        desc: "查询范围内所有员工的平均n分钟回复率",
        value: "",
        key: "rate_avg"
    },
    {
        title: "平均首响",
        desc: "查询范围内所有员工的平均首响",
        value: "",
        key: "time_st_avg"
    },
])
const config = reactive({
    msg_reply_sec: "",
    last_stat_time: "",
    last_stat_date: "",
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

onMounted(() => {
    init()
})

async function init() {
    filterDataInit()
    await loadConfig()
    await loadData()
    loadOverviewData()
}

async function loadConfig() {
    await getConfig().then(res => {
        try {
            let data = res?.data || {}
            config.msg_reply_sec = data.msg_reply_sec
        } catch (e) {
            console.error("statRuleDetail error:", e)
        }
    })
}

function filterDataInit() {
    filterData.year = 1
    filterData.month = Number(dayjs().format("MM"))
}

function filterStaffChange(value) {
    filterData.staff_userid = value.map(i => i.userid)
    search()
}

function getFilterData() {
    let params = {
        page: pagination.current,
        size: pagination.pageSize
    }
    switch (filterData.dateType) {
        case 1:
            params.stat_time = dayjs().startOf('day').unix()
            break
        case 2:
            params.stat_time = dayjs().subtract(1, "days").startOf('day').unix()
            break
        case 0:
            if (filterData.date) {
                params.stat_time = filterData.date.startOf('day').unix()
            }
            break
    }
    if (filterData.staff_userid.length) {
        params.staff_userid = filterData.staff_userid
    }
    if (filterData.order) {
        params.order = filterData.order
    }
    return params
}

function reset() {
    Object.assign(filterData, filterDataDefault)
    filterDataInit()
    search()
}

function search() {
    pagination.current = 1
    loadData()
    loadOverviewData()
}

function loadOverviewData() {
    let params = getFilterData()
    let {stat_time, staff_userid} = params
    getStatData({stat_time, staff_userid}).then(res => {
        let data = res.data || {}
        overviewItems.value.map(item => {
            switch (item.key) {
                case 'time_st_avg':
                    item.value = secondToDate(data.time_st_avg)
                    break
                case 'rate_avg':
                    item.value = computedRate(data.rate_avg, 1)
                    item.title = `${config.msg_reply_sec}分钟回复率`
                    item.desc = `查询范围内所有员工的平均${config.msg_reply_sec}分钟回复率`
                    break
                case 'noreply_rate':
                    item.value = `${(data.noreply_rate * 100).toFixed(2)}%`
                    break
                default:
                    item.value = data[item.key] || 0
            }
        })
    })
}

async function loadData() {
    loading.value = true
    let params = getFilterData()
    await getStatList(params).then(res => {
        let _list = res.data.list || []
        _list.map(item => {
            item.time_st_avg = secondToDate(item.time_st_avg)
            item.rate_avg = computedRate(item.rate_avg, 1)
            item.noreply_rate = `${(item.noreply_rate * 100).toFixed(2)}%`
            item.promoter_stf_no_valid = Number(item.promoter_stf_no_valid || 0)
            item.promoter_cst_no_valid = Number(item.promoter_cst_no_valid || 0)
            item.eff_no = item.promoter_stf_no_valid + item.promoter_cst_no_valid
        })
        config.last_stat_time = Number(res?.data?.last_stat_time || 0)
        config.last_stat_date = dayjs(config.last_stat_time * 1000).format("YYYY-MM-DD HH:mm")
        list.value = _list
        pagination.total = Number(res?.data?.count || 0)
    }).finally(() => {
        loading.value = false
    })
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

function linkDetail(type, record) {
    const routeUrl = router.resolve({
        path: '/module/chat-statistic-single/detail',
        query: {
            type: type,
            staff_userid: record.staff_user_id,
            staff_name: record.staff_name,
            stat_time: getFilterData().stat_time || "",
            msg_reply_sec: config.msg_reply_sec,
        }
    })
    openPluginRouteLink('chat_statistic_single', routeUrl.href)
}

function disabledDate(current) {
    let year = filterData.year == 1 ? Number(dayjs().format("YYYY")) : Number(dayjs().subtract(1, "year").format("YYYY"))
    return Number(current.format("YYYY")) != year || Number(current.format("MM")) != filterData.month
}

function dateChange() {
    if (!filterData.date) {
        filterData.dateType = 1
    } else {
        filterData.dateType = 0
    }
    search()
}

function dateTypeChange() {
    if (filterData.dateType === 0) {
        customDateRef.value.focus()
    } else {
        filterData.date = undefined
        filterDataInit()
        search()
    }
}

function changeDefaultValue() {
    let year = filterData.year == 1 ? Number(dayjs().format("YYYY")) : Number(dayjs().subtract(1, "year").format("YYYY"))
    let month = filterData.month < 10 ? '0' + filterData.month : filterData.month
    filterData.date = dayjs(`${year}-${month}-01`)
    filterData.dateType = 0
    search()
}

function updateStatData() {
    if (updating.value) {
        return
    }
    updating.value = true
    updateStatInfo().then(() => {
        message.success("操作完成，任务已添加")
        search()
    }).finally(() => {
        updating.value = false
    })
}

function exportData() {
    if (pagination.total < 1) {
        return message.error("暂无数据")
    }
    exporting.value = true
    let params = getFilterData()
    params.size = 5000
    getStatList(params).then(res => {
        let _list = res.data.list || []
        _list.map(item => {
            item.time_st_avg = secondToDate(item.time_st_avg)
            item.rate_avg = computedRate(item.rate_avg, 1)
            item.noreply_rate = `${(item.noreply_rate * 100).toFixed(2)}%`
            item.promoter_stf_no_valid = Number(item.promoter_stf_no_valid || 0)
            item.promoter_cst_no_valid = Number(item.promoter_cst_no_valid || 0)
            item.eff_no = item.promoter_stf_no_valid + item.promoter_cst_no_valid
        })
        const head = `员工昵称,总客户,新客户,单聊数,主动沟通,被动沟通,主动有效沟通,被动有效沟通,员工消息数,总消息数,未回复,未回复率,${config.msg_reply_sec}分钟回复率,平均首响\n`
        const fields = [
            "staff_name",
            "cst_total",
            "new_cst_total",
            "chat_total",
            "promoter_stf_no",
            "promoter_cst_no",
            "promoter_stf_no_valid",
            "promoter_cst_no_valid",
            "staff_msg_no_day",
            "msg_no_day",
            "no_reply_chat_no",
            "noreply_rate",
            "rate_avg",
            "time_st_avg"
        ]
        tableToExcel(head, _list, fields, "员工客户详情.csv");
    }).finally(() => {
        exporting.value = false
    })
}
</script>

<style scoped lang="less">
.zm-main-content {
    min-height: 86vh;
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
        margin: 0 16px 16px 0;

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

.table-box {
    :deep(.ant-table) {
        font-size: 12px;

        .ant-table-tbody > tr > td {
            height: 70px;
        }
    }
}

.eft-chat-no {
    font-size: 12px;
    color: #8c8c8c;
    white-space: nowrap;
}

.overview-box {
    display: flex;
    flex-wrap: wrap;

    .overview-item {
        width: calc(16.6% - 40px / 6);
        height: 88px;
        padding: 16px 24px;
        background: #F2F4F7;
        border-radius: 2px;
        margin: 0 8px 0 0;
        font-size: 14px;
        font-weight: 400;
        color: rgba(0, 0, 0, 0.65);

        &:last-child {
            margin-right: 0;
        }

        &.link {
            cursor: pointer;

            .item-value {
                color: #2475FC;
            }
        }

        .item-value {
            font-size: 18px;
            font-weight: 500;
            color: #262626;
            margin-top: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;

            &.no-assign {
                font-weight: 400;
                font-size: 14px;
                color: #8C8C8C;
            }
        }
    }
}
</style>
