<template>
  <div class="staff-box">
    <div class="role-title-box">
      <div class="role-left">
        <div class="role-title">{{ filterData.role_name }}</div>
        <div class="role-line"></div>
        <div class="role-num">
          <a-popover placement="rightTop" overlayClassName="role-tip-popover">
            <template #content>
              <div class="role-pop-content">
                <div class="role-pop-content-item" v-for="item in filterData.permission_detail" :key="item.permission_key">
                  <img class="icon" src="@/assets/image/icon-read.png"/>
                  <span class="label">{{ item.description }}</span>
                </div>
              </div>
            </template>
            <template #title>
              <div class="role-pop-title-box">
                <div class="role-pop-title-left">
                  <span class="role-pop-title-label">{{ filterData.role_name }}</span>
                  <div class="role-pop-title-info">(共{{ filterData.permission_detail.length }}个权限)</div>
                </div>
                <div class="role-pop-title-right" @click="onEditRole" v-if="loginInfo.permission_list.indexOf('user_permission.edit') > -1 && filterData.role_id > 99">
                  <SettingOutlined class="icon" />
                  <span class="role-pop-title-text">权限设置</span>
                </div>
              </div>
            </template>
            共{{ filterData.permission_detail.length }}个权限
          </a-popover>
        </div>
      </div>
    </div>
    <div class="role-options">
      <div class="role-options-left">
        <SelectStaffBoxNormal
          v-if="loginInfo.permission_list.indexOf('user_permission.edit') > -1 && filterData.role_id != 3"
          :btnType="'primary'"
          :btnStyle="''"
          :btnText="'添加成员'"
          :showSelectStaff="false"
          :showClearSelectStaff="true"
          :selectedStaffs="formState.staff_user_list"
          @change="staffChange"/>

        <a-button
          v-if="loginInfo.permission_list.indexOf('user_permission.edit') > -1 && filterData.role_id != 3"
          class="add_tag_btn"
          @click="batchChangeRoles"
        >
          批量更换角色
        </a-button>
        <a-button
          v-if="loginInfo.permission_list.indexOf('user_permission.edit') > -1 && filterData.role_id > 99"
          class="add_tag_btn"
          @click="onEditRole"
        >
          权限设置
        </a-button>
      </div>
      <div class="role-options-right">
        <a-input-search style="width:244px" placeholder="请输入员工昵称进行搜索" v-model:value="filterData.keyword" @search="onSearch"
          :allowClear="true" />
      </div>
    </div>
    <a-table
      class="mt8 role-table-box"
      :loading="loading"
      :data-source="tableData"
      :columns="columns"
      :pagination="pagination"
      :row-selection="{
        selectedRowKeys: formState.staff_userid_list,
        onSelect: onSelect,
        onSelectAll: onSelectAll
      }"
      row-key="userid"
      @change="tableChange"
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="'role_name' === column.dataIndex">
            <div>{{ text }}</div>
        </template>
        <template v-if="'operate' === column.dataIndex">
            <a v-if="loginInfo.permission_list.indexOf('user_permission.edit') > -1 && filterData.role_id != 3" @click="showEditAccount(record)">更换角色</a>
            <span v-else>-</span>
        </template>
      </template>
    </a-table>

    <a-modal :title="isBatchChange ? '批量更换角色' : '更换角色'"
      v-model:open="roleModal.visible"
      :confirm-loading="roleModal.saving"
      @ok="saveStaffRole"
      width="500px">
      <a-form>
        <a-form-item label="选择角色" style="margin-top: 24px;">
          <a-select
            v-model:value="roleModal.role"
            placeholder="请选择角色">
            <template v-for="role in roles">
              <a-select-option
                v-if="!['游客账号', '超级管理员'].includes(role.role_name)"
                :value="role.id"
                :key="role.id">
                {{ role.role_name }}
              </a-select-option>
            </template>
          </a-select>
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue';
import { useStore } from 'vuex';
import { SettingOutlined } from '@ant-design/icons-vue';
import SelectStaffBoxNormal from "@/components/common/select-staff-box-normal.vue";
import { roleList, roleUserList, changeRole } from "@/api/session";
import { message } from 'ant-design-vue';
import { useRouter } from 'vue-router';

const store = useStore()
const loginInfo = computed(() => {
    return store.getters.getUserInfo
})
const emit = defineEmits(['change', 'totalReport', 'updateList'])
const router = useRouter()
const tableData = ref([])
const loading = ref(false)
const finished = ref(false)
const isBatchChange = ref(false)
const selected = ref(null)

