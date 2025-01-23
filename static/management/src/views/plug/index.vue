<template>
    <MainLayout title="功能插件">
        <div class="zm-main-content func-plug-box">
            <a-alert show-icon message="插件启用后,即可到功能中心去使用新的功能,若不需要使用,禁用即可"></a-alert>
            <div class="plug-box">
                <template v-for="(item, index) in lists">
                    <!--系统禁用插件不展示（除已安装的）-->
                    <div v-if="item.system_enabled || item.is_install" class="plug-item" :key="index">
                        <div class="plug-item-top">
                            <img class="customer-label" :src="item.icon">
                            <a-tooltip
                                :title="!item.is_install ? '需安装后才可启用' : item.is_expired ? '需购买后才可启用' : null">
                                <a-switch
                                    v-model:checked="item.enable_bool"
                                    :disabled="!item.is_install || item.is_expired"
                                    @change="statusChange(item)"
                                    checked-children="已启用"
                                    un-checked-children="已禁用"/>
                            </a-tooltip>
                        </div>
                        <div class="plug-item-midden">
                            <div class="midden-title zm-flex-center">
                                <span>{{ item.title }}</span>
                                <span v-if="item?.local_version" class="version-tag ml4">v{{ item?.local_version }}</span>
                            </div>
                            <div class="midden-info" @click="onGoInfo(item)">
                                详情
                                <RightOutlined class="f12 ml4"/>
                            </div>
                        </div>
                        <div v-if="!item.is_install" class="zm-tip-info mt4">未安装此应用</div>
                        <div v-else-if="item.price_type > 1" class="zm-tip-info mt4">
                            有效期至 {{ item.expire_date }}
                            <span v-if="item.is_expired" class="expired-tag ml4">已到期</span>
                        </div>
                        <div class="zm-line-clamp2 plugin-desc mt8">
                            <a-tooltip :title="item?.intro?.length > 40 ? item.intro : null">{{ item.intro }}</a-tooltip>
                        </div>
                        <div class="plug-item-bottom">
                            <div v-if="!item.is_install && !item.is_compatible_main" class="version-warn">
                                该插件不兼容您当前使用的系统版本
                                <a class="ml4" href="https://github.com/zhimaAi/qiweidoc" target="_blank">去更新</a>
                            </div>
                            <div class="zm-flex-between mt8">
                                <div class="price">{{ item.price_info }}</div>
                                <div class="btn-box">
                                    <template v-if="item.is_install">
                                        <a-button v-if="item.price_type > 1" @click="goPay(item)">联系客服</a-button>
                                        <a-popover
                                            v-if="!item.is_last_version"
                                            :getPopupContainer="triggerNode => triggerNode"
                                            placement="bottom"
                                            overlayClassName="update-desc-popover">
                                            <template #content>
                                                <div class="title">{{item.title}}</div>
                                                <div class="last-version-tag">{{item.latest_version.version}}</div>
                                                <div class="update-desc" v-html="item.latest_version.upgrade_description.replace(/\r?\n/g, '<br/>')"></div>
                                            </template>
                                            <a-button type="primary" ghost class="ml8" @click="install(item, true)" :loading="item.installing">更新</a-button>
                                        </a-popover>
                                    </template>
                                    <a-button v-else
                                              @click="install(item)"
                                              :loading="item.installing"
                                              :disabled="!item.is_compatible_main">
                                        {{ item.price_type > 1 ? '安装试用7天' : '安 装' }}
                                    </a-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <ContactKefu ref="kefuRef"/>
    </MainLayout>
</template>

<script setup>
import {onMounted, ref, computed} from 'vue';
import {useStore} from 'vuex';
import {useRouter} from 'vue-router';
import dayjs from 'dayjs';
import MainLayout from "@/components/mainLayout.vue";
import {Modal, message} from 'ant-design-vue'
import {RightOutlined} from '@ant-design/icons-vue'
import {getModules, enableModules, disableModules, installModule} from "@/api/company";
import defaultImg from '@/assets/customer-label.png'
import defaultSensitiveWordsImg from '@/assets/sensitive-words.png'
import ContactKefu from "@/components/common/contact-kefu.vue";

const store = useStore()
const router = useRouter()
const kefuRef = ref(null)
const loading = ref(false)
const modules = computed(() => {
    return store.getters.getModules
})
const lists = computed(() => {
    let _modules = modules.value || []
    if (Array.isArray(_modules)) {
        const nowTime = dayjs().unix()
        _modules.map(m => {
            m.installing = false
            m.enable_bool = m.is_enabled
            if (m.is_expired) {
                m.enable_bool = false
            }
        })
        return _modules
    }
    return []
})

