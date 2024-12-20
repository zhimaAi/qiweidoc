<template>
  <div class="container">
    <a-form :label-col="{ span: 3 }" :wrapper-col="{ span: 21 }">
      <!-- <a-card title="基础设置" :bordered="false"> -->
      <a-form-item required label="规则名称">
        <a-input v-model:value="formState.rule_name" placeholder="请输入规则名称" :max-length="20" style="width: 320px;" />
      </a-form-item>
      <a-form-item required label="聊天场景">
        <a-radio-group v-model:value="formState.chat_type">
          <a-radio :value="0">全部</a-radio>
          <a-radio :value="1">仅单聊</a-radio>
          <a-radio :value="2">仅群聊</a-radio>
        </a-radio-group>
        <div class="group-select" v-if="[2].includes(formState.chat_type)">
          <a-radio-group v-model:value="formState.group_chat_type">
            <a-radio :value="1">全部群聊</a-radio>
            <a-radio :value="2">指定群聊</a-radio>
          </a-radio-group>
          <SelectChatBox v-if="formState.group_chat_type == 2" class="mt8" @change="groupChange"
            :selectedChats="selectedChats" />
        </div>
      </a-form-item>
      <a-form-item required label="触发对象">
        <a-radio-group v-model:value="formState.check_user_type">
          <a-radio :value="0">全部</a-radio>
          <a-radio :value="1">仅客户</a-radio>
          <a-radio :value="2">仅员工</a-radio>
        </a-radio-group>
      </a-form-item>
      <a-form-item required label="敏感词">
        <a-button type="dashed" @click="onShowAdd">
          <template #icon>
            <PlusOutlined />
          </template>
          快速添加 ({{ total }})
        </a-button>
        <div class="trigger-object-info">除选择敏感词组外，可自定义其他敏感词</div>
        <div class="tag-group">
          <a-tag v-for="(item, i) in formState.hint_group_ids" :key="i" class="zm-customize-tag"
            @close="removeGroupKeyword($event, i)" closable>{{ item.group_name }}
          </a-tag>
        </div>

        <div class="textarea-box">
          <div class="tag-group">
            <a-tag v-for="(key, i) in formState.hint_keywords" :key="i" class="zm-customize-tag"
              @close="removeKeyword($event, i)" closable>{{ key }}
            </a-tag>
          </div>
          <a-textarea placeholder="请输入敏感词，键盘回车可换行添加多个" v-model:value="keywordInput" @blur="addKeyword" :rows="2"></a-textarea>
        </div>
      </a-form-item>
      <a-form-item required label="敏感行为">
        <a-checkbox-group v-model:value="formState.target_msg_type">
          <a-checkbox value="link">发送链接</a-checkbox>
          <a-checkbox value="weapp">发送小程序</a-checkbox>
          <a-checkbox value="card">发送名片</a-checkbox>
          <a-checkbox value="qr_code">发送二维码图片</a-checkbox>
          <a-checkbox value="link_text">发送包含链接的文本 (发送的文本内容中包含域地址)</a-checkbox>
        </a-checkbox-group>
      </a-form-item>
      <a-form-item></a-form-item>
      <div class="zm-fixed-bottom-box in-module" style="padding-left: 130px;">
        <a-button @click="cancel">取 消</a-button>
        <a-button @click="save" :loading="saving" class="ml8" type="primary">保 存</a-button>
      </div>
    </a-form>
    <!-- <WhitelistModal ref="whitelistRef" @ok="whitelistChange" /> -->

    <!-- 选择敏感词组 -->
    <SelectSensitivePhrase ref="selectSensitivePhrase" @selected="selectedChange"></SelectSensitivePhrase>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import SelectChatBox from "@/components/common/select-chat-box.vue";
// import SelectStaffBoxNormal from "../../../components/common/select-staff-box-normal.vue";
import { copyObj, assignData } from "@/utils/tools";
// import {ruleInfo, ruleSave, whiteList} from "../../../api/auth-mode/sensitive-words";
// import SelectStaffBox from "../../../components/common/select-staff-box.vue";
// import WhitelistModal from "./whitelist-modal.vue";
import SelectSensitivePhrase from './selectSensitivePhrase.vue'
import { PlusOutlined } from '@ant-design/icons-vue';
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { keywordsList, keywordsRuleInfo, keywordsRuleSave } from "@/api/sensitive";

