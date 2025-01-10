<template>
  <div>
    <MainNavbar :title="[
           {name: '企业高级设置'}
      ]"/>
    <div class="sensitive-panel">
      <LoadingBox v-if="loading"/>
      <MainPanelLoadRule :defaultParams="defaultParams" />
    </div>
  </div>
</template>

  <script setup>
  import { onMounted, ref } from 'vue';
  import { useRoute } from 'vue-router';
  import LoadingBox from "@/components/loadingBox.vue";
  import MainPanelLoadRule from "./components/rule.vue";
  import { copyObj } from "@/utils/tools";
  import MainNavbar from "@/components/mainNavbar.vue";

  const route = useRoute()
  const defaultParams = ref(null)
  const loading = ref(false)

  onMounted(() => {
    let query = copyObj(route.query)
    if (route.query.sender && route.query.sender_type && route.query.conversation_id) {
        // 跳转其他会话（同事回话、客户会话）
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
  }
  </style>