const filterData = reactive({
  role_name: '',
  keyword: '',
  role_id: '',
  permission_detail: []
})

const formState = reactive({
  staff_userid_list: [],
  staff_user_list: []
})

const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  pageSizeOptions: ['10', '20', '50', '100'],
  showQuickJumper: true,
  showSizeChanger: true,
})

const columns = [
  {
    title: '员工昵称',
    dataIndex: 'name',
  },
  {
    title: '角色',
    dataIndex: 'role_name',
  },
  {
    title: '操作',
    dataIndex: 'operate',
  }
]

const onSearch = () => {
  init()
  loadData()
}

const onSearchUser = (item) => {
  init()
  loadRoles()
  filterData.keyword = ''
  formState.staff_userid_list = []
  filterData.role_name = item.role_name
  filterData.role_id = item.id
  filterData.permission_detail = item.permission_detail
  loadData()
}

const onEditRole = () => {
  router.push({
    path: '/module/user_permission/config',
    query: {
      role_id: filterData.role_id
    }
  })
}

function staffChange(staffs) {
  formState.staff_userid_list = staffs.map(item => item.userid)
  formState.staff_user_list = staffs
  isBatchChange.value = true
  roleModal.role = filterData.role_id
  saveStaffRole()
}

const init = () => {
  pagination.current = 1
  pagination.total = 0
  finished.value = false
  loading.value = false
  tableData.value = []
}

const onSelect = (record, selected) => {
  if (selected) {
    formState.staff_userid_list.push(record.userid)
  } else {
    formState.staff_userid_list = formState.staff_userid_list.filter((item) => item !== record.userid)
  }
}

const onSelectAll = (selected, selectedRows) => {
  let ids = tableData.value.map((item) => item.userid)
  if (selected) {
    formState.staff_userid_list = [...new Set([...ids, ...formState.staff_userid_list])]
  } else {
    formState.staff_userid_list = formState.staff_userid_list.filter((item) => !ids.includes(item))
  }
}

const loadData = () => {
  if (loading.value) {
    return
  }
  loading.value = true
  let params = {
    page: pagination.current,
    size: pagination.pageSize
  }
  filterData.keyword = filterData.keyword.trim()
  if (filterData.keyword) {
    params.keyword = filterData.keyword
  }
  if (filterData.role_id) {
    params.role_id = filterData.role_id
  }
  loading.value = false
  finished.value = true

  roleUserList(params).then(res => {
    let data = res.data || {}
    let { items, total } = data
    total = Number(total)
    if (!items || !items?.length || tableData.value.length === total) {
      finished.value = true
    }
    tableData.value = items || []
    // let json = tableData.value.concat(items)
    // let newJson = []
    // for (let item1 of json) {
    //     let flag = true
    //     for (let item2 of newJson) {
    //         if (item1.userid == item2.userid) {
    //             flag = false
    //         }
    //     }
    //     if (flag) {
    //         newJson.push(item1)
    //     }
    // }
    // tableData.value = newJson
    pagination.total = total
    loading.value = false
    if (pagination.current == 1) {
      emit('totalReport', total)
      selectedHandle()
    }
  }).catch(() => {
    loading.value = false
    finished.value = true
  })
}

function tableChange(p) {
  pagination.current = p.current
  pagination.pageSize = p.pageSize
  loadData()
}

const roles = ref([])
const roleModal = reactive({
  visible: false,
  saving: false,
  record: null,
  role: null,
})

const loadRoles = () => {
  roleList().then(res => {
    roles.value = res.data || []
  })
}

const batchChangeRoles = () => {
  roleModal.role = void 0
  if (!formState.staff_userid_list.length) {
    return message.error('请选择员工')
  }
  if (!roles.value.length) {
    loadRoles()
  }
  isBatchChange.value = true
  roleModal.visible = true
  roleModal.role = roles.value[roles.value.length - 1].id
}

const showEditAccount = (record) => {
  roleModal.role = void 0
  if (!roles.value.length) {
    loadRoles()
  }
  isBatchChange.value = false
  roleModal.visible = true
  roleModal.record = record
  roleModal.role = roles.value[roles.value.length - 1].id
}

