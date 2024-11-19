<template>
    <MainLayout title="客户标签">
        <div class="tag-manage">
            <div class="content">
                <div class="mb18">
                企微标签数：<b class="mr24">{{ labelNum }}</b> 企微标签组：<b>{{
                    dataArr.length
                }}</b>
                </div>
                <div class="list">
                    <div>
                        <a-button
                            class="mb10"
                            @click="updateLabel"
                            :loading="Refresh"
                        >
                            <template #icon><RedoOutlined /></template>
                            更新标签数据
                        </a-button>
                    </div>
                    <div class="lefts item fx-ac mb10">
                        <a-input-search
                            v-model="searchValue"
                            placeholder="请输入标签名称"
                            style="width: 200px"
                            :allowClear="true"
                            @search="onSearchChange"
                        />
                    </div>
                    <div><a-button class="lefts mb10" @click="exportTo">导出</a-button></div>
                </div>

                <div class="label_list" v-for="(item, index) in dataArr" :key="index" style="display:flex;">
                <div class="label_list_width" >
                    <div class="label_list_head" >
                    <div v-html="getSearName(item,1)"></div>
                    <div class="label-num">标签数：{{ item.tag.length }}</div>
                    </div>
                    <div>
                    <ul class="label_list_li">
                        <li
                        v-for="(items, indexs) in item.tag"
                        :key="indexs"
                        @click="showDrawer(items)"
                        >
                        <span v-html="getSearName(items,2)"></span>
                        </li>
                    </ul>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </MainLayout>
  </template>
<script setup>
import { onMounted, ref, reactive } from 'vue';
import {RedoOutlined, SyncOutlined} from '@ant-design/icons-vue';
import MainLayout from "@/components/mainLayout.vue";

const dataArr = ref([])
const allData = ref([]) //全部数据
// const label_list = ref([])
const ranges = ref([]) //选择可见范围
const rangesValue = ref([])
const searchValue = ref('') //标签名称
const labelNum = ref(0) //总标签数
const collectTag = reactive({
    flag: false,
    list: []
})

const onShow = () => {
    let list = []
    // collectTags().then((res) => {
    //   let list = res.data || []
    //   list.map((item) => {
    //     item.id = item.tag_id
    //   })
      collectTag.list = list
    //   collectTag.flag = true
    // })
}

// 搜索
const onSearchChange = () => {
    if (!searchValue.value.trim()) {
        dataArr.value = allData.value
        return
    }

    // allData.value.map((item) => {
    //   item.tag.map((query) => {
    //     if (query.name.indexOf(searchValue.value) > -1 && !dataArr.value.includes(item)) {
    //       dataArr.value.push(item)
    //     }
    //   })
    // })

    dataArr.value = []
    let list = []
    for (let i = 0; i < allData.value.length; i++) {
        let item = allData.value[i]
        list[i] = {
        ...item,
        tag: [],
        }
        for (let j = 0; j < item.tag.length; j++) {
        let ele = allData.value[i].tag[j]
        if (ele.name.indexOf(searchValue.value) > -1) {
            list[i].tag.push({
            ...ele,
            pid: item.group_id,
            })
        }
        }
    }

    let arr = []
    for (let i = 0; i < list.length; i++) {
        let item = list[i]
        item.is_open = true
        // 分组优先
        if (item.group_name.indexOf(searchValue.value) > -1){
        let filsub = allData.value.filter(
            (sub) => sub.group_id == item.group_id
        )[0]
        arr.push(filsub)
        }else if(item.tag.length > 0){
        arr.push(item)
        }
    }

    dataArr.value = arr
}

