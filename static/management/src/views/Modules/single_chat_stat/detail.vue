<template>
    <div>
        <MainNavbar :title="[
           {name: '单聊统计', route: '/module/private-chat-stat/index'},
           {name: '员工单聊明细'}
        ]"/>
        <div class="zm-main-content">
            <div class="header">
                <div class="left">
                    <div class="item zm-user-info">
                        <img class="avatar"
                             style="width: 24px;height: 24px;"
                             :src="staffInfo.avatar || defaultAvatar"/>
                        <span class="name">{{ staffInfo.name }}</span>
                    </div>
                    <div class="hr"></div>
                    <div class="item">统计时间：{{ statDateFormat() }}</div>
                </div>
                <div class="right filter-box">
                    <div class="filter-item ml16">
                        <a-input
                            v-model="filterData.keyword"
                            allowClear
                            placeholder="请输入客户名称进行搜索"
                            @change="search"
                            style="width: 220px;"/>
                    </div>
                </div>
            </div>
            <a-tabs v-model:active="type" class="mt8">
                <a-tab-pane :key="1" tab="全部客户"></a-tab-pane>
                <a-tab-pane :key="2" tab="今日新增"></a-tab-pane>
                <a-tab-pane :key="3" tab="未回复"></a-tab-pane>
                <a-tab-pane :key="4" tab="主动沟通"></a-tab-pane>
                <a-tab-pane :key="4" tab="被动沟通"></a-tab-pane>
            </a-tabs>
            <div class="table-box">
                <a-table :loading="loading"
                         :data-source="list"
                         :columns="columns"
                         :pagination="pagination"
                         :scroll="{x: 1000}"
                         @change="tableChange"
                >
                    <template #minuteReplyRateTitle>
                        {{ msgReplySec }}分钟回复率
                    </template>
                    <template #userInfo="text,record">
                        <div class="wk-user-info">
                            <img class="avatar"
                                 style="width: 40px;height: 40px;"
                                 :src="record.avatar"/>
                            <div>
                                <div class="name">{{ record.follow_remark || record.name }}</div>
                                <div class="nickname">微信昵称：{{ record.name }}</div>
                            </div>
                        </div>
                    </template>
                    <template #replyStatus="text,record">
                        <span v-if="record.reply_status == -1 || !record.date_no">--</span>
                        <div class="reply-status" v-else-if="record.reply_status==1">
                            <CheckCircleFilled/>
                            <span>已回复</span>
                        </div>
                        <div class="reply-status no" v-else>
                            <ExclamationCircleFilled/>
                            <span>未回复</span>
                        </div>
                    </template>
                    <template #operate="text,record">
                        <a-button type="link"
                                  style="padding: 0;"
                                  :disabled="!record.date_no"
                                  @click="linkChat(record,1)">明细
                        </a-button>
                    </template>
                </a-table>
            </div>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref, reactive} from 'vue';
import {useRoute} from 'vue-router';
import dayjs from "dayjs";
import {message} from 'ant-design-vue';
import {ExclamationCircleFilled, CheckCircleFilled} from '@ant-design/icons-vue';
import MainNavbar from "@/components/mainNavbar.vue";

