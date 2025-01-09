<template>
    <MainLayout title="群列表">
        <div class="filter-box">
            <div class="filter-item">
                <label>群名称：</label>
                <div class="mr10">
                    <a-input
                        ref="inputGroupNameRef"
                        style="width: 100%;"
                        placeholder="请输入客户群名称搜索"
                        allowClear
                        @pressEnter="initSearch"
                        v-model:value="searchForm.name"
                    ></a-input>
                </div>
            </div>
            <!-- <div class="filter-item">
                <label>群主：</label>
                <div class="mr10">
                    <a-select
                        style="width: 100%"
                        mode="multiple"
                        :maxTagCount="2"
                        placeholder="请选择群主"
                        :open="false"
                        allowClear
                        @change="staffIdsChange"
                        v-model="staff_ids"
                        @dropdownVisibleChange="groupManageFocus"
                    >
                        <a-select-option
                            :value="item.user_id"
                            v-for="(item, index) in selectStaff"
                            :key="index"
                        >
                        </a-select-option>
                    </a-select>
                </div>
            </div>
            <div class="filter-item" style="justify-content: right;">
                <label>群标签：</label>
                <div class="mr10">
                <a-popover placement="topLeft">
                    <template v-slot:content>
                    <div v-if="filterTagParams.type === 3">无标签</div>
                    <div v-else-if="filterTagParams.type === 0 || filterTagParams.contain_tag_keys.length == 0" class="zm-tip-info">
                        未选择标签
                    </div>
                    <div v-else>
                        <div v-if="filterTagParams.contain_tag.length > 0" class="mb16">
                        选择标签：
                        <a-tag
                            v-for="tag in filterTagParams.contain_tag_map"
                            :key="tag.key"
                            >{{ tag.label }}
                        </a-tag>
                        </div>
                        <div v-if="filterTagParams.exclude_tag.length > 0">
                        排除标签：
                        <a-tag
                            v-for="tag in filterTagParams.exclude_tag_map"
                            :key="tag.key"
                            >{{ tag.label }}
                        </a-tag>
                        </div>
                    </div>
                    </template>
                    <template v-slot:title></template>
                    <a-select
                        style="width: 100%"
                        mode="multiple"
                        readonly
                        :placeholder="filterTagText()"
                        class="flex1 filter-tag-select"
                        :open="false"
                        :allowClear="true"
                        v-model="filterTagParams.contain_tag_keys"
                        :maxTagCount="3"
                        :maxTagTextLength="3"
                        @change="fileterTagChange"
                    >
                    <a-select-option
                        v-for="tag in filterTagParams.num_tag_list"
                        :key="tag.key"
                        :value="tag.key"
                    >
                        {{ tag.label }}
                    </a-select-option>
                    </a-select>
                </a-popover>
                </div>
            </div>
            <div class="filter-item">
                <label>建群时间：</label>
                <div>
                <a-date-picker
                    v-model="createGroupDates"
                    style="width: 100%"
                    format="YYYY-MM-DD"
                    :showToday="false"
                    @change="assignDate"
                />
                </div>
            </div>
            <div class="filter-item">
                <label>排序条件：</label>
                <div class="mr10">
                <a-select
                    v-model="searchForm.sort"
                    style="width: 100%"
                    placeholder="请选择客户群标签搜索"
                    @change="sortChange"
                >
                    <a-select-option :value="1">群内总人数</a-select-option>
                    <a-select-option :value="4">创建时间</a-select-option>
                </a-select>
                </div>
            </div>
            <div class="filter-item">
                <label>群状态：</label>
                <div class="mr10">
                <a-select
                    v-model="searchForm.normal_status"
                    style="width: 100%"
                    placeholder="请选择群状态"
                    @change="initSearch"
                >
                    <a-select-option :value="''">全部</a-select-option>
                    <a-select-option :value="1">有效</a-select-option>
                    <a-select-option :value="0">异常</a-select-option>
                </a-select>
                </div>
            </div> -->
            <div class="filter-item"></div>
            <div class="filter-item" style="justify-content: right">
                <a-button @click="initSearch" type="primary">搜索</a-button>
                <a-button @click="resetSearch" class="ml8">重置</a-button>
            </div>
        </div>
        <div class="container">
            <div class="left-block">
                <div class="header-box mt12">
                    <div class="zm-flex-between">
                        <div>群总数：<strong>{{ pagination.total }}</strong></div>
                    </div>
                    <a-tooltip overlayClassName="user-info-tooltip">
                        <template #title>
                            <span>
                                上次更新时间：<span class="num">{{ last_sync_time || '暂无更新时间' }}</span>
                            </span>
                        </template>
                        <a-button
                            type="primary"
                            style="margin-left:20px;"
                            :icon="h(SyncOutlined)"
                            :loading="latest_info.success"
                            @click="pullCustomerGroup"
                        >
                        更新数据
                        </a-button>
                    </a-tooltip>
                </div>
                <a-table
                    class="mt16"
                    :loading="loading"
                    :data-source="list"
                    :columns="columns"
                    :pagination="pagination"
                    @change="tableChange"
                >
                    <template #bodyCell="{ column, text, record }">
                        <template v-if="'name' === column.dataIndex">
                            <div class="zm-user-info">
                                <img class="avatar" src="@/assets/default-avatar.png"/>
                                <span class="name">{{ record.name }}</span>
                            </div>
                        </template>
                        <template v-if="'group_create_time' === column.dataIndex">
                            <div class="create_time">{{ record.group_create_time }}</div>
                        </template>
                        <!-- <template v-if="'btns' === column.dataIndex">
                            <a-popover
                                placement="left"
                                @visibleChange="visiblePopoverChange($event, scope.row)"
                            >
                                <template #content>
                                <div class="group-enter-popup">
                                    <span>请使用企业微信扫描下方二维码，</span>
                                    <span>可快速进群修改群名称</span>
                                    <img
                                    v-if="groupEnterUrl"
                                    :src="groupEnterUrl"
                                    style="width: 120px;height: 128px;"
                                    />
                                </div>
                                </template>
                                <a class="mr16 height-automatic eli-one-text">扫码进群</a>
                            </a-popover>
                        </template> -->
                    </template>
                </a-table>
            </div>
        </div>
        <!-- <selectStaffNew
            selectType="multiple"
            title="选择所属员工"
            ref="setStaffRef"
            @change="selectStaffChange"
        >
        </selectStaffNew> -->
    </MainLayout>
