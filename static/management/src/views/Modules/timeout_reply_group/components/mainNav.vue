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
    {key: 'index', title: '群聊超时', route: '/module/timeout-reply-group/index'},
    {key: 'rule', title: '回复规则', route: '/module/timeout-reply-group/rule'},
]
</script>

<style scoped>

</style>
