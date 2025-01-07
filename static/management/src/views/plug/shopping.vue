<template>
    <div class="_main-container">
        <MainHeader/>
        <div class="main-content">
            <div class="left-block">
                <div class="title">欢迎订购芝麻会话存档插件功能</div>
                <div class="plugin-box">
                    <template v-for="item in modules">
                        <div
                            v-if="item.price_type > 1"
                            @click="select(item)"
                            :key="item.name"
                            :class="['plugin-item', {active: item.name == selected }]">
                            <img class="icon" :src="item.icon"/>
                            <div class="name">{{ item.title }}</div>
                            <div class="price">
                                <template v-if="item.price_type == 2">
                                    <span class="min">¥</span>
                                    <span>{{ item.price_value }}</span>
                                    <span class="min">/年</span>
                                </template>
                                <template v-else>客服报价</template>
                            </div>
                            <div class="desc">{{ item.intro }}</div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="right-block">
                <div v-if="selected" class="title">已选择“{{selectedInfo.title}}”</div>
                <div v-else class="title">暂无选择</div>
                <template v-if="false">
                    <div class="shopping-box">
                        <div class="shop-item">
                            <div>购买时间</div>
                            <div class="val">1年</div>
                        </div>
                        <div class="shop-item">
                            <div>购买数量</div>
                            <div class="val">x1</div>
                        </div>
                    </div>
                    <div class="pay-channel mt24">
                        <div class="tit">选择支付方式</div>
                        <div class="pay-item alipay">
                            <img class="icon" src="@/assets/image/alipay-icon.svg"/> 支付宝
                        </div>
                    </div>
                    <a-divider/>
                    <div class="zm-flex-between">
                        <div>支付金额</div>
                        <div class="price">¥880</div>
                    </div>
                    <a-button type="primary" class="pay-btn">立即支付</a-button>
                    <div class="zm-flex-baseline mt16">
                        <a-checkbox/>
                        <span class="ml8">我已阅读并同意芝麻会话存档<a>《付费用户服务协议》</a></span>
                    </div>
                </template>
                <div v-else class="text-center">
                    <img class="staff-qrcode" src="https://zmwk.cn/static/image/home/qrcode.jpg?v=20231117"/>
                    <div class="zm-tip-info mt16">—— 使用微信扫描二维码添加客服 ——</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref, computed} from 'vue';
import {useStore} from 'vuex';
import {useRoute} from 'vue-router';
import MainHeader from "@/components/mainHeader.vue";

const store = useStore()
const route = useRoute()
const loading = ref(false)
const selected = ref(null)
const modules = computed(() => {
    return store.getters.getModules
})
const selectedInfo = computed(() => {
    return modules.value.find(i => i.name === selected.value)
})

onMounted(() => {
    if (route.query.module) {
        selected.value = route.query.module
    }
})

const select = item => {
    selected.value = item.name
}
</script>

<style scoped lang="less">
.main-content {
    position: absolute;
    top: 76px;
    left: 0;
    right: 0;
    margin: auto;
    display: flex;
    width: 1200px;
    color: #595959;
    font-size: 14px;
    font-weight: 400;

    > div {
        background: #FFF;
        border-radius: 16px;
        flex-shrink: 0;
        height: calc(100vh - 100px);
        overflow-y: auto;
    }

    .left-block {
        width: 808px;
        padding: 32px 24px;
        margin-right: 24px;
        text-align: center;

        .title {
            color: #000000;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .plugin-box {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;

            .plugin-item {
                width: 242px;
                height: 316px;
                border-radius: 16px;
                border: 1px solid #F0F0F0;
                padding: 24px;
                cursor: pointer;
                position: relative;

                &.active {
                    border: 4px solid #2475FC;
                    box-shadow: 0 4px 32px 0 #0000001f;

                    &::after {
                        content: "";
                        background: url("@/assets/image/selected.svg") no-repeat;
                        background-size: 100% 100%;
                        width: 62px;
                        height: 62px;
                        position: absolute;
                        bottom: 0;
                        right: 0;
                    }
                }

                .icon {
                    width: 40px;
                    height: 40px;
                    margin-top: 24px;
                }

                .name {
                    color: #000000;
                    font-size: 16px;
                    font-weight: 600;
                    margin-top: 16px;
                }

                .price {
                    color: #fa541c;
                    font-size: 32px;
                    font-style: normal;
                    font-weight: 600;
                    margin-top: 24px;

                    .min {
                        font-size: 16px;
                        font-weight: 400;
                    }
                }

                .desc {
                    color: #595959;
                    line-height: 22px;
                    font-size: 14px;
                    font-weight: 400;
                    word-break: break-all;
                    text-overflow: ellipsis;
                    overflow: hidden;
                    display: -webkit-box;
                    -webkit-line-clamp: 4;
                    -webkit-box-orient: vertical;
                    margin-top: 16px;
                }
            }
        }
    }

    .right-block {
        width: 368px;
        padding: 24px;

        .title {
            color: #262626;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .shopping-box {
            display: flex;
            align-items: center;
            justify-content: space-between;

            .shop-item {
                width: 152px;
                padding: 16px;
                border-radius: 6px;
                background: #F2F4F7;

                .val {
                    color: #262626;
                    font-weight: 600;
                    margin-top: 4px;
                }
            }
        }

        .pay-channel {
            .tit {
                color: #262626;
                font-size: 14px;
                font-weight: 400;
                margin-bottom: 8px;
            }

            .pay-item {
                padding: 5px 16px;
                border-radius: 6px;
                border: 1px solid #2475FC;
                color: #1890ff;
                font-size: 16px;
                font-weight: 600;
                display: inline-flex;
                align-items: center;

                .icon {
                    width: 18px;
                    height: 18px;
                    margin-right: 8px;
                }
            }
        }

        .price {
            color: #fa541c;
            font-size: 20px;
            font-weight: 600;
        }

        .pay-btn {
            width: 100%;
            margin-top: 35px;
        }

        .staff-qrcode {
            width: 240px;
            height: 240px;
        }
    }
}
</style>
