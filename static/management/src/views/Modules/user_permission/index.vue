<template>
  <div>
    <MainNavbar :title="[
      { name: '权限管理' }
    ]" />
    <div id="sessionMainContent" class="session-main-container">
      <DragStretchBox @change="panelBlockWidthChange" :max-width="panelWin.leftMaxWith"
        :min-width="panelWin.leftMinWith" id="sessionLeftBlock" class="session-left-block">
        <div class="main-content-box">
          <RoleList ref="roleListRef" @change="onSearchUser"></RoleList>
        </div>
      </DragStretchBox>
      <div class="session-right-block">
        <div class="content-block">
          <div class="right-block">
            <RoleInfo ref="roleInfoRef" @updateList="onUpdateList"></RoleInfo>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { panelWinHandle } from "@/views/sessionArchive/components/panelWinHandle";
import DragStretchBox from "@/components/dragStretchBox.vue";
import MainNavbar from "@/components/mainNavbar.vue";
import RoleList from './components/role-list.vue'
import RoleInfo from './components/role-info.vue'

const { panelWin, panelBlockWidthChange } = panelWinHandle()
const roleListRef = ref(null)
const roleInfoRef = ref(null)

const onSearchUser = (item) => {
  if (roleInfoRef.value) {
    roleInfoRef.value.onSearchUser(item)
  }
}

const onUpdateList = () => {
  if (roleListRef.value) {
    roleListRef.value.loadData()
  }
}

onMounted(() => {
})
</script>

<style scoped lang="less">
.session-main-container {
  background: #FFF;
  font-size: 12px;
  margin: 12px;
  display: flex;
  height: calc(100vh - 72px); // 窗口 - 顶部菜单 - 面包屑 - padding（24）
  border-radius: 6px;

  >div {
    height: 100%;
  }

  :deep(.zm-customize-tabs) {
    &>.ant-tabs-nav::before {
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
    width: 217px;
    min-width: 200px;
    ;
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
      height: 100%;
    //   overflow: hidden;

      >div {
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
