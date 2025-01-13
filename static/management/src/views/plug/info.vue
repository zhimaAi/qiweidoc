<template>
    <MainLayout :title="[
       {name: '功能插件', route: '/plug/index'},
       {name: `${detailData.title || ''}详情`}
  ]">
        <div class="info-box">
            <LoadingBox v-if="loading"/>
            <template v-else>
                <div class="client">
                    <img class="client-img" :src="detailData.icon">
                    <div class="client-box">
                        <div class="client-box-top">
                            <div class="top-title">{{ detailData.title }}</div>
                            <span v-if="detailData?.local_version" class="version-tag">v{{ detailData.local_version }}</span>
                            <div class="top-enable" v-if="detailData.is_install && detailData.enable_bool">
                                <img class="enable-img" src="../../assets/svg/enable.svg" alt="">
                                <div class="top-enable-label">已启用</div>
                            </div>
                            <div class="top-enable not-status-box" v-else>
                                <img class="enable-img" src="../../assets/svg/not-enable.svg" alt="">
                                <div class="top-enable-label">待启用</div>
                            </div>
                            <div v-if="!detailData.is_install && !detailData.is_compatible_main" class="version-warn">
                                该插件不兼容您当前使用的系统版本
                                <a class="ml4" href="https://github.com/zhimaAi/qiweidoc" target="_blank">去更新</a>
                            </div>
                        </div>
                        <div v-if="detailData.expire_time > 0 && detailData.price_type > 1" class="zm-tip-info mt8">
                            有效期至 {{ detailData.expire_date }}
                            <span v-if="detailData.is_expired" class="expired-tag ml4">已到期</span>
                        </div>
                        <div class="client-box-midden">{{ detailData.intro }}</div>
                        <div class="client-box-bottom">
                            <template v-if="detailData.is_install">
                                <a-tooltip
                                    v-if="!detailData.enable_bool"
                                    :title="detailData.is_expired ? '已过期，请购买后启用' : null">
                                    <a-button :disabled="detailData.is_expired"
                                              @click="statusChange(true)"
                                              type="primary">立即启用
                                    </a-button>
                                </a-tooltip>
                                <a-button v-else @click="statusChange(false)">禁 用</a-button>
                                <a-button v-if="detailData.price_type > 1" @click="goPay">联系客服</a-button>
                                <a-popover
                                    v-if="!detailData.is_last_version"
                                    :getPopupContainer="triggerNode => triggerNode"
                                    placement="bottom"
                                    overlayClassName="update-desc-popover">
                                    <template #content>
                                        <div class="title">{{detailData.title}}</div>
                                        <div class="last-version-tag">{{detailData.latest_version.version}}</div>
                                        <div class="update-desc" v-html="detailData.latest_version.upgrade_description.replace(/\r?\n/g, '<br/>')"></div>
                                    </template>
                                    <a-button type="primary" ghost @click="install(true)" :loading="installing">更新</a-button>
                                </a-popover>
                            </template>
                            <template v-else>
                                <div class="price">{{ detailData.price_info }}</div>
                                <a-button @click="install" :loading="installing">
                                    {{ detailData.price_type > 1 ? '安装试用7天' : '安 装' }}
                                </a-button>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="banner-box">
                    <Swiper
                        :loop="true"
                        :modules="modules"
                        :autoplay="{ delay: 3000 }"
                        :slidesPerView="2"
                        :style="{
                          '--swiper-navigation-size': '30px',
                          '--swiper-pagination-size': '30px'
                        }"
                        :pagination="{ clickable: true }"
                        :navigation="true"
                        :spaceBetween="16"
                        class="info-swiper-container"
                    >
                        <SwiperSlide v-for="(image, index) in detailData.images" :key="index">
                            <div class="img-preview" @click="() => setVisible(true, image)">预览</div>
                            <img :src="image" class="swiper-slide-img"/>
                            <a-image
                                :style="{ display: 'none' }"
                                :preview="{
                                  visible,
                                  onVisibleChange: setVisible,
                                }"
                                :src="currentImg"
                                class="swiper-slide-img"
                            />
                        </SwiperSlide>
                    </Swiper>
                </div>
                <div class="intro">
                    <div class="intro-title">功能简介</div>
                    <div class="intro-info">{{ detailData.description }}</div>
                </div>
                <div class="detail-list">
                    <div class="detail-title">功能清单</div>
                    <div class="detail-info-box">
                        <div class="detail-info-item" v-for="(item, index) in detailData.features" :key="index">
                            {{ item }}
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <ContactKefu ref="kefuRef"/>
    </MainLayout>
