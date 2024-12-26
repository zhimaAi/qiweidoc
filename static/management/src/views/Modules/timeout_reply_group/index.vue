<template>
    <div>
        <MainNav active="index"/>
        <div class="zm-main-content">
            <a-alert class="zm-custom-alert" type="info" show-icon>
                <template #message>
                    <div class="fw600">功能说明：</div>
                    <div>当群里的客户发送消息员工未回复时，可设置给群主，指定员工或者是群内员工发送未回复提醒，员工可及时回复并处理</div>
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
                        <template v-if="column.dataIndex === 'group_name'">
                            <a-tooltip :title="record.group_name">
                                <div class="zm-line-clamp2">{{record.group_name}}</div>
                            </a-tooltip>
                        </template>
                        <template v-else-if="column.dataIndex === 'enabled'">
                            <a-switch v-model:checked="record.enabled"
                                      @change="statusChange(record)"
                                      checked-children="开"
                                      un-checked-children="关"/>
                        </template>
                        <template v-else-if="column.dataIndex === 'check_time_text'">
                            <a-tooltip v-if="Array.isArray(text)">
                                <template #title>
                                    <div v-for="(c,i) in text" :key="i">{{ c }}</div>
                                </template>
                                <div class="zm-line-clamp1" v-html="text.join('<br/>')"></div>
                            </a-tooltip>
                            <span v-else>{{ text }}</span>
                        </template>
                        <template v-else-if="column.dataIndex === 'notice_rule'">
                            <a-tooltip>
                                <template #title>
                                    <div v-for="(r,i) in record.notice_rules" :key="i">{{r.rule}}</div>
                                </template>
                                <div v-for="(r,i) in record.notice_rules" :key="i" class="zm-line-clamp1">{{r.rule}}</div>
                            </a-tooltip>
                        </template>
                        <template v-else-if="column.dataIndex === 'notice_staff'">
                            <a-tooltip>
                                <template #title>
                                    <div v-for="(r,i) in record.notice_rules" :key="i">规则{{i+1}}：{{r.staff.join("、")}}</div>
                                </template>
                                <div v-for="(r,i) in record.notice_rules" :key="i" class="zm-line-clamp1">规则{{i+1}}：{{r.staff.join("、")}}</div>
                            </a-tooltip>
                        </template>
                        <template v-else-if="column.dataIndex === 'operate'">
                            <a @click="linkEdit(record)">编辑</a>
                            <a class="ml16" @click="delRule(record)">删除</a>
                        </template>
                    </template>
                </a-table>
            </div>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref, reactive, h} from 'vue';
import {useRouter} from 'vue-router';
import {Modal, message} from 'ant-design-vue';
import {PlusOutlined} from '@ant-design/icons-vue';
import MainNav from "@/views/Modules/timeout_reply_group/components/mainNav.vue";
import {delRuleInfo, disabledRule, enableRule, getRules} from "@/api/timeout-reply-group";
import {weekToText} from '@/utils/tools';
import {formatCheckTime} from "@/common/timeoutReply";

const router = useRouter()
const loading = ref(false)
const columns = ref([
    {
        title: "规则名称",
        dataIndex: "name",
        width: 160,
    },
    {
        title: "群聊",
        dataIndex: "group_name",
        width: 160,
    },
    {
        title: "质检时间",
        dataIndex: "check_time_text",
        width: 160,
    },
    {
        title: "提醒规则",
        dataIndex: "notice_rule",
        width: 160,
    },
    {
        title: "提醒员工",
        dataIndex: "notice_staff",
        width: 160,
    },
    {
        title: "状态",
        dataIndex: "enabled",
        width: 110,
    },
    {
        title: "操作",
        dataIndex: "operate",
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

onMounted(() => {
    init()
})

function init() {
    list.value = []
    pagination.current = 1
    pagination.total = 0
    loadData()
}

function loadData() {
    loading.value = true
    getRules({
        page: pagination.current,
        size: pagination.pageSize
    }).then(res => {
        let items = res.data.items || []
        items.map(item => {
            item.group_name = formatGroupName(item)
            item.check_time_text = formatCheckTime(item)
            item.notice_rules = formatNoticeRule(item)
        })
        list.value = items
        pagination.total = Number(res.data.total)
    }).finally(() => {
        loading.value = false
    })
}

function tableChange(p) {
    Object.assign(pagination, p)
    loadData()
}

function linkAddRule() {
    router.push({
        path: "/module/timeout-reply-group/store"
    })
}

function linkEdit(record) {
    router.push({
        path: "/module/timeout-reply-group/store",
        query: {
            rule_id: record.id
        }
    })
}

function formatGroupName(data) {
    let names
    switch (Number(data.group_type.value)) {
        case 1:
            names = data.group_chat_id_detail_list.map(item => {
                return item.name || "未命名客户群"
            })
            return names.toString()
        case 2:
            names = data.group_staff_user_list.map(i => i.name)
            return `当员工${names.toString()}在群内时关注该群`
        case 3:
            if (data?.group_keyword_list?.include_list?.length > 0) {
                names = `当群名包含关键词：”${data.group_keyword_list.include_list.toString()}“`
            }
            if (data?.group_keyword_list?.exclude_list.length > 0) {
                names += `且不包含关键词：”${data.group_keyword_list.exclude_list.toString()}“时`
            }
            names += "关注该群"
            return names
    }
}

function formatNoticeRule(data) {
    let staffs = [], res = []
    data.remind_rules.map(item => {
        staffs = []
        if (item.is_remind_staff_designation) {
            let names = item.designate_remind_user_list.map(i => i.name || '...')
            staffs.push(names.toString())
        }
        if (item.is_remind_group_member) {
            staffs.push("群内员工")
        }
        res.push({
            rule: `超过${item.timeout_value}分钟发送提醒`,
            staff: staffs
        })
    })
    return res
}

function statusChange(record) {
    let status, request
    if (record.enabled) {
        request = enableRule
        status = '开启'
    } else {
        request = disabledRule
        status = '关闭'
    }
    const cancel = () => record.enabled = !record.enabled
    Modal.confirm({
        title: '提示',
        content: `确认${status}该规则`,
        onOk: () => {
            request(record.id).then(res => {
                message.success(`已${status}`)
                loadData()
            }).catch(() => {
                cancel()
            })
        },
        onCancel: () => {
            cancel()
        }
    })
}

function delRule(record) {
    Modal.confirm({
        title: '提示',
        content: '确认删除该规则？',
        onOk: () => {
            delRuleInfo(record.id).then(res => {
                message.success('操作完成')
                loadData()
            })
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
