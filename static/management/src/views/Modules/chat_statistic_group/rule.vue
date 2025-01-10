<template>
    <div>
        <MainNav active="rule"/>
        <div class="zm-main-box">
            <a-card :bordered="false">
                <template #title>
                    <div class="card-title">
                        <span class="title">工作时间段设置</span>
                        <span
                            class="desc">设置工作时间段后，则仅对设定的时间段内对开启会话存档的员工的所在群进行工作质量检测</span>
                    </div>
                </template>
                <TimesRange ref="timeRangeRef" @change="configChange"/>
            </a-card>
            <a-card :bordered="false">
                <template #title>
                    <div class="card-title">
                        <span class="title">@员工后消息回复率</span>
                        <span
                            class="desc">设置消息回复率后，群内成员的消息回复率将根据此处设置的时间进行统计</span>
                    </div>
                </template>
                <a-form-item label="消息回复率" style="margin-bottom: 0px;">
                    <div class="zm-flex-center">
                        <a-input-number
                            v-model:value="config.at_msg_reply_sec"
                            :min="1"
                            :max="180"
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
                        <span class="desc">设置消息回复率后，群内成员的消息回复率将根据此处设置的时间进行统计</span>
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
                        v-model:checked="config.other_effect"
                        @change="configChange">
                        群内其他员工{{ config.msg_reply_sec || '-' }}分钟内回复了，也算作{{
                            config.msg_reply_sec || '-'
                        }}分钟回复率
                    </a-checkbox>
                    <div class="mt10" v-if="config.other_effect">
                        <a-radio-group v-model:value="config.group_staff_type" @change="configChange">
                            <a-radio :value="1">指定员工</a-radio>
                            <a-radio :value="2">全部群内员工</a-radio>
                        </a-radio-group>
                        <div class="tips">
                            指定员工算作{{ config.msg_reply_sec || '-' }}分钟回复率，其他未设置的群内员工回复了，不计入。该设置仅对群内成员生效
                        </div>
                        <div v-if="config.group_staff_type == 1" class="mt10">
                            <SelectStaffBoxNormal
                                :selected-staffs="config.group_staff_info"
                                @change="selectReplyStaff"/>
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
                <div class="main-body" style="padding-bottom: 120px;">
                    <a-alert type="info"
                             :show-icon="false"
                             banner
                             message="群聊时，客户发送的最后一句话成功匹配到关键词或消息类型匹配为选中类型，且员工没有回复，不算作未回复消息"></a-alert>
                    <a-form-item label="全匹配" class="mt24">
                        <a-input placeholder="请输入自定义匹配关键词"
                                 :max-length="20"
                                 v-model:value="input.cst_keywords_full"
                                 @keydown.enter="inputHandle('cst_keywords','full')"
                                 @blur="inputHandle('cst_keywords','full')"
                                 style="width: 360px"/>
                        <div class="keyword-tags">
                            <a-tag v-for="(keyword,i) in config.cst_keywords.full"
                                   class="zm-customize-tag"
                                   style="margin: 8px 8px 0 0;"
                                   closable
                                   @close="(e) => removeTag('cst_keywords','full',i,e)"
                                   :key="i">{{ keyword }}
                            </a-tag>
                        </div>
                    </a-form-item>
                    <a-form-item label="半匹配">
                        <a-input placeholder="请输入自定义匹配关键词"
                                 :max-length="20"
                                 v-model:value="input.cst_keywords_half"
                                 @keydown.enter="inputHandle('cst_keywords','half')"
                                 @blur="inputHandle('cst_keywords','half')"
                                 style="width: 360px"/>
                        <div class="keyword-tags">
                            <a-tag v-for="(keyword,i) in config.cst_keywords.half"
                                   class="zm-customize-tag"
                                   style="margin: 8px 8px 0 0;"
                                   closable
                                   @close="(e) => removeTag('cst_keywords','half',i,e)"
                                   :key="i">{{ keyword }}
                            </a-tag>
                        </div>
                    </a-form-item>
                    <a-form-item label="消息类型" style="margin-bottom: 0;">
                        <a-checkbox-group
                            v-model:value="config.cst_keywords.msg_type_filter"
                            @change="configChange">
                            <a-checkbox value="image">图片</a-checkbox>
                            <a-checkbox value="emoji_preg">emoji</a-checkbox>
                            <a-checkbox value="emotion">表情包</a-checkbox>
                        </a-checkbox-group>
                    </a-form-item>
                    <div class="zm-fixed-bottom-box in-module">
                        <a-button @click="cancel">取 消</a-button>
                        <a-popover
                            v-model:open="saveTipVisible"
                            :getPopupContainer="triggerNode => triggerNode"
                            overlayClassName="zm-tip-popover"
                            trigger="manual"
                            placement="top"
                            title="提示"
                        >
                            <template #content>
                                <div class="zm-nowrap">统计规则修改后需保存才可生效</div>
                                <div class="mt16 text-right">
                                    <a-button class="save-btn" size="small" @click.stop="save">立即保存</a-button>
                                </div>
                            </template>
                            <a-button type="primary" class="ml16" @click="save" :loading="saving">保 存</a-button>
                        </a-popover>
                    </div>
                </div>
            </a-card>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref, reactive, nextTick} from 'vue';