</template>

<script setup>
import {onMounted, ref, computed} from 'vue';
import {useStore} from 'vuex';
import dayjs from 'dayjs';
import {Pagination, Navigation} from 'swiper'
import {Swiper, SwiperSlide} from 'vue-awesome-swiper'
import 'swiper/css'
import 'swiper/css/pagination'
import 'swiper/css/navigation'
import MainLayout from "@/components/mainLayout.vue";
import {useRoute, useRouter} from 'vue-router';
import LoadingBox from "@/components/loadingBox.vue";
import {Modal, message} from 'ant-design-vue'
import {getModulesInfo, enableModules, disableModules, installModule} from "@/api/company";
import ContactKefu from "@/components/common/contact-kefu.vue";

const store = useStore()
const route = useRoute()
const router = useRouter()
const query = route.query
const links = computed(() => route.meta.links || [])
const kefuRef = ref(null)
const loading = ref(false)
const installing = ref(false)
const modules = ref([Pagination, Navigation])

onMounted(() => {
    getModulesInfo({
        name: query.name
    })
})

const lists = computed(() => {
    let _modules = store.getters.getModules || []
    if (Array.isArray(_modules)) {
        const nowTime = dayjs().unix()
        _modules.map(m => {
            m.enable_bool = m.is_enabled
            if (m.is_expired) {
                m.enable_bool = false
            }
        })
        return _modules
    }
    return []
})

const detailData = computed(() => {
    let info = lists.value.find(i =>  i.name === query.name)
    if (info) {
        typeof info.images === 'string' && (info.images = info.images.split(','))
        typeof info.features === 'string' && (info.features = info.features.split(/\r?\n/))
    } else {
        info = {}
    }
    return info
})

const statusChange = (status) => {
    let key = status ? '启用' : '禁用'
    Modal.confirm({
        title: `确认${key}该插件`,
        content: status ? `启用后，可到功能插件-${detailData.title}使用该功能` : '禁用后，功能不可再使用，禁用后可重新启用',
        okText: '确定',
        cancelText: '取消',
        onOk: () => {
            const loadClose = message.loading(`正在${key}`)
            let apiUrl = status ? enableModules : disableModules
            apiUrl({
                name: query.name
            }).then(() => {
                message.success('操作完成')
                detailData.value.enable_bool = status
                store.dispatch('updateModules')
            }).finally(() => {
                loadClose()
            })
        },
    })
}

const visible = ref(false);
const currentImg = ref('')
const setVisible = (value, img) => {
    visible.value = value
    currentImg.value = img
}

