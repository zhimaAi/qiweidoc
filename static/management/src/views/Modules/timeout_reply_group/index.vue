<template>
    <div>
        <MainNav active="index"/>
        <div class="zm-main-content">
            <a-alert class="zm-custom-alert" type="info" show-icon>
                <template #message>
                    <div class="fw600">功能说明：</div>
                    <div>当群里的客户发送消息员工未回复时，按照群分配的规则，可设置给群主，指定员工或者是群内员工或者是分配员工发送未回复提醒，员工可及时回复并处理</div>
                </template>
            </a-alert>
            <div class="mt16">
                <a-button type="primary" :icon="h(PlusOutlined)" @click="linkAddRule">新增规则</a-button>
                <a-table
                    class="mt8"
                    :loading="loading"
                    :data-source="list"
                    :columns="columns"
                    :pagination="pagination"
                    @change="tableChange"
                    :scroll="{x: 1400}"
                >
                    <template #bodyCell="{column, text, record}">
<!--                        <template #checkStaff="text,record">-->
<!--                            <a-tooltip v-if="text.length > 10" :title="text">-->
<!--                                <div class="zm-line-clamp1">{{ text }}</div>-->
<!--                            </a-tooltip>-->
<!--                            <span v-else>{{ text }}</span>-->
<!--                        </template>-->
<!--                        <template #checkTime="text,record">-->
<!--                            <a-tooltip v-if="Array.isArray(text)">-->
<!--                                <template #title>-->
<!--                                    <div v-for="(c,i) in text" :key="i">{{ c }}</div>-->
<!--                                </template>-->
<!--                                <div class="zm-line-clamp1" v-html="text.join('<br/>')"></div>-->
<!--                            </a-tooltip>-->
<!--                            <span v-else>{{ text }}</span>-->
<!--                        </template>-->
<!--                        <template #noticeRule="text,record">-->
<!--                            <a-tooltip>-->
<!--                                <template #title>-->
<!--                                    <div v-for="(r,i) in record.notice_rule_text" :key="i">{{ r.rule }}</div>-->
<!--                                </template>-->
<!--                                <div v-for="(r,i) in record.notice_rule_text" :key="i">{{ r.rule }}</div>-->
<!--                            </a-tooltip>-->
<!--                        </template>-->
<!--                        <template #notifyStaff="text,record">-->
<!--                            <a-tooltip>-->
<!--                                <template #title>-->
<!--                                    <div v-for="(r,i) in record.notice_rule_text" :key="i">-->
<!--                                        规则{{ i + 1 }}：{{ r.staff.join("、") }}-->
<!--                                    </div>-->
<!--                                </template>-->
<!--                                <div v-for="(r,i) in record.notice_rule_text" :key="i">-->
<!--                                    规则{{ i + 1 }}：{{ r.staff.join("、") }}-->
<!--                                </div>-->
<!--                            </a-tooltip>-->
<!--                        </template>-->
<!--                        <template #operate="text,record">-->
<!--                            <a @click="linkEdit(record)">编辑</a>-->
<!--                            <a class="ml16" @click="del(record)">删除</a>-->
<!--                        </template>-->
                    </template>
                </a-table>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref, reactive, h} from 'vue';
import {useRouter} from 'vue-router';
import {Modal, message} from 'ant-design-vue';
import {PlusOutlined} from '@ant-design/icons-vue';
import MainNav from "@/views/Modules/timeout_reply_group/components/mainNav.vue";

const router = useRouter()
const loading = ref(false)
const columns = ref([
    {
        title: "规则名称",
        dataIndex: "name",
        width: 180,
    },
    {
        title: "群聊",
        dataIndex: "group_name",
        scopedSlots: {
            customRender: "groupName"
        },
        width: 180,
    },
    {
        title: "质检时间",
        dataIndex: "check_time_text",
        scopedSlots: {
            customRender: "checkTime"
        },
        width: 180,
    },
    {
        title: "提醒规则",
        dataIndex: "notice_rule_text",
        scopedSlots: {
            customRender: "noticeRule"
        },
        width: 180,
    },
    {
        title: "提醒员工",
        dataIndex: "notice_staff_names",
        scopedSlots: {
            customRender: "notifyStaff"
        },
        width: 180,
    },
    {
        title: "操作",
        dataIndex: "id",
        scopedSlots: {
            customRender: "operate"
        },
        width: 110,
        fixed: "right",
    }
])
const list = ref([])
const pagination = reactive({
    total: 0,
    current: 1,
    pageSize: 50,
    showSizeChanger: true,
    pageSizeOptions: ['50', '100', '300', '500'],
})

function init() {
    this.loadData()
}

function loadData() {
    //loading.value = true
    // list({
    //     category: 1
    //     page: this.pagination.current
    //     size: this.pagination.pageSize
    // }).then(res =>{
    //     let list = res.data.list || []
    //     list.map(item => {
    //         item.check_staff_names = item.check_staff_list.map(i => i.name).toString()
    //         item.check_time_text = formatCheckTime(item)
    //         item.notice_rule_text = formatNoticeRule(item)
    //     })
    //     this.list = list
    //     this.pagination.total = Number(res.data.pagination.total)
    // }).finally(() => {
    //     loading.value = false
    // })
}

function formatCheckTime(data) {
    switch (Number(data.check_type)) {
        case 0:
            return "全天"
        case 1:
            let res = []
            let weeks, times
            data.check_time = JSON.parse(data.check_time)
            data.check_time.map(item => {
                weeks = [],times = []
                item.week.map(w => weeks.push(weekToText(w)))
                item.range.map(r => times.push(`${r[0]}~${r[1]}`))
                res.push(`${weeks.join("、")} ${times.join(" ")}`)
            })
            return res
        case 2:
            return "工作时间"
    }
}

function tableChange(p) {
    Object.assign(pagination, p)
    loadData()
}

function linkAddRule() {
    router.push({
        path: "/timeoutReplyNotify/privateChat/add?navKey=timeoutReplyNotifyPrivate"
    })
}

function linkEdit(record) {
    router.push({
        path: "/timeoutReplyNotify/privateChat/edit?navKey=timeoutReplyNotifyPrivate&rule_id="+record._id
    })
}

function del(record) {
    Modal.$confirm({
        title: "提示",
        content: "确认删除该规则？",
        onOk: () => {
            // del({id: record._id}).then(res => {
            //     message.success("操作完成")
            //     loadData()
            // })
        }
    })
}
</script>

<style scoped lang="less">
.fw600 {
    font-weight: 600;
}
.zm-main-content {
    min-height: 86vh;
}
</style>
