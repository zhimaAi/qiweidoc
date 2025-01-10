<template>
    <div class="remind">
        <a-modal
                :width="800"
                title="提醒设置"
                 v-model:open="visible"
                :closable="false"
                :confirmLoading="confirmLoading"
                @cancel="visible = false"
                @ok="handleOk"
        >
            <a-alert type="info" show-icon
            message="开启后，可选择设置应用通知、企微群通知、钉钉群通知"></a-alert>
            <div class="fx-ac item">
                <span class="property">当前状态：</span>
                <div class="right">
                    <a-switch v-model:checked="notice_switch" checked-children="开" un-checked-children="关"/>
                </div>
            </div>
            <div class="fx-ac item align-start">
                <span class="property">推送方式：</span>
                <div class="right">
                    <a-checkbox-group v-model:value="push_type">
                        <div class="checkbox-item">
                            <a-checkbox :value="1">应用推送
                                <a-tooltip title="将通过接入的自建应用推送消息">
                                    <QuestionCircleOutlined />
                                </a-tooltip>
                            </a-checkbox>
                            <div v-if="push_type.includes(1)" class="notify-block pt8">
                                <a-button class="add" type="dashed" @click="onShowStaff">选择员工</a-button>
                                <div v-if="selectedStaff.length > 0" class="mt8">
                                    <a-tag
                                            class="zm-customize-tag"
                                            closable
                                            @close="del(index)"
                                            v-for="(item, index) in selectedStaff"
                                            :key="index">{{ item.name }}
                                    </a-tag>
                                </div>
                            </div>
                        </div>
                        <div class="checkbox-item">
                            <a-checkbox :value="2">企微群通知
                                <a-tooltip title="当达到触发条件时，消息将推送到已配置的企微群聊中">
                                    <QuestionCircleOutlined />
                                </a-tooltip>
                            </a-checkbox>
                            <div v-if="push_type.includes(2)" class="notify-block">
                                <div class="fx-ac item align-baseline">
                                    <span class="property required">Webhook 地址：</span>
                                    <div class="right">
                                        <a-input v-model:value="wechat_notice_hook" placeholder="请输入webhook地址"
                                                 style="width: 382px;"/>
                                        <div class="mt2"><a href="https://www.kancloud.cn/wikizhima/zmwk/3071448"
                                                            target="_blank">如何获取？</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="fx-ac item align-baseline">
                                    <span class="property required">@人：</span>
                                    <div class="right">
                                        <a-radio-group v-model:value="wechat_notice_type">
                                            <a-radio :value="0">无需@群成员</a-radio>
                                            <a-radio :value="1">@所有人</a-radio>
                                            <a-radio :value="2">@指定群成员</a-radio>
                                        </a-radio-group>
                                        <template v-if="wechat_notice_type == 2">
                                            <div class="tip-message mt2">
                                                请填写要@的员工的企微账号。可以在企微通讯录中查看员工账号
                                            </div>
                                            <SelectStaffBoxNormal
                                                    @change="noticeStaffChange"
                                                    :selectedStaffs="wechat_notice_user"
                                                    maxShowWidth="600px"
                                                    class="mt8"/>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="checkbox-item">
                            <a-checkbox :value="3">钉钉群通知
                                <a-tooltip title="当达到触发条件时，消息将推送到已配置的钉钉群聊中">
                                    <QuestionCircleOutlined />
                                </a-tooltip>
                            </a-checkbox>
                            <div v-if="push_type.includes(3)" class="notify-block">
                                <div class="fx-ac item">
                                    <span class="property required">Webhook 地址：</span>
                                    <div class="right">
                                        <a-input v-model:value="dingtalk_notice_hook" placeholder="请输入webhook地址"
                                                 style="width: 382px;"/>
                                    </div>
                                </div>
                                <div class="fx-ac item align-baseline">
                                    <span class="property required">加签secret：</span>
                                    <div class="right">
                                        <a-input v-model:value="dingtalk_notice_secret" placeholder="请输入加签secret"
                                                 style="width: 382px;"/>
                                        <div class="mt2"><a
                                                href="https://www.kancloud.cn/wikizhima/shipinhaoxiaodian/3162861"
                                                target="_blank">如何获取？</a></div>
                                    </div>
                                </div>
                                <div class="fx-ac item align-baseline">
                                    <span class="property required">@人：</span>
                                    <div class="right">
                                        <a-radio-group v-model:value="dingtalk_notice_type">
                                            <a-radio :value="0">无需@群成员</a-radio>
                                            <a-radio :value="1">@所有人</a-radio>
                                            <a-radio :value="2">@指定群成员</a-radio>
                                        </a-radio-group>
                                        <template v-if="dingtalk_notice_type == 2">
                                            <div class="tip-message mt2">请填写要@群成员手机号</div>
                                            <div class="mt8">
                                                <a-button type="dashed" @click="showAddPhone">
                                                    <PlusOutlined />
                                                    添加群成员
                                                </a-button>
                                                <div class="mt8 phone-box">
                                                    <a-tag v-for="(phone,i) in dingtalk_notice_user"
                                                           :key="i"
                                                           closable
                                                           @close="(e) => delPhone(e,i)"
                                                           class="zm-customize-tag">{{ phone }}
                                                    </a-tag>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- <div class="checkbox-item">
                            <div v-if="push_type.includes(4)" class="notify-block">
                                <div class="fx-ac item">
                                    <span class="property required">Webhook 地址：</span>
                                    <div class="right">
                                        <a-input v-model:value="fs_webhook_url" placeholder="请输入webhook地址"
                                                 style="width: 382px;"/>
                                    </div>
                                </div>
                                <div class="fx-ac item align-baseline">
                                    <span class="property required">加签secret：</span>
                                    <div class="right">
                                        <a-input v-model:value="fs_secret" placeholder="请输入加签secret"
                                                 style="width: 382px;"/>
                                        <div class="mt2"><a
                                                href="https://www.yuque.com/zhimaxiaoshiwangluo/arbkc8/mi3320l9eef7frif?singleDoc#"
                                                target="_blank">如何获取？</a></div>
                                    </div>
                                </div>
                            </div>
                        </div> -->
                    </a-checkbox-group>
                </div>
            </div>
