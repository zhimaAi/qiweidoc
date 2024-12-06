<template>
    <ZmNavTabs :tabs="tabs" :active="active"/>
</template>

<script setup>
import ZmNavTabs from "@/components/zmNavTabs.vue";

const props = defineProps({
    active: {
        type: [Number,String]
    }
})
const tabs = [
    {key: 'index', title: '单聊统计', route: '/module/private-chat-stat/index'},
    {key: 'rule', title: '统计规则', route: '/module/private-chat-stat/rule'},
]
</script>

<style scoped>

</style>
