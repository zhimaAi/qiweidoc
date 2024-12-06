<template>
    <a-tabs v-model:active-key="activeTab"
            @change="mainTabChange"
            class="zm-nav-tabs">
        <a-tab-pane v-for="tab in tabs" :key="tab.key" :tab="tab.title"/>
    </a-tabs>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {useRouter} from 'vue-router';

const router = useRouter()
const activeTab = ref('index')
const props = defineProps({
    active: {
        type: [Number, String],
    },
    tabs: {
        type: Array,
        required: true,
    }
})

onMounted(() => {
    if (props.active) {
        activeTab.value = props.active
    } else {
        activeTab.value = props.tabs[0]?.key
    }
})

function mainTabChange() {
    const tab = props.tabs.find(i => i.key === activeTab.value)
    if (tab.route) {
        router.push({path: tab.route})
    }
}
</script>

<style scoped>

</style>
