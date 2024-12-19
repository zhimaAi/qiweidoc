<template>
  <div>
    <MainNavbar title="标签管理"/>
    <div class="zm-main-content lexicon-box">
      <!-- <a-alert show-icon message="提前预设标签组和标签，方便管理和选择"></a-alert> -->
      <div class="statistic-box">
        <div class="statistic-item">
          <div class="statistic-title">企微标签数</div>
          <div class="statistic-num">{{ tagLength }}</div>
        </div>
        <div class="statistic-item">
          <div class="statistic-title">企微标签组</div>
          <div class="statistic-num">{{ list.length }}</div>
        </div>
      </div>
      <div class="header mt16">
        <a-button type="primary" @click="linkAdd">
          <template #icon>
            <PlusOutlined />
          </template>
          新建标签组
        </a-button>
        <!-- <div class="fx-ac update-box">
          <div>
            <span class="lefts_sizes" v-if="last_sync_time">
              上次更新时间：{{ timeContrast(last_sync_time) }}
            </span>
          </div>
          <a-button :loading="updateLoading" class="lefts btn-update" @click="Update">
            <template #icon>
              <RedoOutlined class="update-icon" />
            </template>
            {{ updateLoading ? "更新中" : "更新标签数据" }}
          </a-button>
        </div> -->
      </div>
      <div class="tag-main">
        <div class="tag-item" v-for="item in list" :key="item.group_id">
          <div class="tag-label-box">
            <div class="tag-label-title">{{ item.group_name }}</div>
            <div class="tag-label-info">标签数：{{ item.tag.length }}</div>
          </div>
          <div class="tag-content-box">
            <div v-for="ctem in item.tag" :key="ctem.id" class="tag">{{ ctem.name }}</div>
          </div>
          <div class="tag-options-box">
            <div class="tag-options" @click="edit(item)">
              <img class="options-icon" src="../../../assets/svg/set.svg" alt="">
              <img class="options-icon active" src="../../../assets/svg/set-active.svg" alt="">
              编辑
            </div>
            <div class="tag-options tag-options-delete" @click="onDelete(item)">
              <img class="options-icon" src="../../../assets/svg/delete.svg" alt="">
              <img class="options-icon active" src="../../../assets/svg/delete-active.svg" alt="">
              删除
            </div>
          </div>
        </div>
      </div>
      <a-modal v-model:open="addLexiconOpen" :title="`${isEdit ? '编辑' : '新建'}标签组`" @ok="debounceHandleOk">
        <a-form class="login-form" :model="formState" name="lexicon" autocomplete="off" @finish="debounceHandleOk"
          :label-col="{ span: 4 }">
          <a-form-item label="标签组：">
            <a-input placeholder="请输入标签组名称" v-model:value="formState.group_name"></a-input>
          </a-form-item>
          <a-form-item label="标签：" style="max-height: 300px; overflow: auto;">
              <div class="list-item" v-for="(item, index) in formState.keyword_list" :key="index">
                <a-input-group compact>
                  <a-input placeholder="请输入标签名称" v-model:value="item.name" />
                </a-input-group>
                <div class="tag-icon-box">
                  <div class="tag-icon-add">
                    <img class="tag-icon" src="../../../assets/svg/tag-add.svg" alt="">
                    <img @click="onTagAdd(index)" class="tag-icon tag-icon-active" src="../../../assets/svg/tag-add-active.svg" alt="">
                  </div>
                  <div class="tag-icon-delete">
                    <img v-if="formState.keyword_list.length > 1" @click="onTagDelete(item, index)" class="tag-icon" src="../../../assets/svg/tag-delete.svg" alt="">
                    <img v-if="formState.keyword_list.length > 1" @click="onTagDelete(item, index)" class="tag-icon tag-icon-active" src="../../../assets/svg/tag-delete-active.svg" alt="">
                  </div>
                </div>
              </div>
          </a-form-item>
        </a-form>
      </a-modal>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, reactive, h } from 'vue';
import { RedoOutlined, PlusOutlined } from '@ant-design/icons-vue';
import { Modal, message } from 'ant-design-vue'
import { debounce } from "@/utils/tools";
import { apiTags, updateTags, deleteGroupTags, deleteTags } from "@/api/company";
import MainNavbar from "@/components/mainNavbar.vue";

const last_sync_time = ref('') // 更新数据
const updateLoading = ref(false)
const isEdit = ref(false)
const tagLength = ref(0)
const formState = reactive({
  group_name: '',
  keyword_list: [
    {
      id: '',
      name: '',
      order: 1
    }
  ],
  group_id: void 0,
  order: 1
})

