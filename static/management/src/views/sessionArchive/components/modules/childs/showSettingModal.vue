<template>
    <a-modal v-model:open="visible"
             :confirm-loading="saving"
             @ok="save"
             width="700px"
             title="设置">
        <div class="show-setting-modal">
            <LoadingBox v-if="loading"/>
            <template v-else>
                <a-form :label-col="{ span: 4 }" :wrapper-col="{ span: 20 }">
                    <div class="setting-form">
                        <div class="set-title">
                            <span class="title">列表标签设置</span>
                            <span class="description">打开开关后，列表会优先显示设置的标签或标签组</span>
                        </div>
                        <a-form-item label="列表显示标签">
                            <a-switch v-model:checked="config.show_customer_tag"/>
                        </a-form-item>
                        <a-form-item label="显示标签组">
                            <a-button @click="showTagGroupModal" type="dashed" :icon="h(PlusOutlined)">选择标签组</a-button>
                            <div v-if="config?.show_customer_tag_group_ids?.length > 0 && !config?.show_customer_tag_groups?.length" class="tag-loading">
                                <a-spin size="small"/>
                            </div>
                            <div v-if="config?.show_customer_tag_groups?.length > 0">
                                <a-tag
                                    v-for="(item, index) in config.show_customer_tag_groups"
                                    class="zm-customize-tag"
                                    :key="item.group_id"
                                    @close="e => deleteGroup(e, item, index)"
                                    closable>{{ item.group_name }}
                                </a-tag>
                            </div>
                        </a-form-item>
                        <a-form-item label="显示标签">
                            <a-button @click="showTagModal" type="dashed" :icon="h(PlusOutlined)">选择标签</a-button>
                            <div v-if="config?.show_customer_tag_ids?.length > 0 && !config?.show_customer_tags?.length" class="tag-loading">
                                <a-spin size="small"/>
                            </div>
                            <div v-if="config?.show_customer_tags?.length > 0">
                                <a-tag
                                    v-for="(item, index) in config.show_customer_tags"
                                    class="zm-customize-tag"
                                    :key="item.id"
                                    @close="e => deleteTag(e, item, index)"
                                    closable>{{ item.name }}
                                </a-tag>
                            </div>
                        </a-form-item>
                        <div class="set-title">
                            <span class="title">已读标识设置</span>
                            <span class="description">客户的消息已读后，给客户的会话加一个已读标识</span>
                        </div>
                        <a-form-item label="已读标识">
                            <a-switch v-model:checked="config.show_is_read"/>
                        </a-form-item>
                    </div>
                </a-form>
                <SelectTagModal
                    ref="tagRef"
                    immediate
                    :keys="config.show_customer_tag_ids"
                    @change="tagChange"/>
                <SelectTagModal
                    ref="tagGroupRef"
                    immediate
                    :group-keys="config.show_customer_tag_group_ids"
                    selectType="group"
                    @change="tagGroupChange"/>
            </template>
        </div>
    </a-modal>
</template>

<script setup>
import {ref, reactive, h} from 'vue';
import {message} from 'ant-design-vue';
import {PlusOutlined} from '@ant-design/icons-vue';
import LoadingBox from "@/components/loadingBox.vue";
import {getChatConfig, setChatConfig} from "@/api/session";
import {assignData} from "@/utils/tools";
import SelectTagModal from "@/components/select-customer-tag/selectTagModal.vue";

const emit = defineEmits(['change'])
const tagRef = ref(null)
const tagGroupRef = ref(null)
const visible = ref(false)
const loading = ref(false)
const saving = ref(false)
const config = reactive({
    show_customer_tag: false,
    show_customer_tag_ids: [],
    show_customer_tags: [],
    show_customer_tag_group_ids: [],
    show_customer_tag_groups: [],
    show_is_read: false
})

function show() {
    visible.value = true
    loadData()
}

function loadData() {
    loading.value = true
    getChatConfig().then(res => {
        let data = res.data || {}
        data.show_customer_tag_ids = data?.show_customer_tag_config?.tag_ids || []
        data.show_customer_tag_group_ids = data?.show_customer_tag_config?.group_ids || []
        assignData(config, data)
    }).finally(() => {
        loading.value = false
    })
}

function showTagModal() {
    tagRef.value.show()
}

function showTagGroupModal() {
    tagGroupRef.value.show()
}

function save() {
    saving.value = true
    const params = {
        show_customer_tag: Number(config.show_customer_tag),
        show_is_read: Number(config.show_is_read),
        show_customer_tag_config: {
            tag_ids: config.show_customer_tag_ids,
            group_ids: config.show_customer_tag_group_ids
        }
    }
    setChatConfig(params).then(() => {
        message.success('已保存')
        visible.value = false
        emit('change', params)
    }).finally(() => {
        saving.value = false
    })
}

function deleteTag(e, tag, index) {
    e.preventDefault();
    config.show_customer_tags.splice(index, 1)
    let idIndex = config.show_customer_tag_ids.indexOf(tag.id)
    idIndex > -1 && config.show_customer_tag_ids.splice(idIndex, 1)
}

function deleteGroup(e, group, index) {
    e.preventDefault();
    config.show_customer_tag_groups.splice(index, 1)
    let idIndex = config.show_customer_tag_group_ids.indexOf(group.group_id)
    idIndex > -1 && config.show_customer_tag_group_ids.splice(idIndex, 1)
}

function tagChange({tagKeys, tags}) {
    config.show_customer_tag_ids = tagKeys
    config.show_customer_tags = tags
}

function tagGroupChange({groupKeys, groups}) {
    config.show_customer_tag_group_ids = groupKeys
    config.show_customer_tag_groups = groups
}

defineExpose({
    show,
})
</script>

<style scoped lang="less">
.show-setting-modal {
    min-height: 280px;

    :deep(.ant-tag.zm-customize-tag) {
        margin: 8px 8px 0 0;
    }

    .set-title {
        padding: 8px 16px;
        border-radius: 2px;
        background: #F2F4F7;
        color: #242933;

        .title {
            color: #262626;
            font-size: 14px;
            font-style: normal;
            font-weight: 600;
            line-height: 22px;
        }

        .description {
            margin-left: 17px;
            color: #8c8c8c;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
        }
    }

    .set-content {
        color: #595959;
        font-size: 14px;
    }

    .tag-loading {
        text-align: left;
        padding: 12px;
    }
}
</style>
