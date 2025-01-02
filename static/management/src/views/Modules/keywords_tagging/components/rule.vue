<template>
  <div class="zm-main-content">
    <a-alert show-icon message="通过设置关键词，对客户或者员工发送的会话记录触发了关键词的可以打上对应的标签。"></a-alert>
    <div class="statistic-box">
      <statistic />
    </div>
    <div class="header mt16">
      <a-button type="primary" @click="linkAddTask">
        <template #icon>
          <PlusOutlined />
        </template>
        新建规则
      </a-button>
    </div>
    <a-table class="mt16 task-table" :loading="loading" :data-source="list" :columns="columns" :scroll="{ x: 1500 }"
      :pagination="pagination" @change="tableChange">
      <template #headerCell="{ column }">
        <template v-if="'total' === column.dataIndex">
          <a-tooltip>
            <template #title>
              <div>今日：今日触发规则的次数</div>
              <div>昨天：昨日触发规则的次数</div>
              <div>总触发次数：该监控总的触发次数</div>
            </template>
            <div>
              今日/昨日/总标签数
              <QuestionCircleOutlined />
            </div>
          </a-tooltip>
        </template>
      </template>
      <template #bodyCell="{ column, text, record }">
        <template v-if="'name' === column.dataIndex">
          <a-tooltip :title="text">
            <div class="zm-line-clamp2" style="max-height: 40px;">{{ text }}</div>
          </a-tooltip>
        </template>
        <template v-else-if="'partial_match' === column.dataIndex">
          <div>{{ text.join('，') }}</div>
        </template>
        <template v-else-if="'full_match' === column.dataIndex">
          <div>{{ text.join('，') }}</div>
        </template>
        <template v-else-if="'all_tag_info' === column.dataIndex">
          <span class="tages_wid">
            <template v-if="record.all_tag_info.length > 1">
              <a-popover :overlayStyle="{ zIndex: 998 }">
                <template #content>
                  <div style="width: 500px">
                    <a-tag style="white-space: pre-wrap; margin-bottom: 5px;" color="blue" v-for="item in record.all_tag_info.slice(
                      1,
                      record.all_tag_info.length
                    )" :key="item.tag_id">{{ item.name }}</a-tag>
                  </div>
                </template>
                <div class="nowrap flex">
                  <a-tag color="blue" style="margin-bottom:0;">{{
                    record.all_tag_info[0] && record.all_tag_info[0].name.length > 10 ?
                      record.all_tag_info[0].name.substring(0, 10) +
                      '...' : record.all_tag_info[0].name
                    }}</a-tag>
                  <div class="nowrap" style="color: #1890ff;width:30px;">
                    +{{ record.all_tag_info.length - 1 }}
                  </div>
                </div>
              </a-popover>
            </template>
            <template v-else-if="record.all_tag_info.length == 1">
              <a-tag color="blue" style="margin-bottom:0;" v-if="record.all_tag_info.length">{{ record.all_tag_info[0]
                && record.all_tag_info[0].name.length > 10 ? record.all_tag_info[0].name.substring(0, 10) + '...' :
                record.all_tag_info[0].name }}</a-tag>
            </template>
            <template v-else>
              <div>
                -
              </div>
            </template>
          </span>
        </template>
        <template v-else-if="'switch' === column.dataIndex">
          <a-switch v-model:checked="record.status_switch_bool" checked-children="开"
            un-checked-children="关" @change="statusChange(record)" />
        </template>
        <template v-else-if="'total' === column.dataIndex">
          <a @click="linkStat(record, 1)">{{ record.today_count }}</a>
          <a-divider type="vertical" />
          <a @click="linkStat(record, 2)">{{ record.yesterday_count }}</a>
          <a-divider type="vertical" />
          <a @click="linkStat(record, 0)">{{ record.total_count }}</a>
        </template>
        <template v-else-if="'created_at' === column.dataIndex">
            {{ dayjs(text).format("YYYY-MM-DD HH:mm:ss") }}
        </template>
        <template v-else-if="'operate' === column.dataIndex">
          <!-- <a class="operate-content" @click="onDetail(record)">详情</a> -->
          <a class="operate-content ml16" @click="edit(record)">编辑</a>
          <a class="operate-content ml16" @click="del(record)">删除</a>
        </template>
      </template>
    </a-table>
  </div>
</template>

<script setup>
import { onMounted, ref, reactive } from 'vue';
import dayjs from 'dayjs';
import { PlusOutlined, QuestionCircleOutlined } from '@ant-design/icons-vue';
import { Modal, message } from 'ant-design-vue'
import { useRouter } from 'vue-router';
import statistic from './statistic.vue'
import { taskList, taskChangeSwitch, taskDelete } from "@/api/session"

const router = useRouter()
const columns = ref([
  {
    title: "规则名称",
    dataIndex: "name",
    width: 140
  },
  {
    dataIndex: "partial_match",
    title: "模糊匹配",
    width: 120
  },
  {
    dataIndex: "full_match",
    title: "精准匹配",
    width: 120
  },
  {
    dataIndex: "all_tag_info",
    title: "客户标签",
    width: 160
  },
  {
    // title: "今日/昨日/总标签数",
    dataIndex: "total",
    width: 180,
  },
  {
    dataIndex: "created_at",
    title: "创建时间",
    width: 200
  },
  {
    title: "是否启用",
    dataIndex: "switch",
    width: 160
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

const loadData = () => {
  loading.value = true
  let params = {
    page: pagination.current,
    size: pagination.pageSize
  }
  taskList(params).then(res => {
    let lists = res.data.items || []
    lists.map((item) => {
      item.status_switch_bool = (item.switch.value == 1)
    })
    list.value = lists
    pagination.total = Number(res.data?.total)
  }).finally(() => {
    loading.value = false
  })
}

const edit = (record) => {
  router.push({
    path: "/module/keywords_tagging/ruleStore",
    query: {
      task_id: record.id
    }
  })
}

const onDetail = (record) => {
  router.push({
    path: "/module/keywords_tagging/ruleStore",
    query: {
      task_id: record.id
    }
  })
}

const del = (record) => {
  Modal.confirm({
    title: '提示',
    content: '确定删除该规则吗？',
    okText: '确定',
    cancelText: '取消',
    onOk: () => {
      const loadClose = message.loading('正在删除')
      record.switching = true
      taskDelete({
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

const linkAddTask = () => {
  router.push('/module/keywords_tagging/ruleStore')
}

const statusChange = (record) => {
  let key = record.status_switch_bool ? '开启' : '关闭'
  const cancel = () => {
    record.status_switch_bool = !record.status_switch_bool
  }
  Modal.confirm({
    title: '提示',
    content: `确认${key}该规则吗？`,
    okText: '确定',
    cancelText: '取消',
    onOk: () => {
      const loadClose = message.loading(`正在${key}`)
      taskChangeSwitch({
        id: record.id,
        switch: record.switch.value ? 0 : 1 // 0:关闭，1：开启
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
    task_id: record.id,
    date_type: type
  }

  router.push({
    path: "/module/keywords_tagging/details",
    query: params
  })
}

onMounted(() => {
  loadData()
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

:deep(.task-table .ant-table) {
  color: #595959;
  font-size: 14px;
}
</style>

<style lang="less">
.user-info-tooltip .ant-tooltip-inner {
  width: max-content;
  }

</style>
