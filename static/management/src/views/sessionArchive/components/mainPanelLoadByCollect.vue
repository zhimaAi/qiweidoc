<template>
    <!----按员工查看---->
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
                <a-tab-pane key="SINGLE_CHAT" tab="单聊"></a-tab-pane>
                <a-tab-pane key="GROUP_CHAT" tab="群聊"></a-tab-pane>
            </a-tabs>
            <StaffBox v-if="main.staffType === 'SINGLE_CHAT'"
                      class="main-content-box"
                      @totalReport="val => totalReport(val, 'SINGLE_CHAT')"
                      @change="val => staffChange(val, 'SINGLE_CHAT')"
                      :default="defaultStaff"
                      :type="1"/>

            <ContactGroup
                v-if="main.staffType === 'GROUP_CHAT'"
                @change="val => contactChange(val, 'GROUP_CHAT')"
                @totalReport="val => contactTotalReport(val, 'GROUP_CHAT')"
                :staffInfo="currentStaff"
                :filterData="filterData"
                :default="defaultParams"
                :key="contactCompKey * 200"
                class="main-content-box"
            />
        </DragStretchBox>
        <div class="session-right-block">
            <div class="content-block">
                <ChatBox ref="chatRef"
                         mainTab="LOAD_BY_COLLECT"
                         class="right-block"
                         :chatInfo="chatInfo"
                         @changeCollect="onChangeCollect"
                         :sessionType="main.staffType === 'GROUP_CHAT' ? 'group' : 'session'"/>
            </div>
        </div>

    </div>
</template>

<script setup>
import {onMounted, reactive, computed, ref, nextTick} from 'vue';
import {panelWinHandle} from "@/views/sessionArchive/components/panelWinHandle";
import StaffBox from "@/views/sessionArchive/components/modules/staffAndCustomerBox.vue";
import ChatBox from "@/views/sessionArchive/components/modules/chatBox.vue";
import ContactGroup from "@/views/sessionArchive/components/contacts/collectGroup.vue";
import DragStretchBox from "@/components/dragStretchBox.vue";
import {staffList} from "@/api/company";

const props = defineProps({
    defaultParams: {
        type: Object,
    }
})
const {panelWin, panelBlockWidthChange} = panelWinHandle()
const main = reactive({
    staffType: 'SINGLE_CHAT',
    sessionSingleChatCount: 0,
    sessionGroupChatCount: 0,
    sessionSingleChat: null,
    sessionGroupChat: null
})
const chatRef = ref(null)
const filterData = ref(null)
const loading = ref(null)
const defaultStaff = ref(null)
const contactCompKey = ref(1)
const CONTACT_TYPES = [
    'SINGLE_CHAT',
    'GROUP_CHAT',
]

const currentStaff = computed(() => {
    // console.log('currentStaff', main)
    switch (main.staffType) {
        case "SINGLE_CHAT":
            return main.sessionSingleChat
        case "GROUP_CHAT":
            return main.sessionGroupChat
    }
})

const currentContact = computed(() => {
    // console.log('currentContact', main)
    switch (main.staffType) {
        case 'SINGLE_CHAT':
            return main.sessionSingleChat
        case 'GROUP_CHAT':
            return main.sessionGroupChat
    }
})

const chatInfo = computed(() => {
    // console.log('currentContact1111', currentContact.value)
    // console.log('currentContact222', currentStaff.value)
    if (!currentContact.value || !currentStaff.value) {
        return {}
    }
    let receiver
    let params = {
        conversation_id: currentContact.value.id,
        is_collect: currentContact.value.is_collect,
        collect_reason: currentContact.value.collect_reason
    }
    switch (main.staffType) {
        case 'GROUP_CHAT':
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
        case 'SINGLE_CHAT':
            receiver = {
                name: currentContact.value.external_name,
                external_userid: currentContact.value.external_userid,
            }
            break
    }
    return {
        params: params,
        sender: {
            name: currentStaff.value.staff_name,
            is_collect: currentStaff.value.is_collect,
            collect_reason: currentStaff.value.collect_reason,
            conversation_id: currentStaff.value.id,
            zm_user_type: 'SINGLE_CHAT'
        },
        receiver: {
            ...receiver,
            zm_user_type: main.staffType
        }
    }
})

const totalReport = (val, type) => {
    main[type === 'SINGLE_CHAT' ? 'sessionSingleChatCount' : 'sessionGroupChatCount'] = val
}

const staffChange = (val, type) => {
    // console.log('main1', main)
    main.sessionSingleChat = val
    type === main.staffType && loadSessionMsg()
}

const contactChange = (val, type) => {
    // console.log('val', val)
    // console.log('type', type)
    // console.log('main', main)
    switch (type) {
        case 'SINGLE_CHAT':
            main.sessionSingleChat = val
            break
        case 'GROUP_CHAT':
            main.sessionGroupChat = val
            break
    }
    // 下面的打开就会重复请求
    // contactCompKey.value += 1
    type === main.staffType && loadSessionMsg()
}

const staffTypeChange = () => {
    staffChange(currentStaff.value, main.staffType)
}

const loadSessionMsg = () => {
    // console.log('chatRef', chatRef)
    nextTick(() => {
        chatRef.value.init()
    })
}

const contactTotalReport = (val, type) => {
    switch (type) {
        case 'SINGLE_CHAT':
            main.sessionSingleChatCount = val
            break
        case 'GROUP_CHAT':
            main.sessionGroupChatCount = val
            break
    }
}

const callbackData = ref({})
const onChangeCollect = (obj) => {
    // console.log('items', obj)
    callbackData.value = obj
    // 下面按照规定改main.sessionSingleChat main.sessionGroupChat也行
    currentContact.value.id = obj.conversation_id
    currentContact.value.is_collect = obj.is_collect
    currentContact.value.collect_reason = obj.collect_reason
}

</script>

<style scoped lang="less">
.session-main-container {
    background: #FFF;
    font-size: 12px;
    margin: 12px;
    display: flex;
    height: calc(100vh - 126px); // 窗口 - 顶部菜单 - 面包屑 - padding（24）
    border-radius: 6px;

    > div {
        height: 100%;
    }

    :deep(.zm-customize-tabs) {
        & >.ant-tabs-nav::before {
            content: '';
            border-bottom: none;
        }
        .ant-tabs-nav-wrap {
            height: 42px;

            .ant-tabs-nav-list {
                padding: 0 16px;
            }
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
