<template>
  <div>
    <MainNavbar :title="[
           {name: '关键词打标签', route: '/module/keywords_tagging/index'},
           {name: query.task_id ? '编辑规则' : '新建规则'}
      ]"/>
    <div class="sensitive-panel">
      <LoadingBox v-if="loading"/>
      <MainPanelLoadRuleAdd />
    </div>
  </div>
</template>

  <script setup>
  import { onMounted, ref, computed } from 'vue';
  import { useRoute } from 'vue-router';
  import LoadingBox from "@/components/loadingBox.vue";
  import MainPanelLoadRuleAdd from "./components/ruleStore.vue";
  import MainNavbar from "@/components/mainNavbar.vue";

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
