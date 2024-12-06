<template>
   <div>
       <MainNav active="rule"/>
       <div class="zm-main-box">
           <a-card :bordered="false">
               <template #title>
                   <div class="card-title">
                       <span class="title">工作时间段设置</span>
                       <span
                           class="desc">设置工作时间段后，则仅对设定的时间段内对开启会话存档的员工进行工作质量检测，单聊和群聊均使用此规则</span>
                   </div>
               </template>
               <TimesRange ref="timeRangeRef" @change="configChange"/>
           </a-card>
           <a-card :bordered="false">
               <template #title>
                   <div class="card-title">
                       <span class="title">@员工后消息回复率</span>
                       <span
                           class="desc">设置消息回复率后，群内成员的消息回复率将根据此处设置的时间进行统计，该设置仅对密码模式生效</span>
                   </div>
               </template>
               <a-form-item label="消息回复率" style="margin-bottom: 0px;">
                   <div class="zm-flex-center">
                       <a-input-number v-model:value="config.group_at_msg_reply_sec" :min="1" :max="180"
                                       @change="configChange"/>
                       <span class="ml4">分钟</span>
                   </div>
                   <div class="notice">
                       注意：消息回复率修改后，只会对修改后的数据生效，历史数据不生效，为防止数据出现误差，请谨慎操作
                   </div>
               </a-form-item>
           </a-card>
           <a-card :bordered="false">
               <template #title>
                   <div class="card-title">
                       <span class="title">消息回复率</span>
                       <span class="desc">设置消息回复率后，单聊/群聊中的消息回复率将根据此处设置的时间进行统计</span>
                   </div>
               </template>
               <a-form-item label="消息回复率" style="margin-bottom: 0;">
                   <div class="zm-flex-center">
                       <a-input-number v-model:value="config.msg_reply_sec" :min="1" :max="20" @change="configChange"/>
                       <span class="ml4">分钟</span>
                   </div>
                   <div class="notice">
                       注意：消息回复率修改后，只会对修改后的数据生效，历史数据不生效，为防止数据出现误差，请谨慎操作
                   </div>
                   <a-checkbox
                       class="mt10"
                       v-model:checked="config.group_other_effect"
                       @change="configChange">
                       群内其他员工{{ config.msg_reply_sec || '-' }}分钟内回复了，也算作{{
                           config.msg_reply_sec || '-'
                       }}分钟回复率
                   </a-checkbox>
                   <div class="mt10" v-if="config.group_other_effect">
                       <a-radio-group v-model:value="config.satff_radio" @change="configChange">
                           <a-radio :value="1">指定员工</a-radio>
                           <a-radio :value="2">全部群内员工</a-radio>
                       </a-radio-group>
                       <div class="tips">
                           指定员工算作{{ config.msg_reply_sec || '-' }}分钟回复率，其他未设置的群内员工回复了，不计入。该设置仅对群内成员生效
                       </div>
                       <div v-if="config.satff_radio == 1" class="mt10">
                           <a-button @click="selectStaff">选择员工</a-button>
                           <div class="fx-ac" style="flex-wrap: wrap;width: 891px;">
                               <a-tag
                                   v-for="(item, index) in staffList"
                                   :key="item.staff_id"
                                   class="staff-tag"
                                   closable
                                   @close="staffDel(index)"
                               >
                                   {{ item.name }}
                               </a-tag>
                           </div>
                       </div>
                   </div>
               </a-form-item>
           </a-card>
           <a-card :bordered="false">
               <template #title>
                   <div class="card-title">
                       <span class="title">群聊未回复规则</span>
                       <span
                           class="desc">设置未回复规则后，群聊中未回复聊天数和未回复聊天占比将根据此规则进行统计</span>
                   </div>
               </template>
               <div class="main-body">
                   <a-alert type="info"
                            :show-icon="false"
                            banner
                            message="群聊时，客户发送的最后一句话成功匹配到关键词或消息类型匹配为选中类型，且员工没有回复，不算作未回复消息"></a-alert>
                   <a-form-item label="全匹配" class="mt24">
                       <a-input placeholder="请输入自定义匹配关键词"
                                :max-length="20"
                                v-model:value="input.group_keywords_full"
                                @keydown.enter="inputHandle('group_keywords','full')"
                                @blur="inputHandle('group_keywords','full')"
                                style="width: 360px"/>
                       <div class="keyword-tags">
                           <a-tag v-for="(keyword,i) in config.group_keywords.full"
                                  class="zm-customize-tag"
                                  style="margin: 8px 8px 0 0;"
                                  :closable="MODE != 2"
                                  @close="(e) => removeTag('group_keywords','full',i,e)"
                                  :key="i">{{ keyword }}
                           </a-tag>
                       </div>
                   </a-form-item>
                   <a-form-item label="半匹配">
                       <a-input placeholder="请输入自定义匹配关键词"
                                :max-length="20"
                                v-model:value="input.group_keywords_half"
                                @keydown.enter="inputHandle('group_keywords','half')"
                                @blur="inputHandle('group_keywords','half')"
                                style="width: 360px"/>
                       <div class="keyword-tags">
                           <a-tag v-for="(keyword,i) in config.group_keywords.half"
                                  class="zm-customize-tag"
                                  style="margin: 8px 8px 0 0;"
                                  closable
                                  @close="(e) => removeTag('group_keywords','half',i,e)"
                                  :key="i">{{ keyword }}
                           </a-tag>
                       </div>
                       <a-checkbox class="mt24" v-model:checked="config.other_effect">群内其他员工回复也算作已回复
                       </a-checkbox>
                   </a-form-item>
                   <a-form-item label="消息类型" style="margin-bottom: 0;">
                       <a-checkbox-group v-model:checked="config.group_keywords.msg_type_filter" @change="configChange">
                           <a-checkbox value="image">图片</a-checkbox>
                           <a-checkbox value="emoji_preg">emoji</a-checkbox>
                           <a-checkbox value="emotion">表情包</a-checkbox>
                       </a-checkbox-group>
                   </a-form-item>
                   <div class="zm-fixed-bottom-box in-module">
                       <a-button type="primary">保 存</a-button>
                   </div>
               </div>
           </a-card>
       </div>
   </div>