const tags_lists = () => {
    // let obj = {}
    if (rangesValue.value.length > 0) {
        ranges.value.map((el) => {
        if (el.data_type == 2) {
            el.type = 2
            el._id = el.department_id
        } else if (el.data_type == 1) {
            el.type = 3;
            el._id = el.staff_id;
        } else {
            el.type = 1;
        }
        })
    }
    // getTagList(obj).then((res) => {
    //     //获取列表
    //     let data = res.data
    //     allData.value = res.data
    //     labelNum.value = 0

    //     dataArr.value = data
            dataArr.value = [
                {
                    "group_id": "etX2IKEAAA48ogTzU5leH2qSzMuD4jJA",
                    "group_name": "类型",
                    "create_time": 1639388003,
                    "tag": [
                        {
                            "id": "etX2IKEAAAeWCDSGAF2PcKFi3p2BQg0A",
                            "name": "渠道活码打标签",
                            "create_time": 1667543350,
                            "order": 16,
                            "cst_num": "3",
                            "repeat_cst_num": "6"
                        },
                        {
                            "id": "etX2IKEAAAMknu2JDRIPd-sywqst2VsQ",
                            "name": "都看2",
                            "create_time": 1660029314,
                            "order": 15,
                            "cst_num": "34",
                            "repeat_cst_num": "50"
                        },
                        {
                            "id": "etX2IKEAAAvV-swLppsrjwEWoF_zhvsg",
                            "name": "Andy测试",
                            "create_time": 1642141016,
                            "order": 14,
                            "cst_num": "5",
                            "repeat_cst_num": "8"
                        },
                        {
                            "id": "etX2IKEAAACgTwWNGQY1hQHbDRbV2_kA",
                            "name": "都看",
                            "create_time": 1660029283,
                            "order": 13,
                            "cst_num": "42",
                            "repeat_cst_num": "62"
                        },
                        {
                            "id": "etX2IKEAAAWratRUXDb989Ump2vKwZvg",
                            "name": "裂变测试",
                            "create_time": 1642387536,
                            "order": 12,
                            "cst_num": "3",
                            "repeat_cst_num": "3"
                        },
                        {
                            "id": "etX2IKEAAAyuMxNCzkVsc9eMowRjPwLw",
                            "name": "lacy测试",
                            "create_time": 1648107159,
                            "order": 11,
                            "cst_num": "2",
                            "repeat_cst_num": "2"
                        },
                        {
                            "id": "etX2IKEAAAxrItUfTHoVCNR00f50bc9A",
                            "name": "2131231231",
                            "create_time": 1655179781,
                            "order": 10,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAWip_8Ar-bL2iVJZRBVG3HA",
                            "name": "zan测试",
                            "create_time": 1662708560,
                            "order": 9,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAH3Fe0nH1-N1ZdsFJjUAr4w",
                            "name": "只看客户",
                            "create_time": 1660027204,
                            "order": 8,
                            "cst_num": "0",
                            "repeat_cst_num": "0"
                        },
                        {
                            "id": "etX2IKEAAA8gJIyJs1Q2QiVEk5ycXhSg",
                            "name": "咕咕咕",
                            "create_time": 1642140716,
                            "order": 7,
                            "cst_num": "2",
                            "repeat_cst_num": "2"
                        },
                        {
                            "id": "etX2IKEAAASsqzQbryBDKTSVdU9jSIKw",
                            "name": "只看员工",
                            "create_time": 1660028687,
                            "order": 6,
                            "cst_num": "0",
                            "repeat_cst_num": "0"
                        },
                        {
                            "id": "etX2IKEAAAwXFC-a1a4nBXEG3rUrCStQ",
                            "name": "普通",
                            "create_time": 1639388003,
                            "order": 5,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAekVrCzW-9DBg1Mi8uamgBQ",
                            "name": "股改股",
                            "create_time": 1642140722,
                            "order": 4,
                            "cst_num": "4",
                            "repeat_cst_num": "4"
                        },
                        {
                            "id": "etX2IKEAAAc2R0gPJjijIMa_kgjg8Mmw",
                            "name": "重要",
                            "create_time": 1639388003,
                            "order": 3,
                            "cst_num": "10",
                            "repeat_cst_num": "11"
                        },
                        {
                            "id": "etX2IKEAAA5H7BIssksM3mDmpnGg1IMA",
                            "name": "标准",
                            "create_time": 1639388003,
                            "order": 2,
                            "cst_num": "28",
                            "repeat_cst_num": "34"
                        },
                        {
                            "id": "etX2IKEAAA1vDvA14TEvBhLDtPH_5D8g",
                            "name": "一般",
                            "create_time": 1639388003,
                            "order": 1,
                            "cst_num": "13",
                            "repeat_cst_num": "18"
                        },
                        {
                            "id": "etX2IKEAAAA0nQkiJsxxkTqiXSzjKDNQ",
                            "name": "关键词打标签-客户单聊",
                            "create_time": 1689238783,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAWiQEtlg6ElubY_c5hJFpNw",
                            "name": "关键词打标签-客户单聊2",
                            "create_time": 1689238827,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAA1XhHxyYcOZ2yWG31r_rIwQ",
                            "name": "测试111",
                            "create_time": 1689239829,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAA7xKHxgbFeZUGR-Smv7_cPA",
                            "name": "产品测试-员工未联系客户1",
                            "create_time": 1698833295,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAQXiASUM3wKutbEOuvA4esg",
                            "name": "产品测试-客户未联系员工1",
                            "create_time": 1698833313,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAEZCSc4CbmeZL_0u6QF3PPw",
                            "name": "产品测试-客户未联系员工2",
                            "create_time": 1698833322,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAA4bL0uvLXIw_sm52A4YYOUg",
                            "name": "产品测试-员工未联系客户2",
                            "create_time": 1698833338,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAA4GiApSck_hszmO5kbn_raA",
                            "name": "客户1",
                            "create_time": 1698833559,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAXX5z80phXXOmxJO9ygwLKg",
                            "name": "客户2",
                            "create_time": 1698833565,
                            "order": 1,
                            "cst_num": "13",
                            "repeat_cst_num": "13"
                        },
                        {
                            "id": "etX2IKEAAAycoGMagtfeVEtd960y7AHw",
                            "name": "客户2-2",
                            "create_time": 1698833570,
                            "order": 1,
                            "cst_num": "13",
                            "repeat_cst_num": "13"
                        },
                        {
                            "id": "etX2IKEAAAtWvOp_GGgbyeLtPanJ_9UA",
                            "name": "客户3",
                            "create_time": 1698833575,
                            "order": 1,
                            "cst_num": "13",
                            "repeat_cst_num": "13"
                        },
                        {
                            "id": "etX2IKEAAAThqTNNt7s8MZ1a9zGkUdzA",
                            "name": "员工1",
                            "create_time": 1698833775,
                            "order": 1,
                            "cst_num": "34",
                            "repeat_cst_num": "34"
                        },
                        {
                            "id": "etX2IKEAAAEzfgjXa3__dFBNTQ2Julcw",
                            "name": "员工2",
                            "create_time": 1698833780,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAHIiOIB882u024l0aak7VLA",
                            "name": "员工3",
                            "create_time": 1698833789,
                            "order": 1,
                            "cst_num": "34",
                            "repeat_cst_num": "34"
                        },
                        {
                            "id": "etX2IKEAAA-axm62H8x79n0btykk_M7A",
                            "name": "员工4",
                            "create_time": 1698833795,
                            "order": 1,
                            "cst_num": "35",
                            "repeat_cst_num": "35"
                        },
                        {
                            "id": "etX2IKEAAAPmRgOqJHB_nxhUk8YYlRAw",
                            "name": "客户5",
                            "create_time": 1698835777,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAv4RMQVo6mK77mgN0oUbYHQ",
                            "name": "变标签1",
                            "create_time": 1698910277,
                            "order": 1,
                            "cst_num": "12",
                            "repeat_cst_num": "12"
                        },
                        {
                            "id": "etX2IKEAAASQyvPN_HsE-fOQKTPn4PfA",
                            "name": "便标签2",
                            "create_time": 1698910285,
                            "order": 1,
                            "cst_num": "12",
                            "repeat_cst_num": "12"
                        },
                        {
                            "id": "etX2IKEAAAoE3tYxB_GsMDHm5ysw18gA",
                            "name": "变标签3",
                            "create_time": 1698910302,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAABhWkrbZF11-7MY0Sy44rGg",
                            "name": "员工变标签1",
                            "create_time": 1698910333,
                            "order": 1,
                            "cst_num": "35",
                            "repeat_cst_num": "35"
                        },
                        {
                            "id": "etX2IKEAAA-uzMoOPlpRAX14wlzl_aZQ",
                            "name": "员工变标签2",
                            "create_time": 1698910343,
                            "order": 1,
                            "cst_num": "33",
                            "repeat_cst_num": "33"
                        },
                        {
                            "id": "etX2IKEAAAoC8iqI61MecYDj1EXBCbMA",
                            "name": "员工变标签3",
                            "create_time": 1698910352,
                            "order": 1,
                            "cst_num": "33",
                            "repeat_cst_num": "33"
                        },
                        {
                            "id": "etX2IKEAAAZ-ZBq_v-SXypOMPa2GX3lA",
                            "name": "员工变标签5",
                            "create_time": 1698910361,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAA0oUBuJPs-3NU5IFl_-KPKg",
                            "name": "rand",
                            "create_time": 1727346160,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        }
                    ],
                    "order": 9999909,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAAjGvUcSaMXWSqNSQwDaY8Aw",
                    "group_name": "测试组1",
                    "create_time": 1639388011,
                    "tag": [
                        {
                            "id": "etX2IKEAAAAZKYM252v7MIOMdT8hC_jw",
                            "name": "企微数据互通",
                            "create_time": 1646039172,
                            "order": 8,
                            "cst_num": "22",
                            "repeat_cst_num": "24"
                        },
                        {
                            "id": "etX2IKEAAAxyZxWl_MBrCPicL3mnjJEw",
                            "name": "jkjh ",
                            "create_time": 1656389917,
                            "order": 7,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAA27fo7qgWc6miNJDc6h1Sow",
                            "name": "标签1",
                            "create_time": 1661141367,
                            "order": 6,
                            "cst_num": "28",
                            "repeat_cst_num": "30"
                        },
                        {
                            "id": "etX2IKEAAAwdwLdPGqxNbrWaJ8Q3XmlQ",
                            "name": "滚滚滚",
                            "create_time": 1640654108,
                            "order": 5,
                            "cst_num": "5",
                            "repeat_cst_num": "5"
                        },
                        {
                            "id": "etX2IKEAAAwBcmemT9ZrEVo6WkyIxHGA",
                            "name": "标签2",
                            "create_time": 1661141372,
                            "order": 4,
                            "cst_num": "29",
                            "repeat_cst_num": "31"
                        },
                        {
                            "id": "etX2IKEAAAkk8rqZ9Wl3ozNkcFfFvMOw",
                            "name": "11",
                            "create_time": 1639388011,
                            "order": 3,
                            "cst_num": "21",
                            "repeat_cst_num": "24"
                        },
                        {
                            "id": "etX2IKEAAAWibUg5kpTX8ncPZa_egctg",
                            "name": "33",
                            "create_time": 1639388011,
                            "order": 2,
                            "cst_num": "34",
                            "repeat_cst_num": "37"
                        },
                        {
                            "id": "etX2IKEAAARDmz_SidVRl8URdZ18bH5g",
                            "name": "22",
                            "create_time": 1639388011,
                            "order": 1,
                            "cst_num": "44",
                            "repeat_cst_num": "50"
                        },
                        {
                            "id": "etX2IKEAAAAmQIcqXwwwePc6y0oQullw",
                            "name": "关键词打标签-群聊测试1",
                            "create_time": 1689230170,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAohAeBYNhRtSSLyZUBEyaGQ",
                            "name": "关键词打标签-群聊测试2",
                            "create_time": 1689230231,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAQADZYwbLZljK2AFD8BPBGg",
                            "name": "关键词打标签-群聊测试3",
                            "create_time": 1689230254,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAf7T9RCGl2Z1mHbXnWfKqWA",
                            "name": "测试23",
                            "create_time": 1689238061,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAALx3FsgpycRghwpaFf0TmFQ",
                            "name": "标签33",
                            "create_time": 1689238083,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAnpDvb8taFUUlnCQMDhCD4w",
                            "name": "测试55",
                            "create_time": 1689238531,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAWCyFwc4uWYJDZ2uWGRr5pw",
                            "name": "测试66",
                            "create_time": 1689239315,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAA943X6kMXqSJjl6QnLBd7jQ",
                            "name": "测试77",
                            "create_time": 1689239325,
                            "order": 1,
                            "cst_num": "34",
                            "repeat_cst_num": "34"
                        },
                        {
                            "id": "etX2IKEAAAgGZItqQsQLCRmfnTHE--NA",
                            "name": "测试名字很长很长很长很长很长很长很长",
                            "create_time": 1698394284,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        }
                    ],
                    "order": 9999908,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAAlnQDB6SKgqgEBzJXtZLncQ",
                    "group_name": "标签组1",
                    "create_time": 1640654095,
                    "tag": [
                        {
                            "id": "etX2IKEAAARoA5SfC-OIPjiDZzyFJKsw",
                            "name": "客户苹果",
                            "create_time": 1660187392,
                            "order": 9999907,
                            "cst_num": "2",
                            "repeat_cst_num": "5"
                        },
                        {
                            "id": "etX2IKEAAAuTlWgE1H-Z5C85lCmw3bSg",
                            "name": "员工猕猴桃",
                            "create_time": 1660190501,
                            "order": 9999906,
                            "cst_num": "1",
                            "repeat_cst_num": "4"
                        },
                        {
                            "id": "etX2IKEAAAVn6WzOSijp554OzX-OhDxQ",
                            "name": "员工桃子",
                            "create_time": 1660189730,
                            "order": 9999905,
                            "cst_num": "1",
                            "repeat_cst_num": "4"
                        },
                        {
                            "id": "etX2IKEAAAtPfABe5f9HCJHYS-xQW1mA",
                            "name": "客户葡萄",
                            "create_time": 1660190990,
                            "order": 9999903,
                            "cst_num": "1",
                            "repeat_cst_num": "4"
                        },
                        {
                            "id": "etX2IKEAAAkrYCb6i3soRBl0pG2BUGUQ",
                            "name": "员工香蕉",
                            "create_time": 1660188015,
                            "order": 9999902,
                            "cst_num": "1",
                            "repeat_cst_num": "4"
                        },
                        {
                            "id": "etX2IKEAAAbZDjCFWwCxYS0iNASR1EqQ",
                            "name": "关键词打标签-单聊测试1",
                            "create_time": 1689231980,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAjjoEC88jIZwlaNqX4xlFTg",
                            "name": "关键词打标签-单聊测试2",
                            "create_time": 1689231997,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAOd3Z50lExpV5GAqdhQADIA",
                            "name": "关键词打标签-单聊测试3",
                            "create_time": 1689232019,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAACRIwx71B-NtztR5cKV7OBg",
                            "name": "会话标签1",
                            "create_time": 1698748687,
                            "order": 1,
                            "cst_num": "35",
                            "repeat_cst_num": "35"
                        },
                        {
                            "id": "etX2IKEAAASoApz5af8jPsnvvNQNcZ4A",
                            "name": "会话标签2",
                            "create_time": 1698748700,
                            "order": 1,
                            "cst_num": "34",
                            "repeat_cst_num": "34"
                        },
                        {
                            "id": "etX2IKEAAAWKQa-YHAHskjcfPd9eiJDw",
                            "name": "会话标签3",
                            "create_time": 1698748707,
                            "order": 1,
                            "cst_num": "34",
                            "repeat_cst_num": "34"
                        },
                        {
                            "id": "etX2IKEAAAOqwDEQhzLL7HXgDPgV5RTQ",
                            "name": "会话标签4",
                            "create_time": 1698748713,
                            "order": 1,
                            "cst_num": "41",
                            "repeat_cst_num": "45"
                        },
                        {
                            "id": "etX2IKEAAAxoM_YBvg5L8FK4Jwg5RemQ",
                            "name": "会话标签5",
                            "create_time": 1698748880,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAH4BRiPkKVmw1e2FywzKgYg",
                            "name": "会话标签6",
                            "create_time": 1698748886,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAMKj7rJ8gTThn8YBlZbL3nQ",
                            "name": "11",
                            "create_time": 1640654095,
                            "order": 0,
                            "cst_num": "34",
                            "repeat_cst_num": "37"
                        },
                        {
                            "id": "etX2IKEAAAiNenoqO9Si6LPW_AYCbxaQ",
                            "name": "22",
                            "create_time": 1640654095,
                            "order": 0,
                            "cst_num": "1",
                            "repeat_cst_num": "4"
                        }
                    ],
                    "order": 9999907,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAA2iqUmhINeWBKayqFdFsbnw",
                    "group_name": "跟进阶段",
                    "create_time": 1683168652,
                    "tag": [
                        {
                            "id": "etX2IKEAAAR-oo9r6_jiFoFCow4_AQaw",
                            "name": "产品演示",
                            "create_time": 1683168652,
                            "order": 9999908,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAkD3zydNT9TTzbpNOPTJCyA",
                            "name": "合同报价",
                            "create_time": 1683168652,
                            "order": 9999907,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAA9SJeSwSadEkuKJ4zTBcRGg",
                            "name": "已加微信",
                            "create_time": 1683168652,
                            "order": 9999906,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAkXtVDWGfy35X_OXiQDK1CA",
                            "name": "新线索",
                            "create_time": 1683168652,
                            "order": 9999905,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAeTlionqhUMKGxYOcb4u8nw",
                            "name": "无效",
                            "create_time": 1683168652,
                            "order": 9999904,
                            "cst_num": "1",
                            "repeat_cst_num": "4"
                        },
                        {
                            "id": "etX2IKEAAAEEHhV96nLUHvLWAAGKVA7w",
                            "name": "成交",
                            "create_time": 1683168652,
                            "order": 9999903,
                            "cst_num": "1",
                            "repeat_cst_num": "4"
                        },
                        {
                            "id": "etX2IKEAAAcA-itbznVSgMcZQ7qLhGpA",
                            "name": "电话沟通",
                            "create_time": 1683168652,
                            "order": 9999902,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAARVzZQ05cj_oQeBfhibhWHg",
                            "name": "输单",
                            "create_time": 1683168652,
                            "order": 9999901,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        }
                    ],
                    "order": 9999906,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAAe50ZV-LEKTrtYF8XS8rVsQ",
                    "group_name": "行业",
                    "create_time": 1683168652,
                    "tag": [
                        {
                            "id": "etX2IKEAAACsQJ2X31YWC9265dzuidVg",
                            "name": "IT服务",
                            "create_time": 1683168652,
                            "order": 9999909,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAOmsbJJpnhZx5GO3xbEw2-A",
                            "name": "互联网\/IT",
                            "create_time": 1683168652,
                            "order": 9999908,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAANYqKPyUq3qXwXNuXOhp4lw",
                            "name": "企业服务",
                            "create_time": 1683168652,
                            "order": 9999907,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAA-yT5EyWBf9lBt8Q-AZjpXg",
                            "name": "其他行业",
                            "create_time": 1683168652,
                            "order": 9999906,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAOjIeBVi6LYM-8zW-oeIdAA",
                            "name": "医美",
                            "create_time": 1683168652,
                            "order": 9999905,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAe0pHO97tOPiVQnbtLBFIhg",
                            "name": "教育",
                            "create_time": 1683168652,
                            "order": 9999904,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAA2AH7anYO8Mbvg5iGwRo0Qg",
                            "name": "家装",
                            "create_time": 1683168652,
                            "order": 9999903,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAABQiStMiIJNPAacxszD5wWQ",
                            "name": "金融",
                            "create_time": 1683168652,
                            "order": 9999902,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAANmdXMo-iaNig331QAw7kug",
                            "name": "房地产",
                            "create_time": 1683168652,
                            "order": 9999901,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        }
                    ],
                    "order": 9999905,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAA-hlzJcEfyjF5svy85dHugg",
                    "group_name": "标签",
                    "create_time": 1683168653,
                    "tag": [
                        {
                            "id": "etX2IKEAAAWSPeZ774w5vuLunNJlvdIA",
                            "name": "A类",
                            "create_time": 1683168653,
                            "order": 9999904,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAp7rEhM4GhVRe3c8xmTxrlA",
                            "name": "B类",
                            "create_time": 1683168653,
                            "order": 9999903,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAApajZ499in9SRtfWM6JX10g",
                            "name": "C类",
                            "create_time": 1683168653,
                            "order": 9999902,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAA2QvjKpl9S8LRMSzUxgKLGg",
                            "name": "D类",
                            "create_time": 1683168653,
                            "order": 9999901,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        }
                    ],
                    "order": 9999904,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAAzJwGj5l2VMkyLujijrCyJA",
                    "group_name": "来源",
                    "create_time": 1683168654,
                    "tag": [
                        {
                            "id": "etX2IKEAAAN7r3fjzYrGpqFrZ0vEiWbA",
                            "name": "其它",
                            "create_time": 1683168654,
                            "order": 9999905,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAXAJYuN62a0qV_1iTUsxeYA",
                            "name": "官网注册",
                            "create_time": 1683168654,
                            "order": 9999904,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAA-HUrJZGstlLFRCOUDPWQLA",
                            "name": "采购线索",
                            "create_time": 1683168654,
                            "order": 9999903,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAsdvDXr6ZxPiFTMT78oTpng",
                            "name": "线下活动",
                            "create_time": 1683168654,
                            "order": 9999902,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAm4yMb2pjwiGPlT-JWflx5A",
                            "name": "销售自拓",
                            "create_time": 1683168654,
                            "order": 9999901,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAzOMFCnYrCJ6Gt8M-U5LuqQ",
                            "name": "企搜宝",
                            "create_time": 1683505728,
                            "order": 0,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        }
                    ],
                    "order": 9999903,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAA53rMkOKDpdYR3xt1tIrR_A",
                    "group_name": "年龄范围",
                    "create_time": 1683168655,
                    "tag": [
                        {
                            "id": "etX2IKEAAAN3dJ5v7lrzcG7B68-yis5A",
                            "name": "中老年",
                            "create_time": 1683168655,
                            "order": 9999903,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAaRFD8wD2bToVJ1TNUmnxrg",
                            "name": "中青年",
                            "create_time": 1683168655,
                            "order": 9999902,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAZTcIkv-RmTguJnqevQACAQ",
                            "name": "青少年",
                            "create_time": 1683168655,
                            "order": 9999901,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        }
                    ],
                    "order": 9999902,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAAmbJOKmhhGvas-tMbNte-IA",
                    "group_name": "性别",
                    "create_time": 1683168656,
                    "tag": [
                        {
                            "id": "etX2IKEAAAZXundaXlTzlggSGGe_NwPA",
                            "name": "女",
                            "create_time": 1683168656,
                            "order": 9999901,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAq1RF3IbWD0sPKwHATFB3nA",
                            "name": "23-05-17",
                            "create_time": 1684312487,
                            "order": 1,
                            "cst_num": "2",
                            "repeat_cst_num": "4"
                        },
                        {
                            "id": "etX2IKEAAAnBkHq_5P62Hsb6uRJf3BtQ",
                            "name": "23-05-15",
                            "create_time": 1684719968,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAMTNzfQlq2prhZPzZpq35Aw",
                            "name": "23-05-23",
                            "create_time": 1684832580,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAA6tTGl85nbV31D3uM32-wVg",
                            "name": "23-05-24",
                            "create_time": 1684894828,
                            "order": 1,
                            "cst_num": "3",
                            "repeat_cst_num": "4"
                        },
                        {
                            "id": "etX2IKEAAAUXNf8q_RXEZOdxT8manEww",
                            "name": "22-09-29",
                            "create_time": 1684902614,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAL40d4E-0X4XVo7XoDrSxzg",
                            "name": "23-06-02",
                            "create_time": 1685702993,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAS-02j9u7JJ-wLpFTOkjYxQ",
                            "name": "23-06-05",
                            "create_time": 1685946934,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAABTM1HF8wilpLv65VpolaoA",
                            "name": "23-06-06",
                            "create_time": 1686045994,
                            "order": 1,
                            "cst_num": "2",
                            "repeat_cst_num": "2"
                        },
                        {
                            "id": "etX2IKEAAAKcYEiBWLxW221jm6BJ4vfQ",
                            "name": "23-06-21",
                            "create_time": 1687340388,
                            "order": 1,
                            "cst_num": "2",
                            "repeat_cst_num": "2"
                        },
                        {
                            "id": "etX2IKEAAAvt6AigslNud4VX5Q5r1zgg",
                            "name": "23-07-06",
                            "create_time": 1688613646,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAA_b3fufbCARfGIPhb3S8PDg",
                            "name": "23-07-12",
                            "create_time": 1689153153,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAvTvfsp_6jnReXu57Sw1E7Q",
                            "name": "23-07-13",
                            "create_time": 1689238988,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAu1uLhWa5mQAcLgNwaNv7gg",
                            "name": "23-07-28",
                            "create_time": 1690538220,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAb1cgXNePdoMpL9wtQSnZZg",
                            "name": "23-08-01",
                            "create_time": 1690858042,
                            "order": 1,
                            "cst_num": "2",
                            "repeat_cst_num": "2"
                        },
                        {
                            "id": "etX2IKEAAAeqaHZFn79zUeL41zitq_xw",
                            "name": "23-08-07",
                            "create_time": 1691392390,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAZ8mzWxSIlsJ7yk5K3qzy6w",
                            "name": "男",
                            "create_time": 1683168656,
                            "order": 0,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        }
                    ],
                    "order": 9999901,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAAacko0hoclN28pUzCdPrD-w",
                    "group_name": "测试2",
                    "create_time": 1639388019,
                    "tag": [
                        {
                            "id": "etX2IKEAAAltNVTqW5o0k0SozpurVaEQ",
                            "name": "进入表单",
                            "create_time": 1683865509,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAokHAs83b5m8h8OhWimVTGQ",
                            "name": "提交表单",
                            "create_time": 1683865517,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAkTOu-7Zd-nQNl5mtIvnsnQ",
                            "name": "小店自动打标签",
                            "create_time": 1684998385,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAWa6_UexB2-WzYQT14aq49w",
                            "name": "44",
                            "create_time": 1639388019,
                            "order": 0,
                            "cst_num": "27",
                            "repeat_cst_num": "37"
                        },
                        {
                            "id": "etX2IKEAAAjwKe9cLY2uvFRezd6BflAw",
                            "name": "55",
                            "create_time": 1639388019,
                            "order": 0,
                            "cst_num": "22",
                            "repeat_cst_num": "32"
                        },
                        {
                            "id": "etX2IKEAAAtxtvrzw7tID9nSnK7v4VFw",
                            "name": "66",
                            "create_time": 1639388019,
                            "order": 0,
                            "cst_num": "3",
                            "repeat_cst_num": "3"
                        }
                    ],
                    "order": 1,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAAoH--M9pT5Wqj4Ji7RnPMcQ",
                    "group_name": "test",
                    "create_time": 1684736227,
                    "tag": [
                        {
                            "id": "etX2IKEAAAcvyu-v31GwYQELPsT8v80w",
                            "name": "parker1417",
                            "create_time": 1684736227,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAXr5N4rrWFDntjdZSF9fcdA",
                            "name": "Andy测试",
                            "create_time": 1685090878,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAWwdCP6XGKAqZ34x2CmRqqg",
                            "name": "lacy红包测试",
                            "create_time": 1705041102,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAMYlxyi6bvbruL1L0Wz5KHw",
                            "name": "lacy红包测试2",
                            "create_time": 1705288754,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        },
                        {
                            "id": "etX2IKEAAAfdkQJpKU6Q8KJAKepYU6VQ",
                            "name": "lacy红包测试3",
                            "create_time": 1705305057,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        }
                    ],
                    "order": 0,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAAOEipu_zenzdUJ5tDuENdcA",
                    "group_name": "11",
                    "create_time": 1672797659,
                    "tag": [
                        {
                            "id": "etX2IKEAAAGvKIKzjmkfkUmbK4H-giPA",
                            "name": "11",
                            "create_time": 1672797659,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        }
                    ],
                    "order": 0,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAA2mrc2olowgRnINzFXMXjeA",
                    "group_name": "芝麻微客标签",
                    "create_time": 1666083349,
                    "tag": [
                        {
                            "id": "etX2IKEAAAip67QTJO3BKA6YvO53zTXw",
                            "name": "流失客户-芝麻",
                            "create_time": 1666083349,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAsvMEmyCLcie1tH6Iw6xb7Q",
                            "name": "重复客户",
                            "create_time": 1714992237,
                            "order": 1,
                            "cst_num": 0,
                            "repeat_cst_num": 0
                        }
                    ],
                    "order": 0,
                    "inner_tag": true,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAAkq_9QQD9jmSl7CruH00BWA",
                    "group_name": "数据互通",
                    "create_time": 1658981624,
                    "tag": [
                        {
                            "id": "etX2IKEAAAVtr-t7RdWsnhsaYuWMJieQ",
                            "name": "默认腾讯广告规则",
                            "create_time": 1658992280,
                            "order": 1,
                            "cst_num": "4",
                            "repeat_cst_num": "10"
                        },
                        {
                            "id": "etX2IKEAAAUfbs9tLXRPTbAdRlFv4xaw",
                            "name": "腾讯广告",
                            "create_time": 1658981624,
                            "order": 0,
                            "cst_num": "20",
                            "repeat_cst_num": "30"
                        },
                        {
                            "id": "etX2IKEAAALB3mPzAib5QzlnkPN22X7g",
                            "name": "腾讯广告2",
                            "create_time": 1658982168,
                            "order": 0,
                            "cst_num": "20",
                            "repeat_cst_num": "30"
                        }
                    ],
                    "order": 0,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAAGP6crxDlGGGAmoYnyF7DtQ",
                    "group_name": "芝麻-公众号粉丝",
                    "create_time": 1657008946,
                    "tag": [
                        {
                            "id": "etX2IKEAAAttCo21QmTcvMpFSNid6nTg",
                            "name": "虎秀的粉丝",
                            "create_time": 1657008946,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        }
                    ],
                    "order": 0,
                    "inner_tag": false,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                },
                {
                    "group_id": "etX2IKEAAAB7beVrEAxSbuuYaVXRaoMA",
                    "group_name": "日期标签",
                    "create_time": 1655263912,
                    "tag": [
                        {
                            "id": "etX2IKEAAAe5GHzovBjkQLzf9uJzi4Ww",
                            "name": "2022\/06\/16",
                            "create_time": 1655265184,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAFUMorpafjSmT79pw93RCOg",
                            "name": "2022-09-09",
                            "create_time": 1662708488,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAyE_jFZH_BWQ4wB0LNqRL8g",
                            "name": "2022-09-27",
                            "create_time": 1664262184,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAmY1VgAWCecrPlxYAyeRX2Q",
                            "name": "2023-3-6",
                            "create_time": 1678089563,
                            "order": 1,
                            "cst_num": "2",
                            "repeat_cst_num": "3"
                        },
                        {
                            "id": "etX2IKEAAA-XgY6BsfIPqnqE2cZ2zY0A",
                            "name": "2023-04-26",
                            "create_time": 1682486598,
                            "order": 1,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAALWiWdQx88Z5GcwKGHZC9-A",
                            "name": "2022年06月15日",
                            "create_time": 1655263912,
                            "order": 0,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        },
                        {
                            "id": "etX2IKEAAAdhfDTBXVlIG4e6hsWaW4RA",
                            "name": "44444",
                            "create_time": 1655264965,
                            "order": 0,
                            "cst_num": "1",
                            "repeat_cst_num": "1"
                        }
                    ],
                    "order": 0,
                    "inner_tag": true,
                    "visible_range_list": [],
                    "editable_range_list": [],
                    "cannt_edit": false
                }
            ]
            labelNum.value = 1

    //     dataArr.value.map((el) => {
    //     labelNum.value += el.tag.length
    //     })
    // })
}
//导出
const exportTo = () => {
    window.location.href = '/tag/export-cst-tag-data'
}