const loading = ref(false)
const list = ref([])

const addLexiconOpen = ref(false);

function forMatTag(array) {
  let newArr = []
  for (let index = 0; index < array.length; index++) {
    const item = array[index];
    newArr.push({
      id: item.id,
      name: item.name,
      order: item.order
    })
  }
  return newArr
}

function checkTagEmpty (array) {
  return array.filter(item => item.name && item.name.trim() !== '')
}

const onTagAdd = (index) => {
  // 有空的则不能新建了
  if (checkTagEmpty(formState.keyword_list).length === formState.keyword_list.length) {
    formState.keyword_list.push({ id: '', name: '', order: index + 2 })
  } else {
    message.error('请输入标签名称')
  }
}

const handleOk = () => {
  // 保持之前要判断是否有空的标签，有则不能保持
  if (checkTagEmpty(formState.keyword_list).length === formState.keyword_list.length - 1) {
    message.error('请输入标签名称')
    return false
  }
  let key = isEdit.value ? '编辑' : '新建'
  const loadClose = message.loading(`正在${key}`)
  let params = {
    group_name: formState.group_name,
    tag: formState.keyword_list,
    order: formState.order
  }
  if (isEdit.value) {
    params.group_id = formState.group_id
    params.tag = forMatTag(formState.keyword_list)
  }
  updateTags(params).then((res) => {
    if (res.status === 'success') {
      message.success(`${key}完成`)
      loadData()
      addLexiconOpen.value = false;
      // 清空数据
      initData()
    }
  }).finally(() => {
    loadClose()
  }).catch(err => {
    // console.log('err', err)
    // 失败也刷新，数据是双向绑定的
    loadData()
  })
}

const debounceHandleOk = debounce(handleOk, 500)

const loadData = async() => {
  loading.value = true
  let params = {}
  await apiTags(params).then(res => {
    if (res.status === 'success') {
      list.value = res.data || []
      tagLength.value = res.data.reduce((accumulator, current) => accumulator + current.tag.length, 0)
    }
  }).finally(() => {
    loading.value = false
  })
}

const edit = (record) => {
//   console.log(record)
  formState.group_name = record.group_name
  formState.keyword_list = record.tag
  formState.group_id = record.group_id
  formState.order = record.order
  // 打开编辑弹窗
  isEdit.value = true
  addLexiconOpen.value = true;
}

function forMatUpperOrder (array) {
  // 找到最大的order
  return array.reduce((prev, cur) => {
    return Math.max(prev, cur.order)
  }, 0)
}

function initData () {
  formState.group_name = '',
  formState.keyword_list = [
    {
      id: '',
      name: '',
      order: 1
    }
  ]
  formState.group_id = void 0
  formState.order = 1
}

const linkAdd = () => {
  // 打开新建弹窗
  // 初始化数据
  initData()
  isEdit.value = false
  formState.order = forMatUpperOrder(list.value) + 1
  // console.log('formState.order', formState.order)
  addLexiconOpen.value = true;
}

function timeContrast (time) {
  // 当前时间
  let new_time = parseInt(new Date().getTime() / 1000) + ''
  let result = ''
  let diffTime = ''

  // 同步时间差
  diffTime = new_time * 1 - +time
  if (diffTime <= 60) {
    result = diffTime + '秒前 '
  } else if (diffTime > 60 && diffTime <= 3660) {
    result = parseInt(diffTime / 60) + '分钟前 '
  } else if (diffTime > 3660 && diffTime <= 86400) {
    result = parseInt(diffTime / 3600) + '小时前 '
  } else if (diffTime > 86400) {
    result = parseInt(diffTime / 86400) + '天前 '
  }
  return result
}

const refresh = ref(0)
const Update = async() => {
  //更新数据
  if (refresh.value == 0) {
    refresh.value = 1;
    updateLoading.value = true;
    await loadData()
    refresh.value = 0;
    updateLoading.value = false;
    message.success('更新成功')
  }
}

const onDelete = (item) => {
  Modal.confirm({
    // title: `确认${key}么`,
    content: '删除后，已添加到客户信息的标签也一起删除',
    okText: '确定',
    cancelText: '取消',
    onOk: () => {
      const loadClose = message.loading(`正在删除`)
      deleteGroupTags({
        group_id: item.group_id
      }).then(() => {
        message.success('操作完成')
        loadData()
      }).finally(() => {
        loadClose()
      })
    }
  })
}

