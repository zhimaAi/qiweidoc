<template>
    <div class="_main-left-block">
        <div class="_menu-box hide-scrollbar">
            <!--        <div class="search-menu-box">-->
            <!--            <a-input-search placeholder="请输入功能名称搜索"/>-->
            <!--        </div>-->
            <a-menu
                v-model:openKeys="state.openKeys"
                v-model:selectedKeys="state.selectedKeys"
                style="width: 256px"
                :mode="state.mode"
                :theme="state.theme"
            >
                <template v-for="menu in menus">
                    <a-sub-menu
                        v-if="menu.subs?.length > 0"
                        :key="menu.key"
                        :icon="menu.icon"
                        :title="menu.title">
                        <a-menu-item v-for="sub in menu.subs" :key="sub.key">
                            <router-link :to="{path: sub.route}">{{ sub.title }}</router-link>
                        </a-menu-item>
                        <!---插件菜单时--->
                        <template v-if="menu.key === 'FunctionCenter'">
                            <template v-for="plugin in lists">
                                <a-menu-item
                                    v-if="plugin.is_enabled && !plugin.is_expired"
                                    @click="pluginSwitch(plugin)"
                                    :key="`zm-plugin-${plugin.name}`">
                                    {{ plugin.title }}
                                </a-menu-item>
                            </template>
                        </template>
                    </a-sub-menu>
                    <a-menu-item
                        v-else
                        :key="menu.key"
                        :icon="menu.icon">
                        <router-link :to="{path: menu.route}">{{ menu.title }}</router-link>
                    </a-menu-item>
                </template>
            </a-menu>
        </div>
    </div>
</template>

<script setup>
import {h, reactive, ref, onMounted, computed} from 'vue';
import {useStore} from 'vuex';
import {useRoute, useRouter} from 'vue-router';
import {
    TeamOutlined,
    AppstoreOutlined,
    SettingOutlined,
    MessageOutlined
} from '@ant-design/icons-vue';
import {getModules} from "@/api/company";
import {getPluginRouteParams} from "@/utils/tools";

const store = useStore()
const route = useRoute();
const router = useRouter();
const loading = ref(false)
const state = reactive({
    mode: 'inline',
    theme: 'light',
    selectedKeys: [],
    openKeys: [
        'SessionQualityInspection',
        'FunctionCenter',
        'CustomerManagement',
        'GroupManagement',
        'companyManagement',
        'Systemctl',
        'SessionStatistics'
    ],
});

const userInfo = computed(() => {
    return store.getters.getUserInfo
})

const lists = computed(() => {
    return store.getters.getModules
})
const changeTheme = checked => {
    state.theme = checked ? 'dark' : 'light';
}

/**
 * key = route name
 * */
const menus = ref([
    {
        key: 'SessionQualityInspection',
        title: '会话质检',
        icon: h(MessageOutlined),
        subs: [
            {key: 'sessionArchiveHome', title: '会话质检', route: '/sessionArchive/index'},
            {key: 'sessionArchiveSearch', title: '会话搜索', route: '/sessionArchive/search'}
        ]
    },
    {
        key: 'FunctionCenter',
        title: '功能插件',
        icon: h(AppstoreOutlined),
        subs: [
            {key: 'plugManagementHome', title: '功能插件', route: '/plug/index'},
        ]
    },
    {
        key: 'CustomerManagement',
        title: '客户管理',
        icon: h(TeamOutlined),
        subs: [
            {key: 'customerManagementHome', title: '客户管理', route: '/customerManagement/index'},
            {key: 'customerManagementTag', title: '客户标签', route: '/customerManagement/tag'}
        ]
    },
    {
        key: 'companyManagement',
        title: '企业管理',
        icon: h(AppstoreOutlined),
        subs: [
            {key: 'companyManagementStaff', title: '员工管理', route: '/companyManagement/staff'},
            {key: 'groupManagementHome', title: '群管理', route: '/groupManagement/index'},
        ]
    },
    {
        key: 'Systemctl',
        title: '系统设置',
        icon: h(SettingOutlined),
        subs: [
            {key: 'systemctlAuthConfig', title: '配置信息', route: '/systemctl/authConfig'},
            // {key: 'systemctlFirm', title: '企业设置', route: '/systemctl/firm'},
            {key: 'systemctlFileStorage', title: '文件存储配置', route: '/systemctl/fileStorage'},
            {key: 'systemctlAccount', title: '账号管理', route: '/systemctl/account'},
            // {key: 'systemctlFileExport', title: '文件导出记录', route: '/systemctl/fileExport'},
        ]
    },
])

