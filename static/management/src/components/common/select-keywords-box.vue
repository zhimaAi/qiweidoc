<template>
  <div>
    <a-button type="dashed" style="margin-right: 16px;" @click="open">
      <PlusOutlined />
      添加关键词
    </a-button>
    <div style="display: flex;flex-wrap: wrap">
      <a-tag v-for="(item, index) in select_list" :key="index" closable @close="onSelectDelete(index)"
        style="margin-top: 6px" class="fx-ac tag">
        <div class="fx-ac" style="flex: 1;">
          <span>{{ item }}</span>
        </div>
      </a-tag>
    </div>
    <a-modal v-model:open="addLexiconOpen" title="添加关键词" @ok="debounceHandleOk">
      <a-form class="login-form" :model="formState" name="lexicon" autocomplete="off" @finish="debounceHandleOk"
        :label-col="{ span: 4 }">
        <a-form-item label="关键词：" style="max-height: 300px; overflow: auto;">
          <div class="list-item" v-for="(item, index) in formState.keyword_list" :key="index">
            <a-input-group compact>
              <a-input placeholder="请输入关键词" v-model:value="item.name" />
            </a-input-group>
            <div class="tag-icon-box">
              <div class="tag-icon-add">
                <img class="tag-icon" src="@/assets/svg/tag-add.svg" alt="">
                <img @click="onTagAdd(index)" class="tag-icon tag-icon-active" src="@/assets/svg/tag-add-active.svg"
                  alt="">
              </div>
              <div class="tag-icon-delete">
                <img v-if="formState.keyword_list.length > 1" @click="onTagDelete(item, index)" class="tag-icon"
                  src="@/assets/svg/tag-delete.svg" alt="">
                <img v-if="formState.keyword_list.length > 1" @click="onTagDelete(item, index)"
                  class="tag-icon tag-icon-active" src="@/assets/svg/tag-delete-active.svg" alt="">
              </div>
            </div>
          </div>
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { PlusOutlined } from '@ant-design/icons-vue';
import { debounce } from "@/utils/tools";
import { Modal, message } from 'ant-design-vue'

const props = defineProps({
    selecteds: {
      type: Array,
      default() {
        return []
      }
    }
})
const emit = defineEmits('change')
const formState = reactive({
  keyword_list: [{
    name: ''
  }]
})
const addLexiconOpen = ref(false)
const select_list = ref([])

watch(() => props.selecteds, (arr) => {
  select_list.value = JSON.parse(JSON.stringify(arr))
}, { immediate: true })

function checkTagEmpty (array) {
  return array.filter(item => item.name && item.name.trim() !== '')
}

const handleOk = () => {
  // 保持之前要判断是否有空的关键词，有则不能保持
  if (checkTagEmpty(formState.keyword_list).length === formState.keyword_list.length - 1) {
    message.error('请输入关键词')
    return false
  }
  let key = '添加'
  const keywords = formState.keyword_list.map(item => item.name)
  emit('change', keywords)
  message.success(`${key}完成`)
  addLexiconOpen.value = false;
}

const debounceHandleOk = debounce(handleOk, 500)

function forMatSelecteds (arr) {
  return arr.map(item => {
    return {
      name: item
    }
  })
}

const open = () => {
  // 打开添加弹窗
  // 初始化数据
  if (props.selecteds.length) {
    formState.keyword_list = forMatSelecteds(props.selecteds)
  }
  addLexiconOpen.value = true;
}

const onTagAdd = (index) => {
  // 有空的则不能新建了
  if (checkTagEmpty(formState.keyword_list).length === formState.keyword_list.length) {
    formState.keyword_list.push({ name: '' })
  } else {
    message.error('请输入关键词')
  }
}

const onTagDelete = (item, index) => {
  // 移除
  const loadClose = message.loading(`正在删除`)
  formState.keyword_list.splice(index, 1)
  loadClose()
  return false
}

const onSelectDelete = (index) => {
  // 移除
  const loadClose = message.loading(`正在删除`)
  select_list.value.splice(index, 1)
  loadClose()
  emit('change', select_list.value)
  return false
}
</script>

<style scoped lang="less">
/deep/ .ant-tag {
  padding: 5px 16px;
  background: #F5F5F5;
}

.login-form {
  padding-top: 24px;
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
