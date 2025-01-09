<template>
  <div>
    <a-button
      :type="btnType"
      :style="btnStyle"
      @click="onShowStaff"
      >
      <PlusOutlined />
      {{btnText}}
    </a-button>
    <div style="display: flex;flex-wrap: wrap" v-if="showSelectStaff">
      <a-tag
        v-for="(item, index) in selectStaff"
        :key="item.userid"
        closable
        @close="deleteStaff(index)"
        style="margin-top: 6px"
        class="fx-ac tag"
        :class="{'red-tip': showSeatTip && !item.is_bind_license}"
      >

        <a-tooltip>
          <div class="fx-ac" v-if="showSeatTip" style="flex: 1;">
            <a-avatar :size="20" :src="defaultImg" style="margin-right: 4px;"></a-avatar>
            <span>{{ item.name }}</span>
          </div>
        </a-tooltip>
        <div class="fx-ac" v-if="!showSeatTip" style="flex: 1;">
          <a-avatar :size="20" :src="defaultImg" style="margin-right: 4px;"></a-avatar>
          <span>{{ item.name }}</span>
        </div>
      </a-tag>
    </div>

    <selectStaffNew
            selectType="multiple"
            ref="setStaff"
            @change="(val) => staffUpdate(val)"
    ></selectStaffNew>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { PlusOutlined } from '@ant-design/icons-vue';
import selectStaffNew from '@/components/select-staff-new/index'
import defaultImg from '@/assets/default-avatar.png'

const emit = defineEmits('change')
const props = defineProps({
    title: {
      type: String,
      default: "选择员工",
    },
    btnText: {
      type: String,
      default: "添加员工",
    },
    // 按钮样式
    btnType: {
      type: String,
      default: "dashed",
    },
    // 样式
    btnStyle: {
      type: String,
      default: "margin-right: 16px;",
    },
    // 是否显示底部的已选员工列表
    showSelectStaff:{
      type:Boolean,
      default:true
    },
    // 打开员工选择弹窗时清空已选的员工
    showClearSelectStaff:{
      type:Boolean,
      default:false
    },
    selectType: {
      default: "multiple",
    },
    // 是否显示员工实名认证提示
    showStaffApproveTip: {
      default: false,
    },
    selectedStaffs: {
      type: Array,
      default() {
        return []
      }
    },
    // 是否显示tag标签
    isTag:{
      type:Boolean,
      default:true
    },
    //席位提示
    showSeatTip: {
      type: Boolean,
      default: false,
    },
    maxShowWidth: {
    }
})

const setStaff = ref(null)

const selectStaff = computed(() => props.selectedStaffs)

const staffUpdate = (val) => {
    selectStaff.value = val;
    emit("change", val || []);
}

const deleteStaff = (index) => {
    selectStaff.value.splice(index, 1);
    emit("change", selectStaff.value);
}

const onShowStaff = () => {
    if (props.showClearSelectStaff) {
        setStaff.value.show([])
        return
    }
    setStaff.value.show(selectStaff.value)
}

onMounted(() => {
})
</script>

<style scoped lang="less">
/deep/ .ant-tag {
  padding: 5px 16px;
  background: #F5F5F5;
}
.red-tip{
  border-color: red;
}
.staff-list{
  transform: translate3d(0, 0, 0);
  position: relative;
  z-index: 998;
  overflow: auto;
  resize: both;
  border: 1px solid rgba(0, 0, 0, 0.15);
  max-width: 46vw;
  min-width: 545px;
  min-height: 47px;
  max-height: 400px;
  padding: 4px;
  // display: grid;
  // row-gap:5px;
  // column-gap:5px;
  // grid-template-columns: repeat(3, 1fr);
  // grid-auto-rows: 36px; //高度固定
  width: 100vw;
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  flex-direction: row;
  align-content: flex-start;
  height: 184px;
  .tag{
    width: 32%;
    height: 35px;
    text-align: left;
    position: relative;
    padding: 0 12px 2px 8px;
    border-radius: 3px;
    // transform: translate3d(0, 0, 0);
    display: flex;
    justify-content: flex-start;
    align-items: center;
    margin-right: 1.3%;
    margin-bottom: 10px;
    &:nth-child(3n){
      margin-right: 0px;
    }
  }
}
</style>
