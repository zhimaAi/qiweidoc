<template>
    <div>
        <MainNav active="index"/>
        <div class="zm-main-content">
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
                        <a-radio-group v-model:value="filterData.dateType" @change="dateTypeChange">
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
                        <ASelectStaff :key="filterStaffKey" :maxTagCount="1" @change="filterStaffChange"/>
                    </div>
                </div>
                <div class="filter-item">
                    <a-button type="primary" @click="search">搜 索</a-button>
                    <a-button @click="reset" class="ml4">重 置</a-button>
                </div>
            </div>
            <div class="zm-flex-between">
                <div class="filter-box">
                    <div class="filter-item" v-if="filterData.dateType == 1" style="margin-bottom: 0;">
                        <span class="item-label" style="width: 100px;">统计截止时间：</span>
                        <div class="item-body">
                            {{ config.last_stat_time > 0 ? config.last_stat_date : "--" }}
                            <a class="ml16" @click="updateStatData"><RedoOutlined/>更新</a>
                        </div>
                    </div>
                </div>
                <a-button @click="exportData" :loading="exporting">导出</a-button>
            </div>
            <div class="table-box mt16">
                <a-table :loading="loading"
                         :data-source="list"
                         :columns="columns"
                         :scroll="{x:1300}"
                         :pagination="pagination"
                         @change="tableChange"
                >
                    <template #headerCell="{ column }">
                        <template v-if="'chat_total' === column.dataIndex">
                            <a-tooltip>
                                <template #title>
                                    <span>所在总群数：该员工所在的群聊数</span><br>
                                    <span>今日新增：今日新加入的群聊</span>
                                </template>
                                所在总群数/今日新增
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'eft_chat_no' === column.dataIndex">
                            <a-tooltip>
                                <template #title>
                                    <span>活跃群：客户发了消息的群</span><br>
                                    <span>回复群：活跃群聊中，回复了消息的群聊</span>
                                </template>
                                活跃群/回复群
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'msg_no_day' === column.dataIndex">
                            <a-tooltip>
                                <template #title>
                                    <span>总消息数：群内员工和客户所有消息的总数</span><br>
                                </template>
                                总消息数
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'staff_self_msg_num' === column.dataIndex">
                            <a-tooltip>
                                <template #title>
                                    <span>员工总消息数：员工所在的群聊的全部员工发送的消息</span><br>
                                    <span>当前员工发送：员工所在的群聊中该员工发送的消息数</span>
                                </template>
                                员工总消息数/当前员工发送
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'no_reply_chat_no' === column.dataIndex">
                            <a-tooltip title="员工所在的群聊活跃群聊中未回复消息的群聊数以及占比">
                                未回复/未回复率
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'at_rate_avg' === column.dataIndex">
                            <a-tooltip
                                :title="`@员工后${config.at_msg_reply_sec}分钟内回复的总轮次/@ 员工后，被@员工回复的总轮次之和，这里轮次是客户发起，员工回复算一轮`">
                                @后{{ config.at_msg_reply_sec }}分钟回复率
                                <QuestionCircleOutlined/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'rate_avg' === column.dataIndex">
                            <a-tooltip
                                :title="`${config.msg_reply_sec}分钟内回复的总轮次/群的总轮次之和，这里轮次是客户发起，群内员工回复算一轮`">
                                {{ config.msg_reply_sec }}分钟回复率
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
                        <template v-else-if="'chat_total' === column.dataIndex">
                            <a @click="linkPath('all',record)">{{ text }}</a>
                            /
                            <a @click="linkPath('new_room',record)">{{ record.new_chat_total }}</a>
                        </template>
                        <template v-else-if="'eft_chat_no' === column.dataIndex">
                            <a @click="linkPath('active_room',record)">{{ text }}</a>
                            /
                            <a @click="linkPath('replay',record)">{{ record.reply_no }}</a>
                        </template>
                        <template v-else-if="'msg_no_day' === column.dataIndex">
                            {{ text }}
                        </template>
                        <template v-else-if="'staff_self_msg_num' === column.dataIndex">
                            {{ record.staff_msg_no_day }} / {{ text }}
                        </template>
                        <template v-else-if="'no_reply_chat_no' === column.dataIndex">
                            <a @click="linkPath('no_replay',record)">{{ text }}</a> / {{ record.noreply_rate }}
                        </template>
                        <template v-else-if="'at_rate_avg' === column.dataIndex">
                            <a @click="linkPath('rate_avg',record)">{{ text }}</a>
                        </template>
                        <template v-else-if="'rate_avg' === column.dataIndex">
                            <a @click="linkPath('rate_avg',record)">{{ text }}</a>
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
import MainNav from "@/views/Modules/chat_statistic_group/components/mainNav.vue";
import ASelectStaff from "@/components/common/aselect-staff.vue";
import {getConfig, getStatList, updateStatInfo} from "@/api/chat_statistic_group";
import tableToExcel, {computedRate} from "@/utils/tools";