</template>

<script setup>
import { onMounted, ref, reactive, h } from 'vue';
import { message } from 'ant-design-vue';
// import selectStaffNew from "@/components/select-staff-new/index";
import {SyncOutlined} from '@ant-design/icons-vue';
import MainLayout from "@/components/mainLayout.vue";
import { groupsList, groupsSync } from "@/api/company";
import { formatDate } from "@/utils/tools.js"
// import jrQrcode from "jr-qrcode";

// const setStaffRef = ref(null)
const inputGroupNameRef = ref(null)
const searchForm = reactive({
    size: 20,
    page: 1,
    name: "",
    member_name: "",
    tagNames: [],
    remark_yes: -1,
    sort: 1,
    normal_status: '',
})

const filterTagParams = reactive({
    type: 0,
    contain_tag: [],
    contain_tag_keys: [],
    contain_tag_map: [],
    exclude_tag: [],
    exclude_tag_map: []
})

const columns = [
    {
        title: '群名',
        dataIndex: 'name',
        scopedSlots: { customRender: 'name' },
        minWidth: '200',
        ellipsis: true,
        sort_key: 'name',
    },
    {
        title: '群主',
        dataIndex: 'owner_name',
        scopedSlots: { customRender: 'owner_name_slot' },
        minWidth: '140',
        sort_key: 'owner_name',
    },
    {
        dataIndex: 'total_member',
        title: '群人数',
        minWidth: '65',
        scopedSlots: { customRender: 'total_member' },
        sort_key: 'total_member',
    },
    // {
    //     title: '同步标签',
    //     dataIndex: 'xkf_tag_dec',
    //     minWidth: '100',
    //     scopedSlots: { customRender: 'syncTag' },
    //     sort_key: 'xkf_tag_dec',
    // },
    // {
    //     title: '可见范围',
    //     dataIndex: 'status',
    //     minWidth: '75',
    //     scopedSlots: { customRender: 'status' },
    //     sort_key: 'status',
    // },
    {
        dataIndex: 'group_create_time',
        scopedSlots: { customRender: 'group_create_time' },
        title: '创建时间',
        minWidth: '120',
        sort_key: 'group_create_time',
    }
    // ,
    // {
    //     dataIndex: 'btns',
    //     title: '操作',
    //     renderHeader: true,
    //     scopedSlots: { customRender: 'btns' },
    //     minWidth: '250',
    //     fixed: 'right',
    //     sort_key: 'play_key'
    // }
]

