<template>
    <MainLayout title="客户列表">
        <div class="filter-box">
            <div class="filter-item">
                <label>搜索客户：</label>
                <div class="mr10">
                    <a-input
                        style="width: 100%;"
                        placeholder="请输入客户昵称搜索"
                        allowClear
                        @pressEnter="initSearch"
                        v-model:value="filterData.keyword"
                    ></a-input>
                </div>
            </div>
            <div class="filter-item"></div>
            <div class="filter-item" style="justify-content: right">
                <a-button @click="initSearch" type="primary">搜索</a-button>
                <a-button @click="resetSearch" class="ml8">重置</a-button>
            </div>
        </div>
        <div class="container">
            <div class="left-block">
                <div class="header-box mt12">
                    <div class="fl-fc">
                        <span class="wz">
                            <a-radio-group
                                v-model:value="dataShowType"
                                button-style="solid"
                                style="margin-right: 4px;"
                                @change="dataTypeChange"
                            >
                                <a-radio-button value="a" :disabled="has_search_flag">
                                近七日
                                </a-radio-button>
                                <a-radio-button value="b" style="width: 74px; text-align: center;">
                                全部
                                </a-radio-button>
                            </a-radio-group>
                            {{!has_search_flag && dataShowType == 'a' ? '共新增' : '共'}}{{ totals || 0 }}个客户
                        </span>
                    </div>

                    <div
                        class="list margin-bottom-16 self-header-two fx-ac"
                        style="justify-content: space-between; margin-top: 12px;"
                    >
                        <div class="fx-ac update-box">
                            <div>
                                <span class="lefts_sizes" v-if="last_sync_time">
                                上次更新时间：{{ timeContrast(last_sync_time) }}
                                </span>
                            </div>
                            <a-button
                                :loading="updateLoading"
                                class="lefts btn-update"
                                @click="Update"
                                >
                                <template #icon><RedoOutlined class="update-icon" /></template>
                                {{ updateLoading ? "更新中" : "更新数据" }}
                            </a-button>
                        </div>
                    </div>

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
                        <template v-if="'external_name' === column.dataIndex">
                            <a-tooltip overlayClassName="user-info-tooltip">
                                <template #title>
                                    <div>客户备注：{{ record.staff_remark }}</div>
                                    <!-- <div>unionID：{{ record.unionid || "--" }}</div> -->
                                    <div>外部联系人ID：{{ record.external_userid }}</div>
                                </template>
                                <div class="qunfa" style="display: flex; align-items: center">
                                    <img v-if="record.avatar"
                                        :src="record.avatar"
                                        style="width: 32px; height: 32px; margin-right: 8px"
                                        alt=""/>
                                    <img v-else src="../../assets/image/taglogo.svg" alt=""/>
                                    <div class="qunfa-box">
                                        <div>
                                            <span class="qunfa_name">
                                                {{ textLimit(record.external_name, 7) }}
                                            </span>
                                            <span class="is-wx-tag" v-if="!record.corp_name">
                                                @微信
                                            </span>
                                            <span style="color: #faad14" v-else>
                                                {{ record.corp_name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a-tooltip>
                        </template>
                        <template v-if="'add_status' === column.dataIndex">
                            <a-tag :color="record.add_status == '0' ? 'volcano' : 'green'">
                                {{ record.add_status == 0 ? "已流失" : "未流失" }}
                            </a-tag>
                        </template>
                        <template v-if="'staff_name_slot' === column.dataIndex">
                            <span class="wk-black-65-text">{{ record.staff_user_name }}</span>
                        </template>

                        <template v-if="'add_time' === column.dataIndex">
                            <span class="wk-black-65-text">{{ record.add_time }}</span>
                        </template>

                        <template v-if="'source' === column.dataIndex">
                            {{ record.add_way }}
                            {{ record.state && record.state.name ? "【" + record.state.name + "】" : "" }}
                        </template>
                        <template v-if="'tag_data' === column.dataIndex">
                            <span class="tages_wid">
                                <template v-if="record.tag_data.length > 1">
                                    <a-popover  :overlayStyle="{ zIndex: 998 }">
                                        <template #content>
                                            <div style="width: 200px">
                                            <a-tag
                                                style="white-space: pre-wrap; margin-bottom: 5px;"
                                                color="blue"
                                                v-for="item in record.tag_data.slice(
                                                1,
                                                record.tag_data.length
                                                )"
                                                :key="item.tag_id"
                                                >{{ item.tag_name }}</a-tag
                                            >
                                            </div>
                                        </template>
                                        <div class="nowrap flex">
                                            <a-tag color="blue" style="margin-bottom:0;">{{
                                            record.tag_data[0] && record.tag_data[0].tag_name.length > 10 ? record.tag_data[0].tag_name.substring(0, 10) + '...' : record.tag_data[0].tag_name
                                            }}</a-tag>
                                            <div class="nowrap" style="color: #1890ff;width:30px;">
                                            +{{ record.tag_data.length - 1 }}
                                            </div>
                                        </div>
                                    </a-popover>
                                </template>
                                <template v-else-if="record.tag_data.length == 1">
                                    <a-tag
                                    color="blue"
                                    style="margin-bottom:0;"
                                    v-if="record.tag_data.length"
                                    >{{ record.tag_data[0] && record.tag_data[0].tag_name.length > 10 ? record.tag_data[0].tag_name.substring(0, 10) + '...' : record.tag_data[0].tag_name }}</a-tag
                                    >
                                </template>
                                <template v-else>
                                    <div>
                                        -
                                    </div>
                                </template>
                            </span>
                        </template>
                    </template>
                </a-table>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import {onMounted, ref, reactive} from 'vue';
import {RedoOutlined} from '@ant-design/icons-vue';
import MainLayout from "@/components/mainLayout.vue";
import { customersList, customersSync } from "@/api/company";
import { formatDate } from "@/utils/tools.js"
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';

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
        dataIndex: "external_name",
        key: "external_name",
        title: "客户昵称",
        scopedSlots: {customRender: "external_name"},
        fixed: "left"
    },
    {
        title: "流失状态",
        dataIndex: "add_status",
        key: "add_status",
        scopedSlots: {customRender: "add_status"}
    },
    {
        title: "所属员工",
        dataIndex: "staff_user_name",
        key: "staff_user_name",
        width: 120,
        scopedSlots: {customRender: "staff_name_slot"}
    },
    {
        title: "添加时间",
        dataIndex: "add_time",
        key: "add_time"
    },
    {
        title: "客户标签",
        key: "tag_data",
        dataIndex: "tag_data",
        scopedSlots: {customRender: "tag_data"}
    }
]

