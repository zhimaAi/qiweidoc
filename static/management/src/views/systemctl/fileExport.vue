<template>
    <MainLayout title="文件导出记录">
        <div class="zm-main-content">
            <a-alert type="info" show-icon
                     message="您可以在这里查看您的导出任务，并下载导出文件；文件导出后系统只会保留7天，7天后将自动删除，请及时下载文件"></a-alert>
            <div class="zm-filter-box mt24">
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
            </div>
            <a-table class="mt24"
                     :dataSource="list"
                     :columns="columns"
                     :pagination="pagination"
                     @change="tableChange">
                <template #bodyCell="{column, text, record, index}">
                    <a-tooltip v-if="column.dataIndex === 'file_name'" :title="text">
                        <div class="zm-line-clamp2">{{ text }}</div>
                    </a-tooltip>
                    <template v-else-if="column.dataIndex === 'state'">
                        <div class="status" v-if="text === 1">
                            <img src="@/assets/image/status-wait.png"/> 排队中
                        </div>
                        <div class="status" v-else-if="text === 2">
                            <img src="@/assets/image/status-run.png"/>导出中
                        </div>
                        <div class="status" v-else-if="text === 3">
                            <img src="@/assets/image/status-ok.png"/> 已完成
                        </div>
                        <div class="status" v-else-if="text === 4">
                            <img src="@/assets/image/status-expired.png"/> 已过期
                        </div>
                        <div class="status" v-else-if="text === 5">
                            <img src="@/assets/image/status-cancel.png"/> 已取消
                        </div>
                        <div class="status" v-else-if="text === 6">
                            <img src="@/assets/image/status-expired.png"/> 导出失败
                        </div>
                        <span v-else>--</span>
                    </template>
                    <template v-else-if="column.dataIndex === 'id'">
                        <a class="mr16" v-if="record.state === 3" @click="download(record)">下载文件</a>
                        <a class="mr16" v-if="![1,2].includes(record.state)" @click="del(record,index)">删除记录</a>
                        <a v-else @click="cancel(record)">取消导出</a>
                    </template>
                </template>
            </a-table>
        </div>
    </MainLayout>
</template>

<script setup>
import {onMounted, ref, reactive} from 'vue';
import {Modal, message} from 'ant-design-vue';
import MainLayout from "@/components/mainLayout.vue";

const columns = ref([
    {
        title: "导出来源",
        dataIndex: "type_text",
        width: 300,
    },
    {
        title: "文件名",
        dataIndex: "file_name",
        width: 320,
    },
    {
        title: "状态",
        dataIndex: "state",
        width: 200,
    },
    {
        title: "操作人",
        dataIndex: "creator_name",
        width: 150,
    },
    {
        title: "导出时间",
        dataIndex: "create_time",
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

const loadData = () => {
    loading.value = true
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
        onOk: () => {
            const loadClose = message.loading('正在取消')
        }
    })
}

const download = record => {
    if (record.state != 3) {
        return message.error('未导出完成')
    }
    if (record.file_path === "") {
        return message.error('导出异常')
    }
    //window.location.href = record.file_path
}

const del = (record, index) => {
    Modal.confirm({
        title: '提示',
        content: '确认删除此记录',
        onOk: () => {
            const loadClose = message.loading('正在删除')
        }
    })
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
</style>
