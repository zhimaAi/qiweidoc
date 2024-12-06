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
                            <a-select-option :value="2">
                                去年
                            </a-select-option>
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
                            v-model:checked="filterData.dateType"
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
                        <!--                        <ASelectStaff :maxTagCount="1" width="200px" @change="filterStaffChange"/>-->
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
                            <QuestionCircleFilled/>
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
                        <template v-if="'cst_no_all' === column.dataIndex">
                            <a-tooltip title="截止到统计时间，员工累计添加的客户总数">
                                总客户
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'cst_no_incr' === column.dataIndex">
                            <a-tooltip title="统计时间内，成为好友的人数">
                                新客户
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'chat_no' === column.dataIndex">
                            <a-tooltip title="统计时间内，产生过会话的单聊数量（员工或客户发送了消息都算）">
                                单聊数
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'promoter_stf_no' === column.dataIndex">
                            <a-tooltip>
                                <template #title>
                                    <div>主动沟通：由员工主动发起的单聊总数</div>
                                    <div>被动沟通：由客户主动发起的单聊总数</div>
                                </template>
                                主动 / 被动沟通
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'promoter_cst_no' === column.dataIndex">
                            <a-tooltip>
                                <template #title>
                                    <div>主动有效沟通：由员工主动发起客户回复过消息的单聊总数</div>
                                    <div>被动有效沟通：由客户主动发起员工回复过消息的单聊总数</div>
                                </template>
                                主动 / 被动有效沟通
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'eff_no' === column.dataIndex">
                            <a-tooltip title="主动有效沟通 + 被动有效沟通">
                                总有效沟通
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'staff_msg_no' === column.dataIndex">
                            <a-tooltip title="单聊中，员工发送的消息总数">
                                员工消息数
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'msg_no_all' === column.dataIndex">
                            <a-tooltip title="单聊中，员工和客户发送的消息的总数量">
                                总消息数
                                <QuestionCircleFilled/>
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
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'rate_avg' === column.dataIndex">
                            <a-tooltip
                                :title="`${config.msg_reply_sec}分钟内回复的总轮次/单聊的总轮次之和，这里轮次是客户发起，所属员工回复算一轮`">
                                {{ config.msg_reply_sec }}分钟回复率
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'time_st_avg' === column.dataIndex">
                            <a-tooltip
                                title="客户先发消息至员工回复之间的时长，为首次回复时长。所有的首次回复总时长/已回复单聊总数，即为平均首次回复时长。">
                                平均首响
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                    </template>

                    <template #bodyCell="{column, text, record}">
                        <template v-if="'stf_name'=== column.dataIndex">
                            <div class="wk-user-info">
                                <img class="avatar"
                                     :src="record.avatar"/>
                                <span class="name">{{ record.stf_name }}</span>
                            </div>
                        </template>
                        <template v-else-if="'cst_no_all'=== column.dataIndex">
                            <a @click="linkPath('/workQualityStat/staffChatDetail?type=all_cst',record)">{{ text }}</a>
                        </template>
                        <template v-else-if="'cst_no_incr'=== column.dataIndex">
                            <a @click="linkPath('/workQualityStat/staffChatDetail?type=new_cst',record)">{{ text }}</a>
                        </template>
                        <template v-else-if="'chat_no'=== column.dataIndex">
                            <a @click="linkPath('/workQualityStat/staffChatDetail?type=all_chat',record)">{{ text }}</a>
                        </template>
                        <template v-else-if="'promoter_stf_no'=== column.dataIndex">
                            <a @click="linkPath('/workQualityStat/staffChatDetail?type=staff_trigger',record)">{{
                                    text
                                }}</a>
                            /
                            <a @click="linkPath('/workQualityStat/staffChatDetail?type=cst_trigger',record)">{{
                                    record.promoter_cst_no
                                }}</a>
                        </template>
                        <template v-else-if="'promoter_cst_no'=== column.dataIndex">
                            <a @click="linkPath('/workQualityStat/effectChatDetail?type=staff_trigger',record)">{{
                                    record.promoter_stf_no_valid
                                }}</a>
                            /
                            <a @click="linkPath('/workQualityStat/effectChatDetail?type=cst_trigger',record)">{{
                                    record.promoter_cst_no_valid
                                }}</a>
                        </template>
                        <template v-else-if="'no_reply_chat_no'=== column.dataIndex">
                            <a-tooltip
                                v-if="!whetherStatCurrentDate"
                                overlayClassName="stat-config-overlay">
                                <template #title>
                                    <div>统计时间</div>
                                    <div>
                                        <div v-for="(desc,i) in statConfig.descText">{{ desc }}</div>
                                    </div>
                                </template>
                                <span class="no-stat-tag">未统计</span>
                            </a-tooltip>
                            <template v-else>
                                <a @click="linkPath('/workQualityStat/staffChatDetail?type=unreply',record)">{{
                                        text
                                    }}</a>
                                / <span>{{ record.noreply_rate }}</span>
                                <div v-show="showEftChatNo" class="eft-chat-no">
                                    有效会话数：{{ record.eft_chat_no }}
                                </div>
                            </template>
                        </template>
                        <template v-else-if="'rate_avg'=== column.dataIndex">
                            <a-tooltip
                                v-if="!whetherStatCurrentDate"
                                overlayClassName="stat-config-overlay">
                                <template #title>
                                    <div>统计时间</div>
                                    <div>
                                        <div v-for="(desc,i) in statConfig.descText">{{ desc }}</div>
                                    </div>
                                </template>
                                <span class="no-stat-tag">未统计</span>
                            </a-tooltip>
                            <a v-else
                               @click="linkPath('/workQualityStat/staffChatDetail?type=minute_reply_rate',record)">{{
                                    text
                                }}</a>
                        </template>
                        <template v-else-if="'time_st_avg'=== column.dataIndex">
                            <a-tooltip
                                v-if="!whetherStatCurrentDate"
                                overlayClassName="stat-config-overlay">
                                <template #title>
                                    <div>统计时间</div>
                                    <div>
                                        <div v-for="(desc,i) in statConfig.descText" :key="i">{{ desc }}</div>
                                    </div>
                                </template>
                                <span class="no-stat-tag">未统计</span>
                            </a-tooltip>
                            <a v-else
                               @click="linkPath('/workQualityStat/staffChatDetail?type=first_reply_time',record)">{{
                                    text
                                }}</a>
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
import {RedoOutlined, QuestionCircleFilled} from '@ant-design/icons-vue';
import MainNav from "@/views/Modules/single_chat_stat/components/mainNav.vue";

