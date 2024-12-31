<template>
    <div>
        <a-select
            :style="{width: width}"
            mode="multiple"
            placeholder="请选择员工"
            :maxTagCount="maxTagCount"
            :open="false"
            allowClear
            v-model:value="staffs"
            @change="selectChangeStaff"
            @dropdownVisibleChange="manageFocus"
        >
            <a-select-option
                v-for="(item, index) in manageOpts"
                :value="item.userid"
                :key="index"><span>{{ item.name }}</span></a-select-option>
        </a-select>

        <SelectStaffModal
            ref="staffRef"
            :selectType="selectType"
            :isSession="isSession"
            :isAuthMode="isAuthMode"
            @selectStaff="selectStaffFunc"
            @cancel="cancelSelect"/>
    </div>
</template>

<script setup>
import {ref} from 'vue';
import SelectStaffModal from "@/components/select-staff-new/index";

const emit = defineEmits(['change'])
const props = defineProps({
    width: {
        type: String,
        default: '160px'
    },
    selectType: {
        default: 'multiple',
    },
    isSession: {
        default: '',
    },
    isAuthMode: {
        default: false,
    },
    maxTagCount: {
        type: Number,
        default: 2,
    },
})
const staffRef = ref(null)
const visible = ref(false)
const flag = ref(false)
const staffs = ref([])
const manageOpts = ref([])

function manageFocus() {
    visible.value = true
    staffRef.value.show(manageOpts.value)
}

function selectStaffFunc(value) {
    manageOpts.value = value;
    staffs.value = value.map((i) => i.userid);
    emit('change', manageOpts.value)
    cancelSelect();
}

function selectChangeStaff() {
    let data = JSON.parse(JSON.stringify(manageOpts.value))
    let manageOpts = []
    data.map(item => {
        if (staffs.value.includes(item.userid)) {
            manageOpts.push(item)
        }
    })
    manageOpts.value = manageOpts;
    emit('change', manageOpts.value)
}

function cancelSelect() {
    flag.value = false;
    visible.value = false
}

function reset() {
    staffs.value = []
    manageOpts.value = []
}
</script>

<style scoped>

</style>
