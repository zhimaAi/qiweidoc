<template>
  <div class="container">
    <a-form :label-col="{ span: 3 }" :wrapper-col="{ span: 21 }">
      <a-card title="基础设置" :bordered="false">
        <a-form-item required label="规则名称">
          <a-input v-model:value="formState.name" placeholder="请输入规则名称" :max-length="20" style="width: 320px;" />
        </a-form-item>
        <a-form-item required label="生效员工">
          <SelectStaffBoxNormal
            :selectedStaffs="formState.staff_info_list"
            @change="staffChange"/>
        </a-form-item>
      </a-card>

      <a-card title="设置关键词" :bordered="false">
        <a-form-item required label="模糊匹配">
          <SelectPartialBoxNormal
            :selecteds="formState.partial_match"
            @change="partialChange"/>
        </a-form-item>
        <a-form-item required label="精准匹配">
          <SelectFullBoxNormal
            :selecteds="formState.full_match"
            @change="fullChange"/>
        </a-form-item>
      </a-card>

      <a-card :bordered="false">
        <template #title>
          <div>
            自动打标签
            <span class="card-title-tip">触发关键词次数为模糊匹配和精准匹配关键词总和</span>
          </div>
        </template>
        <a-form-item required label="聊天场景">
          <a-radio-group v-model:value="formState.check_chat_type.value">
            <a-radio :value="1">仅单聊</a-radio>
            <a-radio :value="2">仅群聊</a-radio>
          </a-radio-group>
          <!-- <div class="group-select" v-if="[2].includes(formState.check_chat_type)">
              <a-radio-group v-model:value="formState.check_chat_type">
                <a-radio :value="1">全部群聊</a-radio>
                <a-radio :value="2">指定群聊</a-radio>
              </a-radio-group>
              <SelectChatBox v-if="formState.check_chat_type == 2" class="mt8" @change="groupChange"
                :selectedChats="selectedChats" />
            </div> -->
        </a-form-item>

        <a-form-item required label="生效用户">
          <a-radio-group v-model:value="formState.check_type.value">
            <a-radio :value="1">仅客户</a-radio>
            <a-radio :value="2">仅员工</a-radio>
            <a-radio :value="3">客户或员工</a-radio>
          </a-radio-group>
        </a-form-item>

        <a-form-item required :label="`规则${index + 1}`" v-for="(item, index) in formState.rules_list" :key="index">
          <div class="rule-box">
            <span v-if="formState.check_type.value == 1">客户每</span>
            <span v-if="formState.check_type.value == 2">员工每</span>
            <span v-if="formState.check_type.value == 3">客户或员工每</span>
            <a-select v-model:value="item.toggle_interval" style="width: 80px; margin: 0 10px;">
              <a-select-option :value="1">天</a-select-option>
              <a-select-option :value="2">周</a-select-option>
              <a-select-option :value="3">月</a-select-option>
            </a-select>
            <span>触发关键词</span>
            <a-input v-model:value="item.toggle_num" style="width: 80px; margin: 0 10px;"></a-input>
            <span>次</span>
            <span>自动打上标签</span>
            <a-select style="width: 320px; margin: 0 10px;" v-model:value="item.tag_ids" @change="change" @dropdownVisibleChange="showTagModal(index)"
              class="tag-select" mode="multiple" :open="false" :max-tag-count="1" allowClear placeholder="请选择客户标签"
              >
              <a-select-option v-for="tag in item.tag" :key="tag.id" :value="tag.id">{{ tag.name }}</a-select-option>
            </a-select>

            <div class="tag-icon-delete">
                <img v-if="formState.rules_list.length > 1" @click="onDelete(item, index)" class="tag-icon"
                    src="@/assets/svg/tag-delete.svg" alt="">
                <img v-if="formState.rules_list.length > 1" @click="onDelete(item, index)"
                    class="tag-icon tag-icon-active" src="@/assets/svg/tag-delete-active.svg" alt="">
            </div>

            <SelectTagModal ref="cstTagRef" @change="filterTagChange" :keys="item.tag_ids" />
          </div>
        </a-form-item>

        <a-form-item class="rule-store-options-item-box">
          <template #label>
            <div class="options-label"></div>
          </template>
          <div class="options-box">
            <a class="add-rule" @click="onAddRule">添加规则</a>
            <div class="select-box">
              <a-checkbox v-model:checked="status_switch_bool">任务创建后默认开启</a-checkbox>
            </div>
          </div>
        </a-form-item>
      </a-card>

      <a-form-item></a-form-item>
      <Foot style="padding-left: 130px; left: 15px; width: calc(100% - 30px);">
        <a-button @click="cancel">取 消</a-button>
        <a-button @click="save" :loading="saving" class="ml8" type="primary">保 存</a-button>
      </Foot>
    </a-form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import SelectTagModal from "@/components/select-customer-tag/selectTagModal.vue";
