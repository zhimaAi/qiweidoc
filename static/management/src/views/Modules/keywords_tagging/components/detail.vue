<template>
  <div class="zm-main-content">
    <div class="statistic-box">
      <statistic />
    </div>
    <div class="detail-header mt16">
      <a-form :model="form" name="basic" autocomplete="off">
        <a-row type="flex" justify="space-around" align="middle">
          <a-col :span="6" style="padding-right: 24px;">
            <a-form-item label="搜索客户">
              <a-input v-model:value="filterData.customer_name" allowClear placeholder="请输入昵称搜索" />
            </a-form-item>
          </a-col>
          <a-col :span="18" style="padding-right: 24px;">
            <a-form-item label="提交时间">
              <a-radio-group v-model:value="form.dateTab" @change="changeTab">
                <a-radio-button v-for="item in dateList" :value="item.value" :key="item.value">
                  {{ item.label }}
                </a-radio-button>
              </a-radio-group>
              <div class="inline-block ml-8 mr-24 mb-8">
                <a-range-picker v-model:value="filterData.dates" value-format="YYYY-MM-DD" class="w-256"
                  @change="filterDateChange" :disabled-date="disabledDate">
                  <!-- <a-icon slot="suffixIcon" type="calendar" /> -->
                </a-range-picker>
              </div>
              <div class="inline-block">
                <a-button @click="searchData" type="primary">搜索</a-button>
                <a-button @click="resetData" class="ml8">重置</a-button>
              </div>
            </a-form-item>
          </a-col>
        </a-row>
      </a-form>
    </div>
    <a-table class="mt16 detail-table" :loading="loading" :data-source="list" :columns="columns"
      :pagination="pagination" @change="tableChange">
      <template #bodyCell="{ column, text, record }">
        <template v-if="'external_name' === column.dataIndex">
          <div class="user-box">
            <img :src="record.avatar" class="avatar" />
            <div class="user-name">{{ text || '-' }}</div>
            <!-- <div class="user-type employee">客户</div> -->
          </div>
        </template>
        <template v-else-if="'tag_info' === column.dataIndex">
          <div class="msg-type-box">
            <div class="zm-line-clamp2" style="max-height: 40px;">{{ record.tag.name }}</div>
          </div>
        </template>
        <template v-else-if="'group_info' === column.dataIndex">
          <!-- chat_id name -->
          <div class="msg-type-box" v-if="text">
            <div class="zm-line-clamp2" style="max-height: 40px;">{{ text.name || '-' }}</div>
          </div>
          <div class="msg-type-box" v-else>
            <div class="zm-line-clamp2" style="max-height: 40px;">-</div>
          </div>
        </template>
        <template v-else-if="'keyword' === column.dataIndex">
          <!-- 关键词标红 -->
          <div v-html="text" class="nodestr"></div>
        </template>
        <template v-else-if="'msg_time' === column.dataIndex">
            {{ dayjs(text).format("YYYY-MM-DD HH:mm:ss") }}
        </template>
        <template v-else-if="'operate' === column.dataIndex">
          <a class="operate-content" @click="edit(record)">聊天明细</a>
        </template>
      </template>
    </a-table>
    <selectStaffNew selectType="single" ref="setStaff" @change="(val) => staffUpdate(val)">
    </selectStaffNew>
  </div>
</template>

<script setup>
import { onMounted, ref, reactive } from 'vue';
import { message } from 'ant-design-vue'
import dayjs from 'dayjs';
import { useRouter, useRoute } from 'vue-router';
import statistic from './statistic.vue'
import selectStaffNew from '@/components/select-staff-new/index';
import { taskLogList } from '@/api/session'

const router = useRouter()
const route = useRoute()
const columns = ref([
  {
    dataIndex: "external_name",
    title: "客户",
    width: 180
  },
  {
    dataIndex: "staff_name",
    title: "所属员工",
    width: 180
  },
  {
    dataIndex: "keyword",
    title: "最近触发关键词",
    width: 222
  },
  {
    dataIndex: "msg_time",
    title: "提交时间",
    width: 130
  },
  {
    dataIndex: "tag_info",
    title: "标签",
    width: 120
  }
])

