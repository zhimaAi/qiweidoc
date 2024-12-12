<template>
  <div>
    <a-button
      type="dashed"
      style="margin-right: 16px;"
      @click="show"
      >选择群聊 ({{ total }})
    </a-button>
    <div style="margin-top: 6px">
      <!-- <template v-for="(item, index) in selectChat" :key="item._id">
        <a-tag
          closable
          @close="del(index)"
          style="margin-bottom: 10px"
        >
          <a-space :size="3" align="center">
            <span>{{ item.name }}</span>
          </a-space>
        </a-tag>
      </template> -->
      <a-table
        class="mt16 select-group-table"
        :data-source="selectChat"
        :columns="columns"
        :pagination="pagination"
        @change="tableChange"
      >
        <template #bodyCell="{ column, text, record }">
          <template v-if="'operate' === column.dataIndex">
              <a class="operate-content ml16" @click="del(record)">移除</a>
          </template>
        </template>
      </a-table>
    </div>

    <!-- <select-group-chat ref="SetChat" @selectChange="change"></select-group-chat> -->

    <!-- 选择群聊 -->
    <SelectGroupChat
      ref="selectGroupChat"
      @selectedGroupChat="change"
    ></SelectGroupChat>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue'
// import SelectGroupChat from "./select-group-chat";
import SelectGroupChat from "@/components/select-group-chat";
import {groupsList} from "@/api/company";

const total = ref(0)
const emit = defineEmits('change')
const props = defineProps({
    selectedChats: {
      type: Array,
      default() {
        return [];
      },
    }
})
const selectGroupChat = ref(null)
const selectChat = computed(() => props.selectedChats || [])
const pagination = reactive({
    total: 0,
    current: 1,
    pageSize: 8,
    showSizeChanger: true,
    pageSizeOptions: ['10', '20', '50', '100']
})
const columns = reactive([
    {
        title: "群聊名称",
        dataIndex: "name",
        width: 180
    },
    {
        title: "群主",
        dataIndex: "owner_name",
        width: 180
    },
    {
        title: "操作",
        dataIndex: "operate",
        width: 160
    }
])

const show = () => {
    selectGroupChat.value.show(selectChat.value);
}

const change = (val) => {
    selectChat.value = val;
    total.value = val.length
    emit("change", val);
}

const del = (index) => {
    selectChat.value.splice(index, 1);
    total.value = selectChat.value.length
    emit("change", selectChat.value);
}

const tableChange = (p) => {
    pagination.current = p.current
    pagination.pageSize = p.pageSize
}

const loadData = () => {
  let params = {
    page: 1,
    size: 1
  }
  groupsList(params).then(res => {
    total.value = Number(res.data?.total)
  })
}

onMounted(() => {
//   loadData()
})
</script>

<style scoped lang="less">
/deep/ .ant-tag {
  padding: 5px 16px;
  background: #ffffff;
}

.select-group-table {
  width: 500px;
}
</style>
