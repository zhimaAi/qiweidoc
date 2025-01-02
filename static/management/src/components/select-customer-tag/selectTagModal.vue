<template>
    <a-modal v-model:open="visible"
             wrapClassName="tag-modal-wrap"
             :title="selectType === 'tag' ? '选择标签' : '选择标签组'"
             @ok="save"
             width="800px">
        <div class="zm-cst-tag-modal">
            <div v-if="selectType === 'tag' && selectedTags.length > 0"
                 class="selected-box">
                <div class="zm-tip-info">已选择标签（{{ selectedTags.length }}）</div>
                <div class="mt8">
                    <a-tag v-for="(tag, index) in selectedTags"
                           :key="tag.id"
                           class="active"
                           closable
                           @close="e => removeTag(e, tag, index)">{{ tag.name }}
                    </a-tag>
                </div>
            </div>
            <div v-if="selectType === 'group' && selectedGroups.length > 0"
                 class="selected-box">
                <div class="zm-tip-info">已选择标签组（{{ selectedGroups.length }}）</div>
                <div class="mt8">
                    <a-tag v-for="group in selectedGroups"
                           :key="group.group_id"
                           class="active"
                           closable
                           @close="e => removeGroup(e, group)">{{ group.group_name }}
                    </a-tag>
                </div>
            </div>
            <div v-if="groupData.length > 0" class="zm-filter-box">
                <div class="zm-filter-item">
                    <div>
                        <a-input-search
                            style="width: 220px;"
                            @search="search"
                            allowClear
                            placeholder="请输入标签或标签组名称"
                            v-model:value="filterData.keyword"/>
                    </div>
                </div>
            </div>
            <div v-for="group in showGroupData"
                 :key="group.group_id"
                 class="tag-box mt8">
                <div class="tag-group zm-flex-center">
                    <div @click="groupExpandChange(group)">
                        <CaretDownOutlined v-if="expandGroupKeys.includes(group.group_id)" class="icon"/>
                        <CaretRightOutlined v-else class="icon"/>
                    </div>
                    <a-checkbox
                        v-model:checked="group.selected"
                        @change="() => groupChange(group)"
                        class="ml8">{{ group.group_name }}
                    </a-checkbox>
                </div>
                <div v-if="expandGroupKeys.includes(group.group_id)"
                     class="tag-items mt8">
                    <a-tag v-for="tag in group.tag"
                           @click="tagClick(tag, group)"
                           :class="{active: selectedTagKeys.includes(tag.id)}"
                           :key="tag.id">{{ tag.name }}
                    </a-tag>
                </div>
            </div>
            <LoadingBox v-if="loading"></LoadingBox>
            <a-empty v-else-if="!showGroupData.length"
                     style="margin-top: 120px;"
                     :description="filterData.keyword ? '暂无匹配标签数据' :  '暂无标签数据'"/>
        </div>
    </a-modal>
</template>

<script setup>
import {onMounted, ref, reactive} from 'vue';
import {CaretDownOutlined, CaretRightOutlined} from '@ant-design/icons-vue';
import LoadingBox from "@/components/loadingBox.vue";
import {apiTags} from "@/api/company";

const emit = defineEmits(['change'])
const props = defineProps({
    immediate: {
        type: Boolean,
        default: false
    },
    selectType: {
        type: String,
        default: 'tag', // tag group
    },
    keys: {
        type: Array,
        default: () => []
    },
    groupKeys: {
        type: Array,
        default: () => []
    }
})
const visible = ref(false)
const loading = ref(false)
const groupData = ref([])
const showGroupData = ref([])
const expandGroupKeys = ref([])
const tagKeyMap = ref({})
const tagGroupKeyMap = ref({})
// selectedTagKeys、selectedTags push、splice时同时执行
// 确保下标对应便于执行删除操作
const selectedTagKeys = ref([])
const selectedTags = ref([])
const selectedGroups = ref([])
const filterData = reactive({
    keyword: ''
})
const isSelectTag = () => props.selectType === 'tag'
const isSelectGroup = () => props.selectType === 'group'

onMounted(() => {
    if (props.immediate) {
        init()
    }
})

function show() {
    visible.value = true
    init()
}

async function init() {
    await loadData()
    selectedTagKeys.value = []
    selectedTags.value = []
    selectedGroups.value = []
    if (props.keys.length) {
        for (let key of props.keys) {
            if (tagKeyMap.value[key]) {
                selectedTagKeys.value.push(key)
                selectedTags.value.push(tagKeyMap.value[key])
            }
        }
    }
    if (props.groupKeys.length) {
        for (let key of props.groupKeys) {
            if (tagGroupKeyMap.value[key]) {
                tagGroupKeyMap.value[key].selected = true
                selectedGroups.value.push(tagGroupKeyMap.value[key])
            }
        }
    }
    if (props.immediate) {
        emitChange()
    }
}

