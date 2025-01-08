<template>
    <div>
        <MainNavbar :title="[
            {name: '群聊超时', route: '/module/timeout-reply-group/index'},
            {name: ruleId > 0 ? '编辑规则' : '新增规则'}
        ]"/>
        <div class="zm-main-content">
            <LoadingBox v-if="loading"/>
            <a-form
                v-else
                class="form-box"
                :labelCol="{span: 4}"
                :wrapperCol="{span: 20}">
                <a-form-item label="规则名称" required>
                    <a-input v-model:value="formState.name"
                             placeholder="请输入规则名称"
                             style="width:320px;"
                             :max-length="50"/>
                </a-form-item>
                <a-form-item label="质检群聊" required>
                    <a-radio-group v-model:value="formState.group_type">
                        <a-radio :value="1">指定群聊</a-radio>
                        <a-radio :value="2">当员工在群内时关注该群</a-radio>
                        <a-radio :value="3">当群名包含关键词时关注该群</a-radio>
                    </a-radio-group>
                    <div class="mt8">
                        <SelectChatBox
                            v-if="formState.group_type == 1"
                            :selected-chats="formState._group_chats"
                            ref="groupRef"
                            @change="groupChange"/>
                        <SelectStaffBoxNormal
                            v-else-if="formState.group_type == 2"
                            :selected-staffs="formState._group_staffs"
                            ref="groupStaffRef"
                            @change="groupStaffChange"/>
                        <div v-else class="content-box" style="width: 530px;">
                            <a-form-item label="包含关键词" :labelCol="{span: 5}" required>
                                <a-select v-model:value="formState.group_keyword_list.include_list"
                                          :token-separators="[',']"
                                          :open="false"
                                          mode="tags"
                                          placeholder="请输入关键词"></a-select>
                                <div><span class="zm-tip-info">最多设置20个关键词</span></div>
                            </a-form-item>
                            <a-form-item label="排除关键词" :labelCol="{span: 5}">
                                <a-select v-model:value="formState.group_keyword_list.exclude_list"
                                          :token-separators="[',']"
                                          :open="false"
                                          mode="tags"
                                          placeholder="请输入关键词"></a-select>
                                <div><span class="zm-tip-info">最多设置20个关键词</span></div>
                            </a-form-item>
                        </div>
                    </div>
                </a-form-item>
                <a-form-item label="质检时间" required>
                    <a-radio-group v-model:value="formState.inspect_time_type" @change="checkTypeChange">
                        <a-radio :value="1">全天质检</a-radio>
                        <a-radio :value="2">工作时间</a-radio>
                        <a-radio :value="3">自定义质检时间</a-radio>
                    </a-radio-group>
                    <div v-if="formState.inspect_time_type == 3" class="mt8">
                        <TimesRange :key="1" ref="customTimeRangeRef"/>
                    </div>
                    <div v-show="formState.inspect_time_type == 2" class="mt8">
                        <TimesRange :key="2" ref="timeRangeRef" disabled :showAddBtn="false"/>
                        <a-button @click="linkStatRule" type="dashed" :icon="h(EditOutlined)" style="width: 100%">
                            编辑工作时间
                        </a-button>
                    </div>
                </a-form-item>
                <a-form-item label="提醒群主">
                    <a-switch v-model:checked="formState.is_remind_group_owner"/>
                    <div class="zm-tip-info">开启后，每次通知都将发送给群主</div>
                </a-form-item>
                <a-form-item label="过滤关键词" required>
                    <div>过滤关键词可到聊天统计-统计规则中设置<a @click="linkStatRule" class="ml8">去设置</a></div>
                    <div class="zm-tip-info">
                        关键词（单聊时，客户发送的最后一句话成功匹配到关键词，且员工没有回复，不算作未回复消息）
                    </div>
                    <div class="content-box mt8">
                        <div class="compact-item">
                            <div class="tag">全匹配</div>
                            <a-select v-model:value="config.filter_full_match_word_list"
                                      mode="tags"
                                      style="width: 100%"
                                      placeholder="请输入过滤关键词"
                                      disabled></a-select>
                        </div>
                    </div>
                    <div class="content-box mt8">
                        <div class="compact-item">
                            <div class="tag">半匹配</div>
                            <a-select v-model:value="config.filter_half_match_word_list"
                                      mode="tags"
                                      style="width: 100%"
                                      placeholder="请输入过滤关键词"
                                      disabled></a-select>
                        </div>
                    </div>
                    <a-button @click="linkStatRule" class="mt8" type="dashed" :icon="h(EditOutlined)"
                              style="width: 100%">
                        编辑未回复规则
                    </a-button>
                </a-form-item>
                <a-form-item label="提醒员工" required>
                    <div v-for="(item,i) in formState.remind_rules"
                         :key="i"
                         class="notify-rule-item">
                        <div class="title">
                            规则{{ i + 1 }}
                            <DeleteOutlined
                                :class="['del-icon', {disabled: formState.remind_rules.length < 2}]"
                                @click="removeNoticeRule(i)"/>
                        </div>
                        <div class="content-box">
                            <div class="zm-flex-center">
                                <span>超过</span>
                                <a-input-number
                                    v-model:value="item.timeout_value"
                                    class="ml4"
                                    placeholder="请输入"
                                    :precision="0"
                                    :max="60"/>
                                <span class="ml4">分钟</span>
                                <span class="ml4">未回复客户消息的群聊，给员工发送提醒</span>
                            </div>
                            <div class="mt8">
                                <a-checkbox v-model:checked="item.is_remind_staff_designation">指定员工</a-checkbox>
                                <a-checkbox v-model:checked="item.is_remind_group_member">群内员工</a-checkbox>
                            </div>
                            <SelectStaffBoxNormal
                                v-if="item.is_remind_staff_designation"
                                :selectedStaffs="item.remind_staff_user_list"
                                @change="staffs => noticeStaffChange(staffs, i)"
                                class="mt16"/>
                        </div>
                    </div>
                    <a-button @click="addNoticeRule"
                              :disabled="formState.remind_rules.length > 2"
                              class="mt8"
                              type="dashed"
                              :icon="h(PlusOutlined)"
                              style="width: 100%">添加规则（{{ formState.remind_rules.length }}/3）
                    </a-button>
                </a-form-item>
            </a-form>
            <div class="zm-fixed-bottom-box in-module">
                <a-button @click="cancel">取 消</a-button>
                <a-button class="ml8" type="primary" @click="save" :loading="saving">保 存</a-button>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref, reactive, h, onMounted, nextTick, watch} from 'vue';
