<template>
    <MainLayout>
        <template #navbar>
            <ZmNavTabs :tabs="StaffAccountsTabs" active="accounts"/>
        </template>
        <div class="zm-main-content">
            <a-alert type="info" show-icon
                     message="设置关联账号，可用于登录系统，支持设置有效期，账号删除后即失效，不再支持登录"></a-alert>
            <div class="mt16">
                <a-button type="primary" :icon="h(PlusOutlined)" @click="showAddAccount">新增账号</a-button>
            </div>
            <a-table
                class="mt16"
                :loading="loading"
                :data-source="list"
                :columns="columns"
                :pagination="pagination"
                @change="tableChange"
            >
                <template #bodyCell="{column, text,record}">
                    <template v-if="'login_perm' === column.dataIndex">
                        <a-switch v-model:checked="record.login_perm"
                                  @change="loginPermChange(record)"
                                  checked-children="开"
                                  un-checked-children="关"/>
                    </template>
                    <template v-else-if="'operate' === column.dataIndex">
                        <a @click="showEditAccount(record)">编辑</a>
                        <a @click="delAccount(record)" class="ml8">删除</a>
                    </template>
                </template>
            </a-table>
        </div>

        <a-modal v-model:open="storeModal.visible"
                 title="添加账号"
                 :confirm-loading="storeModal.saving"
                 @ok="saveAccount"
                 width="500px">
            <a-form>
                <a-form-item label="登录账号">
                    <a-input
                        v-model:value="storeModal.formState.account"
                        :maxlength="32"
                        placeholder="请输入登录账号"
                        allow-clear/>
                </a-form-item>
                <template v-if="!storeModal.record">
                    <a-form-item label="登录密码">
                        <a-input-password
                            v-model:value="storeModal.formState.password"
                            :maxlength="32"
                            placeholder="请输入登录密码"
                            allow-clear/>
                    </a-form-item>
                    <a-form-item label="确认密码">
                        <a-input-password
                            v-model:value="storeModal.formState.verify_password"
                            :maxlength="32"
                            placeholder="请确认登录密码"
                            allow-clear/>
                    </a-form-item>
                </template>
                <a-form-item label="过期时间">
                    <a-radio-group v-model:value="storeModal.formState.exp_type">
                        <a-radio :value="0">永久有效</a-radio>
                        <a-radio :value="1">指定时间</a-radio>
                    </a-radio-group>
                    <div v-if="storeModal.formState.exp_type === 1" class="mt8">
                        <a-date-picker
                            v-model:value="storeModal.formState.exp_time"
                            :disabled-date="disabledDate"
                            :show-time="{format: 'HH:mm'}"
                            value-format="YYYY-MM-DD HH:mm"
                            format="YYYY-MM-DD HH:mm"/>
                    </div>
                </a-form-item>
                <a-form-item label="账号备注">
                    <a-textarea v-model:value="storeModal.formState.description"
                                placeholder="请输入备注"
                                allow-clear
                                :autoSize="{minRows: 3,maxRows: 3}"/>
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
import {PlusOutlined} from '@ant-design/icons-vue';
import MainLayout from "@/components/mainLayout.vue";
import ZmNavTabs from "@/components/zmNavTabs.vue";
import {StaffAccountsTabs} from "@/views/companyManagement/const";
import {delLoginAccount, loginAccountList, permLoginAccount, saveLoginAccount} from "@/api/company";
import {copyObj} from "@/utils/tools";

const store = useStore()
const loading = ref(false)
const inputAccount = ref(false)
const inputPassword = ref(false)
const list = ref([])
const columns = [
    {
        title: '账号',
        dataIndex: 'account'
    },
    {
        title: '备注',
        dataIndex: 'description'
    },
    {
        title: '状态',
        dataIndex: 'exp_status_text'
    },
    {
        title: '角色',
        dataIndex: 'role_name'
    },
    {
        title: '登录',
        dataIndex: 'login_perm'
    },
    {
        title: '过期时间',
        dataIndex: 'exp_time_text'
    },
    {
        title: '操作',
        dataIndex: 'operate',
        width: 120
    },
]
const pagination = reactive({
    current: 1,
    pageSize: 10,
    total: 0,
    pageSizeOptions: ['10', '20', '50', '100'],
    showQuickJumper: true,
    showSizeChanger: true,
})

const storeModal = reactive({
    visible: false,
    saving: false,
    record: null,
    formState: {
        account: '',
        password: '',
        verify_password: '',
        exp_type: 0,
        exp_time: '',
        description: '',
    }
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
    init()
})