const dateList = ref([
  { label: '全部', value: 'all' },
  { label: '今', value: 'today' },
  { label: '昨', value: 'yesterday' },
  { label: '自定义', value: 'custom' }
])

const form = reactive({
  dateTab: 'all',
  search_type: 1
})

const loading = ref(false)
const list = ref([])
const pagination = reactive({
  total: 0,
  current: 1,
  pageSize: 10,
  showSizeChanger: true,
  pageSizeOptions: ['10', '20', '50', '100'],
})
const filterData = reactive({
  customer_name: '',  // 搜索客户 string
  staff_userid: [], // string 发送员工ID
  dates: [] // start_time end_time string 具体时间
})
const selectedStaff = ref([])
const setStaff = ref(null)

const staffUpdate = (val) => {
  if (!val) return
  selectedStaff.value = val;
  filterData.staff_userid = val.map((el) => {
    return el.userid;
  });
  searchData()
}

const searchData = () => {
  list.value = []
  pagination.current = 1
  pagination.total = 0
  loadData();
}

const disabledDate = current => {
  return current && current > dayjs().endOf('day')
}

const filterDateChange = () => {
  if (filterData.dates && filterData.dates.length > 0) {
    let time = filterData.dates[1] - filterData.dates[0]
    if ((time / 86400000) > 30) {
      filterData.dates = []
      message.warning("发送时间跨度不得超过30天")
    }
  }
  searchData()
}

const loadData = () => {
  loading.value = true
  let params = {
    page: pagination.current,
    size: pagination.pageSize
  }

  // 搜索客户
  filterData.customer_name = filterData.customer_name.trim()
  if (filterData.customer_name) {
    params.customer_name = filterData.customer_name
  } else {
    params.customer_name = ''
  }

  // 触发人员
  // 发送员工ID
  if (filterData.staff_userid) {
    params.staff_userid = filterData.staff_userid[0]
  } else {
    params.staff_userid = ''
  }

  if (route.query.task_id) {
    params.task_id = route.query.task_id
  } else {
    params.task_id = ''
  }

  // 提交时间
  if (filterData.dates && filterData.dates.length) {
    params.start_time = dayjs(filterData.dates[0]).format('YYYY-MM-DD 00:00:00')
    params.end_time = dayjs(filterData.dates[1]).format('YYYY-MM-DD 23:59:59')
  }
  taskLogList(params).then(res => {
    list.value = res.data.items || []
    pagination.total = Number(res.data?.total)
  }).finally(() => {
    loading.value = false
  }).catch((err) => {

  })
}

const edit = (record) => {
  // 正式要调整测试
  let params = {}
  if (record.group_info) {
    params.group_chat_id = record.group_info.chat_id
  } else {
    params.conversation_id = record.conversation_id
    params.sender = record.from_user_id
    params.receiver = record.to_user_id || ''
    params.sender_type = record.from_role
    params.receiver_type = record.to_role
  }
  let href = router.resolve({
    path: '/sessionArchive/index',
    query: params
  }).href
  window.open(href)
}

const resetData = () => {
  pagination.current = 1;
  list.value = []
  filterData.customer_name = ''
  filterData.staff_userid = []
  filterData.dates = []
  form.dateTab = 'all'
  changeTab()
  // loadData()
}

const changeTab = () => {
  switch (form.dateTab) {
    case 'all':
      filterData.dates = [];
      break;
    case 'today':
      filterData.dates = [
        dayjs().startOf('day').format('YYYY-MM-DD'),
        dayjs().endOf('day').format('YYYY-MM-DD'),
      ];
      break;
    case 'yesterday':
      filterData.dates = [
        dayjs().subtract(1, 'day').startOf('day').format('YYYY-MM-DD'),
        dayjs().subtract(1, 'day').endOf('day').format('YYYY-MM-DD'),
      ];
      break;
    default:
      break;
  }
  return form.dateTab !== 'custom' && searchData();
}

