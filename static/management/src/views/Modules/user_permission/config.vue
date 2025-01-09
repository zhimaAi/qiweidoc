<template>
  <div>
    <MainNavbar :title="[
      { name: '权限管理', route: '/module/user_permission/index' },
      { name: query.role_id ? '编辑角色' : '新增角色' }
    ]" />
    <div class="sensitive-panel">
      <LoadingBox v-if="loading" />
      <MainPanelLoadRoleAdd />
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import LoadingBox from "@/components/loadingBox.vue";
import MainPanelLoadRoleAdd from "./components/role-add.vue";
import MainNavbar from "@/components/mainNavbar.vue";

const route = useRoute()
const query = route.query
const loading = ref(false)

onMounted(() => {
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
