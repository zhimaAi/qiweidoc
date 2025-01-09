<template>
    <MainLayout>
        <template #navbar>
            <ZmNavTabs :tabs="StaffAccountsTabs" active="staff"/>
        </template>
        <div class="container">
            <div class="left-block">
                <div class="header-box">
                    <div class="operate-box">
                        <a-button type="primary" :loading="syncing" :icon="h(SyncOutlined)" @click="syncStaff">
                            同步员工和部门
                        </a-button>
                    </div>
                    <div class="zm-flex-between mt8">
                        <div>总员工数：<strong>{{ pagination.total }}</strong></div>
                        <div class="zm-filter-box">
                            <!--                            <div class="zm-filter-item">-->
                            <!--                                <div class="zm-filter-label">授权状态：</div>-->
                            <!--                                <a-select-->
                            <!--                                    style="width: 90px;" v-model:value="filterData.auth_status" @change="search"-->
                            <!--                                >-->
                            <!--                                    <a-select-option-->
                            <!--                                        v-for="item in authStatus"-->
                            <!--                                        :key="item.value"-->
                            <!--                                        :value="item.value">{{ item.title }}-->
                            <!--                                    </a-select-option>-->
                            <!--                                </a-select>-->
                            <!--                            </div>-->
                            <div class="zm-filter-item">
                                <div class="zm-filter-label">搜索员工：</div>
                                <a-input-search
                                    style="width: 200px;" placeholder="请输入昵称或别名搜索"
                                    v-model:value="filterData.keyword"
                                    allowClear
                                    @search="search"
                                ></a-input-search>
                            </div>
                        </div>
                    </div>
                </div>
                <a-table
                    class="mt16"
                    :loading="loading"
                    :data-source="list"
                    :columns="columns"
                    :pagination="pagination"
                    @change="tableChange"
                >
                    <template #bodyCell="{ column, text, record }">
                        <template v-if="'name' === column.dataIndex">
                            <div class="zm-user-info">
                                <img class="avatar" src="@/assets/default-avatar.png"/>
                                <span class="name">{{ record.name }}</span>
                            </div>
                        </template>
                        <!-- <template v-else-if="'role_name' === column.dataIndex">
                            {{ text }}
                            <a v-if="showEditRole(record)" @click="showStaffRole(record)" class="ml8">修改</a>
                        </template> -->
                        <!-- <template v-else-if="'login_perm' === column.dataIndex">
                            <a-switch
                                v-if="record.role_id != 3"
                                v-model:checked="record.login_perm"
                                @change="loginPermChange(record)"
                                checked-children="开"
                                un-checked-children="关"/>
                        </template> -->
                        <template v-else-if="'tag_name' === column.dataIndex">
                            <a-popover v-if="text?.length > 3">
                                <template #content>
                                    <div v-for="(name, i) in text" :key="i">{{ name }}</div>
                                </template>
                                <a-tag v-for="(name, i) in text.slice(0, 3)" :key="i">{{ name }}</a-tag>
                                <span>+{{ text.length - 3 }}</span>
                            </a-popover>
                            <template v-else>
                                <a-tag v-for="(name, i) in text" :key="i">{{ name }}</a-tag>
                            </template>
                        </template>
                    </template>
                </a-table>
            </div>
            <div class="right-block ml16">
                <a-tabs v-model:activeKey="activeKey">
                    <a-tab-pane :key="1" tab="组织架构">
                        <StaffDepartmentStruct @change="departmentChange"/>
                    </a-tab-pane>
                </a-tabs>
            </div>
        </div>

        <a-modal title="修改角色"
                 v-model:open="roleModal.visible"
                 :confirm-loading="roleModal.saving"
                 @ok="saveStaffRole"
                 width="500px">
            <a-form>
                <a-form-item label="选择角色">
                    <a-select
                        v-model:value="roleModal.role"
                        placeholder="请选择角色">
                        <template v-for="role in roles">
                            <a-select-option
                                v-if="!['游客账号', '超级管理员'].includes(role.role_name)"
                                :key="role.id">
                                {{ role.role_name }}
                            </a-select-option>
                        </template>
                    </a-select>
                </a-form-item>
            </a-form>
        </a-modal>
    </MainLayout>
</template>

<script setup>
import {onMounted, ref, reactive, h, computed} from 'vue';
import {useStore} from 'vuex';
import dayjs from 'dayjs';
import {message, Modal} from 'ant-design-vue';
import {SyncOutlined} from '@ant-design/icons-vue';
import MainLayout from "@/components/mainLayout.vue";
import {roleList, staffList, staffLoginPermChange, staffRoleChange, syncDepartmentStaff} from "@/api/company";
import StaffDepartmentStruct from "@/components/company/staffDepartmentStruct.vue";
import {set, get} from "@/utils/cache";
import ZmNavTabs from "@/components/zmNavTabs.vue";
import {StaffAccountsTabs} from "@/views/companyManagement/const";