</template>

<script setup>
import {ref, reactive} from 'vue';
import MainLayout from "@/components/mainLayout.vue";
import TimesRange from "@/components/tools/timesRange.vue";
import MainNav from "@/views/Modules/group_chat_stat/components/mainNav.vue";

const timeRangeRef = ref(null)
const staffList = ref([])
const input = reactive({
    single_keywords_full: "",
    single_keywords_half: "",
    group_keywords_full: "",
    group_keywords_half: "",
    staff_single_keywords_full: "",
    staff_single_keywords_half: "",
})
const config = reactive({
    _id: "",
    corp_id: "",
    owner_id: "",
    group_at_msg_reply_sec: 3,
    msg_reply_sec: 3,
    single_keywords: {
        full: [],
        half: [],
        msg_type_filter: [],
    },
    group_keywords: {
        full: [],
        half: [],
        msg_type_filter: [],
    },
    staff_single_keywords: {
        full: [],
        half: [],
    },
    other_effect: true,
    group_other_effect: false, // 0:未选中，1:选中 需要转换成布尔渲染
    group_staff_user_json: [], // 给后端的是json字符串，包含员工id，name
    satff_radio: 2,
    staffIds: []
})

function configChange() {

}

function selectStaff() {
}

function staffDel() {
}

function configFilled(config) {
    // for (let key in this.config) {
    //     if (typeof this.config[key] === "object" && !config[key]) {
    //         continue
    //     }
    //     this.config[key] = config[key]
    // }
}

function inputHandle(field, type) {
    // let inputFiled
    // try {
    //     inputFiled = field + "_" + type
    //     let val = this.input[inputFiled].trim()
    //     if (!val) {
    //         return
    //     }
    //     if (this.config[field][type].length > 19) {
    //         throw "每项最多输入20个关键词"
    //     }
    //     if (this.config[field].half.includes(val)
    //         || this.config[field].full.includes(val)) {
    //         throw "请勿重复输入关键字"
    //     } else {
    //         this.config[field][type].push(val)
    //     }
    // } catch (e) {
    //     this.$message.error(e)
    // }
    // this.input[inputFiled] = ""
    // this.configChange()
}

function removeTag(field, type, index, e) {
    // e.preventDefault();
    // this.config[field][type].splice(index, 1)
    // this.configChange()
}
</script>

<style scoped lang="less">
@import "@/common/sessionStatRule";
</style>
