<template>
    <MainLayout>
      <template #navbar>
        <a-breadcrumb v-if="links.length > 1" class="nav-tabs">
            <a-breadcrumb-item v-for="(item, index) in links" :key="item.name">
                <template v-if="index !== links.length - 1">
                    <router-link v-if="item.to" :to="{ path: item.to }">
                    {{ item.name }}
                    </router-link>
                    <a v-else @click="$router.back(-1)">{{ item.name }}</a>
                </template>
                <span v-else>{{ query.task_id ? `编辑${item.name}`: `新建${item.name}` }}</span>
            </a-breadcrumb-item>
        </a-breadcrumb>
      </template>
      <div class="sensitive-panel">
          <LoadingBox v-if="loading"/>
          <MainPanelLoadRuleAdd />
      </div>
    </MainLayout>
  </template>

  <script setup>
  import { onMounted, ref, computed } from 'vue';
  import MainLayout from "@/components/mainLayout.vue";
  import { useRoute } from 'vue-router';
  import LoadingBox from "@/components/loadingBox.vue";
  import MainPanelLoadRuleAdd from "./components/ruleStore.vue";

  const route = useRoute()
  const query = route.query
  const links = computed(() => route.meta.links || [])

  const loading = ref(false)

  const getDetail = () => {
    // 获取详情接口，渲染数据
  }

  onMounted(() => {
    query.task_id && getDetail()
  })
  </script>

  <style scoped lang="less">
  :deep(.nav-tabs.ant-breadcrumb) {
    background: #FFF;
    height: 56px;
    padding: 17px 24px;
    box-sizing: border-box;

    li {
      font-size: 14px;
      color: #262626;

      a {
        font-weight: 400;

        &:hover {
            background-color: transparent;
            color: #2475FC;
        }
      }
    }
  }
  </style>
