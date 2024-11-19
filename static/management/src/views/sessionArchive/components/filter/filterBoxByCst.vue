<template>
    <div class="zm-filter-box">
<!--        <div class="zm-filter-item">-->
<!--            <a class="show-date-time" @click="showDateModal">-->
<!--                <CalendarOutlined/>-->
<!--                {{ dateModal.year }}年<span v-if="dateModal.month > 0">{{ dateModal.month }}月</span>-->
<!--            </a>-->
<!--        </div>-->
<!--        <div class="zm-filter-item">-->
<!--            <span class="zm-filter-label">客户标签：</span>-->
<!--            <div class="filter-item-content">-->
<!--                <a-input-search-->
<!--                    v-model="filterData.keyword"-->
<!--                    style="width: 155px;"-->
<!--                    @search="search"-->
<!--                    allowClear-->
<!--                    placeholder="请选择客户标签"-->
<!--                    size="small"/>-->
<!--            </div>-->
<!--        </div>-->
        <div class="zm-filter-item">
            <span class="zm-filter-label">搜索客户：</span>
            <div class="filter-item-content">
                <a-input-search
                    v-model:value="filterData.keyword"
                    @search="search"
                    allowClear
                    placeholder="客户昵称进行搜索"
                    size="small"/>
            </div>
        </div>

        <a-modal title="选择时间"
                 v-model:open="dateModal.visible"
                 @ok="confirmDate"
                 @cancel="hideDateModal">
            <span>选择时间：</span>
            <a-select
                v-model:value="dateModal.year"
                :options="yearOptions"
                style="width: 120px"
                placeholder="请选择年份"/>
            <a-select
                v-model:value="dateModal.month"
                clearable
                style="width: 120px"
                :options="MonthOptions"
                placeholder="请选择月份"/>
        </a-modal>
    </div>
</template>

<script setup>
import {onMounted, reactive, ref} from 'vue';
import dayjs from 'dayjs';
import {CalendarOutlined} from '@ant-design/icons-vue';
import {MonthOptions} from "@/utils/tools";

const emit = defineEmits(['change'])
const filterData = reactive({
    keyword: ''
})
const yearOptions = ref([])
const dateModal = reactive({
    visible: false,
    year: null,
    month: null,
    defaultYear: null,
    defaultMonth: null,
    values: [],
})

onMounted(() => {
    dateModal.year = Number(dayjs().format("YYYY"))
    dateModal.month = Number(dayjs().format("MM"))
    dateModal.defaultYear = dateModal.year
    dateModal.defaultMonth = dateModal.month

    let currentYear = Number(dayjs().format("YYYY"))
    let lastYear = Number(dayjs().subtract(1, "y").format("YYYY"))
    yearOptions.value.push(
        {
            value: currentYear,
            label: `${currentYear}年`,
        },
        {
            value: lastYear,
            label: `${lastYear}年`,
        },
    )
})

const search = () => {
    filterData.keyword = filterData.keyword.trim()
    emit('change', filterData)
}

const showDateModal = () => {
    dateModal.visible = true
}

const hideDateModal = () => {
    dateModal.visible = false
    dateModal.year = dateModal.defaultYear
    dateModal.month = dateModal.defaultMonth
}

const confirmDate = () => {
    dateModal.visible = false
    dateModal.defaultYear = dateModal.year
    dateModal.defaultMonth = dateModal.month
    let unit = "year"
    let month = ""
    if (dateModal.month > 0) {
        unit = "month"
        month = dateModal.month > 9 ? dateModal.month : `0${dateModal.month}`
    }
    dateModal.values = [
        dayjs(`${dateModal.year}${month}`).startOf(unit).unix(),
        dayjs(`${dateModal.year}${month}`).endOf(unit).unix(),
    ]
}
</script>

<style scoped lang="less">
.zm-filter-box {
    padding-top: 8px;
    padding-right: 60px;
    flex-wrap: wrap;
    height: 41px;
    overflow: hidden;
    border-bottom: 1px solid rgba(5, 5, 5, 0.06);

    .zm-filter-item {
        margin-bottom: 12px;
        margin-left: 16px;
    }
}

.show-date-time {
    cursor: pointer;
    color: #164799;
}
</style>