const createGroupDates = ref('')
const loading = ref(false)
const list = ref([])
const pagination = reactive({
    total: 0,
    current: 1,
    pageSize: 10,
    showSizeChanger: true,
    pageSizeOptions: ['10', '20', '50', '100'],
})
const filterData = reactive({
    keyword: '',
})

const staff_ids = ref([])
// const selectStaff = ref([])
// const staffIdsChange = () => {
//     let arr = [];
//     let staffs = [];
//     staff_ids.value.forEach((i) => {
//     selectStaff.value.forEach((it) => {
//         if (i === it.user_id) {
//         arr.push(it.staff_id);
//         staffs.push(it);
//         }
//     });
//     });
//     searchForm.staff_id = arr.join(",");
//     selectStaff.value = staffs;
// }

// const groupManageFocus = () => {
//     if (setStaffRef.value) {
//         setStaffRef.value.show(selectStaff.value)
//     }
// }

//选中员工逻辑
// const selectStaffChange = (val) => {
//     selectStaff.value = val;
//     staff_ids.value = val.map((item) => item.user_id);
//     searchForm.staff_id = val.map((item) => item.staff_id).toString();
// }

// const filterTagText = () => {
//     if(filterTagParams.contain_tag_keys.length == 0){
//         return "请选择客户群标签搜索";
//     }
//     switch (filterTagParams.type) {
//         case 0:
//         return "请选择客户群标签";
//         case 1:
//         return "满足任意一个群标签";
//         case 2:
//         return "同时满足所选群标签";
//         case 3:
//         return "无群标签";
//     }
// }

// const fileterTagChange = (val) => {
//     if(val.length == 0){
//         filterTagParams.contain_tag_keys = []
//         filterTagParams.num_tag_list = []
//         filterTagParams.contain_tag_map = []
//         filterTagParams.exclude_tag_map = []
//         filterTagParams.contain_tag = []
//         filterTagParams.exclude_tag = []
//         return
//     }

//     filterTagParams.contain_tag_keys = val
//     // 当前删除的tag
//     let del_tag = filterTagParams.num_tag_list.filter(sub=> !val.includes(sub.key))
//     // 更新当前显示的数据
//     filterTagParams.num_tag_list = filterTagParams.num_tag_list.filter(sub=> val.includes(sub.key))
//     // 过滤掉已经删除的
//     filterTagParams.contain_tag_map = filterTagParams.contain_tag_map.filter(sub=> !sub.key.includes(del_tag[0].key))
//     filterTagParams.exclude_tag_map = filterTagParams.exclude_tag_map.filter(sub=> !sub.key.includes(del_tag[0].key))
//     // 重新赋值keys
//     filterTagParams.contain_tag = filterTagParams.contain_tag_map.map(sub=> sub.key)
//     filterTagParams.exclude_tag = filterTagParams.exclude_tag_map.map(sub=> sub.key)
// }

