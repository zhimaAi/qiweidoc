<template>
    <div id="session-filter-box"
         class="filter-box"
    >
        <div class="left-block">
            <div v-if="type === 'CUSTOMER'" class="filter-item">
                <span class="filter-item-label">客户标签：</span>
                <div class="filter-item-content">
                    <a-select
                        v-model:value="filterData.tag_ids"
                        @change="change"
                        @dropdownVisibleChange="showTagModal"
                        class="tag-select"
                        mode="multiple"
                        :open="false"
                        :max-tag-count="1"
                        allowClear
                        placeholder="请选择客户标签"
                        size="small">
                        <a-select-option
                            v-for="tag in filterData.tags"
                            :key="tag.id"
                            :value="tag.id">{{tag.name}}</a-select-option>
                    </a-select>
                </div>
            </div>
            <div class="filter-item">
                <span class="filter-item-label">{{keywordInfo?.title}}：</span>
                <div class="filter-item-content">
                    <a-input-search
                        v-model:value="filterData.keyword"
                        @search="change"
                        allowClear
                        :placeholder="keywordInfo?.placeholder"
                        size="small"/>
                </div>
            </div>
        </div>
        <div class="right-block">
            <a-button size="small" @click="showSetting">显示设置</a-button>
        </div>

        <ShowSettingModal ref="settingRef" @change="showSettingChange"/>
        <SelectTagModal ref="cstTagRef" @change="filterTagChange" :keys="filterData.tag_ids"/>
    </div>
</template>

<script setup>
import {ref, reactive, computed} from 'vue';
import {UpOutlined, DownOutlined} from '@ant-design/icons-vue';
import ShowSettingModal from "@/views/sessionArchive/components/modules/childs/showSettingModal.vue";
import SelectTagModal from "@/components/select-customer-tag/selectTagModal.vue";

const props = defineProps({
    type: {
        type: String, // STAFF CUSTOMER GROUP
    }
})
const emit = defineEmits(['change', 'showSettingChange'])
const expand = ref(false)
const settingRef = ref(null)
const cstTagRef = ref(null)
const filterData = reactive({
    keyword: '',
    tags: [],
    tag_ids: [],
})

const keywordInfo = computed(() => {
    switch (props.type) {
        case 'STAFF':
            return {
                title: '员工信息',
                placeholder: '请输入员工昵称搜索'
            }
        case 'CUSTOMER':
            return {
                title: '客户信息',
                placeholder: '请输入客户昵称搜索'
            }
        case 'GROUP':
            return {
                title: '群聊信息',
                placeholder: '请输入群聊名称搜索'
            }
    }
})

const change = () => {
    filterData.keyword = filterData.keyword.trim()
    emit('change', filterData)
}

const expandChange = () => {
    expand.value = !expand.value
    window.localStorage.setItem("zm:session:archive:filter:expand", Number(expand.value))
    //filterBoxHeightListen()
}

const ExpandInit = () => {
    let val = window.localStorage.getItem("zm:session:archive:filter:expand")
    if (val != null) {
        expand.value = (val == 1)
    }
}

const showSetting = () => {
    settingRef.value.show()
}

const showSettingChange = config => {
    emit('showSettingChange', config)
}

const showTagModal = () => {
    cstTagRef.value.show()
}

const filterTagChange = ({tagKeys, tags}) => {
    filterData.tag_ids = tagKeys
    filterData.tags = tags
    change()
}
</script>

<style scoped lang="less">
.filter-box {
    padding-top: 8px;
    padding-right: 12px;
    //border-bottom: 1px solid rgba(5, 5, 5, 0.06);
    background: #FFFFFF;
    display: flex;
    justify-content: space-between;
    height: 42px;
    overflow: hidden;

    > div {
        display: flex;
        flex-wrap: wrap;
    }

    .tag-select {
        :deep(.ant-select-selection-item-content) {
            max-width: 66px;
        }
    }


    &.expand {
        height: unset;
    }

    &.labour-session-search {
        padding: 12px 0 4px 0;
    }

    :deep(.ant-btn) {
        font-size: 12px;
        color: #595959;
    }

    .filter-item {
        display: flex;
        align-items: center;
        margin-left: 16px;
        margin-bottom: 10px;

        .filter-item-label {
            white-space: nowrap;
            width: 60px;
            text-align: right;
            flex-shrink: 0;
        }

        .filter-item-content {
            width: 192px;
            flex-shrink: 0;

            > div {
                width: 100%;
            }
        }
    }

    .expand-box {
        position: absolute;
        top: 12px;
        right: 16px;
    }
}
</style>
