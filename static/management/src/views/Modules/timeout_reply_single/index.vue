<template>
    <div>
        <MainNav active="index"/>
        <div class="zm-main-content">
            <a-alert class="zm-custom-alert" type="info" show-icon>
                <template #message>
                    <div class="fw600">功能说明：</div>
                    <div>
                        当单聊的客户发送消息员工未回复时，按照规则，可设置给指定员工以及员工本人发送消息提醒，及时回复消息并处理
                    </div>
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
                        <template v-if="column.dataIndex === 'staff_user_list'">
                            <a-tooltip v-if="text.length > 10" :title="text">
                                <div class="zm-line-clamp1">{{ text }}</div>
                            </a-tooltip>
                            <span v-else>{{ text }}</span>
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
import MainNav from "@/views/Modules/timeout_reply_single/components/mainNav.vue";
import {delRuleInfo, disabledRule, enableRule, getRules} from "@/api/timeout-reply-single";
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
        title: "质检员工",
        dataIndex: "staff_user_names",
        width: 160,
    },
    {
        title: "质检时间",
        dataIndex: "check_time_text",
        width: 160,
    },
    {
        title: "提醒规则",
        dataIndex: "notice_rule_text",
        width: 160,
    },
    {
        title: "提醒员工",
        dataIndex: "notice_staff_names",
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
            item.staff_user_names = item.staff_user_list.map(i => i.name).toString()
            item.notice_staff_names = item.remind_staff_user_list.map(i => i.name)
            if (item.is_remind_staff_himself) {
                item.notice_staff_names.unshift('员工本人')
            }
            item.notice_staff_names = item.notice_staff_names.toString()
            item.check_time_text = formatCheckTime(item)
            item.notice_rule_text = `超过${item.timeout_value}分钟发送提醒`
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
        path: "/module/timeout-reply-single/store"
    })
}

function linkEdit(record) {
    router.push({
        path: "/module/timeout-reply-single/store",
        query: {
            rule_id: record.id
        }
    })
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
