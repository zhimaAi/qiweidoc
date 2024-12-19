<template>
    <div class="_main-header" :style="style">
        <div class="logo-box">
            <img src="@/assets/logo.png" class="logo"/>
        </div>
        <div class="right-header-nav">
            <div v-if="showMenus" class="my-shadow"></div>
            <div>
                <div v-if="showMenus" class="menus-box">
                    <div class="menu-item active">会话质检</div>
                </div>
            </div>
            <a-dropdown v-if="loginInfo.id > 0">
                <div class="user-info-box">
                    <img src="@/assets/default-avatar.png" class="avatar"/>
                    <span class="ml4">{{ loginInfo.account || loginInfo.userid }}</span>
                    <DownOutlined class="ml4"/>
                </div>
                <template #overlay>
                    <a-menu>
                        <a-menu-item></a-menu-item>
                        <a-menu-item>
                            <div class="text-center" @click="logout">
                               <a>退出登录</a>
                            </div>
                        </a-menu-item>
                        <a-menu-item></a-menu-item>
                    </a-menu>
                </template>
            </a-dropdown>
        </div>
    </div>
</template>

<script setup>
import {computed} from 'vue';
import {useStore} from 'vuex';
import {Modal, message} from 'ant-design-vue';
import {DownOutlined} from '@ant-design/icons-vue';
import {logoutHandle} from "@/utils/tools";

const props = defineProps({
    background: String,
    showMenus: {
        type: Boolean,
        default: false
    },
})

const store = useStore()
const loginInfo = computed(() => {
    return store.getters.getUserInfo
})

const style = computed(() => {
    return {
        background: props.background,
    }
})

const logout = () => {
    Modal.confirm({
        title: '提示',
        content: '确认退出当前登录账户？',
        onOk: () => {
            message.loading('正在退出...')
            setTimeout(() => {
                message.destroy()
                logoutHandle()
            }, 1000)
        }
    })
}
</script>

<style scoped lang="less">
._main-header {
    display: flex;
    align-items: center;
    position: fixed;
    z-index: 99;
    top: 0;
    left: 0;
    width: 100%;
    background: #E6EFFF;
    box-shadow: 0 2px 4px 1px rgba(0, 0, 0, 0.12);

    .logo-box {
        display: flex;
        width: 256px;
        flex-shrink: 0;

        .logo {
            height: 52px;
        }
    }

    .right-header-nav {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-left: 24px;
        position: relative;

        .my-shadow {
            position: absolute;
            width: 3px;
            height: 52px;
            left: -4px;
            box-shadow: 5px 0 10px rgba(0, 0, 0, 0.3);
            background: #f6f7fb;
            top: -10px;
        }

        .menus-box {
            display: flex;
            align-items: center;

            .menu-item {
                width: 98px;
                height: 36px;
                margin-right: 25px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                font-size: 16px;
                font-weight: 400;
                color: rgba(0, 0, 0, 0.85);
                border-radius: 4px;

                &.active {
                    background: rgba(36, 117, 252, 0.2);
                    color: #2475fc;
                    font-weight: 600;
                }

                &:not(.active):hover {
                    background: rgba(0, 0, 0, 0.04);
                    color: #2475fc;
                }
            }
        }

        .user-info-box {
            display: flex;
            align-items: center;
            cursor: pointer;
            color: #595959;
            font-size: 14px;
            font-weight: 400;
            margin: 0 16px;

            .avatar {
                width: 24px;
                height: 24px;
                border-radius: 4px;
            }
        }
    }
}
</style>
