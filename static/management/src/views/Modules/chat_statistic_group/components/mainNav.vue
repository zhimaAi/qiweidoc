<template>
    <ZmNavTabs :tabs="tabs" :active="active"/>
</template>

<script setup>
import ZmNavTabs from "@/components/zmNavTabs.vue";

const props = defineProps({
    active: {
        type: [Number, String]
    }
})
const tabs = [
    {key: 'index', title: '群聊统计', route: '/module/chat-statistic-group/index'},
    {key: 'rule', title: '统计规则', route: '/module/chat-statistic-group/rule'},
]
</script>

<style scoped>

</style>
