<template>
    <MainLayout>
        <template #navbar>
            <div class="navbar-wrapper">
                <a-tabs v-model:active-key="BASE_TYPE" @change="mainTabChange" class="zm-nav-tabs">
                    <a-tab-pane key="LOAD_BY_STAFF">
                        <template #tab>
                            <StaffPaymentTag>按员工</StaffPaymentTag>
                        </template>
                    </a-tab-pane>
                    <a-tab-pane key="LOAD_BY_CUSTOMER" tab="按客户" />
                    <a-tab-pane key="LOAD_BY_GROUP" tab="按群聊" />
                    <a-tab-pane key="LOAD_BY_COLLECT" tab="收藏聊天" />
                </a-tabs>
                <!-- 时间选择器 -->
                <!-- <div class="date-picker-wrapper">
                    <a-range-picker v-model:value="dateRange" :placeholder="['开始日期', '结束日期']" size="small"
                        :allow-clear="false" :disabled-date="disabledDate" @change="handleDateChange"
                        :presets="dateRanges" format="YYYY-MM-DD" :show-time="false" :picker-value="dateRange" />
                </div> -->
            </div>
        </template>
        <div id="session-panel">
            <SelectTagModal />
            <LoadingBox v-if="loading" />
            <Component v-else :is="mainPanelComponent" :defaultParams="defaultParams" />
        </div>
    </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed, nextTick, watch, toRefs } from 'vue';
import MainLayout from "@/components/mainLayout.vue";
import MainPanelLoadByStaff from "./components/mainPanelLoadByStaff.vue";
import MainPanelLoadByCollect from "./components/mainPanelLoadByCollect.vue";
import MainPanelLoadByCst from "./components/mainPanelLoadByCst.vue";
import MainPanelLoadByGroup from "./components/mainPanelLoadByGroup.vue";
import { useRouter, useRoute } from 'vue-router';
import LoadingBox from "@/components/loadingBox.vue";
import { copyObj } from "@/utils/tools";
import dayjs from 'dayjs';
import { message } from 'ant-design-vue';
import SelectTagModal from "@/components/select-customer-tag/selectTagModal.vue";
import StaffPaymentTag from "@/views/sessionArchive/components/modules/staffPaymentTag.vue";

const router = useRouter()
const route = useRoute()
const BASE_TYPE = ref('LOAD_BY_STAFF')
const MAIN_TABS = [
    'LOAD_BY_STAFF',
    'LOAD_BY_CUSTOMER',
    'LOAD_BY_GROUP',
    'LOAD_BY_COLLECT'
]
const defaultParams = ref(null)
const loading = ref(true)
// 时间选择器数据
const dateRange = ref([])
const mainPanelComponent = computed(() => {
    switch (BASE_TYPE.value) {
        case 'LOAD_BY_STAFF':
            return MainPanelLoadByStaff
        case 'LOAD_BY_CUSTOMER':
            return MainPanelLoadByCst
        case 'LOAD_BY_GROUP':
            return MainPanelLoadByGroup
        case 'LOAD_BY_COLLECT':
            return MainPanelLoadByCollect
    }
})

// 时间范围预设选项
const dateRanges = computed(() => [
    {
        label: '今天',
        value: [dayjs().startOf('day'), dayjs().endOf('day')]
    },
    {
        label: '最近7天',
        value: [dayjs().subtract(6, 'day').startOf('day'), dayjs().endOf('day')]
    },
    {
        label: '最近30天',
        value: [dayjs().subtract(29, 'day').startOf('day'), dayjs().endOf('day')]
    },
    {
        label: '最近3个月',
        value: [dayjs().subtract(3, 'month').startOf('day'), dayjs().endOf('day')]
    },
    {
        label: '本月',
        value: [dayjs().startOf('month'), dayjs().endOf('month')]
    },
    {
        label: '上月',
        value: [dayjs().subtract(1, 'month').startOf('month'), dayjs().subtract(1, 'month').endOf('month')]
    }
])

// 初始化时间范围
const initDateRange = () => {
    // 从URL参数中获取时间范围
    if (route.query.start_date && route.query.end_date) {
        dateRange.value = [dayjs(route.query.start_date), dayjs(route.query.end_date)]
    } else {
        // 默认设置为最近3个月
        dateRange.value = [dayjs().subtract(3, 'month').startOf('day'), dayjs().endOf('day')]
    }
}

// 禁用日期函数 - 限制最多选择3个月
const disabledDate = (current) => {
    if (!current) return false

    // 禁用未来日期
    if (current.isAfter(dayjs(), 'day')) {
        return true
    }

    // 如果已选择了开始日期，限制结束日期不能超过3个月
    if (dateRange.value && dateRange.value[0] && !dateRange.value[1]) {
        const startDate = dayjs(dateRange.value[0])
        const maxEndDate = startDate.add(3, 'month')
        return current.isAfter(maxEndDate, 'day')
    }

    return false
}

