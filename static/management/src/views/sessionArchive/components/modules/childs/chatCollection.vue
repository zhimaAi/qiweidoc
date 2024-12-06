<template>
  <div class="chat-collection-box" @click="clickStar">
    <img class="chat-collection-icon"
      :src="is_collect ? require('@/assets/svg/is_collect.svg') : require('@/assets/svg/un_collect.svg')" />
    <!-- 聊天收藏-->
    <a-modal v-model:open="showEvaluate" :title="type == 1 ? '收藏单聊' : '收藏群聊'" width="472px" :confirmLoading="collecting"
      :footer="null" @ok="collect">

      <a-form style="margin-left: 8px;margin-right: 8px;" :label-col="{ span: 4 }" :wrapper-col="{ span: 20 }">
        <a-form-item label="收藏原因" style="margin: 0 0 10px;">
          <a-textarea placeholder="请输入，最多200字" :maxlength="200" v-model:value="collect_msg" />
        </a-form-item>

      </a-form>

      <div style="margin-top: 20px;border-top: 1px solid #dfdfdf;height: 30px;">
        <div style="float: left;color: #8C8C8C;margin-top: 15px;">
            <a-checkbox v-model:checked="noShowEvaluate" style="color: #8C8C8C;">3天内不再弹出</a-checkbox>
        </div>
        <div style="float: right;margin-top: 10px;">
          <a-button @click="noCollect">取消</a-button>
          <a-button type="primary" @click="collect" style="margin-left: 10px;">确认</a-button>
        </div>
      </div>

    </a-modal>
  </div>
</template>

<script setup>
import { joinCollect, cancelCollect } from "@/api/session";
import { reactive, ref, watch } from 'vue'
import { message } from 'ant-design-vue';

const props = defineProps({
    chatInfo: {
        type: Object,
        default: () => {
            return {
                params: {},
                sender: {
                    name: '',
                    department_name: '',
                    userid: '',
                    zm_user_type: '', //STAFF CUSTOMER GROUP
                },
                receiver: {
                    name: '',
                    external_userid: '',
                    conversation_id: '',
                    zm_user_type: '',
                }
            }
        }
    },
    currentMsgCancelCollect: {
        type: Number
    }
})

const emit = defineEmits('changeCollect')
const collect_msg = ref('')
const cacheKey = ref('msg_collect')
// console.log('props.chatInfo', props.chatInfo)
const is_collect = ref(props.chatInfo.params.is_collect || 0)
const collecting = ref(false)
const showEvaluate = ref(false)
const noShowEvaluate = ref(false)
const type = ref(void 0) // 1 单聊； 2 群聊
const searchType = ref(void 0) // 1 按员工； 2 按客户
const noCollect = () => {
  showEvaluate.value = false
}

const collect = () => {
//   console.log('props.chatInfo', props.chatInfo)
  // 如果选中3天内不再弹出，设置缓存
  if (noShowEvaluate.value) {
      let Cache = {
          "showEvaluate": showEvaluate.value,
          "set_time": Date.now()
      }
      localStorage.setItem(cacheKey.value, JSON.stringify(Cache))
  }

  let session_type = type.value
  if (searchType.value == 3) {
    session_type = 1
  }

  let params = {
    conversation_id: props.chatInfo.params.conversation_id,
    collect_reason: collect_msg.value
  }
  joinCollect(params).then(res => {
    is_collect.value = res.data.is_collect
    message.success("收藏成功")
    emit('changeCollect', {
      collect_reason: res.data.collect_reason,
      conversation_id: res.data.conversation_id,
      is_collect: res.data.is_collect
    })
  }).finally(() => {
    showEvaluate.value = false
    collect_msg.value = ""
  })
}

const clickStar = () => {
  if (!is_collect.value) {
    // 验证本地是否存在缓存
    let cacheData = localStorage.getItem(cacheKey.value)
    if (cacheData != null) {
      let NewCacheData = JSON.parse(cacheData)
      let now = Date.now()
      if (now - NewCacheData.set_time < 3600000 * 72) {
        // 直接收藏
        collect()
        return
      }
    }
    // 弹出收藏原因
    collect_msg.value = ''
    showEvaluate.value = true
  } else {
      //   取消收藏
      cancelCollect({ conversation_id: props.chatInfo.params.conversation_id }).then(res => {
      message.success("取消收藏成功")
      is_collect.value = 0
      emit('changeCollect', {
        conversation_id: res.data.conversation_id,
        is_collect: res.data.is_collect
      })
    })
  }
}

watch(() => props.currentMsgCancelCollect, () => {
//   console.log('currentMsgCancelCollect')
  // 只要监听是取消就直接修改取消状态
  is_collect.value = 0
})

watch(() => props.chatInfo, (obj) => {
  // 监听收藏状态
//   console.log('obj', obj)
  is_collect.value = obj.params.is_collect
})

defineExpose({
  clickStar
})

</script>

<style scoped lang="less">
.chat-collection-box {
  display: flex;
  width: 24px;
  height: 24px;
  padding: 4px 4px 5px 5px;
  justify-content: center;
  align-items: center;
  border-radius: 6px;

  &:hover {
    background: var(--07, #E4E6EB);
  }
}
.chat-collection-icon {
  cursor: pointer;
  width: 14px;
}
</style>
