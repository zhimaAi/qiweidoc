<template>
    <div class="staff-box">
        <div class="search-box">
            <a-input-search
                v-if="props.type == '1'"
                placeholder="请输入员工昵称搜索"
                allowClear
                size="small"
                v-model:value="filterData.staffValue"
                @search="search"/>
            <a-input-search
                v-if="props.type == '1'"
                placeholder="输入客户昵称/备注名进行搜索"
                allowClear
                size="small"
                v-model:value="filterData.customer"
                @search="search"/>
        </div>
        <ZmScroll class="staff-list group-staff-list"
                  @load="loadData"
                  :finished="finished"
                  :loading="loading">
            <div v-for="(item,i) in list"
                 :key="i"
                 @click="change(item)"
                 :class="['staff-item',{active: isSelected(item)}]">
                <div class="left">
                    <!-- <a-popover overlayClassName="zm-tooltip-popover" placement="top" :content="item.staff_name"> -->
                        <img class="avatar" :src="item.avatar || defaultAvatar"/>
                    <!-- </a-popover> -->
                </div>
                <div class="right">
                    <!-- <div class="staff-name nick-name" :title="item.staff_name">
                        <div class="name-box">{{ item.staff_name }}</div>
                        <div class="name-line">和</div>
                        <div class="name-box">{{ item.external_name }}</div>
                        <div class="name-line">的聊天</div>
                    </div> -->
                    <div class="staff-name nick-name" :title="item.name">
                        <div class="name-box">{{ item.name }}</div>
                        <div class="qunfa-box">
                            <span class="qunfa_name">
                                {{ item.external_name }}
                            </span>
                            <span class="is-wx-tag" v-if="!item.corp_name">
                                @微信
                            </span>
                            <span style="color: #faad14" v-else>
                                {{ item.corp_name }}
                            </span>
                        </div>
                    </div>
                    <div class="staff-name" :title="item.staff_name">
                        员工：<div class="name-box">{{ item.staff_name }}</div>
                    </div>
                </div>
            </div>
            <a-empty v-if="finished && list.length < 1" description="暂无数据" style="margin-top: 60px;"/>
        </ZmScroll>
    </div>
</template>

<script setup>
// import selectStaffNew from "@/components/select-staff-new/index";
import {reactive, ref} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import {useStore} from 'vuex';
import ZmScroll from "@/components/zmScroll.vue";
import {conversationList} from "@/api/session";

const setStaffRef = ref(null)
const route = useRoute()
const router = useRouter()
const store = useStore()
const defaultAvatar = require('@/assets/default-avatar.png')

const props = defineProps({
    type: {
        type: Number,// 1单聊 2群聊
        required: true
    },
    showChatNo: {
        type: Boolean,
        default: false
    },
    default: {
        type: Object,
    }
})

const emit = defineEmits(['change', 'totalReport'])
const list = ref([])
// const staffUseridMap = ref({})
const loading = ref(false)
const finished = ref(false)
const selected = ref(null)

const filterData = reactive({
    staffValue: '',
    customer: '',
    staff_id: ''
})

const pagination = reactive({
    current: 1,
    pageSize: 20,
    total: 0,
})

const init = () => {
    pagination.current = 1
    pagination.total = 0
    finished.value = false
    loading.value = false
    list.value = []
    // staffUseridMap.value = {}
}

const loadData = () => {
    if (loading.value) {
        return
    }
    loading.value = true
    let params = {
        page: pagination.current,
        size: pagination.pageSize
    }
    filterData.staffValue = filterData.staffValue.trim()
    if (filterData.staffValue) {
        params.staff_name = filterData.staffValue
    }
    filterData.customer = filterData.customer.trim()
    if (filterData.customer) {
        params.keyword = filterData.customer
    }
    conversationList(params).then(res => {
        let data = res.data || {}
        let {items, total} = data
        total = Number(total)
        if (!items || !items?.length || list.value.length === total) {
            finished.value = true
        }
        // console.log('staffUseridMap', staffUseridMap)
        for (let item of items) {
        //     if (!staffUseridMap.value[item.external_userid]) {
            list.value.push(item)
        //         staffUseridMap.value[item.external_userid] = true
        //     }
        }
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

const selectedHandle = () => {
    // console.log('props.default11', props.default)
    if (props.default && props.default?.chat_status === props.type && props.default.external_userid) {
        const find = list.value.find(item => item.external_userid === props.default.external_userid)
        if (find) {
            change(find)
        } else {
            // staffUseridMap.value[props.default.external_userid] = true
            list.value.unshift(props.default)
        }
    } else {
        change(list.value[0] || null)
    }
}

const isSelected = item => {
    // console.log('item', item)
    // console.log('selected', selected.value)
    return selected.value && selected.value.external_userid === item?.external_userid
}

const change = (item) => {
    if (item && isSelected(item)) {
        return
    }
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
</script>

<style scoped lang="less">
.search-box {
    padding: 10px 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

// .staff-list {
//     height: calc(100% - 42px);
// }

.group-staff-list {
    height: calc(100% - 80px);
}

.staff-box {
    .staff-item.active {
        background: #E5EFFF;
        border-left: 2px solid #2475FC;

        .right {
            .staff-name {
                // color: #2475FC;
            }
        }
    }

    .staff-item:not(.active):hover {
        background: #F2F4F7;
    }

    .staff-item {
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
                border-radius: 6px;
                margin-right: 8px;
            }
        }

        .right {
            width: calc(100% - 32px);

            .staff-name {
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
                color: #8C8C8C;
                max-width: 72px;
                width: fit-content;
            }

            .name-line {
                margin: 0 4px;
                color: #999999;
            }

            .qunfa-box {
                flex: 1;
                text-overflow: ellipsis;
                overflow: hidden;
                white-space: nowrap;
                display: flex;
                align-items: center;
            }

            .qunfa {
                font-size: 12px;
                width: 250px;
            }

            .qunfa_name {
                color: #595959;
                font-family: "PingFang SC";
                font-size: 12px;
                font-style: normal;
                font-weight: 400;
                line-height: 20px;
                margin-right: 4px;
            }

        }
    }
}
.is-wx-tag {
    color: #07C160;
}
</style>
