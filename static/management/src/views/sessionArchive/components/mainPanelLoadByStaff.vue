<template>
    <!----按员工查看---->
    <div class="_container">
        <div v-if="!(archiveStfModule.is_enabled && archiveStfSettings.is_staff_designated == 0)" class="_header">
            <a-alert type="info" show-icon>
                <template #message>
                    可设置{{archiveStfTotal}}/{{archiveStfSettings.max_staff_num}}个存档员工
                    <a class="ml8" @click="showSetStaffModal">设置</a>
                    <template v-if="!archiveStfModule.is_install">
                        ，如需设置更多，需购买存档员工管理插件<a class="ml8" @click="linkPlugHome">去购买</a>
                    </template>
                </template>
            </a-alert>
        </div>
        <div id="sessionMainContent" class="session-main-container">
            <DragStretchBox
                @change="panelBlockWidthChange"
                :max-width="panelWin.leftMaxWith"
                :min-width="panelWin.leftMinWith"
                id="sessionLeftBlock"
                class="session-left-block"
            >
                <a-tabs v-model:active-key="main.staffType"
                        @change="staffTypeChange"
                        class="zm-customize-tabs">
                    <a-tab-pane key="SESSION_STAFF" :tab="`会话存档中(${main.sessionStaffCount})`"></a-tab-pane>
                    <a-tab-pane key="HISTORY_STAFF" :tab="`历史员工(${main.historyStaffCount})`"></a-tab-pane>
                </a-tabs>
                <StaffBox v-show="main.staffType === 'SESSION_STAFF'"
                          :key="staffCompKey"
                          class="main-content-box"
                          @totalReport="val => totalReport(val, 'SESSION_STAFF')"
                          @change="val => staffChange(val, 'SESSION_STAFF')"
                          :default="defaultStaff"
                          :type="1"/>
                <StaffBox v-show="main.staffType === 'HISTORY_STAFF'"
                          :key="staffCompKey"
                          class="main-content-box"
                          @totalReport="val => totalReport(val, 'HISTORY_STAFF')"
                          @change="val => staffChange(val, 'HISTORY_STAFF')"
                          :default="defaultStaff"
                          :type="2"/>
            </DragStretchBox>
            <div class="session-right-block">
                <FilterBoxByStaff
                    class="filter-box"
                    @change="search"
                    :type="main.contactType"
                    @show-setting-change="showSettingChange"/>
                <div class="content-block">
                    <DragStretchBox
                        :max-width="panelWin.centerMaxWidth"
                        :min-width="panelWin.centerMinWidth"
                        id="sessionCenterBlock"
                        class="center-block"
                        @change="panelBlockWidthChange"
                    >
                        <a-tabs v-model:activeKey="main.contactType"
                                @change="contactTypeChange"
                                class="zm-customize-tabs">
                            <a-tab-pane key="CUSTOMER" :tab="`客户(${main.contactCustomerCount})`"></a-tab-pane>
                            <a-tab-pane key="STAFF" :tab="`员工(${main.contactStaffCount})`"></a-tab-pane>
                            <a-tab-pane key="GROUP" :tab="`群聊(${main.contactGroupCount})`"></a-tab-pane>
                        </a-tabs>
                        <ContactColleague
                            v-show="main.contactType === 'STAFF'"
                            @change="val => contactChange(val, 'STAFF')"
                            @totalReport="val => contactTotalReport(val, 'STAFF')"
                            :staffInfo="currentStaff"
                            :filterData="filterData"
                            :default="defaultParams"
                            :key="contactCompKey"
                            class="main-content-box"
                        />
                        <ContactCustomer
                            v-show="main.contactType === 'CUSTOMER'"
                            @change="val => contactChange(val, 'CUSTOMER')"
                            @totalReport="val => contactTotalReport(val, 'CUSTOMER')"
                            @cancelCollect="onCancelCollect"
                            :staffInfo="currentStaff"
                            :filterData="filterData"
                            :default="defaultParams"
                            :callbackData="callbackData"
                            :key="contactCompKey * 100"
                            :showSettings="showSettings"
                            class="main-content-box"
                        />
                        <ContactGroup
                            v-show="main.contactType === 'GROUP'"
                            @change="val => contactChange(val, 'GROUP')"
                            @totalReport="val => contactTotalReport(val, 'GROUP')"
                            :staffInfo="currentStaff"
                            :filterData="filterData"
                            :default="defaultParams"
                            :key="contactCompKey * 200"
                            class="main-content-box"
                        />
                    </DragStretchBox>
                    <ChatBox ref="chatRef"
                             class="right-block"
                             mainTab="LOAD_BY_STAFF"
                             :chatInfo="chatInfo"
                             :currentMsgCancelCollect="currentMsgCancelCollect"
                             @changeCollect="onChangeCollect"
                             :sessionType="main.contactType === 'GROUP' ? 'group' : 'session'"/>
                </div>
            </div>
        </div>

        <SetSessionStaffModal ref="setStfModalRef" @report="({total}) => archiveStfTotal = total" @change="staffCompKey += 1"/>
    </div>
