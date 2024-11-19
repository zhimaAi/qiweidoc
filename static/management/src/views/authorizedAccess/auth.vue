<template>
    <div class="_main-container">
        <MainHeader/>
        <CorpConfigForm/>
        <MainFooter/>
    </div>
</template>

<script setup>
import {ref} from 'vue';
import MainHeader from "@/components/mainHeader.vue";
import MainFooter from "@/components/mainFooter.vue";
import CorpConfigForm from "@/views/authorizedAccess/components/corpConfigForm.vue";

const progress = ref(0)
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
