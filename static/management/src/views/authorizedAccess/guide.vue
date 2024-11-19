<template>
    <div class="_main-container">
        <MainHeader/>
        <div class="main-content">
            <div class="title">使用前请按照步骤正确完成配置 <a class="ml16">如何配置?</a></div>
            <a-alert class="mt12 zm-custom-alert" type="info" showIcon>
                <template #message>
                    <div>正式使用前提示：</div>
                    <div>1. 先在企业微信后台上传确认函，等待腾讯审核~腾讯审核周期大概1-3天</div>
                    <div>2. 审核通过之后，按照接入流程正常接入</div>
                </template>
            </a-alert>
            <div class="text-center">
                <div>
                    <img src="@/assets/image/guide/cover.png" class="cover"/>
                </div>
                <router-link to="/authorizedAccess/auth">
                    <a-button type="primary" class="main-btn">立即接入</a-button>
                </router-link>

            </div>
        </div>
        <MainFooter/>
    </div>
</template>

<script setup>
import MainHeader from "@/components/mainHeader.vue";
import MainFooter from "@/components/mainFooter.vue";
</script>

<style scoped lang="less">
._main-container {
    background: #E6EFFF;
    min-height: 100vh;
    padding: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;

    .main-content {
        width: 960px;
        height: 700px;
        padding: 32px;
        border-radius: 16px;
        background: #FFF;
        box-shadow: 0 4px 32px 0 #0000001f;
        margin: 8vh auto;
        text-align: left;

        .title {
            color: #242933;
            font-size: 20px;
            font-weight: 600;
            border-radius: 6px;
            padding: 16px 24px;
            background-image: url(@/assets/image/guide/nav-bg.png);
            background-size: cover;
            background-repeat: no-repeat;
        }

        .cover {
            width: 238.8px;
            height: 208.67px;
            margin: 80px auto 24px;
        }

        .main-btn {
            width: 160px;
        }
    }

    ._main-footer {
        .copyright {
            color: #8c8c8c;
            font-size: 12px;
            font-weight: 400;
        }
    }
}
</style>