import SelectStaffBoxNormal from "@/components/common/select-staff-box-normal.vue";
import SelectPartialBoxNormal from "@/components/common/select-keywords-box.vue";
import SelectFullBoxNormal from "@/components/common/select-keywords-box.vue";
import Foot from "@/components/common/foot";
import { copyObj, assignData } from "@/utils/tools";
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { taskInfo, taskSave } from "@/api/session"

const route = useRoute()
const router = useRouter()
const query = route.query
const cstTagRef = ref(null)
const taskId = ref(0)
// const isCopy = ref(false)
const loading = ref(false)
const saving = ref(false)
const setStaff = ref(null)
const selectedStaff = ref([])
const contactCompKey = ref(1)

const formState = reactive({
  id: void 0, // 编辑规则ID
  name: "", // 规则名称
  check_chat_type: {
    name: 'SingleChat',
    value: 1
  },
  check_type: {
    name: 'Custom',
    value: 1
  },
  rules_list: [{
    toggle_interval: 1,
    toggle_num: '1',
    tag: [],
    tag_ids: []
  }], // 规则列表数组
  staff_info_list: [],
  staff_userid_list: [],
  partial_match: [],
  full_match: []
})

const onAddRule = () => {
  if (formState.rules_list.length >= 5) {
    message.success('最多可添加5条规则')
    return false
  }
  formState.rules_list.push({
    toggle_interval: 1,
    toggle_num: '1',
    tag: [],
    tag_ids: []
  })
}

const onDelete = (item, index) => {
  // 移除
  const loadClose = message.loading(`正在删除`)
  formState.rules_list.splice(index, 1)
  loadClose()
  return false
}

const change = () => {
  // filterData.keyword = filterData.keyword.trim()
  // emit('change', filterData)
}

const showTagModal = (index) => {
  currentIndex.value = index
  cstTagRef.value[index].show()
}

const currentIndex = ref(1)

const filterTagChange = ({ tagKeys, tags }) => {
  formState.rules_list[currentIndex.value].tag_ids = tagKeys
  formState.rules_list[currentIndex.value].tag = tags
  change()
}

const search = val => {
  // filterData.value = val
  // main.contactStaff = null
  // main.contactCustomer = null
  // main.contactGroup = null
  contactCompKey.value += 1
}

const onShowStaff = () => {
  setStaff.value.show(selectedStaff.value);
}

const del = (index) => {
  selectedStaff.value.splice(index, 1);
}

function staffChange(staffs) {
  formState.staff_userid_list = staffs.map(item => item.userid)
  formState.staff_info_list = staffs
}

function partialChange(partials) {
  formState.partial_match = partials
}

function fullChange(fulls) {
  formState.full_match = fulls
}

const status_switch_bool = ref(false)

const getDetail = () => {
  if (taskId.value > 0) {
    loading.value = true
    taskInfo({ id: taskId.value }).then(res => {
      try {
        let data = res.data || {}
        status_switch_bool.value = (data.switch.value == 1)
        assignData(formState, data)
      } catch (e) {
      }
    }).finally(() => {
      loading.value = false
    })
  }
}

const cancel = () => {
  router.push({ path: "/module/keywords_tagging/index" })
}

