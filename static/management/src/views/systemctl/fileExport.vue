<template>
    <MainLayout title="文件导出记录">
        <div class="zm-main-content">
            <a-alert type="info" show-icon message="您可以在这里查看您的导出任务，并下载导出文件；文件导出后系统只会保留7天，7天后将自动删除，请及时下载文件"></a-alert>
            <!-- <div class="zm-filter-box mt24">
                <div class="zm-filter-item">
                    <div class="zm-filter-label">导出类型：</div>
                    <a-select v-model:value="filterData.type"
                              placeholder="请选择导出类型"
                              style="width: 240px"
                              @change="search">
                        <a-select-option
                            v-for="item in types"
                            :value="item.value"
                            :key="item.value">
                            {{ item.title }}
                        </a-select-option>
                    </a-select>
                </div>
            </div> -->
            <a-table class="mt24" :dataSource="list" :columns="columns" :pagination="pagination" :loading="loading"
                @change="tableChange">
                <template #bodyCell="{ column, text, record, index }">
                    <template v-if="column.dataIndex === 'task_title'">
                        <div class="zm-line-clamp2">{{ text }}</div>
                    </template>
                    <template v-else-if="column.dataIndex === 'export_type'">
                        <span v-if="text === 1">会话存档</span>
                        <span v-else>{{ text }}</span>
                    </template>
                    <template v-else-if="column.dataIndex === 'created_at'">
                        <span>{{ formatDateTime(text) }}</span>
                    </template>
                    <template v-else-if="column.dataIndex === 'state'">
                        <div class="status" v-if="text === 1">
                            <img src="@/assets/image/status-wait.png" /> 未开始
                        </div>
                        <div class="status" v-else-if="text === 2">
                            <img src="@/assets/image/status-run.png" />导出中
                        </div>
                        <div class="status" v-else-if="text === 3">
                            <img src="@/assets/image/status-ok.png" /> 已完成
                        </div>
                        <div class="status" v-else-if="text === 4">
                            <img src="@/assets/image/status-expired.png" /> 导出失败
                        </div>
                        <div class="status" v-else-if="text === 5">
                            <img src="@/assets/image/status-cancel.png" /> 已取消
                        </div>
                        <div class="status" v-else-if="text === 0">
                            <img src="@/assets/image/status-expired.png" /> 已删除
                        </div>
                        <span v-else>--</span>
                    </template>
                    <template v-else-if="column.dataIndex === 'id'">
                        <a class="mr16" v-if="record.state === 3" @click="download(record)">下载文件</a>
                        <a class="mr16" v-if="[3, 4, 5, 0].includes(record.state)" @click="del(record, index)">删除记录</a>
                        <a v-if="[1, 2].includes(record.state)" @click="cancel(record)">取消导出</a>
                    </template>
                </template>
            </a-table>
        </div>
    </MainLayout>
</template>

<script setup>
import { onMounted, ref, reactive } from 'vue';
import { Modal, message } from 'ant-design-vue';
import dayjs from 'dayjs';
import MainLayout from "@/components/mainLayout.vue";
import { getExportList, stateChange } from "@/api/session";

const columns = ref([
    {
        title: "导出来源",
        dataIndex: "export_type",
        width: 120,
    },
    {
        title: "文件名",
        dataIndex: "task_title",
        width: 320,
    },
    {
        title: "状态",
        dataIndex: "state",
        width: 200,
    },
    {
        title: "操作人",
        dataIndex: "create_user_info",
        width: 150,
    },
    {
        title: "导出时间",
        dataIndex: "created_at",
        width: 200,
    },
    {
        title: "操作",
        dataIndex: "id",
        width: 180,
    },
])
const loading = ref(false)
const list = ref([])
const types = ref([])
const filterData = reactive({
    type: undefined,
})

const pagination = reactive({
    current: 1,
    pageSize: 10,
    total: 0,
    pageSizeOptions: ['10', '20', '50', '100'],
    showQuickJumper: true,
    showSizeChanger: true
})

onMounted(() => {
    loadData()
})

const search = () => {
    pagination.current = 1
    loadData()
}

const loadData = async () => {
    loading.value = true
    try {
        const params = {
            page: pagination.current,
            size: pagination.pageSize,
            ...(filterData.type && { type: filterData.type })
        }
        const res = await getExportList(params)
        if (res.error_code === 200) {
            list.value = res.data.items || []
            pagination.total = res.data.total || 0
            types.value = res.data.types || []
        }
    } catch (error) {
        message.error('获取导出列表失败')
    } finally {
        loading.value = false
    }
}

const tableChange = p => {
    pagination.current = p.current
    pagination.pageSize = p.pageSize
    loadData()
}

const cancel = record => {
    Modal.confirm({
        title: '提示',
        content: '确认取消此导出任务',
        onOk: async () => {
            const loadClose = message.loading('正在取消')
            try {
                const res = await stateChange({
                    task_id: record.id,
                    state: 5 // 取消状态
                })
                if (res.error_code === 200) {
                    message.success('取消成功')
                    loadData() // 重新加载数据
                } else {
                    message.error(res.message || '取消失败')
                }
            } catch (error) {
                message.error('取消失败')
            } finally {
                loadClose()
            }
        }
    })
}

const download = record => {
    if (record.state != 3) {
        return message.error('未导出完成')
    }
    if (record.download_url === "") {
        return message.error('导出异常')
    }
    window.location.href = record.download_url
}

const del = (record, index) => {
    Modal.confirm({
        title: '提示',
        content: '确认删除此记录',
        onOk: async () => {
            const loadClose = message.loading('正在删除')
            try {
                const res = await stateChange({
                    task_id: record.id,
                    state: 0 // 删除状态
                })
                if (res.error_code === 200) {
                    message.success('删除成功')
                    loadData() // 重新加载数据
                } else {
                    message.error(res.message || '删除失败')
                }
            } catch (error) {
                message.error('删除失败')
            } finally {
                loadClose()
            }
        }
    })
}

const formatDateTime = (dateTime) => {
    if (!dateTime) return '--'
    return dayjs(dateTime).format('YYYY-MM-DD HH:mm:ss')
}
</script>

<style scoped lang="less">
.status {
    font-size: 14px;
    font-weight: 400;
    color: rgba(0, 0, 0, 0.65);
    display: flex;
    align-items: center;
    white-space: nowrap;

    img {
        width: 16px;
        height: 16px;
        margin-right: 4px;
    }
}

.zm-main-content {
    :deep(.ant-table-wrapper .ant-table) {
        min-height: calc(100vh - 302px);
    }
}
</style>