const Refresh = ref(false)

//更新标签数据
const updateLabel = () => {
    // Refresh.value = true
    // tagRefreshCstNum()
    // .finally(() => {
    //     Refresh.value = false
    // })
    // .then((res) => {
        tags_lists()
    // })
}

const showDrawer = (item) => {

}

const getSearName = (item, type) => {
    let name = item.group_name || item.name
    let count = item.repeat_cst_num || 0
    let str = searchValue.value
    let names = name.split(str)
    let nameStr = ''
    if (names.length > 1) {
        nameStr = names.join(`<span style="color: #ED744A;">${str}</span>`)
    }
    if(type == 1){
        return searchValue.value && nameStr ? nameStr : name
    }
    return (searchValue.value && nameStr ? nameStr : name ) + `(${count})`
}

onMounted(() => {
    tags_lists()
})
</script>
<style lang="less">
  .tag-manage {
    margin: 12px;

    .explain {
      margin-bottom: 8px;
      font-size: 14px;
      color: rgba(0, 0, 0, 0.45);
      span {
        color: rgba(0, 0, 0, 0.65);
      }
    }
    .ant-modal-body {
      padding: 19px 32px;
      max-height: 500px;
      overflow-y: auto;
    }
    .ant-btn > .anticon + span,
    .ant-btn > span + .anticon {
      margin-left: 2px;
    }
    .ant-modal-content .ant-btn-link {
      padding: 0;
    }
    .top {
      padding: 16px 32px;
      background-color: #fff;
      margin-bottom: 24px;
      .item {
        margin-right: 16px;
        &:last-child {
          margin-right: 0;
        }
      }
    }
  }
  </style>
