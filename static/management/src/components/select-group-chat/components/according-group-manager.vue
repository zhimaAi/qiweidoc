<template>
  <div class="according-group-manager">
    <a-button type="link" class="button" @click="selectGroupManager">
      选择群主
    </a-button>
    <div class="flex-wrap tag-box" ref="tagBoxRef" v-if="tagShow">
      <a-tag
        class="tag fx-ac"
        :class="{ 'tag-width': isWidth }"
        closable
        @close="delTag(index)"
        v-for="(item, index) in staffList"
        :key="index"
      >
        <img :src="item.avatar" alt="" class="img" />
        <div class="name omit">{{ item.name }}</div>
      </a-tag>
    </div>

    <!-- 选择群主 -->
    <SelectStaffNew
      ref="selectTagGroup"
      selectType="multiple"
      title="选择群主"
      @change="staffUpdate"
      :viewAppointPermissions="viewAppointPermissions"
    ></SelectStaffNew>
  </div>
</template>

<script>
import SelectStaffNew from "@/components/select-staff-new/index";
export default {
  name: "AccordingGroupManager",
  props: {
    active: {
      type: String,
      default: "",
    },
    getStaffList: {
      type: Array,
      default: ()=>[],
    },
    viewAppointPermissions: {
      type: Array,
      default: ()=>[],
    },
  },
  components: {
    SelectStaffNew,
  },
  data() {
    return {
      staffList: [],
      tagShow: true,
      isWidth: false,
    };
  },
  watch:{
    getStaffList:{
      handler(val){
        this.staffList = this.getStaffList
      },
      deep:true,
    }
  },
  created(){
    // this.staffList = this.getStaffList
  },
  methods: {
    // 选择群主
    selectGroupManager() {
      this.$refs.selectTagGroup.show(this.staffList);
    },
    staffUpdate(val) {
      this.staffList = val;
      this.getWidth();
      this.$emit("search", this.active, this.staffList);
    },
    // 动态设置tag宽度
    getWidth() {
      this.$nextTick(() => {
        let tagBoxRef = this.$refs.tagBoxRef;
        this.isWidth =
          tagBoxRef.offsetHeight < tagBoxRef.scrollHeight ? true : false;
      });
    },
    delTag(index) {
      this.staffList.splice(index, 1);
      this.tagShow = false;
      this.$nextTick(() => {
        this.tagShow = true;
        this.getWidth();
        this.$emit("search", this.active, this.staffList);
      });
    },
  },
};
</script>

<style lang="less" scoped>
.according-group-manager {
  margin-top: 16px;
  font-size: 14px;
  .tag-box {
    max-height: 140px;
    overflow-y: auto;
  }
  .button {
    padding: 0;
    height: 21px;
  }
  .tag {
    width: 152px;
    height: 32px;
    margin-right: 15px;
    margin-top: 8px;
    padding: 5px 16px;
    &:nth-child(2n) {
      margin-right: 0;
    }
    .img {
      width: 20px;
      height: 20px;
      border-radius: 2px;
    }
    .name {
      font-size: 14px;
      color: rgba(0, 0, 0, 0.65);
      width: 78px;
      margin-left: 2px;
    }
  }
  .tag-width {
    width: 150px;
  }
}
</style>
