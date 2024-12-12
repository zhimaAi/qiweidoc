<template>
    <div class="zm-main-content lexicon-box">
      <a-alert show-icon message="提前预设敏感词组和敏感词，方便管理和选择"></a-alert>
      <div class="header mt16">
        <a-button type="primary" @click="linkAdd">
          <template #icon>
            <PlusOutlined />
          </template>
          新建敏感词组
        </a-button>
      </div>
      <a-table
        class="mt16 lexicon-table"
        :loading="loading"
        :data-source="list"
        :columns="columns"
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
          <template v-if="'group_name' === column.dataIndex">
              <a-tooltip :title="text">
                  <div class="zm-line-clamp2">{{ text }}</div>
              </a-tooltip>
          </template>
          <template v-else-if="'keywords' === column.dataIndex">
            <span class="tages_wid">
                <template v-if="record.keywords.length > 1">
                    <!-- <a-popover  :overlayStyle="{ zIndex: 998 }">
                        <template #content>
                            <div style="width: 200px">
                            <a-tag
                                style="white-space: pre-wrap; margin-bottom: 5px;"
                                color="blue"
                                v-for="item in record.keywords.slice(
                                1,
                                record.keywords.length
                                )"
                                :key="item.id"
                                >{{ item.name }}</a-tag
                            >
                            </div>
                        </template>
                        <div class="nowrap flex">
                            <a-tag color="blue" style="margin-bottom:0;">{{
                            record.keywords[0] && record.keywords[0].name.length > 10 ? record.keywords[0].name.substring(0, 10) + '...' : record.keywords[0].name
                            }}</a-tag>
                            <div class="nowrap" style="color: #1890ff;width:30px;">
                            +{{ record.keywords.length - 1 }}
                            </div>
                        </div>
                    </a-popover> -->
                    <a-tag
                      class="tag-names"
                      v-for="(item, index) in record.keywords"
                      :key="index"
                      >{{ item }}</a-tag
                    >
                </template>
                <template v-else-if="record.keywords.length == 1">
                  <a-tag
                    class="tag-name"
                    v-if="record.keywords.length"
                    >{{ record.keywords[0] && record.keywords[0].length > 10 ? record.keywords[0].substring(0, 10) + '...' : record.keywords[0] }}</a-tag
                    >
                </template>
                <template v-else>
                    <div>
                        -
                    </div>
                </template>
            </span>
          </template>
          <template v-else-if="'create_user_name' === column.dataIndex">
            <div class="default-content">
              {{ text }}
            </div>
          </template>
          <template v-else-if="'created_at' === column.dataIndex">
            <div class="default-content">
              {{ text }}
            </div>
          </template>
          <template v-else-if="'operate' === column.dataIndex">
              <a class="operate-content" @click="edit(record)">编辑</a>
              <a class="operate-content ml16" @click="del(record)">删除</a>
          </template>
        </template>
      </a-table>
      <Remind ref="remind" @update="updateStatus"/>
      <a-modal v-model:open="addLexiconOpen" :title="`${ isEdit ? '编辑' : '新建' }敏感词组`" @ok="handleOk">
        <a-form
          class="login-form"
          :model="formState"
          name="lexicon"
          autocomplete="off"
          @finish="handleOk"
          :label-col="{ span: 4 }"
          >
          <a-form-item label="敏感词组：">
            <a-input placeholder="请输入敏感词组名称"
                v-model:value="formState.group_name"></a-input>
          </a-form-item>
          <a-form-item label="敏感词：">
              <a-textarea placeholder="请输入敏感词，一次可添加100个，换行表示多个"
                v-model:value="keywordInput"
                @blur="addKeyword"
                :rows="4"></a-textarea>
          </a-form-item>
        </a-form>
      </a-modal>
    </div>
  </template>

  <script setup>
  import { onMounted, ref, reactive } from 'vue';
  import { PlusOutlined, QuestionCircleOutlined } from '@ant-design/icons-vue';
  import Remind from "./remind.vue";
  import { Modal, message } from 'ant-design-vue'
  import { keywordsList, keywordsSave, keywordsDelete } from "@/api/sensitive";

  const isEdit = ref(false)
  const remind = ref(null)
  const triggerNotifyStatus = ref(false)
  const formState = reactive({
    id: void 0,
    group_name: '',
    keywords: []
  })
  const keywordInput = ref('')
  const columns = ref([
    {
      title: "敏感词组名称",
      dataIndex: "group_name",
      width: 180
    },
    {
      dataIndex: "keywords",
      title: "敏感词",
      width: 740
    },
    {
      dataIndex: "create_user_name",
      title: "创建人",
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

  const addLexiconOpen = ref(false);

  function initData () {
    formState.id = void 0
    formState.group_name = ''
    formState.keywords = []
    keywordInput.value = ''
  }

const handleOk = (e) => {
    // 正式100
    if (formState.keywords.length && formState.keywords.length > 100) {
        message.error('最多100个敏感词')
        return false
    }
    let params = {
        id: formState.id,
        group_name: formState.group_name,
        keywords: formState.keywords
    }
    const loadClose = message.loading('正在保存')
    keywordsSave(params).then(res => {
        if (res.status === 'success') {
            addLexiconOpen.value = false;
            message.success('操作成功')
            initData()
            loadData()
        }
    }).finally(() => {
        // initData()
        loadClose()
    }).catch((err) => {

    })
}

const addKeyword = () => {
    formState.keywords = []
    keywordInput.value = keywordInput.value.trim()
    if (keywordInput.value) {
        let keywords
        keywords = keywordInput.value.split("\n")
        for (let key of keywords) {
            if (!formState.keywords.includes(key)) {
                formState.keywords && formState.keywords.push(key)
            }
        }
    }
  }

  const loadData = () => {
    loading.value = true
    let params = {
      page: pagination.current,
      size: pagination.pageSize
    }
    keywordsList(params).then(res => {
      let lists = res.data.items || []
      list.value = lists
      pagination.total = Number(res.data?.total)
    }).finally(() => {
      loading.value = false
    })
  }

  const updateStatus = (config) => {
    triggerNotifyStatus.value = config.notice_switch
  }

  const edit = (record) => {
    isEdit.value = true
    addLexiconOpen.value = true;
    formState.id = record.id
    formState.group_name = record.group_name
    formState.keywords = record.keywords
    keywordInput.value = record.keywords.join('\n')
  }

const del = (record) => {
    Modal.confirm({
        title: '提示',
        content: '确认删除该敏感词组，删除后，之前已选择该词组的规则不再包含对应的敏感词',
        okText: '确定',
        cancelText: '取消',
        onOk: () => {
            const loadClose = message.loading('正在删除')
            record.switching = true
            keywordsDelete({
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

  const linkAdd = () => {
    // 打开新建弹窗
    isEdit.value = false
    initData()
    addLexiconOpen.value = true;
  }

  onMounted(() => {
    loadData()
  })
  </script>

<style scoped lang="less">
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

  .zm-line-clamp2 {
    max-height: 40px;
    color: #595959;
    font-size: 14px;
  }

  .default-content {
    color: #595959;
    font-size: 14px;
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

  .tages_wid {
    display: inline-block;

    .tag-name {
      margin-bottom:0;
      color: #595959;
      font-size: 12px;
      height: 22px;
      line-height: 22px;
    }

    .tag-names {
      white-space: pre-wrap;
      margin-bottom: 5px;
      color: #595959;
      font-size: 12px;
      height: 22px;
      line-height: 22px;
    }
  }

  .nowrap {
    white-space: nowrap;
  }

  .flex {
    display: flex;
    align-items: center;
  }

  .login-form {
    padding-top: 24px;
  }

  :deep(.lexicon-table .ant-table) {
    color: #595959;
    font-size: 14px;
  }
</style>

<style lang="less">
  .user-info-tooltip .ant-tooltip-inner {
    width: max-content;
  }
</style>
