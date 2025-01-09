<template>
    <MainLayout class="module-render-layout">
        <template #navbar>
            <div class="hide"></div>
        </template>
        <div class="iframe-container">
            <LoadingBox v-if="loading"></LoadingBox>
            <iframe class="render-module-iframe" :src="moduleUrl" @load="loadFinished"/>
        </div>
    </MainLayout>
</template>

<script setup>
import {computed, watch, ref} from 'vue';
import {useRoute} from 'vue-router';
import MainLayout from "@/components/mainLayout.vue";
import LoadingBox from "@/components/loadingBox.vue";

const route = useRoute()
const loading = ref(true)
const moduleUrl = computed(() => {
    return decodeURIComponent(route.query.link)
})

watch(moduleUrl, (n, o) => {
    loading.value = true
})

function loadFinished() {
    loading.value = false
}
</script>

<style scoped lang="less">
.hide {
    display: none;
}

.module-render-layout {
    :deep(._main-content-block) {
        min-height: 100vh;
    }
}

.iframe-container {
    width: 100%;
    height: 100%;
    position: relative;

    .render-module-iframe {
        width: 100%;
        height: calc(100% - 3px);
        border: none;
    }
}
</style>
