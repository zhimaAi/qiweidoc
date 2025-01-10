<template>
  <div class="zm-main-content">
    <a-alert show-icon message="设置的敏感词和敏感行为被触发后,可在列表中查看对应的触发记录以及会话记录"></a-alert>
    <div class="statistic-box">
      <statistic />
    </div>
    <div class="detail-header mt16">
      <a-form
        :model="form"
        name="basic"
        autocomplete="off">
        <a-row type="flex" justify="space-around" align="middle">
          <a-col :span="6" style="padding-right: 24px;">
            <a-form-item label="敏感词规则">
                <a-select placeholder="全部"
                v-model:value="filterData.rule_id"
                :getPopupContainer="triggerNode => triggerNode.parentNode"
                @change="searchData"
                allowClear>
                    <a-select-option v-for="item in rule_list" :key="item.id" :value="item.id">
                    {{ item.rule_name }}
                    </a-select-option>
                </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="6" style="padding-right: 24px;">
            <a-form-item label="触发类型">
              <a-select placeholder="全部"
                v-model:value="filterData.hint_type"
                :getPopupContainer="triggerNode => triggerNode.parentNode"
                @change="onTriggerType"
                allowClear>
                  <a-select-option v-for="type in hint_type_list" :key="type.value" :value="type.value">
                      {{ type.label }}
                  </a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="6" style="padding-right: 24px;">
            <a-form-item label="触发条件">
              <a-select placeholder="全部"
                v-model:value="filterData.target_msg_type"
                :getPopupContainer="triggerNode => triggerNode.parentNode"
                @change="searchData"
                allowClear>
                  <a-select-option v-for="type in target_msg_type_list" :disabled="(sensitive_behavior.includes(type.value) && filterData.hint_type == 1) || (type.value == 'text' && filterData.hint_type == 2)" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="6" style="padding-right: 24px;">
            <a-form-item label="消息内容">
              <a-input v-model:value="filterData.keyword" allowClear placeholder="请输入消息内容搜索" />
            </a-form-item>
          </a-col>
        </a-row>

        <a-row type="flex" justify="space-around" align="middle">
            <a-col :span="7" style="padding-right: 24px;">
              <a-form-item label="触发人员">
                <div class="trigger-personnel">
                  <a-select class="default-label" v-model:value="form.search_type">
                    <!-- <a-select-option :key='' :value="">全部</a-select-option> -->
                    <a-select-option :key='1' :value="1">员工</a-select-option>
                    <a-select-option :key='2' :value="2">客户</a-select-option>
                  </a-select>

                  <template v-if="form.search_type === 1">
                    <a-select
                      class="default-input"
                      mode="multiple"
                      size="default"
                      :placeholder="'请选择员工'"
                      v-model:value="filterData.staff_userid"
                      :open="false"
                      @change="staffChange"
                      @dropdownVisibleChange="dropdownVisibleChange"
                    >
                      <a-select-option
                        v-for="item in selectList"
                        :key="item._id"
                        :label="item.name"
                      >
                        {{ item.name }}
                      </a-select-option>
                    </a-select>
                  </template>

                  <a-input v-if="form.search_type === 2" v-model:value="filterData.external_username" allowClear class="default-input" placeholder="请输入客户昵称搜索"/>
                </div>
              </a-form-item>
            </a-col>
            <a-col :span="17" style="padding-right: 24px;">
                <a-form-item label="触发时间">
                    <a-radio-group v-model:value="form.dateTab" @change="changeTab">
                        <a-radio-button v-for="item in dateList" :value="item.value" :key="item.value">
                        {{ item.label }}
                        </a-radio-button>
                    </a-radio-group>
                    <div class="inline-block ml-8 mr-24 mb-8">
                        <a-range-picker
                        v-model:value="filterData.dates"
                        value-format="YYYY-MM-DD"
                        class="w-256"
                        @change="filterDateChange"
                        :disabled-date="disabledDate"
                        >
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
    <a-table
      class="mt16 detail-table"
      :loading="loading"
      :data-source="list"
      :columns="columns"
      :scroll="{ x: 1500 }"
      :pagination="pagination"
      @change="tableChange"
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="'target_msg_type' === column.dataIndex">
          <!-- 枚举值，text：文本，link：链接，weapp：小程序，card：名片，link_text：包含链接的文本，qr_code：二维码图片 -->
          <div class="msg-type-box">
          <a-tooltip :title="formatMsgType(text)">
            <div class="zm-line-clamp2" style="max-height: 40px;">{{ formatMsgType(text) }}</div>
          </a-tooltip>
          </div>
        </template>
        <template v-else-if="'hint_type' === column.dataIndex">
          <!-- 1:敏感词，2:敏感行为 -->
          <div class="msg-type-box">
            <div class="zm-line-clamp2" style="max-height: 40px;">{{ formatHintType(text) }}</div>
          </div>
        </template>
        <template v-else-if="'from_user_info' === column.dataIndex">
          <!-- name userid -->
           <!-- from_role 1:客户，2:员工 2:群成员 -->
           <div class="user-box" v-if="record.from_role == 1">
            <img  :src="text.avatar" class="avatar"/>
            <div class="user-name">{{ text.external_name || '-' }}</div>
            <div class="user-type employee">客户</div>
          </div>
          <div class="user-box" v-if="record.from_role == 2">
            <img  src="@/assets/default-avatar.png" class="avatar"/>
            <div class="user-name">{{ text.name || '-' }}</div>
            <div class="user-type client">员工</div>
          </div>
          <div class="user-box" v-if="record.from_role == 3">
            <img  src="@/assets/default-avatar.png" class="avatar"/>
            <div class="user-name">{{ text.name || '-' }}</div>
            <div class="user-type group">群成员</div>
          </div>
        </template>
        <template v-else-if="'conversation_type' === column.dataIndex">
          <!-- 1:单聊，2:群聊，3:同事会话 -->
          <div class="msg-type-box">
            <div class="zm-line-clamp2" style="max-height: 40px;">{{ formatConversationType(text) }}</div>
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
        <template v-else-if="'msg_content' === column.dataIndex">
          <!-- 关键词标红 -->
          <div class="msg-type-box">
            <a-tooltip overlayClassName="msg-content-tip" :title="text">
              <div class="nodestr" v-html="forMatText(text, record.hint_keyword)"></div>
            </a-tooltip>
          </div>
        </template>
        <template v-else-if="'operate' === column.dataIndex">
            <a class="operate-content" @click="edit(record)">聊天明细</a>
        </template>
      </template>
    </a-table>
    <selectStaffNew
      selectType="single"
      ref="setStaff"
      @change="(val) => staffUpdate(val)"
    >
    </selectStaffNew>
  </div>
