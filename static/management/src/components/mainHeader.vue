<template>
    <div class="_main-header" :style="style">
        <div class="logo-box">
            <img :src="company.logo || DEFAULT_ZH_LOGO" class="logo"/>
            <div class="system-name-box">
                <!-- <div class="default-system-name">芝麻会话存档</div> -->
                <!-- <div v-if="company.navigation_bar_title" class="system-line"></div> -->
                <div v-if="company.navigation_bar_title" class="default-system-name">{{ company.navigation_bar_title || '芝麻会话存档' }}</div>
                <div v-else class="default-system-name">芝麻会话存档</div>
            </div>
        </div>
        <div class="right-header-nav">
            <!-- <div v-if="showMenus" class="my-shadow"></div> -->
            <div class="header-main-content">
                <div v-if="showMenus" class="menus-box">
                    <!-- <div class="menu-item active">会话质检</div> -->
                </div>
                <div v-if="diskUsage" class="disk-usage" :class="diskUsageLevel">
                    <div class="disk-usage-info">
                        <div class="disk-usage-title">磁盘空间</div>
                        <div class="disk-usage-detail">
                            {{ formatBytes(diskUsage.used_bytes) }}/{{ formatBytes(diskUsage.total_bytes) }}
                            <span>剩余{{ formatBytes(diskUsage.free_bytes) }}</span>
                        </div>
                    </div>
                    <div class="disk-usage-main">
                        <div class="disk-usage-bar">
                            <div class="disk-usage-bar-value" :style="{width: `${diskUsage.usage_percent}%`}"></div>
                        </div>
                        <span class="disk-usage-percent">
                            {{ diskUsage.usage_percent }}%
                            <a-tooltip placement="bottom">
                                <template #title>
                                    <span>文件存储不足时，可设置存储到OSS中，</span>
                                    <a class="storage-setting-link" @click.stop.prevent="openStorageSettings">去设置</a>
                                </template>
                                <QuestionCircleOutlined class="disk-usage-help"/>
                            </a-tooltip>
                        </span>
                    </div>
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
import {computed, h, onMounted, ref} from 'vue';
import {useStore} from 'vuex';
import {Modal, message} from 'ant-design-vue';
import {DownOutlined, QuestionCircleOutlined} from '@ant-design/icons-vue';
import {logoutHandle} from "@/utils/tools";
import {getSettings} from "@/api/auth-login";
import {getDiskUsage} from "@/api/system";
import {DEFAULT_ZH_LOGO} from "@/constants";

const props = defineProps({
    background: String,
    showMenus: {
        type: Boolean,
        default: false
    },
})

const store = useStore()
const company = computed(() => store.getters.getCompany)
const loginInfo = computed(() => {
    return store.getters.getUserInfo
})
const diskUsage = ref(null)
const DISK_WARNING_CACHE_KEY = 'zm:session:archive:disk-usage-warning-date'

const diskUsageLevel = computed(() => {
    const freePercent = Number(diskUsage.value?.free_percent)
    if (freePercent <= 10) return 'critical'
    if (freePercent <= 20) return 'warning'
    return 'normal'
})

const formatBytes = (bytes) => {
    const value = Number(bytes)
    if (!Number.isFinite(value) || value < 0) return '--'
    const units = ['B', 'KB', 'MB', 'GB', 'TB']
    let size = value
    let unitIndex = 0
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024
        unitIndex++
    }
    const formatted = size >= 10 || unitIndex === 0 ? Math.round(size) : size.toFixed(1)
    return `${formatted}${units[unitIndex]}`
}

const openStorageSettings = () => {
    const url = `${window.location.origin}${window.location.pathname}#/systemctl/fileStorage`
    window.open(url, '_blank', 'noopener,noreferrer')
}

const getToday = () => {
    const now = new Date()
    const month = String(now.getMonth() + 1).padStart(2, '0')
    const day = String(now.getDate()).padStart(2, '0')
    return `${now.getFullYear()}-${month}-${day}`
}

const shouldShowDiskWarning = () => {
    try {
        return localStorage.getItem(DISK_WARNING_CACHE_KEY) !== getToday()
    } catch {
        return true
    }
}

