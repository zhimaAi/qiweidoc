<template>
  <div class="zm-main-content">
    <a-alert show-icon message="设置敏感词，会话内容中包含所设置的敏感词时，将触发提醒，实时质检服务质量和客户行为"></a-alert>
    <div class="statistic-box">
      <statistic />
    </div>
    <div class="header mt16">
      <a-button type="primary" @click="linkAddRule">
        <template #icon>
          <PlusOutlined />
        </template>
        新建规则
      </a-button>
      <div class="operate-box">
          <a-button class="ml24" @click="onRemindShow">
              <div class="flex"> 触发提醒
                  <a-switch v-model:checked="triggerNotifyStatus"
                            @change="triggerNotifyStatus = !triggerNotifyStatus"
                            checked-children="开"
                            un-checked-children="关"
                            class="ml4"/>
              </div>
          </a-button>
      </div>
    </div>
    <a-table
      class="mt16 rule-table"
      :loading="loading"
      :data-source="list"
      :columns="columns"
      :scroll="{ x: 1500 }"
      :pagination="pagination"
      @change="tableChange"
    >
      <template #headerCell="{ column }">
          <template v-if="'total' === column.dataIndex">
              <a-tooltip>
                  <template #title>
                      <div>今日：今日触发规则的次数</div>
                      <div>昨天：昨日触发规则的次数</div>
                      <div>总触发次数：该监控总的触发次数</div>
                  </template>
                  <div>
                  今日/昨日/总触发数
                  <QuestionCircleOutlined />
                  </div>
              </a-tooltip>
          </template>
      </template>
      <template #bodyCell="{ column, text, record }">
        <template v-if="'rule_name' === column.dataIndex">
            <a-tooltip :title="text">
                <div class="zm-line-clamp2" style="max-height: 40px;">{{ text }}</div>
            </a-tooltip>
        </template>
        <template v-else-if="'chat_type' === column.dataIndex">
            <!-- 0:全部，1:单聊，2：群聊 -->
            <div>{{ text == 0 ? '全部' : text == 1 ? '单聊' : '群聊' }}</div>
        </template>
        <template v-else-if="'check_user_type' === column.dataIndex">
            <!-- 0:全部，1:仅客户，2:仅员工 -->
            <div>{{ text == 0 ? '全部' : text == 1 ? '仅客户' : '仅员工' }}</div>
        </template>
        <template v-else-if="'target_msg_type' === column.dataIndex">
          <!-- 枚举值，text：文本，link：链接，weapp：小程序，card：名片，link_text：包含链接的文本，qr_code：二维码图片 -->
          <div class="msg-type-box">
          <a-tooltip :title="formatMsgType(text, record)">
            <div class="zm-line-clamp2" style="max-height: 40px;">{{ formatMsgType(text, record) }}</div>
          </a-tooltip>
          </div>
        </template>
        <template v-else-if="'switch_status' === column.dataIndex">
            <a-switch v-model:checked="record.status_switch_bool"
                      :loading="record.switching"
                      checked-children="开"
                      un-checked-children="关"
                      @change="statusChange(record)"/>
        </template>
        <template v-else-if="'total' === column.dataIndex">
            <a @click="linkStat(record, 1)">{{ record.statistic_today }}</a>
            <a-divider type="vertical"/>
            <a @click="linkStat(record, 2)">{{ record.statistic_yesterday }}</a>
            <a-divider type="vertical"/>
            <a @click="linkStat(record, 0)">{{ record.statistic_total }}</a>
        </template>
        <template v-else-if="'operate' === column.dataIndex">
            <a class="operate-content" @click="edit(record)">编辑</a>
            <a class="operate-content ml16" @click="del(record)">删除</a>
        </template>
      </template>
    </a-table>
    <Remind ref="remind" @update="updateStatus"/>
  </div>
</template>

<script setup>
import { onMounted, ref, reactive } from 'vue';
import { PlusOutlined, QuestionCircleOutlined } from '@ant-design/icons-vue';
import Remind from "./remind.vue";
import { Modal, message } from 'ant-design-vue'
import dayjs from 'dayjs';
import { useRouter } from 'vue-router';
import statistic from './statistic.vue'
import { keywordsRuleList, changeStatus, noticeInfo, keywordsRuleDelete } from "@/api/sensitive";

const router = useRouter()
const remind = ref(null)
const triggerNotifyStatus = ref(false)
const columns = ref([
  {
    title: "规则名称",
    dataIndex: "rule_name",
    width: 140
  },
  {
    dataIndex: "chat_type",
    title: "聊天场景",
    width: 120
  },
  {
    dataIndex: "check_user_type",
    title: "触发对象",
    width: 120
  },
  {
    dataIndex: "target_msg_type",
    title: "触发条件",
    width: 160
  },
  {
    // title: "今日/昨日/总触发数",
    dataIndex: "total",
    width: 180,
  },
  {
    dataIndex: "create_user_name",
    title: "创建人",
    width: 88
  },
  {
    title: "是否启用",
    dataIndex: "switch_status",
    width: 160
  },
  {
    dataIndex: "created_at",
    title: "创建时间",
    width: 200
  },
  {
    title: "操作",
    dataIndex: "operate",
    width: 160,
    fixed: "right"
  }
])

