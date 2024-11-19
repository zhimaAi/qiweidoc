<template>
    <MainLayout>
        <template #navbar>
            <a-tabs v-model:active-key="BASE_TYPE"
                    @change="mainTabChange"
                    class="nav-tabs">
                <a-tab-pane key="LOAD_BY_STAFF" tab="按员工"/>
                <a-tab-pane key="LOAD_BY_CUSTOMER" tab="按客户"/>
                <a-tab-pane key="LOAD_BY_GROUP" tab="按群聊"/>
            </a-tabs>
        </template>
        <div id="session-panel">
            <LoadingBox v-if="loading"/>
            <Component v-else :is="mainPanelComponent" :defaultParams="defaultParams"/>
        </div>
    </MainLayout>
</template>

<script setup>
import {onMounted, h, ref, computed, nextTick} from 'vue';
import MainLayout from "@/components/mainLayout.vue";
import MainPanelLoadByStaff from "./components/mainPanelLoadByStaff.vue";
import MainPanelLoadByCst from "./components/mainPanelLoadByCst.vue";
import MainPanelLoadByGroup from "./components/mainPanelLoadByGroup.vue";
import {useRouter, useRoute} from 'vue-router';
import LoadingBox from "@/components/loadingBox.vue";
import {copyObj} from "@/utils/tools";


const router = useRouter()
const route = useRoute()
const BASE_TYPE = ref('LOAD_BY_STAFF')
const MAIN_TBBS = [
    'LOAD_BY_STAFF',
    'LOAD_BY_CUSTOMER',
    'LOAD_BY_GROUP'
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
    }
})

onMounted(() => {
    let query = copyObj(route.query)
    if (route.query.tab && MAIN_TBBS.includes(route.query.tab)) {
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
        rsetRouteQuery(query)
    })
})

const mainTabChange = () => {
    rsetRouteQuery({...route.query, tab: BASE_TYPE.value})
}

const rsetRouteQuery = queryParams => {
    router.replace({
        path: route.path,
        query: queryParams
    });
}
</script>

<style scoped lang="less">
:deep(.nav-tabs.ant-tabs) {
    background: #FFF;

    .ant-tabs-nav-list {
        margin: 0 24px;
    }

    .ant-tabs-nav {
        margin-bottom: 0;
    }

    .ant-tabs-tab {
        font-size: 16px;
        color: #595959;

        &.ant-tabs-tab-active {
            font-weight: 500;
        }
    }

    .ant-tabs-ink-bar {
        display: none;
    }
}

#session-panel {
    position: relative;
    min-height: 80vh;
    :deep(.ant-tabs-tab) {
        font-size: 12px;
    }

    :deep(.ant-tabs-nav) {
        margin-bottom: 0;
    }

    :deep(.ant-input) {
        font-size: 12px;
    }
}
</style>
