<template>
    <div class="times-range-container">
        <div class="time-range-item" v-for="(item,i) in data" :key="i">
            <div class="time-range-title">
                <span class="text">时间段{{ i + 1 }}</span>
                <DeleteOutlined
                    :class="['del',{disabled: disabled || data.length < 2}]"
                    @click="remove(i)"/>
            </div>
            <div class="time-range-body">
                <a-checkbox-group v-model:value="item.week" :disabled="disabled" @change="change">
                    <a-checkbox
                        v-for="week in weeks"
                        :key="week.value"
                        :value="week.value"
                        :disabled="disabledWeek(i,week)"
                    >
                        {{ week.label }}
                    </a-checkbox>
                </a-checkbox-group>
                <div class="times">
                    <div class="time-item"
                         v-for="(range,index) in item.ranges"
                         :key="index">
                        <a-time-range-picker
                            v-model:value="range.times"
                            :disabled="disabled"
                            @change="change"
                            style="width: 300px"
                            format="HH:mm"
                            value-format="HH:mm"
                            :show-time="{format:'HH:mm'}"
                            :placeholder="['开始时间','结束时间']"
                        >
                        </a-time-range-picker>
                        <div class="operator-box ml8">
                            <PlusCircleOutlined
                                :class="['add',{disabled: disabled || item.ranges.length >= timeLimit}]"
                                @click="addTimeRange(item)"/>
                            <CloseCircleOutlined
                                :class="['remove ml8',{disabled: disabled || item.ranges.length < 2}]"
                                @click="removeTimeRange(item,index)"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <a-button v-if="showAddBtn"
                  :disabled="disabled || data.length >= limit"
                  type="dashed"
                  :icon="h(PlusOutlined)"
                  @click="add"
                  style="width: 100%;">添加时间段
        </a-button>
    </div>
</template>

<script setup>
import {ref, h} from 'vue';
import {DeleteOutlined, PlusOutlined, PlusCircleOutlined, CloseCircleOutlined} from '@ant-design/icons-vue';
import {copyObj, intervalOverlap, timeToNumber, weeks} from "@/utils/tools";

const emit = defineEmits(['change'])
const props = defineProps({
    limit: {
        type: Number,
        default: 5,
    },
    timeLimit: {
        type: Number,
        default: 5,
    },
    disabled: {
        type: Boolean,
        default: false
    },
    showAddBtn: {
        type: Boolean,
        default: true
    }
})

const timeItemStruct = {
    times: [],
}
const dataItemStruct = {
    week: [],
    ranges: [
        copyObj(timeItemStruct),
    ],
}
const data = ref([
    {
        ...dataItemStruct,
    }
])

function add() {
    if (data.value.length >= props.limit) {
        return
    }
    data.value.push(copyObj(dataItemStruct))
    change()
}

function remove(index) {
    if (props.disabled || data.value.length === 1) {
        return
    }
    data.value.splice(index, 1)
    change()
}

function addTimeRange(record) {
    if (props.disabled || record.ranges.length >= props.timeLimit) {
        return
    }
    record.ranges.push(copyObj(timeItemStruct))
    change()
}

function removeTimeRange(record, index) {
    if (props.disabled || record.ranges.length === 1) {
        return
    }
    record.ranges.splice(index, 1)
    change()
}

function disabledWeek(index, week) {
    let item
    for (let i = 0; i < data.value.length; i++) {
        item = data.value[i]
        if (i !== index && item.week.includes(week.value)) {
            return true
        }
    }
    return false
}

function verify() {
    let _data = copyObj(data.value)
    _data = _data.filter(item => {
        item.times = item.ranges.filter(range => range.times.length > 0)
        return item.week.length > 0 && item.times.length > 0
    })
    if (!_data.length) {
        return {
            ok: false,
            error: "请至少完善一条时段规则"
        }
    }
    for (let item of _data) {
        let times = item.ranges.map(range => {
            return [
                timeToNumber(range.times[0]),
                timeToNumber(range.times[1])
            ]
        })
        if (intervalOverlap(...times)) {
            return {
                ok: false,
                error: "工作时间段存在重叠，请检查后重新选择"
            }
        }
    }
    return {
        ok: true,
    }
}

function output() {
    let _data = copyObj(data.value)
    _data = _data.filter(item => {
        item.times = item.ranges.filter(range => range.times.length > 0)
        item.week = item.week.sort()
        return item.week.length > 0 && item.times.length > 0
    })
    return _data
}

function input(value) {
    if (value.length) {
        data.value = value
    }
}

function change() {
    emit('change')
}

defineExpose({
    input,
    output,
    verify,
})
</script>

<style scoped lang="less">
.times-range-container {
    width: 643px;

    .time-range-item {
        background: #F2F4F7;
        border-radius: 2px;
        opacity: 1;
        border: 1px solid #E4E6EB;
        margin-bottom: 10px;

        .time-range-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 16px;
            border-bottom: 1px solid #E4E6EB;

            .text {
                font-size: 14px;
                font-weight: 600;
                color: #242933;
            }

            .del {
                font-size: 16px;
            }

            .del:hover {
                color: #f77d84;
            }
        }

        .time-range-body {
            padding: 16px;
        }

        .time-item {
            display: flex;
            align-items: center;
            margin-top: 8px;

            .operator-box {
                color: #595959;
                font-size: 14px;
                cursor: pointer;

                .add:hover {
                    color: #2475FC;
                }

                .remove:hover {
                    color: #f77d84;
                }
            }
        }
    }

    .disabled {
        cursor: not-allowed;
        color: rgba(0, 0, 0, 0.2);

        &:hover {
            color: rgba(0, 0, 0, 0.2) !important;
        }
    }
}
</style>