const loadData = () => {
    loading.value = true
    store.dispatch('updateModules').finally(() => {
        loading.value = false
    })
}

const pluginSwitch = plugin => {
    router.push(getPluginRouteParams(plugin))
}

onMounted(() => {
    // 普通员工不请求getModules接口
    if (userInfo.value && userInfo.value.role_id && userInfo.value.role_id != 1) {
        loadData()
    }
    const query = route.query
    // state.selectedKeys = [query._key || route?.meta?.selectNav || route.name]
    state.selectedKeys = [query._key || route.meta.activeMenuKey || route.name]
    switch (Number(userInfo.value?.role_id || 0)) {
        case 1:
            // 普通员工
            menus.value = [
                {
                    key: 'SessionQualityInspection',
                    title: '会话质检',
                    icon: h(MessageOutlined),
                    subs: [
                        {key: 'sessionArchiveHome', title: '会话质检', route: '/sessionArchive/index'},
                        {key: 'sessionArchiveSearch', title: '会话搜索', route: '/sessionArchive/search'}
                    ]
                },
                {
                    key: 'Systemctl',
                    title: '系统设置',
                    icon: h(SettingOutlined),
                    subs: [
                        {key: 'systemctlAccount', title: '账号管理', route: '/systemctl/account'},
                    ]
                },
            ]
            break
        case 4:
            // 游客不支持查看系统设置
            let index = menus.value.findIndex(i => i.key === 'Systemctl')
            menus.value.splice(index, 1)
            break
    }
})
</script>

<style scoped lang="less">
._main-left-block {
    flex-shrink: 0;
    width: 256px;
    background: #FFF;

    ._menu-box {
        position: fixed;
        width: 256px;
        height: calc(100vh - 52px);
        top: 52px;
        left: 0;
        overflow-y: auto;
        background: #FFF;
    }

    :deep(.ant-menu-root) {
        text-align: left;
        border: none;

        .ant-menu-item,
        .ant-menu-submenu-title {
            margin-inline: 12px;
            width: calc(100% - 24px);
            padding: 12px !important;
        }

        .ant-menu-sub .ant-menu-item {
            padding-left: 36px !important;
        }

        .ant-menu-item:not(.ant-menu-item-selected):hover,
        .ant-menu-submenu-title:not(.ant-menu-item-selected):hover {
            color: #2475fc;
            font-weight: 500;
        }

        .ant-menu-submenu-title .ant-menu-title-content,
        > .ant-menu-item .ant-menu-title-content {
            font-size: 16px;
            font-weight: 600;
            transition: none;
        }

        .ant-menu-submenu-title .ant-menu-item-icon,
        > .ant-menu-item .ant-menu-item-icon {
            transition: none;
        }
    }

    :deep(.ant-menu-light .ant-menu-item-selected ) {
        color: #FFF;
        background-color: #2475fc;
    }

    :deep(.ant-menu-light .ant-menu-item-selected a) {
        transition: none;
    }

    :deep(.ant-menu-inline .ant-menu-sub.ant-menu-inline) {
        background-color: #FFF;
    }

    .search-menu-box {
        padding: 12px;
    }

    :deep(.menus-hide) {
        display: none;
    }
}
</style>