function init() {
    loadData()
}

function loadData() {
    loading.value = true
    loginAccountList({
        page: pagination.current,
        size: pagination.pageSize
    }).then(res => {
        let items = res.data.items || []
        const time = dayjs().unix()
        items.map(item => {
            if (item.exp_time > -1) {
                item.exp_time_text = dayjs(item.exp_time * 1000).format('YY-MM-DD HH:mm')
                item.exp_status_text = item.exp_time > time ? '生效中' : '已过期'
            } else {
                item.exp_time_text = '永久有效'
                item.exp_status_text = '生效中'
            }
            item.login_perm = (item.can_login == 1)
            item.role_name = item?.role_info?.role_name
        })
        list.value = items
        pagination.total = Number(res.data.total)
    }).finally(() => {
        loading.value = false
    })
}

function loginPermChange(record) {
    let status = record.login_perm ? '开启' : '关闭'
    const cancel = () => {
        record.login_perm = !record.login_perm
    }
    Modal.confirm({
        title: '提示',
        content: `确认${status}该账号登录权限？`,
        onOk: () => {
            permLoginAccount({
                id: record.id,
                can_login: Number(record.login_perm)
            }).then(() => {
                message.success(`已${status}`)
            }).catch(() => {
                cancel()
            })
        },
        onCancel: () => {
            cancel()
        }
    })
}

function delAccount(record) {
    Modal.confirm({
        title: '提示',
        content: '确认删除该账号？',
        onOk: () => {
            delLoginAccount({
                id: record.id
            }).then(() => {
                message.success('已删除')
                loadData()
            })
        }
    })
}

function disabledDate(current) {
    return current && current < dayjs().startOf('day')
}

function tableChange(p) {
    pagination.current = p.current
    pagination.pageSize = p.pageSize
    loadData()
}

function showAddAccount() {
    storeModal.visible = true
    storeModal.record = null
    for (let key in storeModal.formState) {
        if (typeof storeModal.formState[key] === 'string') {
            storeModal.formState[key] = ''
        }
    }
    storeModal.formState.exp_type = 0
}

function showEditAccount(record) {
    storeModal.visible = true
    storeModal.record = record
    for (let key in storeModal.formState) {
        if (typeof storeModal.formState[key] === 'string') {
            storeModal.formState[key] = record[key] || ''
        }
    }
    storeModal.formState.verify_password = record.password
    if (record.exp_time > -1) {
        storeModal.formState.exp_type = 1
        storeModal.formState.exp_time = dayjs(record.exp_time * 1000).format('YYYY-MM-DD HH:mm')
    } else {
        storeModal.formState.exp_type = 0
        storeModal.formState.exp_time = ''
    }
}

function saveAccount() {
    try {
        storeModal.saving = true
        for (let key in storeModal.formState) {
            if (typeof storeModal.formState[key] === 'string') {
                storeModal.formState[key] = storeModal.formState[key].trim()
            }
        }
        if (!storeModal.formState.account) {
            throw '请输入登录账号'
        }
        if (storeModal.formState.account.length < 6) {
            throw '登录账号至少包含6位字符'
        }
        if (!storeModal.record) {
            if (!storeModal.formState.password) {
                throw '请输入登录密码'
            }
            if (storeModal.formState.password.length < 6) {
                throw '登录密码至少包含6位字符'
            }
            if (!storeModal.formState.verify_password) {
                throw '请确认登录密码'
            }
            if (storeModal.formState.verify_password !== storeModal.formState.password) {
                throw '确认密码不一致'
            }
        }
        if (storeModal.formState.exp_type === 1 && !storeModal.formState.exp_time) {
            throw '请选择过期时间'
        }
        const params = copyObj(storeModal.formState)
        if (storeModal.formState.exp_type === 1) {
            params.exp_time = dayjs(params.exp_time).unix()
        } else {
            params.exp_time = -1
        }
        if (storeModal.record) {
            params.id = storeModal.record.id
            delete params.password
            delete params.verify_password
        }
        saveLoginAccount(params).then(res => {
            message.success('已保存')
            storeModal.visible = false
            loadData()
        }).finally(() => {
            storeModal.saving = false
        })
    } catch (e) {
        message.error(e)
        storeModal.saving = false
    }
}
</script>

<style scoped lang="less">
.zm-main-content {
    min-height: 86vh;
}
</style>
