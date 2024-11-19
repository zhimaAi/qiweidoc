<template>
    <div id="session-filter-box"
         :class="['filter-box',{expand: expand}]"
    >
<!--        <a class="expand-box" @click="expandChange">-->
<!--            <template v-if="expand">-->
<!--                <UpOutlined />-->
<!--                <span class="ml4">收起</span>-->
<!--            </template>-->
<!--            <template v-else>-->
<!--                <DownOutlined />-->
<!--                <span class="ml4">展开</span>-->
<!--            </template>-->
<!--        </a>-->
<!--        <div class="filter-item">-->
<!--            <span class="filter-item-label">客户标签：</span>-->
<!--            <div class="filter-item-content">-->
<!--                <a-popover placement="right">-->
<!--                    <template #content>-->
<!--                        <div v-if="filterData.tags.length" style="max-width: 400px;">-->
<!--                            <a-tag v-for="tag in filterData.tags" :key="tag.id" class="mt8">{{ tag.name }}</a-tag>-->
<!--                        </div>-->
<!--                        <div v-else>无</div>-->
<!--                    </template>-->
<!--                    <a-input-->
<!--                        v-model="filterData.keyword"-->
<!--                        @search="change"-->
<!--                        allowClear-->
<!--                        placeholder="请选择客户标签"-->
<!--                        size="small"/>-->
<!--                </a-popover>-->
<!--            </div>-->
<!--        </div>-->
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
</template>

<script setup>
import {ref, reactive, computed} from 'vue';
import {UpOutlined, DownOutlined} from '@ant-design/icons-vue';

const props = defineProps({
    type: {
        type: String, // STAFF CUSTOMER GROUP
    }
})
const emit = defineEmits(['change'])
const expand = ref(false)
const filterData = reactive({
    keyword: '',
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
</script>

<style scoped lang="less">
.filter-box {
    padding-top: 8px;
    padding-right: 60px;
    border-bottom: 1px solid rgba(5, 5, 5, 0.06);
    background: #FFFFFF;
    top: 0;
    left: 57px;
    display: flex;
    flex-wrap: wrap;
    z-index: 99;
    height: 42px;
    overflow: hidden;

    &.expand {
        height: unset;
    }

    &.labour-session-search {
        padding: 12px 0 4px 0;
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
