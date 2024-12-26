<template>
    <div>
        <MainNav active="rule"/>
        <div class="zm-main-box">
            <a-card :bordered="false">
                <template #title>
                    <div class="card-title">
                        <span class="title">工作时间段设置</span>
                        <span
                            class="desc">设置工作时间段后，则仅对设定的时间段内对开启会话存档的员工和客户单聊进行工作质量检测</span>
                    </div>
                </template>
                <TimesRange ref="timeRangeRef" @change="configChange"/>
            </a-card>
            <a-card :bordered="false">
                <template #title>
                    <div class="card-title">
                        <span class="title">关键词规则</span>
                        <span
                            class="desc">设置关键词后，客户发送的最后一条消息触发后员工没有回复不计入超时回复，不会触发提醒</span>
                    </div>
                </template>
                <div class="main-body">
                    <a-alert type="info"
                             :show-icon="false"
                             banner
                             message="客户关键字：单聊时，客户发送的最后一条消息匹配到关键词或消息类型匹配为选中类型，且员工没有回复，不算作未回复消息"></a-alert>
                    <a-form-item label="全匹配" class="mt24">
                        <a-input placeholder="请输入自定义匹配关键词"
                                 :max-length="20"
                                 v-model:value="input.keywords_full"
                                 @keydown.enter="inputHandle('full')"
                                 @blur="inputHandle('full')"
                                 style="width: 360px"/>
                        <div class="keyword-tags">
                            <a-tag v-for="(keyword,i) in config.filter_full_match_word_list"
                                   class="zm-customize-tag"
                                   closable
                                   @close="(e) => removeTag('full',i,e)"
                                   :key="i">{{ keyword }}
                            </a-tag>
                        </div>
                    </a-form-item>
                    <a-form-item label="半匹配">
                        <a-input placeholder="请输入自定义匹配关键词"
                                 :max-length="20"
                                 v-model:value="input.keywords_half"
                                 @keydown.enter="inputHandle('half')"
                                 @blur="inputHandle('half')"
                                 style="width: 360px"/>
                        <div class="keyword-tags">
                            <a-tag v-for="(keyword,i) in config.filter_half_match_word_list"
                                   class="zm-customize-tag"
                                   closable
                                   @close="(e) => removeTag('half',i,e)"
                                   :key="i">{{ keyword }}
                            </a-tag>
                        </div>
                    </a-form-item>
                    <a-form-item label="消息类型" style="margin-bottom: 0;">
                        <a-checkbox v-model:checked="config.include_image_msg">图片</a-checkbox>
                        <a-checkbox v-model:checked="config.include_emoji_msg">emoji</a-checkbox>
                        <a-checkbox v-model:checked="config.include_emoticons_msg">表情包</a-checkbox>
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
                                <div class="zm-nowrap">回复规则修改后需保存才可生效</div>
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
import {onMounted, ref, reactive, watch, nextTick} from 'vue';
import {useRouter} from 'vue-router';
import {message} from 'ant-design-vue';
import MainNav from '@/views/Modules/timeout_reply_single/components/mainNav.vue';
import TimesRange from '@/components/tools/timesRange.vue';
import {getBaseRule, setBaseRule} from "@/api/timeout-reply-single";
import {assignData} from "@/utils/tools";

const router = useRouter()
const timeRangeRef = ref(null)
const saveTipVisible = ref(false)
const loading = ref(false)
const saving = ref(false)
const staffList = ref([])
const input = reactive({
    keywords_full: '',
    keywords_half: '',
})
const config = reactive({
    filter_full_match_word_list: [],
    filter_half_match_word_list: [],
    include_image_msg: false,
    include_emoji_msg: false,
    include_emoticons_msg: false,
})
const configJsonBK = ref(null)

onMounted(() => {
    loadData()
})

watch(config, (n, o) => {
    configChange()
}, {
    deep: true
})

async function loadData() {
    loading.value = true
    await getBaseRule().then(res => {
        let data = res.data || {}
        assignData(config, data)
        let timeData = data.working_hours || []
        timeData = timeData.map(item => {
            let ranges = item.time_period_list.map(time => {
                return {times: [time.start, time.end]}
            })
            return {
                week: item.week_day_list,
                ranges: ranges,
            }
        })
        timeRangeRef.value.input(timeData)
        nextTick(() => {
            configJsonBK.value = JSON.stringify({
                config: config,
                timeData: timeRangeRef.value.output()
            })
        })
    }).finally(() => {
        loading.value = false
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

function inputHandle(type) {
    try {
        let field = `filter_${type}_match_word_list`
        let valueKey = `keywords_${type}`
        input[valueKey] = input[valueKey].trim()
        let val = input[valueKey]
        if (!val) {
            return
        }
        if (config[field].length >= 20) {
            throw '每项最多输入20个关键词'
        }
        if (config[field].includes(val)) {
            throw '请勿重复输入'
        }
        config[field].push(val)
        input[valueKey] = ''
    } catch (e) {
        message.error(e)
    }
}

function removeTag(type, index, e) {
    e.preventDefault();
    config[`filter_${type}_match_word_list`].splice(index, 1)
}

function cancel() {
    router.push({
        path: '/module/timeout-reply-single/index'
    })
}

async function save() {
    try {
        saving.value = true
        let validate = timeRangeRef.value.verify()
        if (!validate.ok) {
            throw validate.error
        }
        let timeData = timeRangeRef.value.output()
        timeData = timeData.map(item => {
            let ranges = item.ranges.map(range => {
                return {start: range.times[0], end: range.times[1]}
            })
            return {
                week_day_list: item.week,
                time_period_list: ranges
            }
        })
        await setBaseRule({...config, working_hours: timeData})
        await loadData()
        saveTipVisible.value = false
        message.success('已保存')
    } catch (e) {
        typeof e === 'string' && message.error(e)
    }
    saving.value = false
}
</script>

<style scoped lang="less">
@import "@/common/sessionStatRule";

.zm-main-box {
    background: #FFF;
    min-height: 100vh;
    padding-bottom: 80px;

    :deep(.ant-card:not(.ant-card-bordered)) {
        box-shadow: none;
    }

    :deep(.ant-tag.zm-customize-tag) {
        margin: 8px 8px 0 0;
    }
}

.save-btn {
    color: #2475FC;
}
</style>
