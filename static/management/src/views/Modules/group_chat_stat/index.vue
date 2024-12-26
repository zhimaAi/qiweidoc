<template>
    <div>
        <MainNav active="index"/>
        <div class="zm-main-content">
            <div>
                <a @click="showStaffModal">设置统计员工</a>
                <span v-if="statStaffs.length > 0" class="zm-tip-info">（已设置{{ statStaffs.length }}名员工）</span>
            </div>
            <div class="filter-box mt16">
                <div class="filter-item">
                    <span class="item-label">选择时间：</span>
                    <div class="item-body">
                        <a-select v-model:value="filterData.year"
                                  @change="changeDefaultValue"
                                  style="width: 80px;">
                            <a-select-option :value="1">今年</a-select-option>
                            <a-select-option :value="2">去年
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
                        <!--                    <ASelectStaff :key="filterStaffKey" :maxTagCount="1" @change="filterStaffChange"/>-->
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
                            {{ statDate || "--" }} <a class="ml16" @click="updateStatData">
                            <RedoOutlined/>
                            更新</a>
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
                        <template v-if="'group_no' === column.dataIndex">
                            <a-tooltip>
                                <template #title>
                                    <span>负责群：该员工所在的群聊数</span><br>
                                    <span>今日新增：今日新加入的群聊</span>
                                </template>
                                负责总群数/今日新增
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'active_group_no' === column.dataIndex">
                            <a-tooltip>
                                <template #title>
                                    <span>活跃群：客户发了消息的群</span><br>
                                    <span>回复群：活跃群聊中，回复了消息的群聊</span>
                                </template>
                                活跃群/回复群
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'staff_msg_no' === column.dataIndex">
                            <a-tooltip>
                                <template #title>
                                    <span>总消息：员工所在的群聊的全部员工发送的消息</span><br>
                                    <span>员工发送：员工所在的群聊中该员工发送的消息数</span>
                                </template>
                                总消息/员工发送
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'no_reply_group_no' === column.dataIndex">
                            <a-tooltip title="员工所在的群聊活跃群聊中未回复消息的群聊数以及占比">
                                未回复/未回复率
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'at_rate_avg' === column.dataIndex">
                            <a-tooltip
                                :title="`${config.group_at_msg_reply_sec}分钟内回复的总轮次/群的总轮次之和，这里轮次是客户发起，员工回复算一轮`">
                                @后{{ config.group_at_msg_reply_sec }}分钟回复率
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                        <template v-else-if="'rate_avg' === column.dataIndex">
                            <a-tooltip
                                :title="`${config.msg_reply_sec}分钟内回复的总轮次/群的总轮次之和，这里轮次是客户发起，所属员工回复算一轮`">
                                {{ config.msg_reply_sec }}分钟回复率
                                <QuestionCircleFilled/>
                            </a-tooltip>
                        </template>
                    </template>

                    <template #bodyCell="{column, text, record}">
                        <template v-if="'at_rate_avg' === column.dataIndex">
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
                            <a v-else @click="linkPath('/workQualityStat/replyRateDetail',record)">{{ text }}</a>
                        </template>
                        <template v-else-if="'rate_avg' === column.dataIndex">
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
                            <a v-else @click="linkPath('/workQualityStat/replyRateDetail',record)">{{ text }}</a>
                        </template>
                        <template v-else-if="'groupTotal' === column.dataIndex">
                            <a @click="linkPath('/workQualityStat/staffAllOrTodayGroup',record)">{{ text }} / {{ record.today_allot }}</a>
                        </template>
                        <template v-else-if="'active_group_no' === column.dataIndex">
                            <a @click="linkPath('/workQualityStat/staffActiveReplyGroup',record)">{{ text }} / {{ record.reply_group_no }}</a>
                        </template>
                        <template v-else-if="'staff_msg_no' === column.dataIndex">
                            {{ record.all_stf_msg_no }} / {{ record.staff_msg_no }}
                        </template>
                        <template v-else-if="'no_reply_group_no' === column.dataIndex">
                            <a @click="linkPath('/workQualityStat/staffUnreplyGroup',record)">{{ text }}</a> /
                            {{ record.noreply_rate }}
                        </template>
                    </template>
                </a-table>
            </div>
        </div>
        <a-modal title="添加统计员工" v-model="staffModal.visible" @ok="saveStatStaff"
                 :confirm-loading="staffModal.saving">
            <a-alert type="info"
                     message="添加统计的员工若取消统计，当天的统计结果会被删除，取消的时间内之后都不会统计，重新添加从添加的那天才统计"/>
            <div class="stat-staff-modal mt16">
                <div class="form-item">
                    <span class="item-tit">统计员工：</span>
                </div>
            </div>
        </a-modal>
    </div>
