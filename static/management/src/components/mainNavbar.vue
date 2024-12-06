<template>
    <div class="_main-nav">
        <a-breadcrumb v-if="Array.isArray(title)">
            <a-breadcrumb-item v-for="(tit,i) in title" :key="i">
                <router-link v-if="tit.route" :to="tit.route">{{tit.name}}</router-link>
                <template v-else>{{ tit.name }}</template>
            </a-breadcrumb-item>
        </a-breadcrumb>
        <div v-else class="title">{{title}}</div>
    </div>
</template>

<script setup>
const props = defineProps({
    title: {
        type: [Array, String]
    }
})
</script>

<style scoped lang="less">
._main-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 24px;
    height: 50px;
    background: #FFF;
    width: 100%;

    :deep(.ant-breadcrumb) {
        font-weight: 500;
    }

    .title {
        font-size: 16px;
        font-weight: 600;
        color: rgba(0, 0, 0, 0.85);
    }
}
</style>