import {useRouter, useRoute} from 'vue-router';
import {message} from 'ant-design-vue';
import {EditOutlined, DeleteOutlined, PlusOutlined} from '@ant-design/icons-vue';
import LoadingBox from "@/components/loadingBox.vue";
import MainNavbar from "@/components/mainNavbar.vue";
import TimesRange from "@/components/tools/timesRange.vue";
import SelectChatBox from "@/components/common/select-chat-box.vue";
import SelectStaffBoxNormal from "@/components/common/select-staff-box-normal.vue";
import {assignData, copyObj, openPluginRouteLink} from "@/utils/tools";
import {getBaseRule, getRuleInfo, setRule, updateRule} from "@/api/timeout-reply-group";
import {timeDataFormat} from "@/common/timeoutReply";

const ruleId = ref(0)
const router = useRouter()
const route = useRoute()
const timeRangeRef = ref(null)
const customTimeRangeRef = ref(null)
const loading = ref(true)
const saving = ref(false)
const timeUnit = ref(1)
const staffs = ref([])
const remindRuleStruct = {
    timeout_unit: 1,
    timeout_value: '',
    is_remind_group_member: false,
    is_remind_staff_designation: false,
    designate_remind_userid_list: [],
    remind_staff_user_list: [],
}
const formState = reactive({
    name: '',
    group_type: 1,
    group_chat_id_list: [],
    group_staff_userid_list: [],
    group_keyword_list: {
        include_list: [],
        exclude_list: []
    },
    is_remind_group_owner: false,
    inspect_time_type: 1,
    remind_rules: [copyObj(remindRuleStruct)],
    _group_chats: [],
    _group_staffs: [],
})

const config = reactive({
    filter_full_match_word_list: [],
    filter_half_match_word_list: [],
    worktime_data: [],
})

onMounted(() => {
    init()
})


watch(() => formState.group_keyword_list.include_list, () => {
    if (formState.group_keyword_list.include_list.length > 20) {
        formState.group_keyword_list.include_list = formState.group_keyword_list.include_list.slice(0, 20)
    }
})

watch(() => formState.group_keyword_list.exclude_list, () => {
    if (formState.group_keyword_list.exclude_list.length > 20) {
        formState.group_keyword_list.exclude_list = formState.group_keyword_list.exclude_list.slice(0, 20)
    }
})

async function init() {
    await loadBaseRule()
    if (route.query.rule_id > 0) {
        ruleId.value = route.query.rule_id
        loadRuleInfo()
    } else {
        loading.value = false
    }
}

async function loadBaseRule() {
    await getBaseRule().then(res => {
        let data = res.data || {}
        let timeData = data.working_hours || []
        data.worktime_data = timeDataFormat(timeData)
        assignData(config, data)
    })
}

function loadRuleInfo() {
    loading.value = true
    getRuleInfo(ruleId.value).finally(() => {
        loading.value = false
    }).then(res => {
        let data = res.data || {}
        data.inspect_time_type = data.inspect_time_type.value
        data.group_type = data.group_type.value
        data._group_chats = data.group_chat_detail_list || []
        data._group_staffs = data.group_staff_user_list || []
        assignData(formState, data)
        switch (data.inspect_time_type) {
            case 2:
                checkTypeChange()
                break
            case 3:
                let timeData = timeDataFormat(data.custom_time_list)
                nextTick(() => {
                    customTimeRangeRef.value.input(timeData)
                })
                break
        }
    })
}