const tableChange = p => {
  pagination.current = p.current
  pagination.pageSize = p.pageSize
  loadData()
}

const init = () => {
  loadData()
}

onMounted(async() => {
  if (route.query.date_type) {
    if (route.query.date_type == 1) {
      form.dateTab = 'today'
      changeTab()
    } else if (route.query.date_type == 2) {
      form.dateTab = 'yesterday'
      changeTab()
    } else {
      form.dateTab = 'all'
      changeTab()
    }
  }

  if (!route.query.date_type) {
    await init()
  }
})
</script>

<style scoped lang="less">
.inline-block {
  display: inline-block;
}

.ml-8 {
  margin-left: 8px;
}

.mr-24 {
  margin-right: 24px;
}

.w-256 {
  width: 256px;
}

.mt-8 {
  margin-top: 8px;
}

.mb-8 {
  margin-bottom: 8px;
}

.flex {
  display: flex;
  align-items: center;
}

.statistic-box {
  margin-top: 16px;
  margin-bottom: 26px;
}

.list {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 16px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: #000000;
  line-height: 24px;

  .wz {
    font-size: 14px;
    color: rgba(0, 0, 0, 0.65);
  }

  .fl-fc {
    display: flex;
    flex-direction: column;
  }

  .wz-color {
    color: rgba(0, 0, 0, 0.45);
  }

  .mb10 {
    margin-bottom: 10px;
  }

  .mr10 {
    margin-right: 10px;
  }

  .lefts {
    margin-left: 10px;
  }

  .lefts_size {
    transform: rotate(-100deg);
    color: #595959;
  }

  .lefts_sizes {
    margin-left: 5px;
    cursor: auto;
  }

  .lefts_check {
    margin-left: 16px;

    .ant-checkbox+span {
      padding-right: 0;
    }
  }

  span {
    cursor: pointer;
    font-size: 13px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(0, 0, 0, 0.45);
    line-height: 22px;
  }
}

.user-box {
  font-family: "PingFang SC";
  display: flex;
  align-items: center;
  gap: 4px;

  .avatar {
    width: 24px;
    height: 24px;
    border-radius: 2px;
    margin-right: 4px;
  }

  .user-name {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 1;
    overflow: hidden;
    color: #000000a6;
    text-overflow: ellipsis;
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 22px;
  }

  .user-type {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 1;
    flex: 1 0 0;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 22px;
  }

  .employee {
    color: #ed744a;
  }

  .group {
    color: #21a665;
  }

  .client {
    color: #164799;
  }
}

.qunfa-box {
  margin-bottom: 0;
  padding: 2px 8px;
}

.qunfa {
  font-size: 12px;
  width: 250px;
}

.qunfa_name {
  font-size: 13px;
  font-family: PingFangSC-Medium, PingFang SC;
  font-weight: 500;
  color: rgba(0, 0, 0, 0.65);
  margin-right: 6px;
}

.tages_wid {
  display: inline-block;
  // width: 400px;

  // span {
  // margin-bottom: 8px;
  // }
}

.nowrap {
  white-space: nowrap;
}

.flex {
  display: flex;
  align-items: center;
}

.operate-content {
  color: #2475fc;
  text-align: center;
  font-family: "PingFang SC";
  font-size: 14px;
  font-style: normal;
  font-weight: 400;
  line-height: 22px;
}

.trigger-personnel {
  width: 100%;

  .default-label {
    width: 32%;

    :deep(.ant-select-selector) {
      border-radius: 6px 0px 0px 6px;
    }
  }

  .default-input {
    width: 68%;
    border-radius: 0px 6px 6px 0px;

    :deep(.ant-select-selector) {
      border-radius: 0px 6px 6px 0px;
      margin-left: -1px;
      height: 32px;
    }
  }
}

.nodestr {
  white-space: pre-wrap;
}

:deep(.detail-table .ant-table) {
  color: #595959;
  font-size: 14px;
}

:deep(.detail-header .ant-form-item) {
  margin-bottom: 8px;
}
</style>

<style lang="less">
.user-info-tooltip .ant-tooltip-inner {
  width: max-content;
}
</style>
