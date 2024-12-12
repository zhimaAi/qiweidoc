<template>
  <div class="according-group-tags">
    <a-button type="link" class="button" @click="selectTab">
      选择群标签
    </a-button>
    <div class="flex-wrap tag-box" ref="tagBoxRef">
      <template v-if="tagShow">
        <a-tag
          class="tag"
          closable
          @close="delTag(index)"
          v-for="(item, index) in tags"
          :key="index"
        >
          {{ item.name }}
        </a-tag>
      </template>
    </div>

    <!-- 选择群标签 -->
    <SelectTagGroup
      ref="selectTagGroup"
      title="选择群聊标签"
      @change="setTagGroupChange"
    ></SelectTagGroup>
  </div>
</template>

<script>
import SelectTagGroup from "../../../components/common/select-tag-group.vue";
export default {
  name: "AccordingGroupTags",
  props: {
    active: {
      type: String,
      default: "",
    },
    getTags: {
      type: Array,
      default: ()=>[],
    },
  },
  components: {
    SelectTagGroup,
  },
  data() {
    return {
      tags: [],
      tagShow: true,
    };
  },
  watch:{
    getTags:{
      handler(val){
        this.tags = this.getTags
      },
      deep:true,
    }
  },
  created(){
    // this.tags = this.getTags
  },
  methods: {
    // 选择群标签
    selectTab() {
      this.$refs.selectTagGroup.show(this.tags);
    },
    //选中的群标签
    setTagGroupChange(val) {
      this.tags = val;
      this.$emit("search", this.active, this.tags);
    },
    // 删除群标签
    delTag(index) {
      this.tags.splice(index, 1);
      this.tagShow = false;
      this.$nextTick(() => {
        this.tagShow = true;
        this.$emit("search", this.active, this.tags);
      });
    },
  },
};
</script>

<style lang="less" scoped>
.according-group-tags {
  margin-top: 16px;
  font-size: 14px;
  .button {
    padding: 0;
    height: 21px;
  }
  .label {
    color: rgba(0, 0, 0, 0.85);
  }
  .tag-box {
    max-height: 140px;
    overflow-y: auto;
  }
  .tag {
    height: 32px;
    margin-right: 15px;
    margin-top: 8px;
    padding: 5px 16px;
    .name {
      font-size: 14px;
      color: rgba(0, 0, 0, 0.65);
      width: 78px;
      margin-left: 2px;
    }
  }
}
</style>
