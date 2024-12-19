<template>
    <!----按群聊查看---->
    <div class="session-main-container">
        <FilterBoxByGroup class="filter-block" @change="search"/>
        <div id="sessionMainContent" class="session-main-content">
            <DragStretchBox
                @change="panelBlockWidthChange"
                :min-width="panelWin.leftMinWith"
                :max-width="panelWin.leftMaxWith"
                id="sessionLeftBlock"
                class="session-left-block">
                <div class="header">
                    <div class="title">群聊<span class="staff-num">（{{ main.groupCount }}）</span></div>
                </div>
                <GroupBox class="main-content-box"
                          :key="groupCompKey"
                          :default="defaultParams"
                          :filterData="filterData"
                          @change="groupChange"
                          @totalReport="totalReport"/>
            </DragStretchBox>
            <ChatBox ref="chatRef"
                     mainTab="LOAD_BY_GROUP"
                     class="session-right-block"
                     :chatInfo="chatInfo"
                     @changeCollect="onChangeCollect"
                     sessionType="group"/>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref, reactive, computed, nextTick, watch} from 'vue';
import {panelWinHandle} from "@/views/sessionArchive/components/panelWinHandle";
import ChatBox from "@/views/sessionArchive/components/modules/chatBox.vue";
import GroupBox from "@/views/sessionArchive/components/modules/groupBox.vue";
import FilterBoxByGroup from "@/views/sessionArchive/components/filter/filterBoxByGroup.vue";
import DragStretchBox from "@/components/dragStretchBox.vue";

const props = defineProps({
    defaultParams: {
        type: Object,
    }
})
const cacheKey = 'zm:session:archive:win:box:width:load:by:group';
const {panelWin, panelBlockWidthChange} = panelWinHandle(cacheKey)
const groupCompKey = ref(1)
const chatRef = ref(null)
const filterData = ref(null)
const main = reactive({
    groupCount: 0,
    selectedGroup: null,
})

const chatInfo = computed(() => {
    // console.log('main', main)
    if (!main.selectedGroup?.chat_id) {
        return null
    }
    return {
        params: {
            group_chat_id: main.selectedGroup?.chat_id,
            conversation_id: main.selectedGroup?.conversations_id,
            is_collect: main.selectedGroup?.is_collect,
            collect_reason: main.selectedGroup?.collect_reason
        },
        receiver: {
            name: main.selectedGroup?.name,
            chat_id: main.selectedContact?.chat_id,
            zm_user_type: 'GROUP'
        }
    }
})

const search = val => {
    filterData.value = val
    main.groupCount = 0
    main.selectedGroup = null
    groupCompKey.value += 1
}

const loadSessionMsg = () => {
    nextTick(() => {
        chatRef.value.init()
    })
}

const groupChange = group => {
    main.selectedGroup = group
    loadSessionMsg()
}

const totalReport = val => {
    main.groupCount = val
}

const callbackData = ref({})
const onChangeCollect = (obj) => {
    // console.log('items', obj)
    callbackData.value = obj
    main.selectedGroup.conversations_id = obj.conversation_id
    main.selectedGroup.is_collect = obj.is_collect
    main.selectedGroup.collect_reason = obj.collect_reason
}
</script>

<style scoped lang="less">
.session-main-container {
    background: #FFF;
    font-size: 12px;
    margin: 12px;
    display: flex;
    flex-direction: column;
    height: calc(100vh - 126px); // 窗口 - 顶部菜单 - 面包屑 - padding（24）
    border-radius: 6px;

    .filter-block {
        flex-shrink: 0;
    }

    .session-main-content {
        flex: 1;
        display: flex;
        overflow: hidden;

        > div {
            height: 100%;
            //overflow: auto;
        }
    }

    .session-left-block {
        border-right: 1px solid rgba(5, 5, 5, 0.06);
        flex-shrink: 0;
        width: 300px;
        min-width: 230px;;
        max-width: 60vw;
        position: relative;

        &:hover .drag-line {
            display: block;
        }

        .header {
            padding: 12px 16px;
            height: 41px;
            display: flex;
            align-items: center;
            justify-content: space-between;

            .title {
                font-size: 12px;
                font-weight: 600;
                color: rgba(0, 0, 0, 0.85);
                display: flex;
                align-items: baseline;
                white-space: nowrap;
                text-overflow: ellipsis;
                overflow: hidden;
            }

            .staff-num {
                white-space: nowrap;
                display: inline-block;
                max-width: 140px;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        .main-content-box {
            height: calc(100% - 41px);
        }
    }

    .session-right-block {
        flex: 1;

        .content-block {
            display: flex;

            .center-block {
                border-right: 1px solid rgba(5, 5, 5, 0.06);
            }

            .right-block {
                flex: 1;
            }
        }
    }
}

.drag-line {
    display: none;
    position: absolute;
    right: -15px;
    top: 0;
    bottom: 0;
    width: 30px;
    z-index: 99;
    cursor: col-resize;
}

.drag-line.large {
    right: -100px;
    width: 200px;
    //background: rgba(0, 0, 0, 0.5);
}
</style>
