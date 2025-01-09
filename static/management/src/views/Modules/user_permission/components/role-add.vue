<template>
  <div class="container">
    <a-form :label-col="{ span: 3 }" :wrapper-col="{ span: 21 }">
      <a-form-item required label="角色名称">
        <a-input v-model:value="formState.role_name" placeholder="请输入角色名称" :max-length="20" style="width: 495px;" />
      </a-form-item>

      <a-form-item style="margin-left: 32px;" v-for="item in formState.permission_list" :key="item.permission_index">
        <div class="role-label">
          <div class="role-label-label">{{ item.server_name }}</div>
          <div class="role-label-line"></div>
          <div class="role-label-info">{{ item.description }}</div>
        </div>

        <div class="role-item-box">
          <div class="role-item" v-for="ctem in item.permission_list" :key="ctem.permission_key">
            <div class="role-item-label">{{ ctem.permission_node_title }}</div>

            <div class="role-item-group">
              <a-checkbox-group v-model:value="ctem.permission_keys">
                <a-checkbox v-for="childs in ctem.permission_detail.child" :key="childs.permission_key" class="role-item-group-text" :value="childs.permission_key">{{ childs.description }}</a-checkbox>
              </a-checkbox-group>
            </div>
          </div>
        </div>
      </a-form-item>

      <a-form-item></a-form-item>
      <div class="zm-fixed-bottom-box in-module" style="padding-left: 130px;">
        <a-button @click="cancel">取 消</a-button>
        <a-button @click="save" :loading="saving" class="ml8" type="primary">保 存</a-button>
      </div>
    </a-form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { copyObj } from "@/utils/tools";
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { permissionList, permissionSave, roleList } from "@/api/session";

const route = useRoute()
const query = route.query
const router = useRouter()
const loading = ref(false)
const saving = ref(false)
const formState = reactive({
  role_name: "", // 角色名称
  permission_list: []
})
const permission_config = ref([])

function formPermissionData (AllArr, selectArr) {
  console.log('rrr')
  AllArr.map((item) => {
    item.permission_list.map(ctem => {
      ctem.permission_detail.child.map(childs => {
        if (selectArr.indexOf(childs.permission_key) > -1) {
          if (ctem.permission_keys && ctem.permission_keys.length) {
            ctem.permission_keys.push(childs.permission_key)
          } else {
            ctem.permission_keys = [childs.permission_key]
          }
        }
      })
    })
  })

  return AllArr
}

const getRoleList = () => {
  let params = {
    role_id: query.role_id
  }
  roleList(params).then((res) => {
    if (res.status === 'success') {
      res.data.map((item) => {
        formState.role_name = item.role_name
        permission_config.value = [...item.permission_config, ...permission_config.value]

        if (permission_config.value.length) {
          console.log('112212')
          formState.permission_list = formPermissionData(formState.permission_list, permission_config.value)
        }
      })
    }
  })
}

const getDetail = () => {
  loading.value = true
  permissionList().then(res => {
    try {
      let lists = copyObj(res.data || {})
      formState.permission_list = lists
      query.role_id && getRoleList()
    } catch (e) {
    }
  }).finally(() => {
    loading.value = false
  })
}

const cancel = () => {
  router.push({ path: "/module/user_permission/index" })
}

const verify = () => {
  formState.role_name = formState.role_name.trim()
  if (!formState.role_name) throw "请输入角色名称"
}

function formatPermission (arrs) {
  let keys = []
  arrs.map(item => {
    item.permission_list.map(ctem => {
      if (ctem.permission_keys) {
        keys.push(...ctem.permission_keys)
      }
    })
  })
  console.log('keys', keys)
  return keys
}

const save = () => {
  saving.value = true
  try {
    verify()
    console.log('666')
    let params = {
      role_name: formState.role_name,
      permission_config: formatPermission(formState.permission_list)
    }
    if (query.role_id) {
      params.id = query.role_id
    }
    permissionSave(params).then(res => {
      if (res.status === 'success') {
        message.success("操作成功")
        setTimeout(() => {
          cancel()
        }, 1200)
        saving.value = false
      }
    })
  } catch (e) {
    message.error(e)
    saving.value = false
  }
}

onMounted(() => {
  getDetail()
})
</script>

<style scoped lang="less">
.container {
  background: #FFFFFF;
  margin: 12px;
  padding: 24px 0;
  min-height: 100vh;
}

/deep/ .ant-checkbox-wrapper.w100 {
  >span:last-child {
    display: inline-block;
    min-width: 100px;
  }
}

.role-label {
  display: flex;
  align-items: center;
  width: 698px;
  padding: 8px 24px 8px 16px;
  border-radius: 6px;
  background: var(--09, #F2F4F7);
  font-family: "PingFang SC";
  gap: 8px;
  margin-bottom: 16px;

  .role-label-label {
    color: #262626;
    font-size: 14px;
    font-style: normal;
    font-weight: 600;
    line-height: 22px;
  }

  .role-label-line {
    width: 1px;
    height: 12px;
    border-radius: 1px;
    background: var(--05, #BFBFBF);
  }

  .role-label-info {
    color: #8c8c8c;
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 22px;
  }
}

.role-item-box {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.role-item {
  display: flex;
  align-items: center;
  font-family: "PingFang SC";

  .role-item-label {
    width: 90px;
    text-align: right;
    color: #262626;
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 22px;
  }

  .role-item-group {
    margin-left: 28px;

    .role-item-group-text {
      color: #595959;
    }
  }
}
</style>