</template>

<script setup>
import {onMounted, reactive, computed, ref, nextTick} from 'vue';
import {useRoute, useRouter} from 'vue-router'
import {useStore} from 'vuex';
import {panelWinHandle} from "@/views/sessionArchive/components/panelWinHandle";
import {staffList} from "@/api/company";
import {getChatConfig} from "@/api/session";
import {assignData} from "@/utils/tools";
import StaffBox from "@/views/sessionArchive/components/modules/staffBox.vue";
import ChatBox from "@/views/sessionArchive/components/modules/chatBox.vue";
import ContactColleague from "@/views/sessionArchive/components/contacts/colleague.vue";
import ContactCustomer from "@/views/sessionArchive/components/contacts/customer.vue";
import ContactGroup from "@/views/sessionArchive/components/contacts/group.vue";
import DragStretchBox from "@/components/dragStretchBox.vue";
import FilterBoxByStaff from "@/views/sessionArchive/components/filter/filterBoxByStaff.vue";
import LoadingBox from "@/components/loadingBox.vue";
import SetSessionStaffModal from "@/views/sessionArchive/components/modules/setSessionStaffModal.vue";

const store = useStore()
const route = useRoute()
const router = useRouter()
const props = defineProps({
    defaultParams: {
        type: Object,
    }
})
const {panelWin, panelBlockWidthChange} = panelWinHandle()
const main = reactive({
    staffType: 'SESSION_STAFF',
    sessionStaffCount: 0,
    historyStaffCount: 0,
    selectedStaff: null,
    selectedHisStaff: null,

    contactType: 'CUSTOMER',
    contactStaffCount: 0,
    contactCustomerCount: 0,
    contactGroupCount: 0,
    contactStaff: null,
    contactCustomer: null,
    contactGroup: null,
})
const chatRef = ref(null)
const setStfModalRef = ref(null)
const filterData = ref(null)
const loading = ref(null)
const defaultStaff = ref(null)
const archiveStfTotal = ref(0)
const contactCompKey = ref(1)
const staffCompKey = ref(1)
const showSettings = reactive({
    show_customer_tag: 0,
    show_is_read: 0,
    show_customer_tag_config: {
        tag_ids: [],
        group_ids: []
    }
})
const CONTACT_TYPES = [
    'STAFF',
    'CUSTOMER',
    'GROUP',
]

const archiveStfModule = computed(() => {
    return store.getters.getArchiveStfInfo || {}
})

const archiveStfSettings = computed(() => {
    return store.getters.getArchiveStfSetting || {}
})

const currentStaff = computed(() => {
    switch (main.staffType) {
        case "SESSION_STAFF":
            return main.selectedStaff
        case "HISTORY_STAFF":
            return main.selectedHisStaff
    }
})

const currentContact = computed(() => {
    switch (main.contactType) {
        case 'STAFF':
            return main.contactStaff
        case 'CUSTOMER':
            return main.contactCustomer
        case 'GROUP':
            return main.contactGroup
    }
})

const chatInfo = computed(() => {
    if (!currentContact.value || !currentStaff.value) {
        return {}
    }
    let receiver
    let params = {
        conversation_id: currentContact.value.id,
        is_collect: currentContact.value.is_collect,
        collect_reason: currentContact.value.collect_reason,
        tab: 'LOAD_BY_STAFF' // 这里的tab是上级的，此处是按员工：LOAD_BY_STAFF
    }
    switch (main.contactType) {
        case 'GROUP':
            receiver = {
                name: currentContact.value.name,
                chat_id: currentContact.value.chat_id,
            }
            params = {
                group_chat_id: currentContact.value.chat_id,
                conversation_id: currentContact.value.id,
                is_collect: currentContact.value.is_collect,
                collect_reason: currentContact.value.collect_reason
            }
            break
        case 'CUSTOMER':
            receiver = {
                name: currentContact.value.external_name,
                external_userid: currentContact.value.external_userid,
            }
            break
        case 'STAFF':
            receiver = {
                name: currentContact.value.name,
                userid: currentContact.value.userid,
            }
            break
    }
    return {
        params: params,
        sender: {
            name: currentStaff.value.name,
            department_name: currentStaff.value.department_name,
            userid: currentStaff.value.userid,
            zm_user_type: 'STAFF'
        },
        receiver: {
            ...receiver,
            zm_user_type: main.contactType
        }
    }
})

const callbackData = ref({})
const currentMsgCancelCollect = ref(0)

const onChangeCollect = (obj) => {
    callbackData.value = obj
    currentContact.value.id = obj.conversation_id
    currentContact.value.is_collect = obj.is_collect
    currentContact.value.collect_reason = obj.collect_reason
}

const onCancelCollect = (val) => {
    currentMsgCancelCollect.value = Math.random()
}