<!--            <div class="fx-ac item">-->
<!--                <span class="property">提醒设置：</span>-->
<!--                <div class="right">-->
<!--                    <a-checkbox v-model="only_notice_once">同一条消息触发多个规则只发一个提醒</a-checkbox>-->
<!--                </div>-->
<!--            </div>-->
        </a-modal>

        <selectStaffNew
                selectType="multiple"
                ref="setStaff" @change="(val) => staffUpdate(val)"
        ></selectStaffNew>

        <a-modal v-model:open="phoneModalVisible"
                 centered
                 :mask="false"
                 title="添加群成员"
                 @ok="phoneModalSave"
                 ok-text="确定"
        >
            <a-input v-model:value="phoneInputVal"
                     @input="formatPhones"
                     placeholder="请输入要@的群成员钉钉绑定的手机号，可输入多个逗号分割"></a-input>
        </a-modal>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { PlusOutlined } from '@ant-design/icons-vue';
import selectStaffNew from '@/components/select-staff-new/index'
import SelectStaffBoxNormal from "@/components/common/select-staff-box-normal.vue";
// import {getNotifyConfig, setNotifyConfig} from "@/api/auth-mode/sensitive-words";
import { QuestionCircleOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue'
import { noticeSave, noticeInfo } from "@/api/sensitive";

const emit = defineEmits('update')
const visible = ref(false)
const phoneModalVisible = ref(false)
const phoneInputVal = ref("")
const confirmLoading = ref(false)
const selectedStaff = ref([])
const notice_switch = ref(false) // 通知开关 0：关闭，1：开启 传给后端需要转换
const push_type = ref([])
const wechat_notice_type = ref(0)
const wechat_notice_hook = ref("")
const wechat_notice_user = ref([])
const dingtalk_notice_type = ref(0)
const dingtalk_notice_hook = ref("")
const dingtalk_notice_secret = ref("")
const dingtalk_notice_user = ref([])
// const fs_webhook_url = ref("")
// const fs_secret = ref("")
// const only_notice_once = ref(true)
const setStaff = ref(null)
const msg_send_frequency_type = ref(false)
const msg_send_frequency_a = ref('')
const msg_send_frequency_b = ref('')
const statusId = ref('')
const infoId = ref(void 0)

const show = () => {
    visible.value = true;
}

const onShowStaff = () => {
    setStaff.value.show(selectedStaff.value);
}

const addEmployee = () => {
    setStaffBox.value.show(selectedStaff.value);
}

const findMatchingElementsById = (arr1, arr2) => {
  const map = new Map();
  const matches = [];

  // 使用map记录array1中的元素
  arr1.forEach(element => map.set(element.userid, element));

  // 在array2中查找匹配的元素
  arr2.forEach(element => {
    const match = map.get(element);
    if (match) {
      matches.push(match); // 添加匹配对
    }
  });

  return matches;
};

const getConfig = async() => {
    try {
        let res = await noticeInfo();
        if (res.status !== 'success') {
            return;
        }
        let config = res.data || {}
        infoId.value = config.id
        notice_switch.value = (config.notice_switch == 1);
        // only_notice_once.value = (config.only_notice_once == 1);
        wechat_notice_type.value = Number(config.wechat_notice_type || 0);
        dingtalk_notice_type.value = Number(config.dingtalk_notice_type || 0);
        if (config.app_notice_switch) {
            push_type.value.push(1)
        }
        if (config.wechat_notice_switch) {
            push_type.value.push(2)
        }
        if (config.dingtalk_notice_switch) {
            push_type.value.push(3)
        }
        if (push_type.value.includes(1)) {
            if (config.app_notice_userid.length && config.staff_user_info.length) {
                let staffs = findMatchingElementsById(config.staff_user_info, config.app_notice_userid)
                selectedStaff.value = staffs
            }
        }
        if (push_type.value.includes(2)) {
            wechat_notice_hook.value = config.wechat_notice_hook || ""
            if (config.wechat_notice_user.length && config.staff_user_info.length) {
                let staffs = findMatchingElementsById(config.staff_user_info, config.wechat_notice_user)
                wechat_notice_user.value = staffs
            }
        }
        if (push_type.value.includes(3)) {
            dingtalk_notice_hook.value = config.dingtalk_notice_hook || ""
            dingtalk_notice_secret.value = config.dingtalk_notice_secret || ""
            dingtalk_notice_user.value = config.dingtalk_notice_user
        }
        // if (push_type.value.includes(4)) {
        //     fs_webhook_url.value = config.fs_webhook_url || ""
        //     fs_secret.value = config.fs_secret || ""
        // }
        statusId.value = res.data.id || "";
    } catch (error) {
    }
}

const handleOk = async() => {
    try {
        let params = {
            id: infoId.value,
            notice_switch: Number(notice_switch.value),
            // only_notice_once: Number(only_notice_once.value),
        }
        if (notice_switch.value && !push_type.value.length) {
            throw "请选择至少一种推送方式"
        }
        if (notice_switch.value && push_type.value.includes(1)) {
            if (!selectedStaff.value || selectedStaff.value.length === 0) {
                throw "请选择接收通知管理员"
            }
            let staff_ids = selectedStaff.value.map(i => i.userid)
            params.app_notice_switch = 1
            params.app_notice_userid = staff_ids
        }
        if (push_type.value.includes(2)) {
            wechat_notice_hook.value = wechat_notice_hook.value.trim()
            if (notice_switch.value && wechat_notice_hook.value == "") {
                throw "请输入企微Webhook地址"
            }
            if (notice_switch.value && wechat_notice_type.value == 2 && wechat_notice_user.value.length < 1) {
                throw "请选择指定企微群成员"
            }
            params.wechat_notice_switch = 1
            params.wechat_notice_type = wechat_notice_type.value
            params.wechat_notice_hook = wechat_notice_hook.value
            params.wechat_notice_user = wechat_notice_user.value.map(i => i.userid)
        }
        if (push_type.value.includes(3)) {
            dingtalk_notice_hook.value = dingtalk_notice_hook.value.trim()
            dingtalk_notice_secret.value = dingtalk_notice_secret.value.trim()
            if (notice_switch.value && dingtalk_notice_hook.value == "") {
                throw "请输入钉钉群Webhook地址"
            }
            if (notice_switch.value && dingtalk_notice_secret.value == "") {
                throw "请输入钉钉群加签secret"
            }
            if (notice_switch.value && dingtalk_notice_type.value == 2 && dingtalk_notice_user.value.length < 1) {
                throw "请选择指定钉钉群成员"
            }
            params.dingtalk_notice_switch = 1
            params.dingtalk_notice_hook = dingtalk_notice_hook.value
            params.dingtalk_notice_type = dingtalk_notice_type.value
            params.dingtalk_notice_secret = dingtalk_notice_secret.value
            params.dingtalk_notice_user = dingtalk_notice_user.value
        }
        switch (msg_send_frequency_type.value) {
            case 1:
                if (notice_switch.value && msg_send_frequency_a.value === "") {
                    throw "请输入限制时间"
                }
                params.msg_send_frequency = msg_send_frequency_a.value
                break
            case 2:
                if (notice_switch.value && msg_send_frequency_b.value === "") {
                    throw "请输入限制时间"
                }
                params.msg_send_frequency = msg_send_frequency_b.value
                break
        }
        if (statusId.value) {
            params.id = statusId.value;
        }
        let res = await noticeSave(params)
        message.success("已保存")
        emit("update", {
            notice_switch: notice_switch.value,
        });
        await getConfig()
        visible.value = false;
    } catch (error) {
        typeof error === "string" && message.error(error);
    }
}

const staffUpdate = (val) => {
    selectedStaff.value = val;
}

const del = (index) => {
    selectedStaff.value.splice(index, 1);
}

const showAddPhone = () => {
    phoneInputVal.value = ""
    phoneModalVisible.value = true
}

const formatPhones = () => {
    phoneInputVal.value = phoneInputVal.value.replace(/，/g, ",")
}

const phoneModalSave = () => {
    phoneInputVal.value = phoneInputVal.value.trim()
    if (phoneInputVal.value == "") {
        return message.error("请输入群成员手机号")
    }
    let phones = phoneInputVal.value.split(",")
    for (let phone of phones) {
        if (!dingtalk_notice_user.value.includes(phone)) {
            dingtalk_notice_user.value.push(phone)
        }
    }
    phoneInputVal.value = ""
    phoneModalVisible.value = false
}

const delPhone = (e, index) => {
    e.preventDefault();
    dingtalk_notice_user.value.splice(index, 1)
}

const noticeStaffChange = (staffs) => {
    wechat_notice_user.value = staffs;
}

onMounted(() => {
    getConfig()
})

defineExpose({
    show
})
</script>

<style lang="less" scoped>
.property {
  width: 72px;
  flex-shrink: 0;
  text-align: right;

  &.required:before {
    content: "*";
    color: #FB363F;
  }
}

.right {
  .add {
    margin-right: 10px;
  }

  /deep/.ant-checkbox-group {
    flex-direction: column;
  }
}

.item {
  margin-top: 24px;

  &:first-child {
    margin-top: 0;
  }
}

.color {
  color: rgba(0, 0, 0, 0.45);
}

.notify-block {
  background: #F2F4F7;
  border-radius: 2px;
  padding: 16px;
  margin-top: 8px;
  width: 680px;

  .property {
    width: 114px;
  }

  .item {
    margin-top: 16px;

    &:first-child {
      margin-top: 0;
    }
  }
}

.align-baseline {
  align-items: baseline;
}

.align-start {
  align-items: flex-start;
}

.tip-message {
  font-size: 14px;
  font-weight: 400;
  color: #8C8C8C;
}

.mt2 {
  margin-top: 2px;
}

.pt8 {
  padding-bottom: 8px;
}

.phone-box {
    white-space: break-spaces;
}

.checkbox-item {
  margin-bottom: 16px;

  &:last-child {
    margin-bottom: 0;
  }
}
</style>