const defaultAvatar = require('@/assets/default-avatar.png');
const router = useRouter()
const columns = ref([
    {
        title: "所属员工",
        dataIndex: "staff_name",
        width: 120,
    },
    {
        //title: "所在总群数/今日新增",
        dataIndex: "chat_total",
        width: 100,
    },
    {
        //title: "活跃群聊数",
        dataIndex: "eft_chat_no",
        width: 80,
    },
    {
        //title: "发送消息数",
        dataIndex: "msg_no_day",
        width: 60,
    },
    {
        //title: "当前员工发送",
        dataIndex: "staff_self_msg_num",
        width: 120,
    },
    {
        //title: "未回复群聊",
        dataIndex: "no_reply_chat_no",
        width: 100,
    },
    {
        //title: "@后X分钟回复率",
        dataIndex: "at_rate_avg",
        width: 100,
    },
    {
        //title:  "分钟回复率",
        dataIndex: "rate_avg",
        width: 80,
    },
])
const customDateRef = ref(null)
const filterDataDefault = {
    year: 1,
    month: 1,
    dateType: 1,
    date: undefined,
    staff_userid: [],
    order: ""
}
const loading = ref(false)
const updating = ref(false)
const exporting = ref(false)
const statDate = ref('')
const filterStaffKey = ref(1)
const list = ref([])
const filterData = reactive({
    ...filterDataDefault
})
const config = reactive({
    at_msg_reply_sec: "",
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
    loadData()
}

function filterDataInit() {
    filterData.year = 1
    filterData.month = Number(dayjs().format("MM"))
}

function filterStaffChange(value) {
    filterData.staff_userid = value.map(i => i.userid)
    search()
}

function reset() {
    Object.assign(filterData, filterDataDefault);
    filterStaffKey.value += 1
    filterDataInit()
    search()
}

function search() {
    pagination.current = 1
    loadData()
}

async function loadConfig() {
    await getConfig().then(res => {
        try {
            let data = res?.data || {}
            config.msg_reply_sec = data.msg_reply_sec
            config.at_msg_reply_sec = data.at_msg_reply_sec
        } catch (e) {
            console.error("statRuleDetail error:", e)
        }
    })
}

function loadData() {
    loading.value = true
    let params = getFilterData()
    getStatList(params).then(res => {
        let _list = res.data.list || []
        _list.map(item => {
            item.rate_avg = computedRate(item.rate_avg, 1)
            item.at_rate_avg = computedRate(item.at_rate_avg, 1)
            if (item.noreply_rate > 0) {
                item.noreply_rate = (item.noreply_rate * 100).toFixed(2) + '%'
            } else {
                item.noreply_rate = 0
            }
        })
        config.last_stat_time = Number(res?.data?.last_stat_time || 0)
        config.last_stat_date = dayjs(config.last_stat_time * 1000).format("YYYY-MM-DD HH:mm")
        list.value = _list
        pagination.total = Number(res.data.count)
    }).finally(() => {
        loading.value = false
    })
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

function disabledDate(current) {
    let year = filterData.year == 1 ? Number(dayjs().format("YYYY")) : Number(dayjs().subtract(1, "year").format("YYYY"))
    return Number(current.format("YYYY")) != year || Number(current.format("MM")) != filterData.month
}

function dateChange() {
    filterData.dateType = 0
    search()
}

function dateTypeChange() {
    if (filterData.dateType === 0) {
        customDateRef.value.focus()
        filterData.date = dayjs()
        search()
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

function linkPath(type, record) {
    const routeUrl = router.resolve({
        path: '/module/chat-statistic-group/detail',
        query: {
            type: type,
            staff_userid: record.staff_user_id,
            staff_name: record.staff_name,
            stat_time: getFilterData().stat_time || "",
        }
    })
    window.open(routeUrl.href, '_blank');
}

function updateStatData() {
    if (updating.value) {
        return
    }
    updating.value = true
    updateStatInfo().then(res => {
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
            item.rate_avg = computedRate(item.rate_avg, 1)
            item.at_rate_avg = computedRate(item.at_rate_avg, 1)
            if (item.noreply_rate > 0) {
                item.noreply_rate = (item.noreply_rate * 100).toFixed(2) + '%'
            }
        })
        const head =  `员工,所在总群,今日新增,活跃群,回复群,总消息数,员工总消息数,当前员工发送,未回复,未回复率,@后${ config.at_msg_reply_sec }分钟回复率,${config.msg_reply_sec}分钟回复率\n`
        const fields = [
            "staff_name", "chat_total", "new_chat_total", "eft_chat_no", "reply_no","msg_no_day", "staff_msg_no_day", "staff_self_msg_num", "no_reply_chat_no", "noreply_rate", "at_rate_avg", "rate_avg"
        ]
        tableToExcel(head, _list, fields, "员工群聊详情.csv");
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

            &.not_allot {
                width: auto;
                padding-left: 40px;
                margin-bottom: 0;
                cursor: pointer;
            }

            .link {
                color: #2475fc;
            }
        }

        .item-body {
            flex: 1;
        }
    }
}

.table-box {
    :deep(.ant-table) {
        font-size: 12px;
    }
}

.no-stat-tag {
    font-size: 14px;
    font-weight: 500;
    color: #8C8C8C;
    cursor: default;
}
</style>
