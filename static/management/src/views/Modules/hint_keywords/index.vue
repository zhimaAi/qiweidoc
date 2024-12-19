<template>
  <div>
    <a-tabs v-model:activeKey="activeKey" @change="onChangeType" class="nav-tabs">
        <a-tab-pane key="LOAD_BY_RULE" tab="敏感词规则" force-render></a-tab-pane>
        <a-tab-pane key="LOAD_BY_DETAIL" tab="触发明细"></a-tab-pane>
        <a-tab-pane key="LOAD_BY_LEXICON" tab="敏感词库"></a-tab-pane>
    </a-tabs>
    <div class="sensitive-panel">
        <LoadingBox v-if="loading"/>
        <Component v-else :is="mainPanelComponent" :defaultParams="defaultParams"/>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import LoadingBox from "@/components/loadingBox.vue";
import MainPanelLoadRule from "./components/rule.vue";
import MainPanelLoadDetail from "./components/detail.vue";
import MainPanelLoadLexicon from "./components/lexicon.vue";
import { copyObj } from "@/utils/tools";

const router = useRouter()
const route = useRoute()
const activeKey = ref('LOAD_BY_RULE')
const MAIN_TBBS = [
  'LOAD_BY_RULE',
  'LOAD_BY_DETAIL',
  'LOAD_BY_LEXICON'
]

const defaultParams = ref(null)
// eslint-disable-next-line vue/return-in-computed-property
const mainPanelComponent = computed(() => {
  switch (activeKey.value) {
    case 'LOAD_BY_RULE':
        return MainPanelLoadRule
    case 'LOAD_BY_DETAIL':
        return MainPanelLoadDetail
    case 'LOAD_BY_LEXICON':
        return MainPanelLoadLexicon
    default:
      return MainPanelLoadRule
  }
})

const loading = ref(false)

const onChangeType = () => {
  rsetRouteQuery({ ...route.query, tab: activeKey.value })
}

const rsetRouteQuery = queryParams => {
  router.replace({
    path: route.path,
    query: queryParams
  });
}

onMounted(() => {
  let query = copyObj(route.query)
  if (route.query.sender && route.query.sender_type && route.query.conversation_id) {
      // 跳转其他会话（同事回话、客户会话）
      // 群聊会话也有兼容
      switch (route.query.sender_type) {
          case 'Customer':
              activeKey.value = 'LOAD_BY_CUSTOMER'
              break
          case 'Staff':
              activeKey.value = 'LOAD_BY_STAFF'
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
  if (route.query.tab && MAIN_TBBS.includes(route.query.tab)) {
    activeKey.value = route.query.tab
  }
})
</script>

<style scoped lang="less">
:deep(.nav-tabs.ant-tabs) {
  background: #FFF;

  .ant-tabs-nav-wrap {
    height: 56px;
  }

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
    // display: none;
  }
}
</style>
