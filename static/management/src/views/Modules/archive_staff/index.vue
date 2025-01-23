<template>
    <div>
        <div class="zm-main-content">
            <a-alert show-icon type="info"
                     message="默认显示5个会话存档员工，支持设置，语音播放下载开启后，消息页面语音消息支持播放和下载"/>

            <LoadingBox v-if="loading"/>
            <a-form v-else
                    class="mt24"
                    :labelCol="{span: 4}"
                    :wrapperCol="{span: 20}">
                <a-form-item label="会话存档员工">
                    <a-radio-group v-model:value="formState.is_staff_designated" @change="save">
                        <a-radio :value="0">全部存档员工</a-radio>
                        <a-radio :value="1">
                            <span class="zm-flex-center">
                                指定存档员工
                                <a-input-number
                                    v-model:value="formState.max_staff_num"
                                    :min="0"
                                    @blur="save"
                                    class="ml4"
                                    placeholder="请输入"
                                    style="width: 140px;">
                                    <template #addonAfter>个</template>
                                </a-input-number>
                            </span>
                        </a-radio>
                    </a-radio-group>
                </a-form-item>
                <a-form-item label="语音播放下载">
                    <a-switch
                        v-model:checked="formState.enable_voice_play"
                        @change="save"
                        checked-children="开"
                        un-checked-children="关"></a-switch>
                </a-form-item>
            </a-form>
        </div>
    </div>
</template>

<script setup>
import {ref, onMounted} from 'vue';
import {message} from 'ant-design-vue';
import {assignData, copyObj} from '@/utils/tools'
import {setArchiveStaffSettings, getArchiveStaffSettings} from "@/api/archive-staff";
import LoadingBox from "@/components/loadingBox.vue";

const loading = ref(false)
const formState = ref({
    enable_voice_play: true,
    is_staff_designated: 0,
    max_staff_num: 5
})

onMounted(() => {
    loading.value = true
    getArchiveStaffSettings().then(res => {
        assignData(formState.value, res.data || {})
    }).finally(() => {
        loading.value = false
    })
})

function save() {
    if (formState.max_staff_num == "") {
        formState.max_staff_num = 5
    }
    let params = copyObj(formState.value)
    params.enable_voice_play = Number(params.enable_voice_play)
    setArchiveStaffSettings(params).then(res => {
        message.success('已保存')
    })
}

</script>

<style scoped lang="less">
.zm-main-content {
    min-height: calc(100vh - 24px);
}
</style>