<style lang="less" scoped>
  .mr10 {
    margin-right: 10px;
  }
  .ml16 {
    margin-left: 16px;
  }
  .mr16 {
    margin-right: 16px;
  }
  .mr24 {
    margin-right: 24px;
  }
  .mb18 {
    margin-bottom: 18px;
  }
  .mb10{
    margin-bottom: 10px;
  }
  .content {
    background: #ffffff;
    border-radius: 2px;
    padding: 20px 24px;
    .data-presentation {
      margin: 16px 0;
      .data-item {
        padding: 16px;
        width: 242px;
        margin-right: 24px;
        background: rgba(0, 0, 0, 0.02);
        &:last-child {
          margin-right: 0;
        }
        .data-title {
          font-size: 14px;
          color: rgba(0, 0, 0, 0.45);
        }
        .data-num {
          font-size: 30px;
          color: rgba(0, 0, 0, 0.85);
          margin-top: 8px;
          font-weight: bold;
        }
      }
    }
    .list {
      font-size: 16px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: #000000;
      display: flex;
      border-bottom: 1px solid #e8e8e8;
      // justify-content: space-between;
      line-height: 24px;
      padding-bottom: 10px;
      flex-wrap: wrap;
      .lefts {
        margin-left: 10px;
      }
      .lefts_size {
        margin-left: 10px;
        color: #595959;
      }
      span {
        font-size: 13px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(0, 0, 0, 0.45);
        line-height: 22px;
      }
    }
  }
  .label_list_head {
    display: inline-block;
    height: 100%;
    width: 10%;
    float: left;
    word-wrap: break-word;
    margin-right: 10px;
    font-size: 14px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(0, 0, 0, 0.85);
    .label-num {
      font-size: 14px;
      color: rgba(0, 0, 0, 0.45);
    }
  }
  .label_list {
    padding: 10px 5px;
    justify-content: space-between;
    border-bottom: 1px solid rgba(232, 232, 232, 1);
    .label_list_width {
      display: inline-block;
      width: 90%;
    }
    .label_list_edit {
      float: right;
      cursor: pointer;
      margin-right: 10px;
      margin-top: 14px;
      &:hover {
        color: rgba(36, 117, 252, 1);
      }
    }
    .label_list_li {
      cursor: pointer;
      list-style: none;
      padding: 1px;
      margin-bottom: 0;
      display: inline-block;
      height: 100%;
      width: 87%;
      li {
        display: inline-block;
        padding: 3px 8px;
        font-size: 12px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(0, 0, 0, 0.65);
        line-height: 20px;
        margin-bottom: 10px;
        margin-right: 10px;
        background: rgba(0, 0, 0, 0.04);
        border-radius: 2px;
        border: 1px solid rgba(0, 0, 0, 0.15);
        &:hover {
          border: 1px solid rgba(36, 117, 252, 1);
          color: rgba(36, 117, 252, 1);
        }
      }
    }
  }
  </style>
