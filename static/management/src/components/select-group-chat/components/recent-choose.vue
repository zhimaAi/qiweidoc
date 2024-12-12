<template>
  <div class="recent-choose">
    <div class="label">最近选择</div>
    <div class="flex-wrap">
      <div
        class="recent-item fx-ac-jc pointer"
        :class="{ selected: item.selected , disabled: disabledIds.includes(item.chat_id)}"
        v-for="(item, index) in list"
        :key="index"
        @click="selected(item)"
      >
        <a-tooltip>
          <template #title>
            <div>{{ item.name }}</div>
          </template>
          <div class="recent-item-content omit">{{ item.name }}</div>
        </a-tooltip>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "RecentChoose",
  props: {
    // 最近选择的群聊
    list: {
      type: Array,
      default: () => [],
    },
    disabledIds:{
      type:Array,
      default:() => []
    },
  },
  data() {
    return {};
  },
  methods: {
    // 选中
    selected(item) {
      if (this.disabledIds.includes(item.chat_id)) {
          return
      }
      this.$emit("selected", item);
    },
  },
};
</script>

<style lang="less" scoped>
.recent-choose {
  margin-top: 16px;
  font-size: 14px;
  .label {
    color: rgba(0, 0, 0, 0.85);
  }
  .recent-item {
    color: rgba(0, 0, 0, 0.65);
    width: 102px;
    height: 32px;
    background-color: rgba(0, 0, 0, 0.04);
    margin: 8px 8px 0 0;
    border-radius: 2px;
    border: 1px solid rgba(0, 0, 0, 0.15);
    padding: 5px 16px;
    &.disabled {
      opacity: 0.65;
      cursor: not-allowed;
    }
    &:nth-child(3n) {
      margin-right: 0;
    }
    &:hover {
      border: 1px solid #2475fc;
    }
  }
  .selected {
    position: relative;
    border: 1px solid #2475fc;
    &::after {
      content: "";
      width: 20px;
      height: 20px;
      background: url("../../../assets/image/recently-selected.png") no-repeat;
      background-size: contain;
      position: absolute;
      right: 0;
      bottom: 0;
    }
  }
}
</style>