const install = (update = false) => {
    if (update && !detailData.value.is_compatible_main) {
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
    installing.value = true
    installModule({
        name: detailData.value.name
    }).then(res => {
        store.dispatch('updateModules')
        message.success(update ? '已更新' : '已安装')
    }).finally(() => {
        installing.value = false
    })
}

const goPay = () => {
    kefuRef.value.show()
    // let href = router.resolve({
    //     path: '/plug/shopping',
    //     query: {
    //         module: detailData.value.name
    //     }
    // }).href
    // window.open(href)
}
</script>

<style scoped lang="less">
:deep(.nav-tabs.ant-breadcrumb) {
    background: #FFF;
    height: 56px;
    padding: 17px 24px;
    box-sizing: border-box;

    li {
        font-size: 14px;
        color: #262626;

        a {
            font-weight: 400;

            &:hover {
                background-color: transparent;
                color: #2475FC;
            }
        }
    }
}

.info-box {
    margin: 16px;
    background-color: white;
    padding: 24px;
    overflow: hidden;
    position: relative;
    min-height: 100vh;

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

    .client {
        display: flex;
        align-items: center;
        padding-bottom: 24px;
        border-bottom: 1px solid #D9D9D9;

        .client-img {
            height: 100px;
            width: 100px;
        }

        .client-box {
            display: flex;
            flex-direction: column;
            margin-left: 16px;

            .client-box-top {
                display: flex;
                align-items: center;
                gap: 4px;
                font-family: "PingFang SC";

                .top-title {
                    color: #000000;
                    font-size: 16px;
                    font-style: normal;
                    font-weight: 600;
                    line-height: 24px;
                }

                .top-enable {
                    display: flex;
                    padding: 0 6px;
                    align-items: center;
                    gap: 2px;
                    border-radius: 6px;
                    background: #E8FCF3;
                    color: #21a665;
                    font-size: 14px;
                    font-style: normal;
                    font-weight: 500;
                    line-height: 22px;

                    .enable-img {
                        width: 16px;
                        height: 16px;
                    }
                }

                .not-status-box {
                    background: #EDEFF2;
                    color: #3a4559;
                }
            }

            .client-box-midden {
                color: #595959;
                font-size: 14px;
                font-style: normal;
                font-weight: 400;
                line-height: 22px;
                margin: 6px 0px 16px;
            }

            .client-box-bottom {
                display: flex;
                align-items: center;
                gap: 8px;
            }
        }
    }

    .banner-box {
        width: 100%;
        margin-top: 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        overflow: hidden;
        padding-bottom: 20px;

        .banner-item-box {
            display: flex;
            width: 100%;
            gap: 8px;

            .banner-item {
                display: none;
                flex: 1;

                .item-img {
                    width: 100%;
                }
            }

            .active {
                display: block;
            }
        }

        .banner-item-box-small {
            display: flex;
            height: 47px;
            gap: 8px;

            .banner-item {
                cursor: pointer;

                .item-img {
                    height: 45px;
                }
            }

            .active {
                border: 1px solid #2475FC;
            }
        }
    }

    .intro {
        display: flex;
        flex-direction: column;
        font-family: "PingFang SC";
        gap: 8px;
        margin-top: 24px;

        .intro-title {
            align-self: stretch;
            color: #000000;
            font-size: 16px;
            font-style: normal;
            font-weight: 600;
            line-height: 24px;
        }

        .intro-info {
            align-self: stretch;
            color: #595959;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
        }
    }

    .detail-list {
        margin-top: 24px;
        display: flex;
        flex-direction: column;
        font-family: "PingFang SC";
        gap: 8px;

        .detail-title {
            align-self: stretch;
            color: #000000;
            font-size: 16px;
            font-style: normal;
            font-weight: 600;
            line-height: 24px;
        }

        .detail-info-box {
            display: flex;
            flex-direction: column;
            align-self: stretch;
            color: #595959;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
        }
    }
}

.img-preview {
    background: rgba(0, 0, 0, 0.3);
    width: 50px;
    height: 50px;
    position: absolute;
    top: 50%;
    left: 50%;
    color: white;
    align-items: center;
    justify-content: center;
    display: none;
    cursor: pointer;
    border-radius: 2px;
    transform: translate(-50%, -50%);
}

.swiper-slide {
    &:hover {
        .img-preview {
            display: inline-flex;
        }
    }
}

.swiper-slide-img {
    border: 1px solid #D9D9D9;
    border-radius: 6px;
}

.price {
    color: #fa541c;
    font-size: 16px;
    font-weight: 600;
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
}

.expired-tag {
    color: #fa541c;
    font-weight: 600;
}
</style>

<style lang="less">
.info-swiper-container {
    overflow: visible;
    width: 100%;
    /* height: 600px; */

    .swiper-pagination-horizontal {
        bottom: -22px;
    }

    .swiper-button-next:after, .swiper-button-prev:after {
        line-height: 2;
        font-weight: 600;
    }

    .swiper-button-prev, .swiper-button-next {
        opacity: 0.5;

        &:hover {
            opacity: 1;
        }
    }
}

.swiper-slide-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>