const router = useRouter()
const activeTab = ref('index')
const columns = [
    {
        title: "员工昵称",
        dataIndex: "stf_name",
        width: 140,
        fixed: "left",
    },
    {
        //title: "总客户",
        dataIndex: "cst_no_all",
        sorter: true,
        width: 100,
    },
    {
        //title: "新增客户",
        dataIndex: "cst_no_incr",
        sorter: true,
        width: 100,
    },
    {
        //title: "单聊总数",
        dataIndex: "chat_no",
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
        dataIndex: "eff_no",
        sorter: true,
        scopedSlots: {
            customRender: "allEffChat"
        },
        width: 130,
    },
    {
        //title: "员工发送消息数",
        dataIndex: "staff_msg_no",
        sorter: true,
        width: 130,
    },
    {
        //title: "总消息数",
        dataIndex: "msg_no_all",
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
        width: 130,
    },
    {
        //title: "平均首响",
        dataIndex: "time_st_avg",
        sorter: true,
        width: 120,
    },
]
const filterDataDefault = {
    year: 1,
    month: 1,
    dateType: 1,
    date: undefined,
    open_userids: [],
    staff_ids: [],
    order: ""
}
const loading = ref(false)
const updateing = ref(false)
const exporting = ref(false)
const showConfigTip = ref(false)
const showEftChatNo = ref(false)
const whetherStatCurrentDate = ref(true)
const list = ref([])
const sessionStafflist = ref([])
const filterData = reactive({
    ...filterDataDefault
})
const overviewItems = reactive([
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
const defaultDate = dayjs()
const config = reactive({
    msg_reply_sec: "",
    last_stat_time: "",
    last_stat_date: "",
})

const statConfig = reactive({
    weeks: [],
    descText: []
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

function mainTabChange() {
    if (activeTab.value === 'rule') {
        router.push({
            path: ''
        })
    }
}

async function init() {
    filterDataInit()
    await loadConfig()
    await loadData()
    loadOverviewData()
}

async function loadConfig() {
    // await statRuleDetail().then(res => {
    //     try {
    //         let config = res.data || {}
    //         config.time_range_json = JSON.parse(config.time_range_json)
    //         let weeks = [],
    //             descText = [],
    //             statDate,
    //             timeRanges
    //         config.time_range_json.map(item => {
    //             weeks.push(...item.week)
    //             statDate = weekToText(item.week).toString()
    //             timeRanges = item.range.map(r => {
    //                 return `${r.s} 至 ${r.e}`
    //             })
    //             descText.push(
    //                 `${statDate} ${timeRanges.join("；")}`
    //             )
    //         })
    //         this.statConfig = {
    //             weeks: weeks,
    //             descText: descText
    //         }
    //         let singleJson = JSON.parse(config.single_keywords_json)
    //         if (!singleJson || (!singleJson.full.length && !singleJson.half.length)) {
    //             this.showConfigTip = true
    //         }
    //     } catch (e) {
    //         console.error("statRuleDetail error:", e)
    //     }
    // })
}

function filterDataInit() {
    filterData.year = 1
    filterData.month = Number(dayjs().format("MM"))
}

function filterStaffChange(value) {
    filterData.open_userids = value.map(i => i.user_id)
    filterData.staff_ids = value.map(i => i.staff_id)
    search()
}

function getFilterData() {
    let params = {
        page: pagination.current,
        size: pagination.pageSize
    }
    switch (filterData.dateType) {
        case 1:
            params.date_no = dayjs().format("YYYYMMDD")
            break
        case 2:
            params.date_no = dayjs().subtract(1, "days").format("YYYYMMDD")
            break
        case 0:
            if (filterData.date) {
                params.date_no = filterData.date.format("YYYYMMDD")
            }
            break
    }
    if (filterData.open_userids.length) {
        params.open_userids = filterData.open_userids
        params.staff_ids = filterData.staff_ids
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
    // let request = privateChatStat
    // if (this.MODE == 2) {
    //     request = authModePrivateChatStat
    // }
    // let params = this.getFilterData()
    // let {open_userids, staff_ids, date_no} = params
    // request({open_userids, staff_ids, date_no}).then(res => {
    //     let data = res.data || {}
    //     this.overviewItems.map(item => {
    //         switch (item.key) {
    //             case 'time_st_avg':
    //                 item.value = secondToDate(data.time_st_avg)
    //                 break
    //             case 'rate_avg':
    //                 item.value = computedRate(data.rate_avg, 1)
    //                 item.title = `${this.config.msg_reply_sec}分钟回复率`
    //                 item.desc = `查询范围内所有员工的平均${this.config.msg_reply_sec}分钟回复率`
    //                 break
    //             case 'noreply_rate':
    //                 item.value = `${(data.noreply_rate * 100).toFixed(2)}%`
    //                 break
    //             default:
    //                 item.value = data[item.key] || 0
    //         }
    //     })
    // })
}

async function loadData() {
    let params = getFilterData()
    whetherStatCurrentDate.value = whetherStatCurrentDateRun(params.date_no)
    // let request = chatStatByStaff
    // let dataItemFormat = item => item
    // if (this.MODE == 2) {
    //     request = authModeChatStatByStaff
    //     dataItemFormat = item => {
    //         item.open_userid = item.stf_userid
    //         return item
    //     }
    // }
    // await request(params).then(res => {
    //     let list = res.data.list || []
    //     let numberFields = ['cst_no_all', 'cst_no_incr', 'chat_no', 'promoter_stf_no', 'promoter_cst_no', 'staff_msg_no', 'msg_no_all']
    //     list.map(item => {
    //         for (let field of numberFields) {
    //             !item[field] && (item[field] = 0)
    //         }
    //         item.create_time = formatDate(item.create_time)
    //         item.time_st_avg = secondToDate(item.time_st_avg)
    //         item.rate_avg = computedRate(item.rate_avg, 1)
    //         item.noreply_rate = `${(item.noreply_rate * 100).toFixed(2)}%`
    //         item.promoter_stf_no_valid = Number(item.promoter_stf_no_valid || 0)
    //         item.promoter_cst_no_valid = Number(item.promoter_cst_no_valid || 0)
    //         item.eff_no = item.promoter_stf_no_valid + item.promoter_cst_no_valid
    //         item = dataItemFormat(item)
    //     })
    //     let config = res.data.stat_conf || {}
    //     let staffList = res.data.session_stf_list || []
    //     config.msg_reply_sec = config.msg_reply_sec / 60
    //     if (this.MODE == 2) {
    //         config.last_stat_time = config.auth_last_stat_time || 0
    //         staffList.map(stf => {
    //             stf.stf_name = stf.name
    //             stf.user_id = stf.open_userid
    //         })
    //     }
    //     config.last_stat_date = moment(config.last_stat_time * 1000).format("YYYY-MM-DD HH:mm")
    //     this.config = config
    //     this.list = list
    //     this.pagination.total = Number(res.data.count)
    //     this.sessionStafflist = staffList
    // }).finally(res => {
    //     this.loading = false
    // })
}

function whetherStatCurrentDateRun(dateStr) {
    try {
        let weekday = dayjs(dateStr).weekday()
        return statConfig.weeks.includes(weekday)
    } catch (e) {
    }
    return true
}

function tableChange(p, _, sorter) {
    // if (sorter.field && sorter.order) {
    //     this.columns.map(item => {
    //         if (item.dataIndex === sorter.field) {
    //             item.sortOrder = sorter.order
    //         } else {
    //             item.sortOrder = false
    //         }
    //     })
    //     this.filterData.order = `${sorter.field} ${sorter.order == 'ascend' ? 'ASC' : 'DESC'}`
    // } else {
    //     this.columns.map(item => item.sortOrder = false)
    //     this.filterData.order = ""
    // }
    // Object.assign(pagination, p)
    // loadData()
}

function linkPath(path, record) {
    const routeUrl = router.resolve({
        path: path,
        query: {
            navKey: 'workQualityStatPrivate',
            open_userid: record.open_userid,
            filter_date: this.getFilterData().date_no || "",
            msg_reply_sec: this.config.msg_reply_sec,
        }
    })
    window.open(routeUrl.href, '_blank');
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
        //TODO
        // this.$refs.customDateRef.focus()
    } else {
        filterData.date = undefined
        filterDataInit()
        search()
    }
}

function changeDefaultValue() {
    let year = filterData.year == 1 ? Number(dayjs().format("YYYY")) : Number(dayjs().subtract(1, "year").format("YYYY"))
    let month = filterData.month < 10 ? '0' + filterData.month : filterData.month
    filterData.date = moment(`${year}-${month}-01`)
    filterData.dateType = 0
    search()
}

function disabledLastYear() {
    return dayjs().format("YYYY") <= 2023
}

function updateStatData() {
    if (updateing.value) {
        return
    }
    updateing.value = true
    // let request = updateStatData
    // if (this.MODE == 2) {
    //     request = authModeUpdateStatData
    // }
    // request({
    //     session_type: 1
    // }).then(res => {
    //     this.$message.success("操作完成，任务已添加")
    //     this.search()
    // }).finally(() => {
    //     this.updateing = false
    // })
}

function exportData() {
    if (this.pagination.total < 1) {
        return message.error("暂无数据")
    }
    exporting.value = true
    let params = getFilterData()
    params.size = 5000
    // request(params).then(res => {
    //     let list = res.data.list || []
    //     let config = res.data.stat_conf || {}
    //     config.msg_reply_sec = config.msg_reply_sec / 60
    //     config.last_stat_date = moment(config.last_stat_time * 1000).format("YYYY-MM-DD HH:mm")
    //     this.config = config
    //     let numberFields = ['cst_no_all', 'cst_no_incr', 'chat_no', 'promoter_stf_no', 'promoter_cst_no', 'staff_msg_no', 'msg_no_all']
    //     list.map(item => {
    //         for (let field of numberFields) {
    //             !item[field] && (item[field] = 0)
    //         }
    //         item.create_time = formatDate(item.create_time)
    //         item.time_st_avg = secondToDate(item.time_st_avg)
    //         //item.reply_rate = computedRate(Number(item.reply_no_intime), Number(item.round_no))
    //         item.rate_avg = computedRate(item.rate_avg, 1)
    //         item.noreply_rate = `${(item.noreply_rate * 100).toFixed(2)}%`
    //     })
    //     const head = `员工昵称,总客户,新客户,单聊数,主动沟通,被动沟通,员工消息数,总消息数,未回复,未回复率,${config.msg_reply_sec}分钟回复率,平均首响\n`
    //     const fields = [
    //         "stf_name", "cst_no_all", "cst_no_incr", "chat_no", "promoter_stf_no", "promoter_cst_no", "staff_msg_no", "msg_no_all", "no_reply_chat_no", "noreply_rate", "rate_avg", "time_st_avg"
    //     ]
    //     tableToExcel(head, list, fields, "员工客户详情.csv");
    // }).finally(() => {
    //     this.exporting = false
    // })
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
