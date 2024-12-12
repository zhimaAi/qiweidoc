<template>
  <a-modal v-model:open="visible" centered @ok="handleOk" wrapClassName="select-words-chat" :destroyOnClose="true"
    @cancel="handleCancel" :width="746">
    <template #title>
      <div class="select-words-title">选择敏感词</div>
    </template>
    <template v-slot:footer>
      <div class="fl-jsb-ac">
        <a-button key="back" @click="handleCancel"> 取消 </a-button>
        <a-button key="submit" type="primary" :loading="submitLoading" @click="handleOk">
          确定 <span v-if="selectedItems && selectedItems.length > 0">({{ selectedItems.length }})</span>
        </a-button>
      </div>
    </template>
    <div class="main flex">
      <div class="left-box">
        <div class="box-title">敏感词组</div>
          <ZmScroll class="datas-item" @load="peopleListLoad" :finished="staff_list_nomore" :loading="loading">
            <div class="group-box">
              <div class="group-item" :class="{'active': item.id === currentItem.id}" v-for="item in list" :key="item.id" @click="onSelect(item)">
                <div class="item-left">
                  <a-checkbox
                    v-model:checked="item.checked"
                    :value="item.id"
                    @change="onSelectTagGroup($event, item)"
                  >
                    {{ item.group_name }}
                  </a-checkbox>
                </div>
                <div class="item-right">{{ item.keywords.length }}</div>
              </div>
            </div>
        </ZmScroll>
      </div>
      <div class="right-box flex1" v-if="currentItem.keywords">
        <div class="box-title">敏感词 ({{ currentItem.keywords.length }})</div>
        <div class="group-box">
          <div class="group-item" v-for="(item, index) in currentItem.keywords" :key="index">
            <div class="item-left">
              <div class="item-text">{{ item }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </a-modal>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { keywordsList } from "@/api/sensitive";
import ZmScroll from "@/components/zmScroll.vue";

const currentItem = ref({})
const emit = defineEmits('selected')
const loading = ref(false)
const visible = ref(false)
const selectedItems = ref([])

const handleCancel = () => {
  visible.value = false
  selectedItems.value = []
}

const onSelectTagGroup = (e, item) => {
  if (e.target.checked) {
    // 选中
    selectedItems.value.push(item)
  } else {
    // 取消选中
    let index = selectedItems.value.indexOf(item)
    if (index > -1) {
      selectedItems.value.splice(index, 1)
    }
  }
}

const onSelect = (item) => {
  currentItem.value = item
}

const submitLoading = ref(false)

const handleOk = () => {
  submitLoading.value = true
  emit('selected', selectedItems.value)
  visible.value = false
  submitLoading.value = false
}

function forMatCheck (array, selectedItems) {
  if (!selectedItems) return array
  if (!selectedItems.length) return array
  let selectIds = selectedItems.map(item => item.id)
  array.map(item => {
    item.checked = selectIds.indexOf(item.id) > -1 ? true : false
  })
  return array
}

const show = (hint_group_ids) => {
  visible.value = true
  selectedItems.value = hint_group_ids || []
}

const staff_list_nomore = ref(false) // 列表没有更多了
const list = ref([])
const pagination = reactive({
  total: 0,
  current: 0,
  pageSize: 10
})

const loadData = () => {
  if (loading.value) return
  loading.value = true
  let params = {
    page: pagination.current,
    size: pagination.pageSize
  }
  keywordsList(params).then(res => {
    let lists = res.data.items || []
    if (!lists.length) {
      staff_list_nomore.value = true
      return false
    }
    pagination.total = Number(res.data?.total)
    if (list.value.length < pagination.total) {
      staff_list_nomore.value = false
    } else {
      staff_list_nomore.value = true
    }
    if (!list.value.length && lists.length) {
      // 首次，选中第一个
      currentItem.value = lists[0]
    }
    if (visible.value) {
      list.value = forMatCheck(list.value.concat(lists), selectedItems.value)
      currentItem.value = list.value[0]
    } else {
      list.value = list.value.concat(lists)
    }
  }).finally(() => {
    loading.value = false
  })
}

// 下滑加载更多
function peopleListLoad () {
  if (loading.value) return
  pagination.current = pagination.current + 1
  loadData()
}

onMounted(() => {
})

defineExpose({
  show
})
</script>

<style scoped lang="less">
.select-words-title {
  color: #000000d9;
  font-feature-settings: 'liga' off, 'clig' off;
  font-family: "PingFang SC";
  font-size: 16px;
  font-style: normal;
  font-weight: 600;
  line-height: 24px;
  height: 40px;
}
.select-words-chat {
  .main {

    .left-box,.right-box {

      .box-title {
        color: #262626;
        font-family: "PingFang SC";
        font-size: 14px;
        font-style: normal;
        font-weight: 600;
        line-height: 22px;
        margin-bottom: 12px;
        padding-left: 8px;
      }

      .group-box {
        overflow: auto;

        .group-item {
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: space-between;
          width: 100%;
          height: 32px;
          padding: 5px 8px;
          gap: 8px;
          border-radius: 2px;
          &:hover {
            background: var(--09, #F2F4F7);
          }

          .item-left {
            display: flex;
            align-items: center;
            gap: 6px;
            height: 22px;
            flex: 1 0 0;
            overflow: hidden;
            color: #595959;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-family: "PingFang SC";
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;

            .item-checkbox {
              width: 16px;
              height: 16px;
              cursor: pointer;
            }

            label {
              cursor: pointer;
            }
          }

          .item-right {
            color: #8c8c8c;
            text-align: right;
            font-family: "PingFang SC";
            font-size: 12px;
            font-style: normal;
            font-weight: 400;
            line-height: 20px;
          }
        }

        .active {
          background: var(--01-, #E5EFFF);

          .item-left {
            label {
              color: #2475FC;
            }
          }

          .item-right {
            color: #2475FC;
          }
        }
      }
    }
  }
}
</style>

<style lang="less">
.select-words-chat {
  .ant-modal-header {
    border-bottom: 0;
    padding: 16px 16px 0 24px;
    margin-bottom: 0;
  }
  .ant-modal-body {
    border-top: 1px solid rgba(0, 0, 0, 0.15);
  }
  .ant-modal-content {
    padding: 0;
  }
  .ant-modal-title {
    font-weight: 400;
  }
  .main {
    height: 460px;
  }
  .ant-modal-footer {
    padding: 10px 16px;
    border-top: 1px solid rgba(0, 0, 0, 0.15);
    margin-top: 0;
  }
  .left-box {
    width: 371px;
    height: 100%;
    border-right: 1px solid rgba(0, 0, 0, 0.15);
    padding: 16px;
    display: flex;
    flex-direction: column;
    .list-header {
      margin-top: 16px;
      font-size: 14px;
      color: rgba(0, 0, 0, 0.45);
      span {
        color: rgba(0, 0, 0, 0.65);
      }
    }
    .list {
      margin-top: 16px;
      flex: 1;
      overflow-y: auto;
      .ant-checkbox-group {
        width: 100%;
      }
      .item {
        transform: translate3d(0, 0, 0);
        padding: 8px 5px;
        font-size: 14px;
        width: 100%;
        &:hover {
          background-color: rgba(239, 244, 252, 1);
        }
        .ant-checkbox-wrapper,
        .ant-radio-wrapper {
          display: flex;
          align-items: center;
        }
        .group-name {
          font-weight: 500;
          font-size: 14px;
          color: #000000;
          margin-bottom: 4px;
          width: 270px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .head-portrait {
          width: 20px;
          height: 20px;
          border-radius: 2px;
          border-radius: 50%;
        }
        .leader{
          font-size: 12px;
          font-weight: 400;
          color: rgba(0,0,0,0.35);
        }
        .name {
          margin-left: 4px;
          width: 98px;
          font-size: 12px;
          font-weight: 400;
          color: #989898;
        }
        .leader-num{
          font-size: 12px;
          font-weight: 500;
          color: #989898;
        }
        .member {
          margin-left: 18px;
          color: rgba(0, 0, 0, 0.45);
          // span {
          //   color: rgba(0, 0, 0, 0.65);
          // }
        }
      }
    }
  }
  .right-box {
    font-size: 14px;
    padding: 16px;
    height: 100%;
    display: flex;
    flex-direction: column;
    .right-header {
      padding: 0 5px 8px 13px;
      .title {
        color: rgba(0, 0, 0, 0.85);
        span {
          color: rgba(0, 0, 0, 0.45);
        }
      }
    }
    // .right-list-spin{
    //   display: flex;
    //   justify-content: center;
    //   align-items: center;
    //   height: 100%;
    // }
    .right-list {
      flex: 1;
      overflow-y: auto;
      .right-item {
        padding: 8px 5px;
        &:hover {
          background-color: rgba(239, 244, 252, 1);
        }
        .item-left {
          .title {
            color: #000000;
            margin-bottom: 4px;
            font-weight: 500;
            width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }
          .head-portrait {
            width: 20px;
            height: 20px;
            border-radius: 2px;
            border-radius: 50%;
          }
          .leader{
            font-size: 12px;
            font-weight: 400;
            color: rgba(0,0,0,0.35);
          }
          .name {
            margin-left: 4px;
            width: 98px;
            font-size: 12px;
            font-weight: 400;
            color: #989898;
          }
          .leader-num{
            font-size: 12px;
            font-weight: 500;
            color: #989898;
          }
        }
      }
    }
  }
}

.datas-item {
  height: 420px;
  margin-top: 8px;
  overflow-y: auto;

  .select {
    border-radius: 2px;
    opacity: 1;
    border: 1px solid #2475FC;
  }
}
</style>