const defaultAvatar = require('@/assets/default-avatar.png');
const route = useRoute()
const type = ref(null)
const loading = ref(false)
const list = ref([])
const staffInfo = ref({
    name: "",
    avatar: "",
})
const totalMap = reactive({
    staff_trigger: 0,
    cst_trigger: 0,
})
const filterData = reactive({
    year: 1,
    month: 1,
    dateType: 1,
    dates: [],
    timeType: 1,
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

const columns = reactive([
    {
        title: "客户信息",
        dataIndex: "staff_name",
        width: 220,
        scopedSlots: {
            customRender: "userInfo"
        }
    },
    {
        title: "添加时间",
        dataIndex: "add_time",
        width: 150,
    },
    {
        title: "发起人",
        dataIndex: "promoter_type_text",
        width: 100,
    },
    {
        title: "员工发送消息数",
        dataIndex: "staff_msg_no",
        width: 140,
        sorter: true,
    },
    {
        title: "客户发送消息数",
        dataIndex: "cst_msg_no",
        width: 140,
        sorter: true,
    },
    {
        title: "回复状态",
        dataIndex: "reply_status",
        scopedSlots: {
            customRender: "replyStatus"
        },
        width: 116,
    },
    {
        title: "操作",
        dataIndex: "id",
        scopedSlots: {
            customRender: "operate"
        },
        width: 110,
    }
])

onMounted(() => {
    //type.value = route.query.type
    init()
})

function init() {
    search()
}

function statDateFormat() {
    return dayjs(route.query.filter_date).format("YYYY-MM-DD")
}

function search() {
    pagination.current = 1
    loadData()
    loadOtherTotal()
}

function getFilterParams() {
    let params = {
        page: pagination.current,
        size: pagination.pageSize,
        open_userid: route.query.open_userid,
        date_no: route.query.filter_date,
        valid_type: 1,
        promoter_type: type.value === 'staff_trigger' ? 0 : 1
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
    // loading.value = true
    // let params = getFilterParams()
    // let request = chatStatDetail
    // let dataItemFormat = item => item
    // if (this.MODE == 2) {
    //     if (params.promoter_type > -1) {
    //         params.promoter_type += 1
    //     }
    //     request = authModeChatStatDetail
    //     dataItemFormat = item => {
    //         item.promoter_type_text = item.promoter_type == 1 ? '员工' : '客户'
    //         item.open_userid = item.stf_userid
    //         item.finshed = false
    //         return item
    //     }
    // }
    //request(params).then(res => {
    //     let list = res.data.list || []
    //     let setNullFields = ['staff_msg_no', 'cst_msg_no', 'rate', 'time_st', 'promoter_type_text', 'un_reply_content', 'send_time']
    //     list.map(item => {
    //         item.add_time = moment(item.add_time * 1000).format("YY-MM-DD HH:mm")
    //         item.promoter_type_text = item.promoter_type == 0 ? '员工' : '客户'
    //         if (item.last_msg) {
    //             item.last_msg.content && formatMessage(item.last_msg)
    //             item.send_time = formatDate(Number(item.last_msg.msg_time || 0))
    //         }
    //         if (item.reply_status == -1) {
    //             item.time_st = "--"
    //             item.rate = "--"
    //         } else {
    //             item.time_st = secondToDate(item.time_st)
    //             item.rate = computedRate(Number(item.reply_no_intime), Number(item.round_no))
    //         }
    //         if (!item.date_no) {
    //             for (let field of setNullFields) {
    //                 item[field] = "--"
    //             }
    //         }
    //         item.staff_user_id = this.staffInfo['real_user_id'] ?? ''
    //         item = dataItemFormat(item)
    //     })
    //     list.value = list
    //     pagination.total = Number(res.data.count)
    //     totalMap[this.type] = this.pagination.total
    // }).finally(() => {
    //     loading.value = false
    // })
}

function loadOtherTotal() {
    let params = getFilterParams()
    params.page = 1
    params.size = 1
    // params.promoter_type = type === 'staff_trigger' ? 1 : 0
    // let request = chatStatDetail
    // if (this.MODE == 2) {
    //     if (params.promoter_type > -1) {
    //         params.promoter_type += 1
    //     }
    //     request = authModeChatStatDetail
    // }
    // request(params).then(res => {
    //     let total = Number(res.data.count)
    //     this.totalMap[this.type === 'staff_trigger' ? 'cst_trigger' : 'staff_trigger'] = total
    // })
}

function tableChange(p, _, sorter) {
    if (sorter.field && sorter.order) {
        columns.map(item => {
            if (item.dataIndex === sorter.field) {
                item.sortOrder = sorter.order
            } else {
                item.sortOrder = false
            }
        })
        filterData.order = `${sorter.field} ${sorter.order == 'ascend' ? 'ASC' : 'DESC'}`
    } else {
        columns.map(item => item.sortOrder = false)
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
</style>