const onTagDelete = (item, index) => {
  // 移除
  // console.log('item', item)
  if (formState.keyword_list.length === 1) {
    message.error('请至少保留一个')
    return
  }

  Modal.confirm({
    // title: `确认${key}么`,
    content:h("div", null, [
    h("span", {
        style:
        "display: inline-block; margin-top: 22px;"
    },'删除后，已添加到客户信息的标签也一起删除'),
    h(
        "span",
        {
        style:
            "color: red; display: block; margin-top: 4px;"
        },
        "（若标签组内只有1个标签时，删除标签会连带标签组一起删除）"
    )
    ]),
    okText: '确定',
    style: 'width: 520px;',
    cancelText: '取消',
    onOk: () => {
      const loadClose = message.loading(`正在删除`)
      // 如果是新增的则不用调接口
      if (!item.id) {
        formState.keyword_list.splice(index, 1)
        loadClose()
        return false
      }
      deleteTags({
        tag_id: item.id
      }).then(() => {
        message.success('操作完成')
        formState.keyword_list.splice(index, 1)
      }).finally(() => {
        loadClose()
      }).catch(err => {

      })
    }
  })

}

onMounted(() => {
  loadData()
})
</script>

<style scoped lang="less">
.header {
  display: flex;
  gap: 16px;

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
    margin-bottom: 0;
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

.statistic-box {
  display: flex;
  align-items: center;
  gap: 24px;
  font-family: "PingFang SC";

  .statistic-item {
    display: flex;
    align-items: center;
    gap: 8px;

    .statistic-title {
      color: #262626;
      font-size: 14px;
      font-style: normal;
      font-weight: 600;
      line-height: 22px;
    }

    .statistic-num {
      color: #595959;
      font-size: 14px;
      font-style: normal;
      font-weight: 400;
      line-height: 22px;
    }
  }
}

.update-box {
  display: flex;
  align-items: center;

  .btn-update {
    color: #595959;

    &:hover,
    &:hover .update-icon {
      color: #4096ff;
    }

  }
}

.tag-main {
  background-color: white;
  display: flex;
  flex-direction: column;
  margin-top: 16px;

  .tag-item {
    font-family: "PingFang SC";
    display: flex;
    padding: 16px;
    border-bottom: 1px solid #E8E8E8;

    .tag-label-box {
      flex-basis: 215px;
      display: flex;
      flex-direction: column;

      .tag-label-title {
        align-self: stretch;
        color: #000000d9;
        font-feature-settings: 'liga' off, 'clig' off;
        font-size: 14px;
        font-style: normal;
        font-weight: 600;
        line-height: 22px;
      }

      .tag-label-info {
        color: #00000073;
        font-feature-settings: 'liga' off, 'clig' off;
        font-size: 14px;
        font-style: normal;
        font-weight: 400;
        line-height: 22px;
      }
    }

    .tag-content-box {
      flex: 1;
      display: flex;
      flex-wrap: wrap;
      gap: 8px;

      .tag {
        display: flex;
        padding: 4px 16px;
        height: 30px;
        align-items: flex-start;
        gap: 10px;
        border-radius: 6px;
        border: 1px solid #00000026;
        background: var(--000000, #00000005);
        color: #000000a6;
        font-size: 14px;
        font-style: normal;
        font-weight: 400;
        line-height: 22px;
      }
    }

    .tag-options-box {
      flex-basis: 80px;
      display: flex;
      flex-direction: column;
      gap: 16px;
      color: #595959;
      font-size: 14px;
      font-style: normal;
      font-weight: 400;
      line-height: 22px;

      .tag-options {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;

        .options-icon {
          width: 16px;
        }

        .active {
          display: none;
        }

        &:hover {
          color: #2475FC;

          .options-icon {
            display: none;
          }

          .active {
            display: block;
          }
        }
      }

      .tag-options-delete {
        &:hover {
          color: #FB363F;

          .options-icon {
            display: none;
          }

          .active {
            display: block;
          }
        }
      }
    }
  }
}

.list-item {
  display: flex;
  align-items: center;
  margin-bottom: 8px;

  .tag-icon-box {
    padding: 0 8px;
    display: flex;
    gap: 8px;
    justify-content: space-between;
  }

  .tag-icon {
    cursor: pointer;
    width: 24px;
    height: 24px;
  }
}

.tag-icon-add {

  .tag-icon-active {
    display: none;
  }

  &:hover {
    .tag-icon {
      display: none;
    }
    .tag-icon-active {
      display: inline-block;
    }
  }
}

.tag-icon-delete {

  .tag-icon-active {
    display: none;
  }

  &:hover {
    .tag-icon {
      display: none;
    }
    .tag-icon-active {
      display: inline-block;
    }
  }
}
</style>

<style lang="less">
.user-info-tooltip .ant-tooltip-inner {
  width: max-content;
}
</style>