// 时间范围变化处理
const handleDateChange = (dates, dateStrings) => {
    if (!dates || dates.length !== 2) return

    const [startDate, endDate] = dates
    const maxEndDate = startDate.add(3, 'month')

    if (endDate.isAfter(maxEndDate, 'day')) {
        // 如果超过3个月，自动调整为3个月并提示
        const adjustedEndDate = startDate.add(3, 'month')
        dateRange.value = [startDate, adjustedEndDate]
        message.warning("时间跨度不得超过3个月，已自动调整")
        return
    }

    // 更新URL参数
    const query = {
        ...route.query
    }
    resetRouteQuery(query)

    // 更新defaultParams以传递时间参数给子组件
    updateDefaultParams()
}

// 更新默认参数
const updateDefaultParams = () => {
    if (dateRange.value && dateRange.value.length === 2) {
        const [startDate, endDate] = dateRange.value
        if (defaultParams.value) {
            defaultParams.value = {
                ...defaultParams.value,
                start_date: startDate.format('YYYY-MM-DD') + ' 00:00:00',
                end_date: endDate.format('YYYY-MM-DD') + ' 23:59:59',
                _refresh: Date.now() // 添加时间戳强制刷新
            }
        }
    }
}

// 监听时间范围变化
watch(dateRange, (newDateRange) => {
    if (newDateRange && newDateRange.length === 2) {
        updateDefaultParams()
    }
}, { deep: true })

onMounted(() => {
    let query = copyObj(route.query)

    // 初始化时间范围
    initDateRange()

    if (route.query.tab && MAIN_TABS.includes(route.query.tab)) {
        BASE_TYPE.value = route.query.tab
    }
    /**
     * 检测queru参数是否携带指定聊天信息
     */
    if (route.query.group_chat_id) {
        // 跳转指定群聊
        BASE_TYPE.value = 'LOAD_BY_GROUP'
        defaultParams.value = {
            group_chat_id: query.group_chat_id,
            start_date: dateRange.value[0].format('YYYY-MM-DD') + ' 00:00:00',
            end_date: dateRange.value[1].format('YYYY-MM-DD') + ' 23:59:59'
        }
        delete query.group_chat_id
    } else if (route.query.sender && route.query.sender_type && route.query.conversation_id) {
        // 跳转其他会话（同事回话、客户会话）
        // 群聊会话也有兼容
        switch (route.query.sender_type) {
            case 'Customer':
                BASE_TYPE.value = 'LOAD_BY_CUSTOMER'
                break
            case 'Staff':
                BASE_TYPE.value = 'LOAD_BY_STAFF'
                break
            case 'Collect':
                BASE_TYPE.value = 'LOAD_BY_COLLECT'
                break
        }
        defaultParams.value = {
            sender: query.sender,
            receiver: query.receiver,
            sender_type: query.sender_type,
            receiver_type: query.receiver_type,
            conversation_id: query.conversation_id,
            start_date: dateRange.value[0].format('YYYY-MM-DD') + ' 00:00:00',
            end_date: dateRange.value[1].format('YYYY-MM-DD') + ' 23:59:59'
        }
        delete query.sender
        delete query.sender_type
        delete query.receiver
        delete query.receiver_type
        delete query.conversation_id
    } else {
        // 默认情况下也要设置时间参数
        defaultParams.value = {
            start_date: dateRange.value[0].format('YYYY-MM-DD') + ' 00:00:00',
            end_date: dateRange.value[1].format('YYYY-MM-DD') + ' 23:59:59'
        }
    }

    query.tab = BASE_TYPE.value
    loading.value = false
    nextTick(() => {
        // 重置路由信息
        resetRouteQuery(query)
    })
})

const mainTabChange = () => {
    const query = {
        ...route.query,
        tab: BASE_TYPE.value
    }
    resetRouteQuery(query)
    // 更新defaultParams以传递时间参数给新的组件
    defaultParams.value = {
        ...defaultParams.value,
        start_date: dateRange.value[0].format('YYYY-MM-DD') + ' 00:00:00',
        end_date: dateRange.value[1].format('YYYY-MM-DD') + ' 23:59:59',
        _refresh: Date.now()
    }
}

const resetRouteQuery = queryParams => {
    router.replace({
        path: route.path,
        query: queryParams
    });
}
</script>

<style scoped lang="less">
#session-panel {
    position: relative;
    min-height: calc(100vh - 115px);

    :deep(.ant-tabs-tab) {
        font-size: 12px;
    }

    :deep(.ant-tabs-nav) {
        margin-bottom: 0;
    }

    :deep(.ant-input),
    :deep(.ant-select-selection-placeholder) {
        font-size: 12px;
    }
}

.navbar-wrapper {
    height: 50px;
    line-height: 50px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    background: white;
    padding-right: 12px;
}

.zm-nav-tabs {
    flex: 0 0 auto;
}

.date-picker-wrapper {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
}

.date-picker-wrapper .ant-picker {
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.date-picker-wrapper .ant-picker:hover {
    border-color: #40a9ff;
    box-shadow: 0 2px 8px rgba(64, 169, 255, 0.2);
}

.date-picker-wrapper .ant-picker:focus-within {
    border-color: #1890ff;
    box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
}
</style>