import {useRouter} from 'vue-router';
import {message} from 'ant-design-vue';
import TimesRange from "@/components/tools/timesRange.vue";
import MainNav from "@/views/Modules/chat_statistic_group/components/mainNav.vue";
import {getConfig, setConfig} from "@/api/chat_statistic_group";
import {assignData} from "@/utils/tools";
import SelectStaffBoxNormal from "@/components/common/select-staff-box-normal.vue";

const router = useRouter()
const loading = ref(false)
const saving = ref(false)
const saveTipVisible = ref(false)
const timeRangeRef = ref(null)
const staffList = ref([])
const input = reactive({
    cst_keywords_full: "",
    cst_keywords_half: "",
})
const config = reactive({
    at_msg_reply_sec: 3,
    msg_reply_sec: 3,
    cst_keywords: {
        full: [],
        half: [],
        msg_type_filter: [],
    },
    work_time: [],
    other_effect: false,
    group_staff_type: 1,
    group_staff_ids: [],
    group_staff_info: []
})
const configJsonBK = ref(null)

onMounted(() => {
    loadData()
})

async function loadData() {
    loading.value = true
    await getConfig().finally(() => {
        loading.value = false
    }).then(res => {
        let data = res?.data || {}
        data.group_staff_type = data.group_staff_type || 1
        assignData(config, data)
        let timeData = config.work_time.map(item => {
            let ranges = item.range.map(time => {
                return {times: [time.s, time.e]}
            })
            return {week: item.week, ranges: ranges,}
        })
        timeRangeRef.value.input(timeData)
        nextTick(() => {
            configJsonBK.value = JSON.stringify({
                config: config,
                timeData: timeRangeRef.value.output()
            })
        })
    })
}


function configChange() {
    if (configJsonBK.value) {
        let currentData = JSON.stringify({
            config: config,
            timeData: timeRangeRef.value.output()
        })
        if (configJsonBK.value != currentData) {
            saveTipVisible.value = true
        } else {
            saveTipVisible.value = false
        }
    }
}

function selectReplyStaff(value) {
    config.group_staff_info = value
    config.group_staff_ids = value.map(i => i.userid)
}

function inputHandle(field, type) {
    let inputFiled
    try {
        inputFiled = field + "_" + type
        let val = input[inputFiled].trim()
        if (!val) {
            return
        }
        if (config[field][type].length > 19) {
            throw "每项最多输入20个关键词"
        }
        if (config[field].half.includes(val) || config[field].full.includes(val)) {
            throw "请勿重复输入关键字"
        } else {
            config[field][type].push(val)
        }
    } catch (e) {
        message.error(e)
    }
    input[inputFiled] = ""
    configChange()
}

function removeTag(field, type, index, e) {
    e.preventDefault();
    config[field][type].splice(index, 1)
    configChange()
}

function cancel() {
    router.push({
        path: '/module/chat-statistic-group/index'
    })
}

async function save() {
    try {
        saving.value = true
        let validate = timeRangeRef.value.verify()
        if (!validate.ok) {
            throw validate.error
        }
        if (config.other_effect && config.group_staff_type === 1 && !config.group_staff_ids.length) {
            throw '请选择指定员工'
        }
        let timeData = timeRangeRef.value.output()
        timeData = timeData.map(item => {
            let ranges = item.ranges.map(range => {
                return {s: range.times[0], e: range.times[1],}
            })
            return {week: item.week, range: ranges}
        })
        let params = {...config, work_time: timeData}
        params.other_effect = Number(params.other_effect)
        delete params.group_staff_info
        await setConfig(params)
        await loadData()
        saveTipVisible.value = false
        message.success('已保存')
    } catch (e) {
        console.log('Err:', e)
        typeof e === 'string' && message.error(e)
    }
    saving.value = false
}
</script>

<style scoped lang="less">
@import "@/common/sessionStatRule";
.zm-main-box {
    min-height: 100vh;
}
</style>
