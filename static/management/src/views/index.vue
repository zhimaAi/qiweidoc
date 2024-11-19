<template>
    <div class="_main-container">
        <LoadingBox/>
    </div>
</template>

<script setup>
import {onBeforeMount} from 'vue';
import {useRouter} from 'vue-router';
import LoadingBox from "@/components/loadingBox.vue";

const router = useRouter();
onBeforeMount(() => {
    router.push({path: '/sessionArchive/index'})
})
</script>

<style scoped lang="less">
._main-content-block {
    display: flex;
    padding-top: 52px;
}
</style>