const loading = ref(false)
const list = ref([])
const pagination = reactive({
  total: 0,
  current: 1,
  pageSize: 10,
  showSizeChanger: true,
  pageSizeOptions: ['10', '20', '50', '100'],
})

function formatMsgType (array, record) {
  let newArray = []
  if (record.hint_keywords.length || record.hint_group_ids.length) {
      newArray.push('发送敏感词')
  }
  for (let i = 0; i < array.length; i++) {
    const item = array[i];
    // text：文本，link：链接，weapp：小程序，card：名片，link_text：包含链接的文本，qr_code：二维码图片
    if (item === 'text') {
      newArray.push('发送文本')
    }
    if (item === 'link') {
      newArray.push('发送链接')
    }
    if (item === 'weapp') {
      newArray.push('发送小程序')
    }
    if (item === 'card') {
      newArray.push('发送名片')
    }
    if (item === 'link_text') {
      newArray.push('发送包含链接的文本')
    }
    if (item === 'qr_code') {
      newArray.push('发送二维码图片')
    }
  }
  return newArray.join('、')
}

const loadData = () => {
  loading.value = true
  let params = {
    page: pagination.current,
    size: pagination.pageSize
  }
  keywordsRuleList(params).then(res => {
    let lists = res.data.items || []
    lists.map((item) => {
      item.status_switch_bool = (item.switch_status == 1)
    })
    list.value = lists
    pagination.total = Number(res.data?.total)
  }).finally(() => {
    loading.value = false
  })
}

const updateStatus = (config) => {
  triggerNotifyStatus.value = Boolean(config.notice_switch)
}

const onRemindShow = () => {
  if (remind.value) {
    remind.value.show()
  }
}

const edit = (record) => {
  router.push({
      path: "/module/hint_keywords/ruleStore",
      query: {
          task_id: record.id
      }
  })
}

const del = (record) => {
  Modal.confirm({
      title: '提示',
      content: '确定删除此规则吗？规则删除后，已经触发该规则的统计记录不会被删除',
      okText: '确定',
      cancelText: '取消',
      onOk: () => {
          const loadClose = message.loading('正在删除')
          record.switching = true
          keywordsRuleDelete({
              id: record.id
          }).then(() => {
              message.success('已删除')
              loadData()
          }).finally(() => {
              record.switching = false
              loadClose()
          })
      }
  })
}

const tableChange = p => {
  pagination.current = p.current
  pagination.pageSize = p.pageSize
  loadData()
}

const linkAddRule = () => {
  router.push('/module/hint_keywords/ruleStore')
}

const statusChange = (record) => {
  let key = record.status_switch_bool ? '开启' : '关闭'
  const cancel = () => {
      record.status_switch_bool = !record.status_switch_bool
  }
  Modal.confirm({
      title: '提示',
      content: `确认${key}该规则？`,
      okText: '确定',
      cancelText: '取消',
      onOk: () => {
        const loadClose = message.loading(`正在${key}`)
        changeStatus({
          id: record.id,
          switch_status: record.switch_status ? 0 : 1 // 0:关闭，1：开启
        }).then(() => {
            message.success('操作完成')
            loadData()
        }).finally(() => {
            loadClose()
        }).catch(() => cancel())
      },
      onCancel: cancel
  })
}

const linkStat = (record, type) => {
  let params = {
    rule_id: record.id,
    date_type: type,
    tab: 'LOAD_BY_DETAIL'
  }

  let href = router.resolve({
    path: "/module/hint_keywords/index",
    query: params
  }).href
  window.open(href)
}

const getConfig = async() => {
    try {
        let res = await noticeInfo();
        if (res.status !== 'success') {
            return;
        }
        updateStatus(res.data)
    } catch (error) {
    }
}

onMounted(() => {
  loadData()
  getConfig()
})
</script>

<style scoped lang="less">
.statistic-box {
  margin-top: 16px;
  margin-bottom: 26px;
}

.header {
  display: flex;
  justify-content: space-between;

  .operate-box {
    display: flex;

    .filter-item {
      display: flex;
      align-items: center;

      .filter-item-label {
        white-space: nowrap;
      }
    }
  }
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

.msg-type-box {
  display: flex;
  gap: 4px;
}

:deep(.rule-table .ant-table) {
  color: #595959;
  font-size: 14px;
}
</style>

<style lang="less">
.user-info-tooltip .ant-tooltip-inner {
  width: max-content;
}
</style>