const verify = () => {
  formState.name = formState.name.trim()
  try {
    if (!formState.name) throw "请输入规则名称"
    if (!formState.staff_userid_list.length) {
      throw "请选择生效员工"
    }
    if (!formState.full_match.length && !formState.partial_match.length) {
      throw "请选择模糊匹配或精准匹配"
    }
    formState.rules_list.map((item, index) => {
      if (!item.tag_ids.length) {
        throw `请选择规则${index + 1}的客户标签`
      }
    })
    return true
  } catch (e) {
    message.error(e)
    return false
  }
}

function forMatRuleList (arr) {
  return arr.map(item => {
    return {
      toggle_interval: item.toggle_interval,
      toggle_num: item.toggle_num,
      tag_ids: item.tag_ids
    }
  })
}

const save = () => {
  try {
    if (!verify()) {
        return
    }
    saving.value = true
    let params = {
      name: formState.name,
      check_chat_type: formState.check_chat_type.value,
      switch: status_switch_bool.value ? 1 : 0,
      check_type: formState.check_type.value,
      rules_list: forMatRuleList(formState.rules_list),
      staff_userid_list: formState.staff_userid_list,
      partial_match: formState.partial_match,
      full_match: formState.full_match
    }
    if (taskId.value > 0) {
      params.id = taskId.value
    }
    taskSave(params).then(res => {
      if (res.status === 'success') {
        message.success("操作成功")
        setTimeout(() => {
          cancel()
        }, 1200)
      }
    }).finally(() => {
        saving.value = false
    })
  } catch (e) {
    message.error(e)
    saving.value = false
  }
}

onMounted(() => {
  taskId.value = query.task_id || 0
  // isCopy.value = (query.is_copy == 1)
  if (taskId.value) {
    getDetail()
  }
})
</script>

<style scoped lang="less">
.c8C8C8C {
  color: #8C8C8C;
}

.cED744A {
  color: #ED744A;
}

.container {
  background: #FFFFFF;
  padding-bottom: 80px;
  margin: 16px;
  padding: 24px 0;
  min-height: calc(100vh - 180px);

  /deep/ .ant-card-head {
    padding: 0 24px;
    font-size: 14px;
    font-weight: 600;
    color: #262626;

    .ant-card-head-title {
      padding: 12px 0;
    }
  }

  /deep/ .ant-form-item-control {
    line-height: 28px;
  }

  /deep/ .ant-card-body {
    .ant-form-item {
      margin-bottom: 20px;

      &:last-child {
        margin-bottom: 0;
      }
    }
  }

  /deep/ .whitelist-popover .ant-popover-title {
    border: none;
  }

  /deep/ .ant-popover-inner-content {
    padding-top: 0;
  }

  .cs-wrapper {
    width: 400px;
    background: #F2F4F7;
    border-radius: 2px;
    padding: 16px;
    margin-top: 8px;
  }
}

.ml4 {
  margin-left: 4px;
}

.pd0 {
  padding: 0;
}

.popover-scroll {
  max-height: 260px;
  overflow-y: scroll;
}

/deep/ .ant-checkbox-wrapper.w100 {
  >span:last-child {
    display: inline-block;
    min-width: 100px;
  }
}

.trigger-object-info {
  align-self: stretch;
  color: #8c8c8c;
  font-family: "PingFang SC";
  font-size: 14px;
  font-style: normal;
  font-weight: 400;
  line-height: 22px;
  margin: 2px 0 8px;
}

.textarea-box {
  width: 500px;
  padding: 16px;
  border-radius: 2px;
  background: var(--09, #F2F4F7);
}

.group-select {
  margin-top: 16px;
}

.add-rule {
  margin-top: 20px;
}

.select-box {
  margin-top: 10px;
}

.card-title-tip {
  font-size: 12px;
  color: #595959;
  margin-left: 10px;
}

.rule-box {
  display: flex;
  align-items: center;
}

.tag-icon-delete {
  cursor: pointer;

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
.rule-store-options-item-box {
  .ant-form-item-label >label::after {
    display: none;
  }
}
</style>
