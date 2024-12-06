<template>
    <div class="group-box">
        <div class="search-box">
            <a-input-search
                placeholder="请输入群聊名称进行搜索"
                allowClear
                size="small"
                v-model:value="filterData.keyword"
                @search="search"/>
        </div>
        <ZmScroll
            :finished="finished"
            :loading="loading"
            @load="onloadData"
            class="group-list">
            <!---联系同事-->
            <div v-for="(item,i) in list"
                @click="change(item)"
                :class="['group-item', {active: isSelected(item)}]"
                :key="i">
                <div class="left">
                    <img class="avatar" src="@/assets/default-group-avatar.png"/>
                </div>
                <div class="right">
                    <div class="flex-between" :title="item.name">
                        <div class="group-name">
                            <!-- <span v-if="item.is_new_user == 1" class="is-new-tag">新</span> -->
                            <div class="user-name">{{ item.name || '未命名群聊' }}</div>
                        </div>
                    </div>
                    <!-- <div class="flex-between">
                        <div class="last-msg">
                            <img class="icon-14" src="@/assets/image/icon-message.png"/>
                            <span class="ml4">{{ item.last_msg_date }}</span>
                        </div>
                    </div> -->
                </div>
            </div>
            <!-- <LoadingBox v-if="loading"/> -->
            <!-- <a-empty v-else-if="list.length < 1" description="暂无数据" style="margin-top: 60px;"/> -->
            <a-empty v-if="finished && list.length < 1" description="暂无数据" style="margin-top: 60px;"/>
        </ZmScroll>
    </div>
</template>

<script setup>
import {ref, reactive} from 'vue';
import ZmScroll from "@/components/zmScroll.vue";
import '@/common/contacts.less';
import {conversationGroupList} from "@/api/session";

const props = defineProps({
    default: {
        type: Object,
    },
    staffInfo: {
        type: Object,
    }
})
const emit = defineEmits(['change', 'totalReport'])

const setStaffRef = ref(null)
const loading = ref(false)
const finished = ref(false)
const selected = ref(null)

const filterData = reactive({
    keyword: ''
})

const list = ref([])
const chatKeyMap = ref({})
const pagination = reactive({
    current: 1,
    pageSize: 20,
    total: 0,
})

// watch(() => props.staffInfo, (n, o) => {
//     init()
// }, {deep: true})

const init = () => {
    pagination.current = 1
    pagination.total = 0
    finished.value = false
    loading.value = false
    // 正式放开
    list.value = []
    // loadData()
}

const loadData = () => {
    loading.value = true
    let params = {
        page: pagination.current,
        size: pagination.pageSize
    }
    filterData.keyword = filterData.keyword.trim()
    if (filterData.keyword) {
        params.keyword = filterData.keyword
    }
    conversationGroupList(params).then(res => {
        let data = res.data || {}
        let {items, total} = data
        total = Number(total)
        if (!items || !items?.length || list.value.length == total) {
            finished.value = true
        }
        list.value = [...list.value, ...items]
        if (pagination.current == 1) {
            emit('totalReport', total)
            selectedHandle()
        }
        pagination.total = total
        pagination.current += 1
        loading.value = false
    }).catch(() => {
        loading.value = false
        finished.value = true
    })
}

const selectedHandle = async () => {
    // console.log('props.default', props.default)
    if (props.default && props.default.conversation_id && props.default.receiver_type === 'Group') {
        const find = list.value.find(item => item.id === props.default.conversation_id)
        if (find) {
            change(find)
        } else {
            let item
            // try {
            //     const res = await conversationGroupList({
            //         page: 1,
            //         size: 1,
            //         conversation_id: props.default.conversation_id
            //     })
            //     if (res?.data?.items && res?.data?.items.length) {
            //         item = res.data.items[0]
            //         list.value.unshift(item)
            //         chatKeyMap.value[item.chat_id] = true
            //     }
            // } catch (e) {
            // }
            change(item || list.value[0] || null)
        }
    } else {
        change(list.value[0] || null)
    }
}

const isSelected = item => {
    return selected.value && item?.id === selected.value?.id
}

const change = (item) => {
    // console.log('itemsssssssssss', item)
    if (item && isSelected(item)) {
        // console.log('xxxxxxxxxxxxxxxxxx')
        return
    }
    // console.log('itemxxxxxxxxxxxxxxxxxx', item)
    selected.value = item
    emit("change", item)
}

const staff_ids = ref([])
const selectStaff = ref([])
const staffIdsChange = () => {
    let arr = [];
    let staffs = [];
    staff_ids.value.forEach((i) => {
    selectStaff.value.forEach((it) => {
        if (i === it.user_id) {
        arr.push(it.staff_id);
        staffs.push(it);
        }
    });
    });
    filterData.staff_id = arr.join(",");
    selectStaff.value = staffs;
}

const groupManageFocus = () => {
    if (setStaffRef.value) {
        setStaffRef.value.show(selectStaff.value)
    }
}

//选中员工逻辑
const selectStaffChange = (val) => {
    selectStaff.value = val;
    staff_ids.value = val.map((item) => item.user_id);
    filterData.staff_id = val.map((item) => item.staff_id).toString();
}

const search = () => {
    init()
    loadData()
}

const onloadData = () => {
    // console.log('111')
    loadData()
}

// onMounted(() => {
//     init()
// })
</script>

<style scoped lang="less">

.search-box {
    padding: 10px 16px;
    display: flex;
    flex-direction: column;
}

.group-list {
    height: calc(100% - 42px);
}

.group-box {
    .group-item.active {
        background: #E5EFFF;
        border-left: 2px solid #2475FC;
    }

    .group-item:not(.active):hover {
        background: #F2F4F7;
    }

    .group-item {
        cursor: pointer;
        width: 100%;
        padding: 8px 16px;
        display: flex;
        align-items: center;
        //border-bottom: 1px solid rgba(0, 0, 0, 0.04);

        .left {
            flex-shrink: 0;
            width: fit-content;

            .avatar {
                width: 40px;
                height: 40px;
                border-radius: 2px;
                margin-right: 8px;
            }
        }

        .right {
            width: calc(100% - 32px);

            .group-name {
                font-size: 12px;
                font-weight: 400;
                color: #8c8c8c;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                line-height: 20px;
                display: flex;
                align-items: center;
            }

            .nick-name {
                color: #595959;
                display: flex;
                align-items: center;
            }

            .name-box {
                color: #1A1A1A;
                max-width: 72px;
                width: fit-content;
                margin-right: 4px;
            }

        }
    }

    :deep(.ant-select-selector) {
        height: 24px;
    }
}
</style>