const route = useRoute()
const router = useRouter()
const query = route.query
const selectSensitivePhrase = ref(null)
// const whitelistRef = ref(null)
const taskId = ref(0)
const isCopy = ref(false)
const loading = ref(false)
// const sessionArchiveAuth = ref(false)
const saving = ref(false)
// const currentWhitelistType = ref('')
const selectedChats = ref([])
const selectedStaffs = ref([])
// const whitelist = reactive({
//   1: [],
//   2: [],
//   3: [],
// })
// const whitelistTotal = reactive({
//   1: 0,
//   2: 0,
//   3: 0,
// })
const keywordInput = ref('')
const formState = reactive({
  id: void 0, // 编辑规则ID
  rule_name: "", // 规则名称
  chat_type: 0, // 聊天会话场景 0:全部，1:单聊，2：群聊
  check_user_type: 0, // 触发对象 0:全部，1:仅客户，2:仅员工
  // session_scope: 0,
  // staff_type: 0,
  group_chat_type: 1, // 群聊类型 1:全部，2:指定群聊
  hint_group_ids: [], // 选择的敏感词组
  hint_keywords: [], // 主动添加的敏感词
  target_msg_type: [], // 触发消息类型 敏感行为
  group_chat_id: [], // 指定群聊ID列表
  // staff_list: [],
  // mobile_withe_switch: true,
  // bank_card_withe_switch: true,
  // email_withe_switch: true,
})

const selectedChange = (select) => {
  formState.hint_group_ids = select
}

const onShowAdd = () => {
  selectSensitivePhrase.value.show(formState.hint_group_ids)
}

const addKeyword = () => {
  keywordInput.value = keywordInput.value.trim()
  if (keywordInput.value) {
    let keywords
      keywords = keywordInput.value.split("\n")
    for (let key of keywords) {
      if (!formState.hint_keywords.includes(key)) {
        formState.hint_keywords.length < 20 && formState.hint_keywords.push(key)
      }
    }
  }
  keywordInput.value = ""
}

const getDetail = () => {
  if (taskId.value > 0) {
    loading.value = true
    keywordsRuleInfo({id: taskId.value}).then(res => {
      try {
        let data = res.data || {}
        data.group_chat_type = 1 // 群聊类型 默认全部
        // data.staff_type = 0
        if (data.group_chat_id.length) {
          data.group_chat_type = 2 // 群聊类型 指定群聊
          data.group_chat_info.map(group => {
            if (!group.name) {
              group.member_list = JSON.parse(group.member_list)
              group.name = group.member_list.map(i => i.name).join("、")
            }
          })
          selectedChats.value = data.group_chat_info
        }
        //   if (data.staff_list) {
        //     // data.staff_type = 1
        //     data.staffs.map(staff => {
        //       staff.staff_id = staff._id
        //     })
        //     selectedStaffs.value = data.staffs
        //   }
        assignData(formState, data)
        formState.hint_group_ids = data.hint_group_info
      } catch (e) {
      }
    }).finally(() => {
        loading.value = false
    })
  }
}

// const loadWhitelist = () => {
//   whitelistRequest(1)
//   whitelistRequest(2)
//   whitelistRequest(3)
// }