function save() {
    visible.value = false
    emitChange()
}

function emitChange() {
    emit('change', {
        tagKeys: selectedTagKeys.value,
        tags: selectedTags.value,
        groups: selectedGroups.value,
        groupKeys: selectedGroups.value.map(i => i.group_id)
    })
}

async function loadData() {
    loading.value = true
    await apiTags().then(res => {
        let data = res.data || []
        tagKeyMap.value = []
        let formatTagData = item => {
            // 选择标签组时组装 tagGroupKeyMap
            tagGroupKeyMap.value[item.group_id] = item
        }
        if (isSelectTag()) {
            formatTagData = item => {
                // 选择标签时组装 tagKeyMap
                item.tag.map(t => tagKeyMap.value[t.id] = t)
            }
        }
        data.map(item => {
            item.selected = false
            expandGroupKeys.value.push(item.group_id)
            formatTagData(item)
        })
        groupData.value = data
        search()
    }).finally(() => {
        loading.value = false
    })
}

function search() {
    loading.value = true
    filterData.keyword = filterData.keyword.trim()
    if (filterData.keyword) {
        showGroupData.value = []
        let temp
        for (let g of groupData.value) {
            if (g.group_name.indexOf(filterData.keyword) > -1) {
                showGroupData.value.push(g)
            } else {
                temp = []
                for (let t of g.tag) {
                    t.name.indexOf(filterData.keyword) > -1 && temp.push(t)
                }
                temp.length > 0 && showGroupData.value.push({...g, tag: temp})
            }
        }
    } else {
        showGroupData.value = groupData.value
    }
    loading.value = false
}

function groupExpandChange(group) {
    const i = expandGroupKeys.value.indexOf(group.group_id)
    if (i > -1) {
        expandGroupKeys.value.splice(i, 1)
    } else {
        expandGroupKeys.value.push(group.group_id)
    }
}

function groupChange(group) {
    if (group.selected) {
        selectedGroups.value.push(group)
    } else {
        let index = selectedGroups.value.findIndex(i => i.group_id === group.group_id)
        selectedGroups.value.splice(index, 1)
    }
    if (isSelectGroup()) {
        return
    }
    if (group.selected) {
        group.tag.map(tag => {
            if (!selectedTagKeys.value.includes(tag.id)) {
                selectedTagKeys.value.push(tag.id)
                selectedTags.value.push(tag)
            }
        })
    } else {
        group.tag.map(tag => {
            let index = selectedTagKeys.value.indexOf(tag.id)
            if (index > -1) {
                selectedTagKeys.value.splice(index, 1)
                selectedTags.value.splice(index, 1)
            }
        })
    }
}

function tagClick(tag, group) {
    if (!isSelectTag()) {
        return
    }
    const i = selectedTagKeys.value.indexOf(tag.id)
    if (i > -1) {
        selectedTagKeys.value.splice(i, 1)
        selectedTags.value.splice(i, 1)
        group.selected = false
    } else {
        selectedTagKeys.value.push(tag.id)
        selectedTags.value.push(tag)
        const tagIds = group.tag.map(i => i.id)
        let counter = 0
        selectedTagKeys.value.map(i => {
            if (tagIds.includes(i)) {
                counter += 1
            }
        })
        if (counter === tagIds.length) {
            group.selected = true
        }
    }
}

function removeTag(e, tag, i) {
    e.preventDefault();
    selectedTagKeys.value.splice(i, 1)
    selectedTags.value.splice(i, 1)
}

function removeGroup(e, group) {
    e.preventDefault()
    group.selected = false
    groupChange(group)
}

defineExpose({
    show,
    init,
})
</script>

<style scoped lang="less">
.zm-cst-tag-modal {
    max-height: 60vh;
    min-height: 380px;
    overflow-y: auto;

    :deep(.ant-checkbox-group) {
        display: block;
    }

    .selected-box {
        margin-bottom: 12px;
    }

    .tag-items {
        display: flex;
        flex-wrap: wrap;
    }

    :deep(.ant-tag) {
        margin: 4px;
        line-height: 28px;
        cursor: pointer;

        &.active {
            color: #1890ff;
            background: #e6f7ff;
            border-color: #91d5ff;
        }
    }
}
</style>
