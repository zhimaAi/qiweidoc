<template>
    <MainLayout>
        <template #navbar>
            <a-tabs v-model:active-key="BASE_TYPE"
                    @change="mainTabChange"
                    class="zm-nav-tabs">
                <a-tab-pane key="LOAD_BY_STAFF">
                    <template #tab>
                        <StaffPaymentTag>按员工</StaffPaymentTag>
                    </template>
                </a-tab-pane>
                <a-tab-pane key="LOAD_BY_CUSTOMER" tab="按客户"/>
                <a-tab-pane key="LOAD_BY_GROUP" tab="按群聊"/>
                <a-tab-pane key="LOAD_BY_COLLECT" tab="收藏聊天"/>
            </a-tabs>
        </template>
        <div id="session-panel">
            <SelectTagModal/>
            <LoadingBox v-if="loading"/>
            <Component v-else :is="mainPanelComponent" :defaultParams="defaultParams"/>
        </div>
    </MainLayout>
</template>

<script setup>
import {onMounted, h, ref, computed, nextTick} from 'vue';
import MainLayout from "@/components/mainLayout.vue";
import MainPanelLoadByStaff from "./components/mainPanelLoadByStaff.vue";
import MainPanelLoadByCollect from "./components/mainPanelLoadByCollect.vue";
import MainPanelLoadByCst from "./components/mainPanelLoadByCst.vue";
import MainPanelLoadByGroup from "./components/mainPanelLoadByGroup.vue";
import {useRouter, useRoute} from 'vue-router';
import LoadingBox from "@/components/loadingBox.vue";
import {copyObj} from "@/utils/tools";
import SelectTagModal from "@/components/select-customer-tag/selectTagModal.vue";
import StaffPaymentTag from "@/views/sessionArchive/components/modules/staffPaymentTag.vue";

const router = useRouter()
const route = useRoute()
const BASE_TYPE = ref('LOAD_BY_STAFF')
const MAIN_TABS = [
    'LOAD_BY_STAFF',
    'LOAD_BY_CUSTOMER',
    'LOAD_BY_GROUP',
    'LOAD_BY_COLLECT'
]
const defaultParams = ref(null)
const loading = ref(true)
const mainPanelComponent = computed(() => {
    switch (BASE_TYPE.value) {
        case 'LOAD_BY_STAFF':
            return MainPanelLoadByStaff
        case 'LOAD_BY_CUSTOMER':
            return MainPanelLoadByCst
        case 'LOAD_BY_GROUP':
            return MainPanelLoadByGroup
        case 'LOAD_BY_COLLECT':
            return MainPanelLoadByCollect
    }
})

onMounted(() => {
    let query = copyObj(route.query)
    if (route.query.tab && MAIN_TABS.includes(route.query.tab)) {
        BASE_TYPE.value = route.query.tab
    }
    /**
     * 检测queru参数是否携带指定聊天信息
     */
    if (route.query.group_chat_id) {
        // 跳转指定群聊
        BASE_TYPE.value = 'LOAD_BY_GROUP'
        defaultParams.value = {
            group_chat_id: query.group_chat_id
        }
        delete query.group_chat_id
    } else if (route.query.sender && route.query.sender_type && route.query.conversation_id) {
        // 跳转其他会话（同事回话、客户会话）
        // 群聊会话也有兼容
        switch (route.query.sender_type) {
            case 'Customer':
                BASE_TYPE.value = 'LOAD_BY_CUSTOMER'
                break
            case 'Staff':
                BASE_TYPE.value = 'LOAD_BY_STAFF'
                break
            case 'Collect':
                BASE_TYPE.value = 'LOAD_BY_COLLECT'
                break
        }
        defaultParams.value = {
            sender: query.sender,
            receiver: query.receiver,
            sender_type: query.sender_type,
            receiver_type: query.receiver_type,
            conversation_id: query.conversation_id
        }
        delete query.sender
        delete query.sender_type
        delete query.receiver
        delete query.receiver_type
        delete query.conversation_id
    }
    query.tab = BASE_TYPE.value
    loading.value = false
    nextTick(() => {
        // 重置路由信息
        resetRouteQuery(query)
    })
})

const mainTabChange = () => {
    resetRouteQuery({...route.query, tab: BASE_TYPE.value})
}

const resetRouteQuery = queryParams => {
    router.replace({
        path: route.path,
        query: queryParams
    });
}
</script>

<style scoped lang="less">
#session-panel {
    position: relative;
    min-height: calc(100vh - 115px);
    :deep(.ant-tabs-tab) {
        font-size: 12px;
    }

    :deep(.ant-tabs-nav) {
        margin-bottom: 0;
    }

    :deep(.ant-input),
    :deep(.ant-select-selection-placeholder){
        font-size: 12px;
    }
}
</style>
