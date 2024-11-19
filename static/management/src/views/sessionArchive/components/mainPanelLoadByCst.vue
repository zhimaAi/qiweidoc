<template>
    <!----按客户查看---->
    <div class="session-main-container">
        <FilterBoxByCst class="filter-block" @change="search"/>
        <div id="sessionMainContent" class="session-main-content">
            <DragStretchBox
                @change="panelBlockWidthChange"
                :max-width="panelWin.leftMaxWith"
                :min-width="panelWin.leftMinWith"
                id="sessionLeftBlock"
                class="session-left-block">
                <div class="header">
                    <div class="title">客户<span class="staff-num">（{{ main.customerCount }}）</span></div>
                </div>
                <CustomerBox
                    :key="cstCompKey"
                    :filterData="filterData"
                    :default="defaultParams"
                    class="main-content-box"
                    @change="cstChange"
                    @totalReport="totalReport"/>
            </DragStretchBox>
            <div class="session-right-block">
                <div class="content-block">
                    <DragStretchBox
                        @change="panelBlockWidthChange"
                        :max-width="panelWin.centerMaxWidth"
                        :min-width="panelWin.centerMinWidth"
                        id="sessionCenterBlock"
                        class="center-block">
                        <a-tabs v-model:active-key="main.contactType" class="zm-customize-tabs">
                            <a-tab-pane key="CUSTOMER" :tab="`员工(${main.contactStaffCount})`"></a-tab-pane>
                        </a-tabs>
                        <ContactStaff
                            :key="contactCompKey"
                            :cstInfo="main.selectedCustomer"
                            :default="defaultParams"
                            @change="contactChange"
                            @totalReport="contactTotalReport"/>
                    </DragStretchBox>
                    <div class="right-block">
                        <ChatBox ref="chatRef" :chatInfo="chatInfo" style="height: 100%;"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref, reactive, computed, nextTick} from 'vue';
import {panelWinHandle} from "@/views/sessionArchive/components/panelWinHandle";
import CustomerBox from "@/views/sessionArchive/components/modules/customerBox.vue";
import FilterBoxByCst from "@/views/sessionArchive/components/filter/filterBoxByCst.vue";
import ChatBox from "@/views/sessionArchive/components/modules/chatBox.vue";
import DragStretchBox from "@/components/dragStretchBox.vue";
import ContactStaff from "@/views/sessionArchive/components/contacts/staff.vue";

const props = defineProps({
    defaultParams: {
        type: Object,
    }
})
const {panelWin, panelBlockWidthChange} = panelWinHandle()
const cstCompKey = ref(1)
const contactCompKey = ref(1)
const chatRef = ref(null)
const filterData = ref(null)
const main = reactive({
    customerCount: 0,
    contactStaffCount: 0,
    selectedCustomer: null,
    selectedContact: null,
})

const chatInfo = computed(() => {
    let params = null,
        sender = null,
        receiver = null
    if (main.selectedContact?.id) {
        params = {
            conversation_id: main.selectedContact?.id
        }
        receiver = {
            name: main.selectedContact?.name,
            userid: main.selectedContact?.userid,
            zm_user_type: 'STAFF'
        }
    }
    if (main.selectedCustomer) {
        sender = {
            name: main.selectedCustomer?.external_name,
            external_userid: main.selectedCustomer?.external_userid,
            zm_user_type: 'CUSTOMER'
        }
    }
    return {
        params,
        sender,
        receiver
    }
})

const search = val => {
    filterData.value = val
    main.customerCount = 0
    main.contactStaffCount = 0
    main.selectedCustomer = null
    main.selectedContact = null
    cstCompKey.value += 1
}

const loadSessionMsg = () => {
    nextTick(() => {
        console.log('-----')
        chatRef.value.init()
    })
}

const cstChange = cst => {
    main.selectedCustomer = cst
    contactCompKey.value += 1
}

const contactChange = cst => {
    main.selectedContact = cst
    loadSessionMsg()
}

const totalReport = val => {
    main.customerCount = val
}

const contactTotalReport = val => {
    main.contactStaffCount = val
}
</script>

<style scoped lang="less">
.session-main-container {
    background: #FFF;
    font-size: 12px;
    margin: 12px;
    display: flex;
    flex-direction: column;
    height: calc(100vh - 126px); // 窗口 - 顶部菜单 - 面包屑 - padding（24）
    min-height: 800px;

    .filter-block {
        flex-shrink: 0;
    }

    .session-main-content {
        display: flex;
        flex: 1;
        overflow: hidden;

        > div {
            height: 100%;
        }

        .session-left-block {
            border-right: 1px solid rgba(5, 5, 5, 0.06);
            flex-shrink: 0;
            width: 300px;
            min-width: 230px;;
            max-width: 60vw;

            .header {
                padding: 12px 16px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                height: 42px;

                .title {
                    font-size: 12px;
                    font-weight: 600;
                    color: rgba(0, 0, 0, 0.85);
                    display: flex;
                    align-items: baseline;
                    white-space: nowrap;
                    text-overflow: ellipsis;
                    overflow: hidden;
                }

                .staff-num {
                    white-space: nowrap;
                    display: inline-block;
                    max-width: 140px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
            }
        }

        .session-right-block {
            flex: 1;

            .content-block {
                display: flex;
                height: 100%;

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

        :deep(.zm-customize-tabs) {
            .ant-tabs-nav-wrap {
                height: 42px;
            }
        }
    }
}
</style>
