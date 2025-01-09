<template>
  <div class="staff-box">
    <div class="role-title-box">
      <div class="role-title">全部角色</div>
      <div class="role-add" @click="onAdd" v-if="loginInfo.permission_list.indexOf('user_permission.edit') > -1">
        <img src="@/assets/svg/add.svg" class="add" />
      </div>
    </div>
    <div v-for="item in list" @click="change(item)" :class="['staff-item', { active: isSelected(item) }]">
      <div class="item-title">{{ item.role_name }}</div>
      <div class="item-num">{{ item.staff_total }}</div>
      <div class="item-more" v-if="loginInfo.permission_list.indexOf('user_permission.edit') > -1 && item.id > 99">
        <a-dropdown>
          <a class="ant-dropdown-link" @click.prevent>
            <MoreOutlined />
          </a>
          <template #overlay>
            <a-menu>
              <a-menu-item @click="onEditRole(item)">
                <a href="javascript:;" style="color: #595959;">修改角色</a>
              </a-menu-item>
              <a-menu-item @click="onDeleteRole(item)">
                <a href="javascript:;" style="color: #FB363F;" >删除角色</a>
              </a-menu-item>
            </a-menu>
          </template>
        </a-dropdown>
      </div>
    </div>
    <a-empty v-if="list.length < 1" description="暂无数据" class="role-empty" />
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { useStore } from 'vuex';
import { MoreOutlined } from '@ant-design/icons-vue';
import { roleList, deleteRole } from "@/api/session";
import { useRouter } from 'vue-router';
import { Modal, message } from 'ant-design-vue';

const store = useStore()
const loginInfo = computed(() => {
    return store.getters.getUserInfo
})
const router = useRouter()
const emit = defineEmits(['change', 'totalReport'])
const list = ref([])
const selected = ref(null)

const onAdd = () => {
  router.push('/module/user_permission/config')
}

const onDeleteRole = (item) => {
  let params = {
    role_id: item.id
  }
  Modal.confirm({
    title: '提示',
    content: '确认删除该角色？',
    onOk: () => {
      deleteRole(params).then(() => {
        message.success('已删除')
        loadData()
      })
    }
  })
}

const onEditRole = (item) => {
  router.push({
    path: '/module/user_permission/config',
    query: {
      role_id: item.id
    }
  })
}

const loadData = () => {
  let params = {}
  roleList(params).then(res => {
    let items = res.data || []
    list.value = items
    selectedHandle()
  }).catch(() => {
  })
}

const selectedHandle = () => {
  change(list.value[0] || null)
}

const isSelected = item => {
  return selected.value && selected.value.id === item?.id
}

const change = (item) => {
  selected.value = item
  if (!item) {
    return
  }
  emit("change", item)
}

onMounted(() => {
  loadData()
})

defineExpose({
  loadData
})
</script>

<style scoped lang="less">
.role-title-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-family: "PingFang SC";
  font-style: normal;
  margin-bottom: 8px;
  height: 32px;

  .role-title {
    color: #262626;
    font-size: 14px;
    font-weight: 600;
    line-height: 22px;
  }

  .role-add {
    display: flex;
    padding: 4px;
    justify-content: center;
    align-items: center;
    gap: 4px;
    border-radius: 6px;
    width: 24px;
    height: 24px;
    cursor: pointer;

    .add {
      width: 20px;
    }

    &:hover {
      background: var(--07, #E4E6EB);
    }
  }
}

.staff-box {
  padding: 24px;

  .staff-item {
    cursor: pointer;
    width: 100%;
    display: flex;
    height: 32px;
    padding: 5px 8px;
    align-items: center;
    gap: 8px;
    align-self: stretch;
    border-radius: 6px;
    margin-bottom: 4px;

    .item-title {
      height: 22px;
      flex: 1 0 0;
      overflow: hidden;
      color: #595959;
      text-overflow: ellipsis;
      white-space: nowrap;
      font-family: "PingFang SC";
      font-size: 14px;
      font-style: normal;
      font-weight: 400;
      line-height: 22px;
    }

    .item-num {
      color: #8c8c8c;
      text-align: right;
      font-family: "PingFang SC";
      font-size: 12px;
      font-style: normal;
      font-weight: 400;
      line-height: 20px;
    }

    .item-more {
      display: none;
    }

    &:hover {
      background: var(--07, #E4E6EB);

      .item-more {
        display: block;
        width: 16px;
        height: 16px;
        border-radius: 6px;
      }

      .item-num {
        display: none;
      }
    }

  }

  .active {
    background: var(--01-, #E5EFFF);

    .item-title,.item-num {
      color: #2475FC;
    }

    &:hover {
      background: var(--01-, #E5EFFF);
    }
  }
}

.role-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  margin-top: 60px;
}
</style>