const markDiskWarningShown = () => {
    try {
        localStorage.setItem(DISK_WARNING_CACHE_KEY, getToday())
    } catch {
        // 浏览器禁用缓存时仍允许本次提示显示
    }
}

const showDiskWarning = (usage) => {
    const freePercent = Number(usage?.free_percent)
    if (!Number.isFinite(freePercent) || freePercent >= 20 || !shouldShowDiskWarning()) return

    markDiskWarningShown()
    Modal.warning({
        title: '磁盘空间已满',
        content: h('div', {style: {lineHeight: '24px'}}, [
            h('div', [
                '当前磁盘空间仅剩',
                h('span', {style: {color: '#ff4d4f'}}, `${formatBytes(usage.free_bytes)}（${freePercent}%）`),
                h('span', {style: {color: '#ff4d4f'}}, '，避免消息存储失败'),
            ]),
            h('div', '请尽快处理。'),
        ]),
        okText: '知道了',
        centered: true,
    })
}

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

onMounted(() => {
  try {
    getSettings().then((res) => {
      if (res.status === 'success') {
        if (res.data) {
          store.commit('setCompany', res.data)
        } else {
          store.commit('setCompany', {
            title: '',
            logo: '',
            navigation_bar_title: '',
            login_page_title: '',
            login_page_description: '',
            copyright: ''
          })
        }
      }
    }).catch(() => {
      // 用默认的头像和企业信息
      store.commit('setCompany', {
        title: '',
        logo: '',
        navigation_bar_title: '',
        login_page_title: '',
        login_page_description: '',
        copyright: ''
      })
    })
  } catch {
    // 使用默认企业信息
  }

  getDiskUsage().then((res) => {
    const data = res?.data || null
    diskUsage.value = data
    showDiskWarning(data)
  }).catch(() => {
    diskUsage.value = null
  })
})
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
        align-items: center;
        max-width: 365px;
        min-width: 256px;
        flex-shrink: 0;
        height: 52px;
        padding: 20px;

        .logo {
            height: 32px;
            margin-right: 8px;
        }
    }

    .system-name-box {
        display: flex;
        align-items: center;
        font-family: "PingFang SC";
        font-style: normal;
    }

    .system-line {
        width: 1px;
        height: 16px;
        border-radius: 1px;
        background: #D9D9D9;
        margin: 0 8px;
    }

    .default-system-name {
        white-space: nowrap;
        color: #000000;
        font-size: 16px;
        font-weight: 600;
    }

    .system-name{
        white-space: nowrap;
        color: #262626;
        font-size: 14px;
        font-weight: 400;
        max-width: 230px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .right-header-nav {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-left: 24px;
        position: relative;

        .header-main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

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

        .disk-usage {
            display: flex;
            align-items: center;
            gap: 16px;
            width: min(360px, 34vw);
            min-width: 220px;
            margin: 0 24px 0 0;
            color: #595959;
            font-size: 11px;

            .disk-usage-info {
                flex: 0 0 auto;
            }

            .disk-usage-title {
                margin-bottom: 2px;
                font-size: 12px;
                color: #262626;
            }

            .disk-usage-main {
                flex: 1;
                min-width: 0;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .disk-usage-bar {
                flex: 1;
                min-width: 0;
                height: 8px;
                overflow: hidden;
                border-radius: 5px;
                background: #e8e8e8;
            }

            .disk-usage-bar-value {
                height: 100%;
                border-radius: 5px;
                background: #9bc56a;
                transition: width .2s ease;
            }

            .disk-usage-percent {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                white-space: nowrap;
                color: #595959;
            }

            .disk-usage-help {
                color: #8c8c8c;
                font-size: 13px;
                cursor: help;
            }

            .storage-setting-link {
                color: #2475fc;
                cursor: pointer;
            }

            .disk-usage-detail {
                display: flex;
                justify-content: space-between;
                gap: 8px;
                white-space: nowrap;
                color: #8c8c8c;
            }

            &.warning .disk-usage-bar-value {
                background: #faad14;
            }

            &.critical .disk-usage-bar-value {
                background: #ff4d4f;
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