function checkTypeChange() {
    if (formState.inspect_time_type == 2) {
        nextTick(() => {
            timeRangeRef.value.input(config.worktime_data)
        })
    }
}

function noticeStaffChange(staffs, index) {
    let info = formState.remind_rules[index]
    info.designate_remind_userid_list = staffs.map((item) => {
        return item.userid
    })
    info.remind_staff_user_list = staffs
    formState.remind_rules[index] = info
}

function groupChange(value) {
    formState.group_chat_id_list = value.map(item => item.chat_id)
    formState._group_chats = value
}

function groupStaffChange(value) {
    formState.group_staff_userid_list = value.map(item => item.userid)
    formState._group_staffs = value
}

function addNoticeRule() {
    if (formState.remind_rules.length > 2) {
        return
    }
    formState.remind_rules.push(copyObj(remindRuleStruct))
}

function removeNoticeRule(index) {
    if (formState.remind_rules.length < 2) {
        return
    }
    formState.remind_rules.splice(index, 1)
}

function linkStatRule() {
    let link = router.resolve({
        path: '/module/timeout-reply-group/rule'
    })
    openPluginRouteLink('timeout_reply_group', link.href)
}

function verify() {
    try {
        formState.name = formState.name.trim()
        if (!formState.name) {
            throw "请输入规则名称"
        }
        switch (formState.group_type) {
            case 1:
                if (!formState.group_chat_id_list.length) throw "请选择指定群聊"
                break
            case 2:
                if (!formState.group_staff_userid_list.length) throw "请选择指定群聊员工"
                break
            case 3:
                if (!formState.group_keyword_list.include_list.length) {
                    throw "请填写群聊关键词"
                }
                break
        }
        if (formState.inspect_time_type == 3) {
            let validate = customTimeRangeRef.value.verify()
            if (!validate.ok) {
                throw validate.error
            }
        }
        for (let rule of formState.remind_rules) {
            if (!rule.timeout_value) {
                throw "请输入超过时间"
            }
            if (!rule.is_remind_group_member && !rule.is_remind_staff_designation) {
                throw "请选择提醒员工"
            }
            if (rule.is_remind_staff_designation && !rule.designate_remind_userid_list.length) {
                throw "请指定提醒员工"
            }
        }
        return true
    } catch (e) {
        console.log('Err:', e)
        message.error(e)
    }
    return false
}

function cancel() {
    router.push({path: '/module/timeout-reply-group/index'})
}

async function save() {
    try {
        if (!verify()) {
            return
        }
        saving.value = true
        let params = copyObj(formState)
        params.custom_time_list = []
        if (params.inspect_time_type === 3) {
            let timeData = customTimeRangeRef.value.output()
            params.custom_time_list = timeData.map(item => {
                let ranges = item.ranges.map(range => {
                    return {start: range.times[0], end: range.times[1]}
                })
                return {
                    week_day_list: item.week,
                    time_period_list: ranges
                }
            })
        }
        params.remind_rules = params.remind_rules.map(item => {
            delete item.remind_staff_user_list
            return item
        })
        delete params._group_staffs
        delete params._group_chats
        if (ruleId.value > 0) {
            await updateRule(ruleId.value, params)
        } else {
            await setRule(params)
        }
        message.success('已保存')
        setTimeout(() => {
            cancel()
        }, 1200)
    } catch (e) {
        saving.value = false
    }
}
</script>

<style scoped lang="less">
.zm-main-content {
    min-height: 100vh;
    padding-bottom: 80px;

    :deep(.ant-row) {
        align-items: baseline;
    }
}

.form-box {
    width: 772px;

    .compact-item {
        display: flex;
        align-items: center;

        .tag {
            padding: 3px 12px;
            border-radius: 2px 0 0 2px;
            border: 1px solid #D9D9D9;
            border-right: none;
            background: #F0F0F0;
            color: #595959;
            white-space: nowrap;
        }
    }

    .content-box {
        padding: 16px;
        border-radius: 2px;
        background: #F2F4F7;

        :deep(.ant-form-item) {
            &:last-child {
                margin-bottom: 0;
            }
        }
    }

    .notify-rule-item {
        margin-bottom: 8px;

        .title {
            padding: 8px 16px;
            border-radius: 2px 2px 0 0;
            border-bottom: 1px solid #E4E6EB;
            background: #F2F4F7;
            color: #000000;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    }
}

.del-icon {
    &:not(.disabled):hover {
        color: red;
    }

    &.disabled {
        color: #d9d9d9;

        &:hover {
            cursor: not-allowed;
        }
    }
}

.zm-fixed-bottom-box {
    padding-left: 152px;
}
</style>
