<template>
    <div class="_main-left-block">
        <div class="_menu-box">
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
                        <a-menu-item
                            v-for="sub in menu.subs"
                            :key="sub.key">
                            <router-link :to="{path: sub.route}">{{ sub.title }}</router-link>
                        </a-menu-item>
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
import {h, reactive, onMounted} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import {
    TeamOutlined,
    AppstoreOutlined,
    SettingOutlined,
    MessageOutlined
} from '@ant-design/icons-vue';

const route = useRoute();
const router = useRouter();
const state = reactive({
    mode: 'inline',
    theme: 'light',
    selectedKeys: [],
    openKeys: [
        'SessionQualityInspection',
        'CustomerManagement',
        'GroupManagement',
        'companyManagement',
        'Systemctl'
    ],
});

onMounted(() => {
    const query = route.query
    state.selectedKeys = [query._key || route.name]
})

const changeTheme = checked => {
    state.theme = checked ? 'dark' : 'light';
}

const menus = [
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
        key: 'CustomerManagement',
        title: '客户管理',
        icon: h(TeamOutlined),
        subs: [
            { key: 'customerManagementHome', title: '客户管理', route: '/customerManagement/index' }
            // ,
            // {key: 'customerManagementTag', title: '客户标签', route: '/customerManagement/tag'}
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
            // {key: 'systemctlAccount', title: '账号管理', route: '/systemctl/account'},
            // {key: 'systemctlFileExport', title: '文件导出记录', route: '/systemctl/fileExport'},
        ]
    },
]
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
        }
    }

    :deep(.ant-menu-light .ant-menu-item-selected ) {
        color: #FFF;
        background-color: #2475fc;
    }

    :deep(.ant-menu-inline .ant-menu-sub.ant-menu-inline) {
        background-color: #FFF;
    }

    .search-menu-box {
        padding: 12px;
    }
}
</style>
