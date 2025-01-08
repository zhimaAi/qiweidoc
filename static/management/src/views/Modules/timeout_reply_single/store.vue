<template>
    <div>
        <MainNavbar :title="[
            {name: '单聊超时', route: '/module/timeout-reply-single/index'},
            {name: ruleId > 0 ? '编辑规则' : '新增规则'}
        ]"/>
        <div class="zm-main-content">
            <LoadingBox v-if="loading"></LoadingBox>
            <a-form v-else class="form-box" :labelCol="{span: 4}" :wrapperCol="{span: 20}">
                <a-form-item label="规则名称" required>
                    <a-input v-model:value="formState.name"
                             placeholder="请输入规则名称"
                             style="width:320px;"
                             :max-length="50"/>
                </a-form-item>
                <a-form-item label="质检员工" required>
                    <SelectStaffBoxNormal
                        :selectedStaffs="formState.staff_user_list"
                        @change="staffChange"/>
                </a-form-item>
                <a-form-item label="质检时间" required>
                    <a-radio-group
                        v-model:value="formState.inspect_time_type"
                        @change="checkTypeChange">
                        <a-radio :value="1">全天质检</a-radio>
                        <a-radio :value="3">自定义质检时间</a-radio>
                        <a-radio :value="2">工作时间</a-radio>
                    </a-radio-group>
                    <div v-if="formState.inspect_time_type == 3" class="mt8">
                        <TimesRange :key="1" ref="customTimeRangeRef"/>
                    </div>
                    <div v-show="formState.inspect_time_type == 2" class="mt8">
                        <TimesRange :key="2" ref="timeRangeRef" disabled :showAddBtn="false"/>
                        <a-button
                            @click="linkStatRule"
                            type="dashed"
                            :icon="h(EditOutlined)"
                            style="width: 100%">编辑工作时间
                        </a-button>
                    </div>
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
                                      placeholder="暂无过滤关键词"
                                      disabled></a-select>
                        </div>
                        <div class="compact-item mt8">
                            <div class="tag">半匹配</div>
                            <a-select v-model:value="config.filter_half_match_word_list"
                                      mode="tags"
                                      style="width: 100%"
                                      placeholder="暂无过滤关键词"
                                      disabled></a-select>
                        </div>
                    </div>
                    <a-button
                        @click="linkStatRule"
                        class="mt8"
                        type="dashed"
                        :icon="h(EditOutlined)"
                        style="width: 100%">编辑未回复规则
                    </a-button>
                </a-form-item>
                <a-form-item label="提醒员工" required>
                    <div class="zm-flex-center">
                        <span class="mr4">超过</span>
                        <a-input-number
                            v-model:value="formState.timeout_value"
                            placeholder="请输入"
                            :precision="0"
                            :max="1440"/>
                        <span class="ml4">分钟</span>
                        <span class="ml4">未回复客户消息的单聊，给员工发送提醒</span>
                    </div>
                    <div class="content-box mt8">
                        <a-checkbox v-model:checked="formState.is_remind_staff_designation">指定员工提醒</a-checkbox>
                        <a-checkbox v-model:checked="formState.is_remind_staff_himself">提醒员工本人</a-checkbox>
                        <SelectStaffBoxNormal
                            v-if="formState.is_remind_staff_designation"
                            :selected-staffs="formState.remind_staff_user_list"
                            @change="noticeStaffChange"
                            class="mt16"/>
                    </div>
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
import {onMounted, ref, reactive, h, nextTick} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import {message} from 'ant-design-vue';
import {EditOutlined} from '@ant-design/icons-vue';
import MainNavbar from "@/components/mainNavbar.vue";
import TimesRange from "@/components/tools/timesRange.vue";
import SelectStaffBoxNormal from "@/components/common/select-staff-box-normal.vue";
import {getBaseRule, getRuleInfo, setRule, updateRule} from "@/api/timeout-reply-single";
import {assignData, openPluginRouteLink} from "@/utils/tools";
import {timeDataFormat} from "@/common/timeoutReply";
import LoadingBox from "@/components/loadingBox.vue";

const route = useRoute()
const router = useRouter()
const timeRangeRef = ref(null)
const customTimeRangeRef = ref(null)
const ruleId = ref(0)
const loading = ref(true)
const saving = ref(false)
const timeUnit = ref(1)
const staffs = ref([])
const config = reactive({
    filter_full_match_word_list: [],
    filter_half_match_word_list: [],
    worktime_data: [],
})

const formState = reactive({
    name: "",
    inspect_time_type: 1,//质检时间类型 1全天质检 2工作时间 3自定义质检
    timeout_unit: 1, // 时间单位 1是分钟 2是小时
    timeout_value: '',
    is_remind_staff_himself: false,
    is_remind_staff_designation: false,
    designate_remind_userid_list: [],
    staff_userid_list: [],
    custom_time_list: [],

    staff_user_list: [],
    remind_staff_user_list: [],
})
const statHalfKeywords = ref([])
const statRuleInfo = ref({})
const workTimeRangeData = ref([])

onMounted(() => {
    init()
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
        data.timeout_unit = data.timeout_unit.value
        data.inspect_time_type = data.inspect_time_type.value
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

function staffChange(staffs) {
    formState.staff_userid_list = staffs.map(item => item.userid)
    formState.staff_user_list = staffs
}

function noticeStaffChange(staffs, index) {
    formState.designate_remind_userid_list = staffs.map(item => item.userid)
    formState.remind_staff_user_list = staffs
}

function linkStatRule() {
    let link = router.resolve({
        path: '/module/timeout-reply-single/rule'
    })
    openPluginRouteLink('timeout_reply_single', link.href)
}

function verify() {
    formState.name = formState.name.trim()
    try {
        if (!formState.name) {
            throw "请输入规则名称"
        }
        if (!formState.staff_userid_list.length) {
            throw "请选择质检员工"
        }
        if (formState.inspect_time_type == 3) {
            let validate = customTimeRangeRef.value.verify()
            if (!validate.ok) {
                throw validate.error
            }
        }
        if (!formState.timeout_value) {
            throw "请输入超过时间"
        }
        if (!formState.is_remind_staff_himself && !formState.is_remind_staff_designation) {
            throw "请选择提醒员工"
        }
        if (formState.is_remind_staff_designation && !formState.designate_remind_userid_list.length) {
            throw "请指定提醒员工"
        }
        return true
    } catch (e) {
        console.log('Err:', e)
        message.error(e)
        return false
    }
}

function cancel() {
    router.push({path: '/module/timeout-reply-single/index'})
}

async function save() {
    try {
        if (!verify()) {
            return
        }
        saving.value = true
        let params = {
            ...formState,
        }
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
        delete params.staff_user_list
        delete params.remind_staff_user_list
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
    }
}

.zm-fixed-bottom-box {
    padding-left: 152px;
}
</style>
