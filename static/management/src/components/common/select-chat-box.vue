<template>
  <div>
    <a-button
        :icon="h(PlusOutlined)"
        type="dashed"
        class="mr16"
        @click="show">选择群聊 ({{ total }})</a-button>
    <div class="mt8" v-if="selectChat?.length > 0">
      <a-table
        class="mt16 select-group-table"
        :data-source="selectChat"
        :columns="columns"
        :pagination="pagination"
        :scroll="{y: 600}"
        @change="tableChange"
      >
        <template #bodyCell="{ column, text, record }">
          <template v-if="'operate' === column.dataIndex">
              <a class="operate-content" @click="del(record)">移除</a>
          </template>
        </template>
      </a-table>
    </div>
    <!-- 选择群聊 -->
    <SelectGroupChat
      ref="selectGroupChat"
      @selectedGroupChat="change"
    ></SelectGroupChat>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, reactive, h} from 'vue'
import {PlusOutlined} from '@ant-design/icons-vue';
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
//const selectChat = computed(() => props.selectedChats || [])
const selectChat = ref([])
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
        width: 100,
        fixed: 'right'
    }
])

watch(() => props.selectedChats, (n, o) => {
    selectChat.value = props.selectedChats
    total.value = props.selectedChats.length
}, {
    immediate: true
})
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

//.select-group-table {
//  width: 500px;
//}
</style>