const last_sync_time = ref('') // 更新数据
const dataShowType = ref('b')
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

const totals = ref(0)
// const unique_totals = ref(0) // 去重客户数
const updateLoading = ref(false)
const has_search_flag = ref(false)
const staff_ids = ref([])

function timeContrast(time) {
    // 当前时间
    let new_time = parseInt(new Date().getTime() / 1000) + "";

    let result = "";
    let diffTime = "";
    // 同步时间差
    // diffTime = new_time * 1 - moment(time).format("X");
    diffTime = new_time * 1 - +time;
    if (diffTime <= 60) {
    result = diffTime + "秒前 ";
    } else if (diffTime > 60 && diffTime <= 3660) {
    result = parseInt(diffTime / 60) + "分钟前 ";
    } else if (diffTime > 3660 && diffTime <= 86400) {
    result = parseInt(diffTime / 3600) + "小时前 ";
    } else if (diffTime > 86400) {
    result = parseInt(diffTime / 86400) + "天前 ";
    }
    return result
}

const dataTypeChange = () => {
    search()
}

const textLimit = (text, max) => {
    if (!text) {
        return  ""
    }
    return text.length > max ? text.substring(0, max) + ".." : text
}

const refresh = ref(0)

const Update = () => {
    //更新数据
    if (refresh.value == 0) {
    refresh.value = 1;
    updateLoading.value = true;
    customersSync({}).then((res) => {
        refresh.value = 0;
        updateLoading.value = false;
        message.success('更新成功')
    });
    }
}

onMounted(() => {
    loadData()
})

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
    filterData.keyword = ''
    initSearch();
}

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
        size: pagination.pageSize
    }
    filterData.keyword = filterData.keyword.trim()
    if (filterData.keyword) {
        params.keyword = filterData.keyword
    }
    if (dataShowType.value === 'a') {
        params.add_time = dayjs().subtract(6, 'day').format('YYYY-MM-DD HH:mm:ss')
    }
    customersList(params).then(res => {
        list.value = res.data.items || []
        last_sync_time.value = res.data.last_sync_time || ''
        pagination.total = Number(res.data.total)
        totals.value = res.data.total
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

    .name-box {
        display: flex;
    }
  }
}

.header-box {
    display: flex;
    flex-direction: column;
    align-items: self-start;
    gap: 8px;
}

.scan-code-group {
    color: #2475fc;
    cursor: pointer;
}

.margin-left-15 {
    margin-left: 15px;
}

.list {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 16px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: #000000;
    line-height: 24px;

    .wz {
      font-size: 14px;
      color: rgba(0, 0, 0, 0.65);
    }
    .fl-fc{
      display: flex;
      flex-direction: column;
    }

    .wz-color {
      color: rgba(0, 0, 0, 0.45);
    }

    .mb10 {
      margin-bottom: 10px;
    }
    .mr10 {
      margin-right: 10px;
    }
    .lefts {
      margin-left: 10px;
    }

    .lefts_size {
      transform: rotate(-100deg);
      color: #595959;
    }

    .lefts_sizes {
      margin-left: 5px;
      cursor: auto;
    }
    .lefts_check{
      margin-left:16px;
      .ant-checkbox+span{
        padding-right:0;
      }
    }

    span {
      cursor: pointer;
      font-size: 13px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(0, 0, 0, 0.45);
      line-height: 22px;
    }
}

.update-box {
    display: flex;
    align-items: center;

    .btn-update {
        color: #595959;

        &:hover, &:hover .update-icon{
            color: #4096ff;
        }

    }
}

.qunfa-box {
  margin-bottom: 0;
  padding: 2px 8px;
}

.qunfa {
  font-size: 12px;
  width: 250px;
}

.qunfa_name {
  font-size: 13px;
  font-family: PingFangSC-Medium, PingFang SC;
  font-weight: 500;
  color: rgba(0, 0, 0, 0.65);
  margin-right: 6px;
}

.tages_wid {
  display: inline-block;
  // width: 400px;

  // span {
    // margin-bottom: 8px;
  // }
}

.margin-bottom-16 {
  margin-bottom: 16px;
}

.nowrap{
  white-space: nowrap;
}

.flex {
    display: flex;
    align-items: center;
}

.is-wx-tag {
    color: #07C160;
}
</style>

<style lang="less">
.user-info-tooltip .ant-tooltip-inner {
    width: max-content;
}
</style>
