<template>
    <div>
        <MainNavbar :title="[
            {name: '群聊统计', route: '/module/group-chat-stat/index'},
            {name: '员工群聊明细'}
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
                    <div class="item">群聊总数：{{ pagination.total }}</div>
                    <div class="hr"></div>
                    <div class="item">统计时间：{{ statDateFormat() }}</div>
                </div>
                <div class="right filter-box">
                    <div class="filter-item ml16">
                        <a-input
                            v-model="filterData.keyword"
                            allowClear
                            placeholder="请输入群聊名称进行搜索"
                            @change="search"
                            style="width: 220px;"/>
                    </div>
                </div>
            </div>
            <a-tabs v-model:active="activeTab">
                <a-tab-pane :key="1" tab="全部群聊"></a-tab-pane>
                <a-tab-pane :key="2" tab="今日新增群聊"></a-tab-pane>
                <a-tab-pane :key="3" tab="活跃群聊"></a-tab-pane>
                <a-tab-pane :key="4" tab="已回复群聊"></a-tab-pane>
                <a-tab-pane :key="4" tab="未回复群聊"></a-tab-pane>
            </a-tabs>
            <div class="table-box">
                <a-table :loading="loading"
                         :data-source="list"
                         :columns="columns"
                         :pagination="pagination"
                         @change="tableChange"
                >
                    <template #replyRate>
                        {{ msgReplySec }}分钟回复率
                    </template>
                    <template #replyStatus="text,record">
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
                    <template #operate="text,record">
                        <a @click="linkChat(record)">明细</a>
                        <a class="ml16 group_scan_code"
                           @click.stop="(e)=>{showGroupCode(record,e)}">进群</a>
                    </template>
                </a-table>
            </div>
            <!--        <GroupCode ref="groupCodeRef" class="custom-group-code"></GroupCode>-->
        </div>
    </div>
</template>

<script setup>
import {ref, reactive} from 'vue';
import {useRoute} from 'vue-router';
import dayjs from "dayjs";
import {message} from 'ant-design-vue';
import {ExclamationCircleFilled, CheckCircleFilled} from '@ant-design/icons-vue';
import MainNavbar from "@/components/mainNavbar.vue";

const defaultAvatar = require('@/assets/default-avatar.png');
const route = useRoute()
const loading = ref(false)
const staffInfo = ref({
    name: '',
    avatar: '',
})
const activeTab = ref(1)
const list = ref([])
const columns = [
    {
        title: "群聊名称",
        dataIndex: "room_name",
        width: 300,
    },
    {
        title: "群主",
        dataIndex: "stf_name",
        width: 120,
    },
    {
        title: "员工发送消息数",
        dataIndex: "staff_msg_no",
    },
    {
        title: "客户发送消息数",
        dataIndex: "cst_msg_no",
    },
    // {
    //     // title: "3分钟回复率",
    //     dataIndex: "rate",
    //     slots: {
    //         title: "replyRate"
    //     },
    // },
    // {
    //     title: "首次回复时长",
    //     dataIndex: "time_st",
    // },
    {
        title: "回复状态",
        dataIndex: "reply_status",
        width: 120,
        scopedSlots: {
            customRender: "replyStatus"
        },
    },
    {
        title: "发起人",
        dataIndex: "promoter_type_text",
        width: 100,
    },
    {
        title: "操作",
        dataIndex: "id",
        width: 120,
        scopedSlots: {
            customRender: "operate"
        }
    },
]

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

function createdFunc() {
    this.init()
}

function init() {
    this.loadData()
}

function statDateFormat() {
    return dayjs(route.query.filterDate).format("YYYY-MM-DD")
}

function search() {
    pagination.current = 1
    loadData()
}

function loadData() {
    loading.value = true
    let data = {
        page: pagination.current,
        size: pagination.pageSize,
        open_userid: route.query.openUserid,
        date_no: route.query.filterDate,
        is_active: 1,//活跃群聊
    }
    filterData.keyword = filterData.keyword.trim()
    if (filterData.keyword) {
        data.keyword = filterData.keyword
    }
    // let request = staffGroups
    // if (this.MODE == 2) {
    //     request = authModeStaffGroups
    // }
    // request(data).then(res => {
    //     let list = res.data.list || []
    //     list.map(item => {
    //         if (item.reply_status == -1) {
    //             item.time_st = "--"
    //             item.promoter_type_text = "--"
    //             item.rate = "--"
    //         } else {
    //             item.time_st = secondToDate(item.time_st)
    //             item.promoter_type_text = item.promoter_type == 0 ? '员工' : '客户'
    //             item.rate = computedRate(Number(item.reply_no_intime), Number(item.round_no))
    //         }
    //     })
    //     this.list = list
    //     this.pagination.total = Number(res.data.count)
    //     this.staffInfo = res.data.staff_info || {}
    // }).finally(() => {
    //     this.loading = false
    // })
}

function tableChange(p) {
    pagination = Object.assign(pagination, p)
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
</style>
