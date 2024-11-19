<template>
    <div class="_main-container">
        <MainHeader/>
        <LoadingBox v-if="loading"/>
        <CorpBaseFrom v-else/>
        <MainFooter/>
    </div>
</template>

<script setup>
import {onMounted, getCurrentInstance, ref} from 'vue';
import {useRouter} from 'vue-router';
import MainHeader from "@/components/mainHeader.vue";
import MainFooter from "@/components/mainFooter.vue";
import CorpBaseFrom from "@/views/authorizedAccess/components/corpBaseFrom.vue";
import {checkInit} from "@/api/auth-login";
import LoadingBox from "@/components/loadingBox.vue";

const {proxy} = getCurrentInstance();
const router = useRouter()
const loading = ref(true)
onMounted(() => {
    checkInit().then(res => {
        if (res?.data?.init) {
            // 已存在企业信息-去登陆
            proxy.$message.success('已有企业，去登陆')
            setTimeout(() => {
                router.push({path: '/login'})
            }, 1000)
        }
        loading.value = false
    }).catch(() => {
        loading.value = false
    })
})
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
}
</style>