// const whitelistRequest = (type) => {
  // whiteList({
  //     page: 1,
  //     size: 100,
  //     white_type: type,
  // }).then(res => {
  // let res = { "msg": "success", "res": 0, "data": { "white_list_ret": { "pagination": { "pageParam": "page", "pageSizeParam": "per-page", "forcePageParam": true, "route": null, "params": null, "urlManager": null, "validatePage": true, "totalCount": "4", "defaultPageSize": 20, "pageSizeLimit": [1, 50] }, "list": [{ "_id": "44", "owner_id": "494", "corp_int_id": "561", "white_type": "3", "white": "1445114677@qq.com", "create_staff_id": "3432", "create_time": "2024-09-30 10:17:19", "update_time": "1727662639", "white_type_dec": "邮箱", "create_staff_name": "彭建龙" }, { "_id": "19", "owner_id": "494", "corp_int_id": "561", "white_type": "3", "white": "365@qq.com", "create_staff_id": "1896", "create_time": "2024-06-07 15:12:47", "update_time": "1717744367", "white_type_dec": "邮箱", "create_staff_name": "李静" }, { "_id": "18", "owner_id": "494", "corp_int_id": "561", "white_type": "3", "white": "6556@qq.com", "create_staff_id": "1896", "create_time": "2024-06-07 15:12:47", "update_time": "1717744367", "white_type_dec": "邮箱", "create_staff_name": "李静" }, { "_id": "17", "owner_id": "494", "corp_int_id": "561", "white_type": "3", "white": "976354@qq.com", "create_staff_id": "1896", "create_time": "2024-06-07 15:12:47", "update_time": "1717744367", "white_type_dec": "邮箱", "create_staff_name": "李静" }] }, "count_list": [{ "white_type": "1", "num": "5" }, { "white_type": "2", "num": "3" }, { "white_type": "3", "num": "4" }] } }
  // let data = res.data.white_list_ret || {}
  // whitelist[type] = data.list || []
  // whitelistTotal[type] = data.pagination.totalCount || 0
  // })
// }

const groupChange = (val) => {
  selectedChats.value = val
  formState.group_chat_id = val.map(item => item.chat_id)
}

// const staffChange = (val) => {
//     selectedStaffs.value = val
//     formState.staff_list = val.map(item => item.user_id)
// }

// const showWhiteListModal = (type) => {
//     currentWhitelistType.value = type
//     whitelistRef.value.show(type)
// }

// const whitelistChange = () => {
//   whitelistRequest(currentWhitelistType.value)
// }

const removeGroupKeyword = ($event, index) => {
  $event.preventDefault();
  formState.hint_group_ids.splice(index, 1)
}

const removeKeyword = ($event, index) => {
  $event.preventDefault();
  formState.hint_keywords.splice(index, 1)
}

const cancel = () => {
  router.push({ path: "/module/hint_keywords/index?tab=LOAD_BY_RULE" })
}

const verify = () => {
  formState.rule_name = formState.rule_name.trim()
  if (!formState.rule_name) throw "请输入规则名称"
  if ([0, 2].includes(formState.chat_type)) {
    if (formState.group_chat_type == 2 && !formState.group_chat_id.length) throw "请选择指定群聊"
  } else {
    formState.group_chat_id = []
    selectedChats.value = []
  }
  if ([2].includes(formState.check_user_type)) {
    // if (!formState.staff_list.length) throw "请选择指定员工"
  } else {
    // formState.staff_list = []
    selectedStaffs.value = []
  }
  if (!(formState.hint_keywords.length || formState.hint_group_ids.length || formState.target_msg_type.length)) throw "敏感词或敏感行为至少完善一项"
}

const save = () => {
  saving.value = true
  try {
    verify()
    let data = copyObj(formState)
    if (!isCopy.value && taskId.value > 0) {
      data.id = taskId.value
    }
    if (data.hint_group_ids) {
        data.hint_group_ids = data.hint_group_ids.map(item => item.id)
    }
    keywordsRuleSave(data).then(res => {
      if (res.status === 'success') {
        message.success("操作成功")
        setTimeout(() => {
          cancel()
        }, 1200)
        saving.value = false
      }
    })
  } catch (e) {
    message.error(e)
    saving.value = false
  }
}

const total = ref(0)

const loadData = () => {
  let params = {
    page: 1,
    size: 1
  }
  keywordsList(params).then(res => {
    total.value = Number(res.data?.total)
  })
}

onMounted(() => {
  taskId.value = query.task_id || 0
  isCopy.value = (query.is_copy == 1)
  if (taskId.value) {
    // loadWhitelist()
    getDetail()
  }
  loadData()
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
  margin: 12px;
  padding: 24px 0;
  min-height: 100vh;

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
</style>
