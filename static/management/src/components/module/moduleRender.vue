<template>
    <MainLayout class="module-render-layout">
        <template #navbar>
            <div class="hide"></div>
        </template>
        <iframe class="render-module-iframe" :src="moduleUrl"/>
    </MainLayout>
</template>

<script setup>
import MainLayout from "@/components/mainLayout.vue";

const props = defineProps({
    moduleUrl: {
        type: String
    }
})
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
.render-module-iframe {
    width: 100%;
    height: 100%;
    border: none;
}
</style>