const store = useStore()
const columns = [
    {
        dataIndex: 'name',
        title: "员工",
        width: 150,
    },
    // {
    //     dataIndex: "auth_status",
    //     width: 95,
    //     title: "授权状态",
    // },
    {
        dataIndex: "cst_total",
        width: 90,
        title: "客户总数",
    },
    {
        dataIndex: "department_name",
        width: 140,
        title: "所属部门",
    },
    {
        dataIndex: "tag_name",
        width: 135,
        title: "员工标签",
    },
    {
        dataIndex: "role_name",
        width: 135,
        title: "角色",
    },
    // {
    //     dataIndex: "login_perm",
    //     width: 135,
    //     title: "登录",
    // },
]
const loading = ref(false)
const syncing = ref(false)
const activeKey = ref(1)
const list = ref([])
const roles = ref([])
const pagination = reactive({
    total: 0,
    current: 1,
    pageSize: 10,
    showSizeChanger: true,
    pageSizeOptions: ['10', '20', '50', '100'],
})
const filterData = reactive({
    keyword: '',
    auth_status: '',
    department_id: '',
})
const roleModal = reactive({
    visible: false,
    saving: false,
    record: null,
    role: null,
})

const userInfo = computed(() => {
    return store.getters.getUserInfo
})

const administrator = [2, 3]

onMounted(() => {
    const tabIndex = StaffAccountsTabs.map(item => item.key).indexOf('accounts')
    if (administrator.indexOf(userInfo.value.role_id) === -1 && tabIndex !== -1) {
        StaffAccountsTabs.splice(tabIndex , 1)
    }
    autoSync()
    loadData()
})

const autoSync = () => {
    if (get('zm:sync:staff:last:day') != dayjs().format('YYYYMMDD')) {
        syncStaff()
        set('zm:sync:staff:last:day', dayjs().format('YYYYMMDD'))
    }
}

const syncStaff = () => {
    const loading = message.loading('正在同步')
    syncing.value = true
    syncDepartmentStaff().then(res => {
        message.success('同步完成')
    }).finally(() => {
        loading()
        syncing.value = false
    })
}

const search = () => {
    list.value = []
    pagination.total = 1
    pagination.current = 1
    loadData()
}

const loadData = () => {
    loading.value = true
    let params = {
        page: pagination.current,
        size: pagination.pageSize
    }
    filterData.keyword = filterData.keyword.trim()
    if (filterData.keyword) {
        params.keyword = filterData.keyword
    }
    if (filterData.department_id) {
        params.department_id = filterData.department_id
    }
    staffList(params).then(res => {
        let items = res.data.items || []
        items.map(item => {
            // item.login_perm = (item?.can_login == 1)
            item.role_name = item?.role_info?.role_name
        })
        list.value = items
        pagination.total = Number(res.data.total)
    }).finally(() => {
        loading.value = false
    })
}

const tableChange = p => {
    pagination.current = p.current
    pagination.pageSize = p.pageSize
    loadData()
}

const departmentChange = select => {
    let key = select[0] || null
    if (key) {
        let ids = key.split('-')
        filterData.department_id = ids.slice(-1)[0]
    }
    search()
}

const loadRoles = () => {
    roleList().then(res => {
        roles.value = res.data || []
    })
}

// const loginPermChange = record => {
//     let status = record.login_perm ? '开启' : '关闭'
//     const cancel = () => {
//         record.login_perm = !record.login_perm
//     }
//     Modal.confirm({
//         title: '提示',
//         content: `确认${status}该员工登录权限？`,
//         onOk: () => {
//             staffLoginPermChange({
//                 id: record.id,
//                 can_login: Number(record.login_perm)
//             }).then(() => {
//                 message.success(`已${status}`)
//             }).catch(() => {
//                 cancel()
//             })
//         },
//         onCancel: () => {
//             cancel()
//         }
//     })
// }

const showStaffRole = record => {
    if (!roles.value.length) {
        loadRoles()
    }
    roleModal.visible = true
    roleModal.record = record
    roleModal.role = record.role_id
}

const saveStaffRole = () => {
    roleModal.saving = true
    staffRoleChange({
        id: roleModal.record.id,
        role_id: roleModal.role
    }).then(() => {
        loadData()
        message.success('操作完成')
        roleModal.visible = false
    }).finally(() => {
        roleModal.saving = false
    })
}

// const showEditRole = record => {
//     // 超级管理员拥有此权限
//     // 且超级管理员不能被修改
//     return userInfo.value?.role_id == 3 && record.role_id != 3
// }
</script>

<style scoped lang="less">
.container {
    display: flex;
    margin: 12px;

    > div {
        background: #FFF;
        border-radius: 2px;
        overflow-y: scroll;
        height: calc(100vh - 130px);
    }

    .left-block {
        padding: 12px;
        flex: 1;
    }

    .right-block {
        flex-shrink: 0;
        width: 16vw;
        max-width: 300px;

        :deep(.ant-tabs) {
            .ant-tabs-tab {
                margin: 0 12px;
            }

            .ant-tabs-content {
                padding: 0 12px;
            }
        }
    }
}
</style>