const statusChange = (item) => {
    let key = item.enable_bool ? '启用' : '禁用'
    const cancel = () => {
        item.enable_bool = !item.enable_bool
    }
    Modal.confirm({
        title: `确认${key}该插件`,
        content: item.enable_bool ? `启用后，可到功能插件-${item.title}使用该功能` : '禁用后，功能不可再使用，禁用后可重新启用',
        okText: '确定',
        cancelText: '取消',
        onOk: () => {
            const loadClose = message.loading(`正在${key}`)
            let apiUrl = item.enable_bool ? enableModules : disableModules
            apiUrl({
                name: item.name
            }).then(() => {
                message.success('操作完成')
                store.dispatch('updateModules')
            }).finally(() => {
                loadClose()
            }).catch(() => cancel())
        },
        onCancel: cancel
    })
}

const onGoInfo = (item) => {
    router.push({
        path: '/plug/info',
        query: {
            name: item.name
        }
    })
}

const install = (item, update = false) => {
    if (update && !item.is_compatible_main) {
        Modal.confirm({
            title: '提示',
            content: '新版插件不兼容您当前使用的系统版本，请先更新系统！',
            okText: '去更新',
            onOk: () => {
                window.open('https://github.com/zhimaAi/qiweidoc')
            }
        })
        return false
    }
    item.installing = true
    installModule({
        name: item.name
    }).then(res => {
        store.dispatch('updateModules')
        message.success(update ? '已更新' : '已安装')
    }).finally(() => {
        item.installing = false
    })
}

const goPay = item => {
    kefuRef.value.show()
    // let href = router.resolve({
    //     path: '/plug/shopping',
    //     query: {
    //         module: item.name
    //     }
    // }).href
    // window.open(href)
}
</script>

<style scoped lang="less">
.func-plug-box {
    min-height: calc(100vh - 128px);

    :deep(.update-desc-popover.ant-popover) {
        .ant-popover-content {
            width: 300px;
            background: #FFF;
            background-image: url("@/assets/image/plugin/update-desc-bg.png");
            background-repeat: no-repeat;
            background-size: 100%;
            background-position: 0 -25px;
            border-radius: 4px;

            .ant-popover-inner {
                background: unset;
                padding: 16px;
            }

            .title {
                color: #242933;
                font-size: 16px;
                font-weight: 600;
            }
            .last-version-tag {
                display: inline-block;
                padding: 1px 8px;
                gap: 10px;
                border-radius: 8px;
                background: linear-gradient(270deg, #659DFC 0%, #66D1FF 100%);
                color: #ffffff;
                font-size: 12px;
                font-weight: 400;
                margin: 6px 0 16px;
            }
            .update-desc {
                color: #595959;
                font-size: 14px;
                font-weight: 400;
            }
        }
    }

    .plug-box {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-top: 16px;

        .plug-item {
            width: 358px;
            height: 282px;
            border-radius: 6px;
            border: 1px solid var(--06, #D9D9D9);
            background: #FFF;
            padding: 24px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            position: relative;

            &:hover {
                box-shadow: 0 2px 4px 1px rgba(0, 0, 0, 0.12);
            }

            .plug-item-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 12px;

                .customer-label {
                    width: 40px;
                    height: 40px;
                }

                .status-box {
                    display: inline-flex;
                    padding: 0 6px;
                    align-items: center;
                    gap: 2px;
                    border-radius: 6px;
                    background: #E8FCF3;
                    height: 22px;
                    color: #21a665;
                    font-family: "PingFang SC";
                    font-size: 14px;
                    font-style: normal;
                    font-weight: 500;
                    line-height: 22px;

                    .enable {
                        width: 16px;
                        height: 16px;
                    }
                }

                .not-status-box {
                    background: #EDEFF2;
                    color: #3a4559;
                }
            }

            .plug-item-midden {
                display: flex;
                align-items: center;

                .midden-title {
                    margin-right: 16px;
                    color: #000000;
                    font-size: 16px;
                    font-style: normal;
                    font-weight: 600;
                    line-height: 24px;
                }

                .midden-info {
                    display: inline-flex;
                    cursor: pointer;
                    color: #8c8c8c;
                    font-size: 14px;
                    font-style: normal;
                    font-weight: 400;


                    &:hover {
                        color: #1677ff;
                    }
                }
            }

            .plugin-desc {
                color: #595959;
                line-height: 22px;
            }

            .plug-item-bottom {
                position: absolute;
                bottom: 24px;
                left: 0;
                width: 100%;
                padding: 0 24px;

                .price {
                    color: #fa541c;
                    font-size: 16px;
                    font-weight: 600;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .btn-box {
                    white-space: nowrap;
                }
            }
        }
    }
}

.f12 {
    font-size: 12px;
}

.version-tag {
    padding: 0 4px;
    border-radius: 4px;
    background: #595959;
    color: #ffffff;
    font-size: 12px;
    font-weight: 400;
    height: 17px;
    line-height: 17px;
}

.version-warn {
    padding: 5px 16px;
    border-radius: 6px;
    background: #FBE7E8;
    color: #fb363f;
    font-size: 14px;
    font-weight: 400;
    margin-top: 8px;
}

.expired-tag {
    color: #fa541c;
    font-weight: 600;
}
</style>
