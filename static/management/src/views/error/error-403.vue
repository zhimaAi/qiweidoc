<template>
    <div>
        <MainLayout v-if="isShowLayout">
            <!-- 为了保持和下面的样式一致 -->
            <template #navbar>
                <MainNavbar :title="[
                    {name: '无权限访问'}
                ]"/>
            </template>
            <div class="_main-error-box">
                <MainHeader/>
                <div class="error-box">
                    <img src="@/assets/image/403.svg" class="403"/>
                    <div class="right-box">
                        <div class="right-title">当前您暂无权限访问</div>
                        <div class="right-info">若需使用该功能请联系超级管理员或管理员添加权限</div>
                    </div>
                </div>
            </div>
        </MainLayout>
        <div class="_main-error-box" v-else>
            <MainNavbar :title="[
                {name: '无权限访问'}
            ]"/>
            <div class="error-box">
                <img src="@/assets/image/403.svg" class="403"/>
                <div class="right-box">
                    <div class="right-title">当前您暂无权限访问</div>
                    <div class="right-info">若需使用该功能请联系超级管理员或管理员添加权限</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import MainLayout from "@/components/mainLayout.vue";
import MainNavbar from "@/components/mainNavbar.vue";
const isShowLayout = ref(true)

if (window.parent?.location.hash.indexOf('/plug/render') !== -1) {
    // 插件页面
    isShowLayout.value = false
} else {
    // 主页面
    isShowLayout.value = true
}
</script>

<style lang="less" scoped>
._main-error-box {
    overflow: hidden;
}
.error-box {
    margin: 16px;
    background: white;
    width: calc(100% - 32px);
    height: calc(100vh - 134px);
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 24px;

    img {
        width: 200px;
        height: 200px;
    }

    .right-box {
        display: flex;
        flex-direction: column;
        gap: 16px;

        .right-title {
            color: #000000;
            font-family: "PingFang SC";
            font-size: 36px;
            font-style: normal;
            font-weight: 600;
            line-height: 44px;
        }

        .right-info {
            width: 367px;
            color: #595959;
            font-family: "PingFang SC";
            font-size: 20px;
            font-style: normal;
            font-weight: 400;
            line-height: 28px;
        }
    }
}
</style>
