<template>
  <div>
    <a-button
      type="dashed"
      style="margin-right: 16px;"
      @click="$refs.SetStaff.show(selectStaff)"
      >
      <PlusOutlined />
      {{btnText}}
    </a-button>
    <div
      :size="10"
      class="staff-list"
      v-if="showStaffFlag"
      :style="{'max-width': maxShowWidth ? maxShowWidth : ''}"
      ref="staffWraResizeRef"
    >
      <a-tag
        v-for="(item, index) in selectStaff"
        :key="item.staff_id"
        closable
        @close="deleteStaff(index)"
        style="margin-top: 6px; overflow: hidden;"
        class="fx-ac tag"
        :class="{'red-tip':
          showSeatTip &&
          ((!item.is_bind_license && checkSeat) || noActivation.includes(item.staff_id) || noName.includes(item.staff_id))
        }"
      >
        <a-tooltip>
          <template slot="title" v-if="!item.is_bind_license && checkSeat">
            <span v-if="seatTipText" style="font-size: 14px;">
              {{seatTipText}}
            </span>
            <span v-else>该员工暂无席位或席位已到期，将无法收到任务提醒无法创建朋友圈，请更换员工后再试</span>
          </template>
          <template slot="title"
            v-if="(item.is_bind_license || !checkSeat) && (noActivation.includes(item.staff_id) || noName.includes(item.staff_id))"
          >
            <span v-if="noActivation.includes(item.staff_id)" style="font-size: 14px;">
              该员工未激活，请激活员工后在进行保存
            </span>
            <span v-else-if="noName.includes(item.staff_id)" style="font-size: 14px;">
              该员工未实名，请联系员工实名认证后在进行保存
            </span>
          </template>
          <div class="fx-ac" v-if="showSeatTip" style="flex: 1;overflow: hidden;">
            <a-avatar :size="20" :src="item.avatar" style="margin-right: 4px;"></a-avatar>
            <WWOpenData
              style="display:inline-block;flex: 1;overflow: hidden;"
              type='userName'
              :showStatus="showStatus"
              :openid='item.name'
            >
              <span>{{item.name }}</span>
            </WWOpenData>
          </div>
        </a-tooltip>
        <div class="fx-ac" v-if="!showSeatTip" style="flex: 1;overflow: hidden;">
          <a-avatar :size="20" :src="item.avatar" style="margin-right: 4px;"></a-avatar>
          <WWOpenData
            style="display:inline-block;flex: 1;overflow: hidden;"
            type='userName'
            :showStatus="showStatus"
            :openid='item.name'
          >
            <span>{{item.name }}</span>
          </WWOpenData>
          <!-- <span>{{ item.name }}</span> -->
        </div>
      </a-tag>
    </div>
    <!-- <set-staff
        :title="title"
        ref="SetStaff"
        :showStaffApproveTip="showStaffApproveTip"
        @change="change"
    ></set-staff> -->
    <selectStaffNew
      :title="title"
      :selectType="selectType"
      :bind_type="bind_type"
      :isSession="isSession"
      ref="SetStaff"
      :viewAppointPermissions="viewAppointPermissions"
      @change="change"
    >
      <a-alert
        slot="alert"
        v-if="showStaffApproveTip"
        type="info"
        style="margin-bottom: 16px;"
      >
        <template #message>
          请确保所选员工已实名认证，
          <a-tooltip
            title="手机端打卡企业微信APP-点击【设置】-【个人信息与权限】-【个人信息查询与导出】查看是否实名"
            :getPopupContainer="
              (triggerNode) => {
                return triggerNode.parentNode;
              }
            "
          >
            <a>如何查看员工是否已实名？</a>
          </a-tooltip>
        </template>
      </a-alert>
    </selectStaffNew>
  </div>
</template>

<script>
// import SetStaff from "./set-staff";
import { PlusOutlined } from '@ant-design/icons-vue';
import selectStaffNew from "@/components/select-staff-new/index";
import wxInitFunc from "@/utils/initWxConfig.js"
import WWOpenData from "@/components/wwOpenData/index"
export default {
  name: "select-staff-box",
  components: {
    // SetStaff
    selectStaffNew,
    WWOpenData,
    PlusOutlined
  },
  props: {
    title: {
      type: String,
      default: "选择员工",
    },
    btnText: {
      type: String,
      default: "添加员工",
    },
    // 1 有席位； 2 无席位
    bind_type: {
      default: "",
    },
    selectType: {
      default: "multiple",
    },
    // 已开启过会话存档的员工(1)
    isSession: {
        default: "",
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
    seatTipText: {
      type: String,
      default: () => ''
    },
    noActivation: {
      type: Array,
      default() {
        return []
      }
    },
    noName: {
      type: Array,
      default() {
        return []
      }
    },
    checkSeat: {
      type: Boolean,
      default: function() {
        return true
      }
    },
    maxShowWidth: {
    },
    viewAppointPermissions: {
      type: Array,
      default: function() {
        return []
      }
    }
  },
  mounted() {
    this.selectStaff = this.selectedStaffs
  },
  beforeUnmount() {
    this.myObserver && this.myObserver.disconnect();
  },
  computed: {
    showStatus() {
      let status = this.$store.state.user.active_corp.setting_status == 0 || this.$store.state.user.active_corp.setting_status == 3
      if (status) {
        this.$api.getAgentConfig({url: location.pathname}).then(res => {
          wxInitFunc("", res.data)
        })
      }
      return status
    },
    showStaffFlag() {
      return this.isTag && this.selectStaff && this.selectStaff.length
    }
  },
  watch: {
    selectedStaffs() {
      this.selectStaff = this.selectedStaffs
    },
    showStaffFlag: {
      handler() {
        if (this.showStaffFlag) {
          this.$nextTick(() => {
            this.selStaffWraHeight()
          })
        } else {
          this.myObserver && this.myObserver.disconnect();
        }
      },
      immediate: true
    }
  },
  data() {
    return {
      selectStaff: [],
    };
  },
  methods: {
    change(val) {
      // console.log(val);
      this.selectStaff = val;
      this.$emit("change", val);
    },
    deleteStaff(index) {
      this.selectStaff.splice(index, 1);
      this.$emit("change", this.selectStaff);
    },
    selStaffWraHeight() {
      try {
        let str = 'selectStaffBox-staffWraResize' + this.$store.state.user.userInfo.user_id
        let height = this.$local_get(str)
        if (height) {
          let arr = height.split(',')
          this.$refs.staffWraResizeRef.style.height = arr[1] + 'px'
          // this.$refs.staffWraResizeRef.style.width = arr[0] + 'px'
        }
        this.myObserver = new ResizeObserver(entries => {
          entries.forEach(entry => {
            let hei =  this.$refs.staffWraResizeRef.offsetHeight
            let wid =  this.$refs.staffWraResizeRef.offsetWidth
            if (!hei || !wid) {
              return
            }
            this.$local_set(str, wid + ',' + hei)
          })
        })
        this.myObserver.observe(this.$refs.staffWraResizeRef)
      } catch (error) {

      }
    },
  },
};
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
