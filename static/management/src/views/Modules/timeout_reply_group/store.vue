<template>
    <div>
        <MainNavbar :title="[
            {name: '单聊超时', route: '/module/timeout-reply-single/index'},
            {name: '新增规则'}
        ]"/>
        <div class="zm-main-content">
            <a-form class="form-box" :labelCol="{span: 4}" :wrapperCol="{span: 20}">
                <a-form-item label="规则名称" required>
                    <a-input v-model:value="formState.name"
                             placeholder="请输入规则名称"
                             style="width:320px;"
                             :max-length="50"/>
                </a-form-item>
                <a-form-item label="质检员工" required>
                    <!--                    <SelectStaffBoxNormal :selectedStaffs="staffs" @change="staffChange"/>-->
                </a-form-item>
                <a-form-item label="质检时间" required>
                    <a-radio-group v-model:checked="formState.check_type" @change="checkTypeChange">
                        <a-radio :value="0">全天质检</a-radio>
                        <a-radio :value="1">自定义质检时间</a-radio>
                        <a-radio :value="2">工作时间</a-radio>
                    </a-radio-group>
                    <div v-if="formState.check_type == 1" class="mt8">
                        <TimesRange :key="1" ref="customTimeRangeRef"/>
                    </div>
                    <div v-show="formState.check_type == 2" class="mt8">
                        <TimesRange :key="2" ref="timeRangeRef" disabled :showAddBtn="false"/>
                        <a-button @click="linkStatRule" type="dashed" :icon="h(EditOutlined)" style="width: 100%">
                            编辑工作时间
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
                            <div class="tag">半匹配</div>
                            <a-select v-model:value="statHalfKeywords"
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
                    <template v-for="(item,index) in formState.notice_rule" :key="index">
                        <div class="zm-flex-center">
                            <span class="mr4">超过</span>
                            <a-input-number placeholder="请输入" v-model:value="item.time_limit" :precision="0"
                                            :max="60"/>
                            <span class="ml4">分钟</span>
                            <span class="ml4">未回复客户消息的单聊，给员工发送提醒</span>
                        </div>
                        <div class="content-box mt8">
                            <a-checkbox-group v-model:checked="item.notice_type">
                                <a-checkbox :value="1">指定员工提醒</a-checkbox>
                                <a-checkbox :value="2">提醒员工本人</a-checkbox>
                            </a-checkbox-group>
                            <!--                            <SelectStaffBoxNormal-->
                            <!--                                v-if="item.notice_type && item.notice_type.includes(1)"-->
                            <!--                                :selectedStaffs="item.staffs"-->
                            <!--                                @change="staffs => noticeStaffChange(staffs,index)"-->
                            <!--                                class="mt16"/>-->
                        </div>
                    </template>
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
import {ref, reactive, h} from 'vue';
import {EditOutlined} from '@ant-design/icons-vue';
import MainNavbar from "@/components/mainNavbar.vue";
import TimesRange from "@/components/tools/timesRange.vue";

const ruleId = ref(0)
const saving = ref(false)
const timeUnit = ref(1)
const staffs = ref([])
const formState = reactive({
    name: "",
    category: 1,
    check_type: 2,
    check_target: [],
    notice_rule: [
        {
            time_limit: "",
            notice_type: [],
            notice_staff: [],
        }
    ]
})
const statHalfKeywords = ref([])
const statRuleInfo = ref({})
const workTimeRangeData = ref([])

function checkTypeChange() {
    // if (this.formState.check_type == 2) {
    //     this.$nextTick(() => {
    //         this.$refs.timeRangeRef.input(this.workTimeRangeData)
    //     })
    // }
}

function staffChange(staffs) {
    // this.formState.check_target = staffs.map((item) => {
    //     return item.user_id
    // })
    // this.staffs = staffs
}

function noticeStaffChange(staffs, index) {
    // let info = this.formState.notice_rule[index]
    // info.notice_staff = staffs.map((item) => {
    //     return item.user_id
    // })
    // this.$set(this.formState.notice_rule, index, info)
}

function linkStatRule() {
    let link = router.resolve({
        path: "/workQualityStat/rules?navKey=workQualityStatRule"
    })
    window.open(link.href, '_blank')
}

function linkSessionAirchive() {
    router.push({
        path: "/sessionArchive/home?navKey=sessionArchiveHome"
    })
}

function verify() {
    // this.formState.name = this.formState.name.trim()
    // try {
    //     if (!this.formState.name) throw "请输入规则名称"
    //     if (!this.formState.check_target.length) throw "请选择质检员工"
    //     if (this.formState.check_type == 1) {
    //         let validate = this.$refs.customTimeRangeRef.verify()
    //         if (!validate.ok) {
    //             throw validate.error
    //         }
    //     }
    //     for (let notice of this.formState.notice_rule) {
    //         if (!notice.time_limit) {
    //             throw "请输入超过时间"
    //         }
    //         if (!notice.notice_type.length) {
    //             throw "请选择提醒员工"
    //         }
    //         if (notice.notice_type.includes(1) && !notice.notice_staff.length) {
    //             throw "请指定提醒员工"
    //         }
    //     }
    //     return true
    // } catch (e) {
    //     console.log('e', e)
    //     this.$message.error(e)
    //     return false
    // }
}

function cancel() {
    router.push({
        path: "/timeoutReplyNotify/privateChat/index?navKey=timeoutReplyNotifyPrivate"
    })
}

function save() {

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
