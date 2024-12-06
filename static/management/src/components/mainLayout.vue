<template>
    <div class="_main-container">
        <MainHeader background="#FFF" showMenus/>
        <div class="_main-content-block">
            <MainLeftMenus/>
            <div class="_main-right-block">
                <slot name="navbar">
                    <MainNavbar :title="title"/>
                </slot>
                <slot></slot>
            </div>
        </div>
    </div>
</template>

<script setup>
import MainLeftMenus from "@/components/mainLeftMenus.vue";
import MainHeader from "@/components/mainHeader.vue";
import MainNavbar from "@/components/mainNavbar.vue";

const props = defineProps({
    title: {
        type: [Array, String]
    }
})
</script>

<style scoped lang="less">
._main-content-block {
    display: flex;
    padding-top: 52px;

    ._main-right-block {
        //flex: 1;
        width: calc(100vw - 256px);
    }
}
</style>