</template>

<script setup>
import { onMounted, ref, reactive } from 'vue';
import { QuestionCircleOutlined } from '@ant-design/icons-vue';
import { Modal, message } from 'ant-design-vue'
import dayjs from 'dayjs';
import { useRouter, useRoute } from 'vue-router';
import statistic from './statistic.vue'
import selectStaffNew from '@/components/select-staff-new/index';
import { ruleDetail, keywordsRuleList } from "@/api/sensitive";
import { staffList } from "@/api/company";

const router = useRouter()
const route = useRoute()
const selectList = ref([])
const columns = ref([
  {
    dataIndex: "from_user_info",
    title: "触发人员",
    width: 200
  },
  {
    dataIndex: "msg_time",
    title: "触发时间",
    width: 120
  },
  {
    dataIndex: "hint_type",
    title: "类型",
    width: 120
  },
  {
    dataIndex: "target_msg_type",
    title: "触发条件",
    width: 120
  },
  {
    dataIndex: "msg_content",
    title: "消息内容",
    width: 222
  },
  {
    dataIndex: "conversation_type",
    title: "触发场景",
    width: 120
  },
  {
    dataIndex: "group_info",
    title: "群聊名称",
    width: 120
  },
  {
    title: "操作",
    dataIndex: "operate",
    width: 88,
    fixed: "right"
  }
])

const rule_list = ref([]) // 敏感词规则

const hint_type_list = ref([
  { label: '敏感词', value: '1' },
  { label: '敏感行为', value: '2' }
])

const sensitive_behavior = ref(['link', 'weapp', 'card', 'qr_code', 'link_text'])

const target_msg_type_list = ref([
  { label: '发送敏感词', value: 'text' },
  { label: '发送链接', value: 'link' },
  { label: '发送小程序', value: 'weapp' },
  { label: '发送名片', value: 'card' },
  { label: '发送二维码图片', value: 'qr_code' },
  { label: '发送包含链接的文本', value: 'link_text' }
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
  hint_type: void 0,  // 触发类型 string 1:敏感词，2:敏感行为
  target_msg_type: void 0, // 触发条件 string  枚举值，text：文本，link：链接，weapp：小程序，card：名片，link_text：包含链接的文本，qr_code：二维码图片
  keyword: '',  // 消息内容 string
  staff_userid: [], // string 发送员工ID
  external_username: '', // string 发送客户昵称
  rule_id: void 0, // 敏感词规则
  dates: [], // start_time end_time string 具体时间
})
const selectedStaff = ref([])
const setStaff = ref(null)

const staffChange = (e) => {
  let lists = [];
  selectedStaff.value.map((item) => {
    e.map((el) => {
      if (item.userid === el) {
        lists.push(item);
      }
    });
  });
  selectedStaff.value = lists;
  filterData.staff_userid = lists.map((el) => {
    return el.userid;
  });
  searchData()
}