onMounted(() => {
    checkDefaultChat()
    getShowSettings()
    if (route.query.conversation_type) {
        if (route.query.conversation_type == '1') {
            main.contactType = 'CUSTOMER'
        } else if (route.query.conversation_type == '2') {
            main.contactType = 'GROUP'
        } else if (route.query.conversation_type == '3') {
            main.contactType = 'STAFF'
        }
        loadSessionMsg()
    }
})

const checkDefaultChat = async () => {
    if (props.defaultParams && props.defaultParams.sender) {
        let type = props.defaultParams.receiver_type.toUpperCase()
        if (!CONTACT_TYPES.includes(type)) {
            return
        }
        main.contactType = type
        loading.value = true
        try {
            let params = {
                page: 1,
                size: 1,
                userid: props.defaultParams.sender,
            }
            let res = await staffList(params)
            if (type === 'STAFF' && (
                !res?.data?.items
                || !res?.data?.items.length
                || res.data.items[0].chat_status != 1)) {
                // 当接受者为员工
                // 发送者为非回话存档员工或不存在时根据接受者查询员工
                params.userid = props.defaultParams.receiver
                res = await staffList(params)
            }
            if (res?.data?.items && res?.data?.items.length) {
                defaultStaff.value = res.data.items[0]
                main.staffType = defaultStaff.value.chat_status == 1 ? 'SESSION_STAFF' : 'HISTORY_STAFF'
            }
        } catch (e) {

        }
        loading.value = false
    }
}

const getShowSettings = () => {
    getChatConfig().then(res => {
        let data = res.data || {}
        assignData(showSettings, data)
    })
}

const search = val => {
    filterData.value = val
    main.contactStaff = null
    main.contactCustomer = null
    main.contactGroup = null
    contactCompKey.value += 1
}

const totalReport = (val, type) => {
    main[type === 'SESSION_STAFF' ? 'sessionStaffCount' : 'historyStaffCount'] = val
}

const staffChange = (val, type) => {
    if (type === 'SESSION_STAFF') {
        main.selectedStaff = val
    } else {
        main.selectedHisStaff = val
    }
    main.contactStaff = null
    main.contactCustomer = null
    main.contactGroup = null
    contactCompKey.value += 1
}

const contactChange = (val, type) => {
    switch (type) {
        case 'STAFF':
            main.contactStaff = val
            break
        case 'CUSTOMER':
            main.contactCustomer = val
            break
        case 'GROUP':
            main.contactGroup = val
            break
    }
    type === main.contactType && loadSessionMsg()
}

const staffTypeChange = () => {
    staffChange(currentStaff.value, main.staffType)
}

const contactTypeChange = () => {
    loadSessionMsg()

}

const showSettingChange = config => {
    assignData(showSettings, config)
    contactCompKey.value += 1
}

const showSetStaffModal = () => {
    setStfModalRef.value.show()
}

const loadSessionMsg = () => {
    nextTick(() => {
        chatRef.value.init()
    })
}

const contactTotalReport = (val, type) => {
    switch (type) {
        case 'STAFF':
            main.contactStaffCount = val
            break
        case 'CUSTOMER':
            main.contactCustomerCount = val
            break
        case 'GROUP':
            main.contactGroupCount = val
            break
    }
}

const linkPlugHome = () => {
    const link = router.resolve({
        path: '/plug/index'
    })
    window.open(link.href)
}
</script>

<style scoped lang="less">
._container {
    display: flex;
    flex-direction: column;
    background: #FFF;
    font-size: 12px;
    margin: 12px;
    height: calc(100vh - 126px); // 窗口 - 顶部菜单 - 面包屑 - padding（24）
    border-radius: 6px;
    overflow: hidden;

    ._header {
        margin: 12px 12px 0;
        :deep(.ant-alert-message) {
            color: #3A4559;
        }
    }
}
.session-main-container {
    height: 100%;
    flex: 1;
    overflow: hidden;
    display: flex;
    > div {
        height: 100%;
    }

    :deep(.zm-customize-tabs) {
        .ant-tabs-nav-wrap {
            height: 42px;
        }

        & > .ant-tabs-nav::before {
            content: '';
            border-bottom: none;
        }
    }

    .session-left-block {
        border-right: 1px solid rgba(5, 5, 5, 0.06);
        flex-shrink: 0;
        width: 300px;
        min-width: 200px;;
        max-width: 60vw;
    }

    .session-right-block {
        flex: 1;
        display: flex;
        flex-direction: column;

        .filter-box {
            flex-shrink: 0;
        }

        .content-block {
            flex: 1;
            display: flex;
            overflow: hidden;

            > div {
                height: 100%;
            }

            .center-block {
                border-right: 1px solid rgba(5, 5, 5, 0.06);
            }

            .right-block {
                flex: 1;
            }
        }
    }

    .main-content-box {
        height: calc(100% - 42px);
    }
}
</style>
