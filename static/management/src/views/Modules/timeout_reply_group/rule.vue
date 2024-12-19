<template>
    <div>
        <MainNav active="rule"/>
        <div class="zm-main-box">
            <a-card :bordered="false">
                <template #title>
                    <div class="card-title">
                        <span class="title">工作时间段设置</span>
                        <span class="desc">设置工作时间段后，则仅对设定的时间段内对开启会话存档的员工和客户单聊进行工作质量检测</span>
                    </div>
                </template>
                <TimesRange ref="timeRangeRef" @change="configChange"/>
            </a-card>
            <a-card :bordered="false">
                <template #title>
                    <div class="card-title">
                        <span class="title">关键词规则</span>
                        <span
                            class="desc">设置关键词后，群聊中客户发送的最后一条消息触发后员工没有回复不计入超时回复，不会触发提醒</span>
                    </div>
                </template>
                <div class="main-body">
                    <a-alert type="info"
                             :show-icon="false"
                             banner
                             message="群聊时，客户发送的最后一句话成功匹配到关键词，且员工没有回复，不算作未回复消息"></a-alert>
                    <a-form-item label="全匹配" class="mt24">
                        <a-input placeholder="请输入自定义匹配关键词"
                                 :max-length="20"
                                 v-model:value="input.single_keywords_full"
                                 @keydown.enter="inputHandle('single_keywords','full')"
                                 @blur="inputHandle('single_keywords','full')"
                                 style="width: 360px"/>
                        <div class="keyword-tags">
                            <a-tag v-for="(keyword,i) in config.single_keywords.full"
                                   class="zm-customize-tag"
                                   style="margin: 8px 8px 0 0;"
                                   closable
                                   @close="(e) => removeTag('single_keywords','full',i,e)"
                                   :key="i">{{ keyword }}
                            </a-tag>
                        </div>
                    </a-form-item>
                    <a-form-item label="半匹配">
                        <a-input placeholder="请输入自定义匹配关键词"
                                 :max-length="20"
                                 v-model:value="input.single_keywords_half"
                                 @keydown.enter="inputHandle('single_keywords','half')"
                                 @blur="inputHandle('single_keywords','half')"
                                 style="width: 360px"/>
                        <div class="keyword-tags">
                            <a-tag v-for="(keyword,i) in config.single_keywords.half"
                                   class="zm-customize-tag"
                                   style="margin: 8px 8px 0 0;"
                                   closable
                                   @close="(e) => removeTag('single_keywords','half',i,e)"
                                   :key="i">{{ keyword }}
                            </a-tag>
                        </div>
                    </a-form-item>
                    <a-form-item label="消息类型" style="margin-bottom: 0;">
                        <a-checkbox-group v-model:checked="config.single_keywords.msg_type_filter"
                                          @change="configChange">
                            <a-checkbox value="image">图片</a-checkbox>
                            <a-checkbox value="emoji_preg">emoji</a-checkbox>
                            <a-checkbox value="emotion">表情包</a-checkbox>
                        </a-checkbox-group>
                    </a-form-item>
                    <div class="zm-fixed-bottom-box in-module">
                        <a-button>取 消</a-button>
                        <a-popover
                            v-model:open="saveTipVisible"
                            :getPopupContainer="triggerNode => triggerNode"
                            overlayClassName="zm-tip-popover"
                            placement="top"
                            trigger="click"
                            title="提示"
                        >
                            <template #content>
                                <div class="zm-nowrap">统计规则修改后需保存才可生效</div>
                                <div class="mt16 text-right">
                                    <a-button class="save-btn" size="small" @click.stop="save">立即保存</a-button>
                                </div>
                            </template>
                            <a-button type="primary" class="ml16" @click="save">保 存</a-button>
                        </a-popover>
                    </div>
                </div>
            </a-card>
        </div>
    </div>
</template>

<script setup>
import {ref, reactive} from 'vue';
import MainNav from '@/views/Modules/timeout_reply_group/components/mainNav.vue';
import TimesRange from '@/components/tools/timesRange.vue';

const timeRangeRef = ref(null)
const saveTipVisible = ref(true)
const staffList = ref([])
const input = reactive({
    single_keywords_full: '',
    single_keywords_half: '',
    group_keywords_full: '',
    group_keywords_half: '',
    staff_single_keywords_full: '',
    staff_single_keywords_half: '',
})
const config = reactive({
    _id: '',
    corp_id: '',
    owner_id: '',
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

function inputHandle(field, type) {

}

function removeTag(field, type, index, e) {

}

function save() {

}
</script>

<style scoped lang="less">
@import "@/common/sessionStatRule";
.zm-main-box {
    background: #FFF;
    min-height: 100vh;
    :deep(.ant-card:not(.ant-card-bordered)) {
        box-shadow: none;
    }
}
.save-btn  {
    color: #2475FC;
}
</style>