const dropdownVisibleChange = () => {
  getStaffList();
  setStaff.value.show(selectedStaff.value);
}

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

const onTriggerType = () => {
  if (filterData.hint_type == 1) {
    filterData.target_msg_type = 'text'
  } else if (filterData.hint_type == 2) {
    filterData.target_msg_type = 'link'
  } else {
    filterData.target_msg_type = void 0
  }
  searchData()
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

function formatMsgType (value) {
  let newValue = ''
  // text：文本，link：链接，weapp：小程序，card：名片，link_text：包含链接的文本，qr_code：二维码图片
  if (value === 'text') {
    newValue = '发送敏感词'
  } else if (value === 'link') {
    newValue = '发送链接'
  } else if (value === 'weapp') {
    newValue = '发送小程序'
  } else if (value === 'card') {
    newValue = '发送名片'
  } else if (value === 'link_text') {
    newValue = '发送包含链接的文本'
  } else if (value === 'qr_code') {
    newValue = '发送二维码图片'
  }
  return newValue
}

function formatHintType (value) {
  let newValue = ''
  // 1:敏感词，2:敏感行为
  if (value == '1') {
    newValue = '敏感词'
  } else if (value == '2') {
    newValue = '敏感行为'
  }
  return newValue
}

function formatConversationType (value) {
  let newValue = ''
  // 1:单聊，2:群聊，3:同事会话
  if (value == '1') {
    newValue = '单聊'
  } else if (value == '2') {
    newValue = '群聊'
  } else if (value == '3') {
    newValue = '同事会话'
  }
  return newValue
}

const loadData = async() => {
  loading.value = true
  let params = {
    page: pagination.current,
    size: pagination.pageSize
  }

  // 触发类型
  if (filterData.hint_type) {
    params.hint_type = filterData.hint_type
  } else {
    params.hint_type = ''
  }

  // 触发条件
  if (filterData.target_msg_type) {
    params.target_msg_type = filterData.target_msg_type
  } else {
    params.target_msg_type = ''
  }

  // 消息内容
  filterData.keyword = filterData.keyword.trim()
  if (filterData.keyword) {
    params.keyword = filterData.keyword
  }

  // 触发人员
  // 发送员工ID
  if (filterData.staff_userid) {
    params.staff_userid = filterData.staff_userid[0]
  } else {
    params.staff_userid = ''
  }
  // 发送客户昵称
  if (filterData.external_username) {
    params.external_username = filterData.external_username
  } else {
    params.external_username = ''
  }

  if (filterData.rule_id) {
    params.rule_id = filterData.rule_id
  } else {
    params.rule_id = ''
  }

  // 触发时间
  if (filterData.dates && filterData.dates.length) {
    // params.start_time = dayjs(dayjs(filterData.dates[0]).format('YYYY-MM-DD 00:00:00')).unix()
    // params.end_time = dayjs(dayjs(filterData.dates[1]).format('YYYY-MM-DD 23:59:59')).unix()
    params.start_time = dayjs(filterData.dates[0]).format('YYYY-MM-DD 00:00:00')
    params.end_time = dayjs(filterData.dates[1]).format('YYYY-MM-DD 23:59:59')
  }
  await ruleDetail(params).then(res => {
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
      params.conversation_type = record.conversation_type
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
  filterData.hint_type = void 0
  filterData.target_msg_type = void 0
  filterData.keyword = ''
  filterData.staff_userid = []
  filterData.external_username = ''
  filterData.rule_id = void 0
  filterData.dates = []
  loadData()
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

const staffListData = ref([])
const getStaffList = async() => {
  try {
    let res = await staffList({ page: 1, size: 999 });
    if (res.status !== 'success') {
      return;
    }
    staffListData.value = res.data.items;
    selectList.value = staffListData.value.map((el) => {
      return {
        _id: el.userid,
        ...el,
      };
    });
  } catch (error) {
  }
}

const getRuleList = async() => {
  loading.value = true
  let params = {
    page: 1,
    size: 1000
  }
  await keywordsRuleList(params).then(res => {
    rule_list.value = res.data.items || []
  }).finally(() => {
  })
}

const init = async() => {
  await loadData()
}

const forMatText = (str, keyWord) => {
  var substr = "/" + keyWord + "/g";
  var replaceStr = str.replace(eval(substr), "<span style='color: #fb363f;'>" + keyWord + "</span>")
  return replaceStr;
}

onMounted(async () => {
  if (route.query.rule_id) {
    filterData.rule_id = parseInt(route.query.rule_id)
  }

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
  await getRuleList()

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
  white-space: nowrap; /* 保证文本在一行内显示 */
  overflow: hidden; /* 隐藏溢出的内容 */
  text-overflow: ellipsis; /* 使用省略号表示溢出的文本 */
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

.msg-content-tip .ant-tooltip-inner {
  white-space: pre-wrap;
}
</style>
