<template>
    <a-tree
        v-model:selected-keys="selectedKeys"
        @select="selectChange"
        block-node
        showLine
        :tree-data="data"
    />
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {departmentList} from "@/api/company";

const emit = defineEmits(['change']);
const data = ref([])
const selectedKeys = ref([])

onMounted(() => {
    loadData()
})

const loadData = () => {
    departmentList().then(res => {
        let list = res.data || []
        list = list.map(item => {
            return getNode(item)
        })
        data.value = list
    })
}

const getNode = (department, parentKey = 0) => {
    let key = `${parentKey}-${department.department_id}`
    if (department?.child_node?.length > 0) {
        department.children = department.child_node.map(item => {
            return getNode(item, key)
        })
    }
    return {
        key: key,
        title: department.name,
        children: department.children,
    }
}

const selectChange = () => {
    emit('change', selectedKeys.value)
}
</script>

<style scoped lang="less">

</style>