// const rowSelection = ref({
//   checkStrictly: false,
//   onChange: (selectedRowKeys, selectedRows) => {
//   },
//   onSelect: (record, selected, selectedRows) => {
//   },
//   onSelectAll: (selected, selectedRows, changeRows) => {
//   },
// });

// const groupEnterUrl = ref('')
// const visiblePopoverChange = (e, record) => {
//     if(e){
//         // let data = {
//         //     group_id: record.chat_id,
//         //     group_name:record.name
//         // }
//         // getGroupEnterLink(data).then((res) => {
//         //     groupEnterUrl.value = jrQrcode.getQrBase64(res.data.url);
//         // })
//     }
// }

onMounted(() => {
    loadData()
})

const last_sync_time = ref('')
const refreshFlag = ref(false)
const latest_info = reactive({
    end_time: void 0,
    success: false
})
const pullCustomerGroup = () => {
    if (!refreshFlag.value) {
        refreshFlag.value = true;
        latest_info.success = true;
        groupsSync({}).then((res) => {
            //已经在拉取的状态
            message.success('更新成功')
            // checkTaskStatus();
        }).finally(() => {
            refreshFlag.value = false;
            latest_info.success = false;
        })
    }
}

// const assignDate = (value, date) => {
//     searchForm.start_time = date;
//     searchForm.end_time = date;
// }

const lastGetTime = ref(0)

const initSearch = () => {
    lastGetTime.value = new Date().getTime() / 1000;
    searchForm.page = 1;
    search();
}

const resetSearch = () => {
    filterTagParams.type = 0
    filterTagParams.contain_tag = []
    filterTagParams.contain_tag_keys = []
    filterTagParams.contain_tag_map = []
    filterTagParams.exclude_tag = []
    filterTagParams.exclude_tag_map = []
    staff_ids.value = [];
    createGroupDates.value = "";
    searchForm.name = ""
    initSearch();
}

// const sortChange = () => {

// }

const search = () => {
    list.value = []
    pagination.total = 1
    pagination.current = 1
    loadData()
}

const loadData = () => {
    loading.value = true
    let params = {
        page: pagination.current,
        size: pagination.pageSize,
        keyword: searchForm.name
    }
    filterData.keyword = filterData.keyword.trim()
    if (filterData.keyword) {
        params.keyword = filterData.keyword
    }
    groupsList(params).then(res => {
        list.value = res.data.items || []
        last_sync_time.value = res.data.last_sync_time || ''
        pagination.total = Number(res.data.total)
    }).finally(() => {
        loading.value = false
    })
}

const tableChange = p => {
    pagination.current = p.current
    pagination.pageSize = p.pageSize
    loadData()
}
</script>

<style scoped lang="less">
.container {
    display: flex;
    margin: 12px;

    > div {
        background: #FFF;
        border-radius: 2px;
        // overflow-y: scroll;
        // height: calc(100vh - 250px);
    }

    .left-block {
        padding: 12px;
        flex: 1;
    }

    .right-block {
        flex-shrink: 0;
        width: 16vw;
        max-width: 300px;

        :deep(.ant-tabs) {
            .ant-tabs-tab {
                margin: 0 12px;
            }

            .ant-tabs-content {
                padding: 0 12px;
            }
        }
    }
}

.filter-box {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  padding: 16px 24px 0;
  background-color: white;
  margin: 12px;

  .mr10{
    margin-right: 10px;
  }
  .filter-item {
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    width: 25%;
    flex-shrink: 0;

    > div {
      width: calc(100% - 70px);
    }

    label {
      white-space: nowrap;
      display: inline-block;
      width: 70px;
      flex-shrink: 0;
      text-align: right;
    }
  }
}

.header-box {
    display: flex;
    align-items: center;
    gap: 8px;
}

.scan-code-group {
    color: #2475fc;
    cursor: pointer;
}
</style>

<style lang="less">
.user-info-tooltip .ant-tooltip-inner {
    width: max-content;
    white-space: nowrap;
}
</style>