</template>

<script setup>
import {ref, reactive, onMounted} from 'vue';
import {useRouter} from 'vue-router';
import dayjs from 'dayjs';
import {message} from 'ant-design-vue';
import {RedoOutlined, QuestionCircleFilled} from '@ant-design/icons-vue';
import MainNav from "@/views/Modules/group_chat_stat/components/mainNav.vue";

const router = useRouter()
const columns = [
    {
        title: "所属员工",
        dataIndex: "name",
        width: 120,
    },
    {
        //title: "所在总群数/今日新增",
        dataIndex: "group_no",
        width: 80,
    },
    {
        //title: "活跃群聊数",
        dataIndex: "active_group_no",
        width: 80,
    },
    {
        //title: "发送消息数",
        dataIndex: "staff_msg_no",
        width: 80,
    },
    {
        //title: "未回复群聊",
        dataIndex: "no_reply_group_no",
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
]
const defaultDate = dayjs()
const filterDataDefault = {
    year: 1,
    month: 1,
    dateType: 1,
    date: undefined,
    staffs: [],
    order: ""
}
const loading = ref(false)
const updateing = ref(false)
const exporting = ref(false)
const statStaffs = ref([])
const statDate = ref('')
const filterStaffKey = ref(1)
const list = ref([])
const whetherStatCurrentDate = ref(true)
const filterData = reactive({
    ...filterDataDefault
})
const config = reactive({
    group_at_msg_reply_sec: "",
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
const staffModal = reactive({
    visible: false,
    saving: false,
    staffs: [],
})

onMounted(() => {
    init()
})

async function init() {
    filterDataInit()
    loadData()
    loadStatStaff()
}

function loadStatStaff() {
    // getStatStaffs().then(res => {
    //     let list = res.data || []
    //     list.map(item => {
    //         item.staff_id = item._id
    //     })
    //     statStaffs.value = list
    // })
}

function filterDataInit() {
    filterData.year = 1
    filterData.month = Number(dayjs().format("MM"))
}

function filterStaffChange(staff) {
    filterData.staffs = staff
    search()
}

function reset() {
    filterData = Object.assign(data, filterDataDefault);
    filterStaffKey.value += 1
    filterDataInit()
    search()
}

function search() {
    pagination.current = 1
    loadData()
}

function loadData() {
    //loading.value = true
    let params = getFilterData()
    whetherStatCurrentDate.value = whetherStatCurrentDateRun(params.date_no)
    // groupMemberStat(params).then(res => {
    //     statDate = dayjs(res.data.statistic_time * 1000).format("YYYY-MM-DD HH:mm")
    //     let list = res.data.list || []
    //     let config = res.data.stat_conf || {}
    //     config.group_at_msg_reply_sec = config.group_at_msg_reply_sec / 60
    //     config.msg_reply_sec = config.msg_reply_sec / 60
    //     config.last_stat_date = moment(config.last_stat_time * 1000).format("YYYY-MM-DD HH:mm")
    //     this.config = config
    //     list.map(item => {
    //         item.msg_total = Number(item.cst_msg_no) + Number(item.staff_msg_no)
    //         item.rate_avg = computedRate(item.rate_avg, 1)
    //         item.at_rate_avg = computedRate(item.at_rate_avg, 1)
    //         item.reply_group_no = Number(item.active_group_no) - Number(item.no_reply_group_no)
    //         if (item.noreply_rate > 0) {
    //             item.noreply_rate = (item.noreply_rate * 100).toFixed(2)+'%'
    //         }
    //     })
    //     this.list = list
    //     this.pagination.total = Number(res.data.count)
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
    if (filterData.staffs.length) {
        params.user_ids = filterData.staffs.map(i => i.user_id).toString()
    }
    if (filterData.order) {
        params.order = filterData.order
    }
    return params
}

function tableChange(pagination, _, sorter) {
    // if (sorter.field && sorter.order) {
    //     columns.value.map(item => {
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
    // this.pagination = pagination
    // this.loadData()
}

function disabledDate(current) {
    let year = filterData.year == 1 ? Number(moment().format("YYYY")) : Number(moment().subtract(1, "year").format("YYYY"))
    return Number(current.format("YYYY")) != year || Number(current.format("MM")) != filterData.month
}

function dateChange() {
    filterData.dateType = 0
    search()
}

function dateTypeChange() {
    if (filterData.dateType === 0) {
        $refs.customDateRef.focus()
        filterData.date = moment()
        search()
    } else {
        filterData.date = undefined
        filterDataInit()
        search()
    }
}

function changeDefaultValue() {
    let year = filterData.year == 1 ? Number(moment().format("YYYY")) : Number(moment().subtract(1, "year").format("YYYY"))
    let month = filterData.month < 10 ? '0' + filterData.month : filterData.month
    filterData.date = moment(`${year}-${month}-01`)
    filterData.dateType = 0
    search()
}

function linkPath(path, record) {
    const routeUrl = router.resolve({
        path: path,
        query: {
            staff_user_id: record.staff_user_id,
            filter_date: getFilterData().date_no || "",
            group_at_msg_reply_sec: config.group_at_msg_reply_sec,
            msg_reply_sec: config.msg_reply_sec,
        }
    })
    window.open(routeUrl.href, '_blank');
}

function disabledLastYear() {
    return dayjs().format("YYYY") <= 2024
}

function updateStatData() {
    if (updateing.value) {
        return
    }
    updateing.value = true
    // groupStatOnce().then(res => {
    //     this.search()
    // }).finally(() => {
    //     this.updateing = false
    // })
}

function exportData() {
    if (pagination.total < 1) {
        return message.error("暂无数据")
    }
    exporting.value = true
    let params = getFilterData()
    params.size = 5000
    // groupMemberStat(params).then(res => {
    //     let list = res.data.list || []
    //     list.map(item => {
    //         item.msg_total = Number(item.cst_msg_no) + Number(item.staff_msg_no)
    //         item.rate_avg = computedRate(item.rate_avg, 1)
    //         item.at_rate_avg = computedRate(item.at_rate_avg, 1)
    //         item.reply_group_no = Number(item.active_group_no) - Number(item.no_reply_group_no)
    //         if (item.noreply_rate > 0) {
    //             item.noreply_rate = (item.noreply_rate * 100).toFixed(2)+'%'
    //         }
    //     })
    //     const head =  `员工,负责群,今日新增,活跃群,回复群,员工消息,总消息,未回复,未回复率,@后${ this.config.group_at_msg_reply_sec }分钟回复率,${this.config.msg_reply_sec}分钟回复率\n`
    //     const fields = [
    //         "name", "group_no", "today_allot", "active_group_no", "reply_group_no","staff_msg_no", "msg_total", "no_reply_group_no", "noreply_rate", "at_rate_avg", "rate_avg"
    //     ]
    //     tableToExcel(head, list, fields, "员工群聊详情.csv");
    // }).finally(() => {
    //     exporting.value = false
    // })
}

function showStaffModal() {
    staffModal.staffs = this.statStaffs
    staffModal.visible = true
}

function statStaffChange(val) {
    staffModal.staffs = val
}

function saveStatStaff() {
    staffModal.saving = true
    // let userids = this.staffModal.staffs.map(i => i.user_id).filter(Boolean).join(',')
    // saveStatStaff({
    //     user_ids: userids
    // }).then(res => {
    //     this.statStaffs = this.staffModal.staffs
    //     this.$message.success('已保存')
    //     this.staffModal.visible = false
    // }).finally(() => {
    //     this.staffModal.saving = false
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