const saveStaffRole = () => {
  roleModal.saving = true
  let users = []
  if (isBatchChange.value) {
    users = formState.staff_userid_list
  } else {
    users.push(roleModal.record.userid)
  }
  changeRole({
    new_role_id: roleModal.role,
    staff_userid: users
  }).then(() => {
    loadData()
    message.success('操作完成')
    roleModal.visible = false
    emit('updateList')
  }).finally(() => {
    roleModal.saving = false
  })
}

const selectedHandle = () => {
  if (props.default && props.default?.chat_status === props.type && props.default.external_userid) {
    const find = tableData.value.find(item => item.external_userid === props.default.external_userid)
    if (find) {
      change(find)
    } else {
      tableData.value.unshift(props.default)
    }
  } else {
    change(tableData.value[0] || null)
  }
}

const isSelected = item => {
  return selected.value && selected.value.external_userid === item?.external_userid
}

const change = (item) => {
  if (item && isSelected(item)) {
    return
  }
  selected.value = item
  emit("change", item)
}

defineExpose({
  onSearchUser
})
</script>

<style scoped lang="less">
.role-title-box {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-family: "PingFang SC";
  font-style: normal;
  margin-bottom: 16px;

  .role-left {
    display: flex;
    font-family: "PingFang SC";
    align-items: center;
    justify-content: start;

    .role-title {
      color: #262626;
      font-size: 14px;
      font-weight: 600;
      line-height: 22px;
    }

    .role-line {
      width: 1px;
      height: 16px;
      border-radius: 1px;
      background: var(--06, #D8DDE5);
      margin: 0 8px;
    }

    .role-num {
      color: #2475fc;
      font-size: 14px;
      font-style: normal;
      font-weight: 400;
      line-height: 22px;
      cursor: pointer;
    }
  }
}

.role-options {
  display: flex;
  align-items: center;
  justify-content: space-between;

  .role-options-left {
    display: flex;
    align-items: center;
    gap: 8px;
  }
}

.staff-box {
  padding: 24px;
  height: 100%;
  overflow: auto;

    &::-webkit-scrollbar {
      width: 4px; /*  设置纵轴（y轴）轴滚动条 */
      height: 4px; /*  设置横轴（x轴）轴滚动条 */
    }
    /* 滚动条滑块（里面小方块） */
      &::-webkit-scrollbar-thumb {
      border-radius: 4px;
      background: transparent;
    }
    /* 滚动条轨道 */
      &::-webkit-scrollbar-track {
      border-radius: 4px;
      background: transparent;
    }

    /* hover时显色 */
    &:hover::-webkit-scrollbar-thumb {
      background: rgba(0, 0, 0, 0.2);
    }
    &:hover::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.1);
    }

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

    &:hover {
      background: var(--07, #E4E6EB);
    }

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

  }

  .active {
    background: var(--01-, #E5EFFF);

    .item-title,
    .item-num {
      color: #2475FC;
    }

    &:hover {
      background: var(--01-, #E5EFFF);
    }
  }
}
</style>
<style lang="less">
.role-tip-popover {
  width: 416px;
  box-sizing: border-box;

  .role-pop-title-box {
    display: flex;
    align-content: center;
    justify-content: space-between;

    .role-pop-title-left,.role-pop-title-right {
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      gap: 4px;
      border-radius: 6px;

      .role-pop-title-label {
        color: #262626;
        font-family: "PingFang SC";
        font-size: 14px;
        font-style: normal;
        font-weight: 600;
        line-height: 22px;
      }

      .icon {
        font-size: 15px;
      }

      .role-pop-title-text {
        font-family: "PingFang SC";
        font-size: 14px;
        font-style: normal;
        font-weight: 400;
        line-height: 22px;
      }
    }

    .role-pop-title-right {
      padding: 1px 8px;

      &:hover {
        color: #2475fc;
        background: var(--07, #E4E6EB);
      }
    }
  }

  .role-pop-title-info {
    color: #8c8c8c;
    font-family: "PingFang SC";
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 22px;
  }

  .role-pop-content {
    display: flex;
    flex-wrap: wrap;

    .role-pop-content-item {
      width: 190px;
      height: 30px;
      display: flex;
      align-items: center;
      gap: 4px;

      .icon {
        width: 16px;
        height: 16px;
      }

      .label {
        color: #595959;
        font-family: "PingFang SC";
        font-size: 14px;
        font-style: normal;
        font-weight: 400;
        line-height: 22px;
      }
    }
  }
}
</style>
