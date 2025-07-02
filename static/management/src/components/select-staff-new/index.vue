<template>
  <!-- 添加成员 -->
  <div>
      <a-modal
          width="820px"
          v-model:open="dialogShow"
          centered
          :confirmLoading="addConfirmLoading"
          @ok="addPeopleHandleOk"
          @cancel="addPeopleHandleCancel"
          class="select-staff-new-dialog"
      >
          <template #title>
              <div class="fx-ac">
                  <div>{{title || '选择员工'}}</div>
                  <!-- <div class="desc ml10" style="color: #999;font-size: 12px;">可快速对已选择员工新建标签</div> -->
                  <a class="modal_a_title ml10" @click="modalATitle" v-if="group_chat">刷新员工数据</a>
              </div>
          </template>
          <template #footer>
              <!-- <a-button @click="createTag" :disabled="addConfirmLoading">新建员工标签</a-button> -->
              <a-button @click="addPeopleHandleCancel" :disabled="addConfirmLoading">取消</a-button>
              <a-button type="primary" :loading="addConfirmLoading" @click="addPeopleHandleOk">
                  确定
              </a-button>
          </template>
          <!-- <a class="modal_a_title" @click="modalATitle" v-if="group_chat"
            >刷新员工数据</a
          > -->
          <slot name="alert"></slot>
          <a-spin :spinning="big_spinning">
              <div class="add-people flex">
                  <div class="add-left">
                      <a-tabs v-model:activeKey="tabAcitive">
                          <a-tab-pane :key="1" tab="员工">
                              <allStaff
                                  :selectData="selectDataAll"
                                  @setSelect="setSelect"
                                  :isSession="isSession"
                                  :isAuthMode="isAuthMode"
                                  :excludeRole="excludeRole"
                                  :bind_type="bind_type"
                                  :filter-staff="filterStaff"
                                  :group_chat="group_chat"
                                  ref="tabRef_1"
                                  :selectType="selectType"
                                  :viewAppointPermissions="viewAppointPermissions"
                              ></allStaff>
                          </a-tab-pane>
                          <!-- <a-tab-pane :key="2" tab="组织架构">
                            <framework
                              :selectData="selectData"
                              @setSelect="setSelect"
                              ref="tabRef_2"
                              :isSession="isSession"
                              :excludeRole="excludeRole"
                              :bind_type="bind_type"
                              :group_chat="group_chat"
                              @setLoading="setLoading"
                              :selectType="selectType"
                            ></framework>
                            <newFramework
                              :selectData="selectDataAll"
                              @setSelect="setSelect"
                              ref="tabRef_2"
                              :isSession="isSession"
                              :isAuthMode="isAuthMode"
                              :excludeRole="excludeRole"
                              :bind_type="bind_type"
                              :filter-staff="filterStaff"
                              :group_chat="group_chat"
                              @setLoading="setLoading"
                              :selectType="selectType"
                              :viewAppointPermissions="viewAppointPermissions"
                            >
                            </newFramework>
                          </a-tab-pane> -->
                          <!-- <a-tab-pane :key="3" tab="员工分组"> -->
                          <!-- <staff-group
                            :selectData="selectData"
                            @setSelect="setSelect"
                            :isSession="isSession"
                            :excludeRole="excludeRole"
                            :bind_type="bind_type"
                            :group_chat="group_chat"
                            ref="tabRef_3"
                            @setLoading="setLoading"
                            :selectType="selectType"
                          >
                          </staff-group> -->
                          <!-- <new-staff-group
                            :selectData="selectDataAll"
                            @setSelect="setSelect"
                            :isSession="isSession"
                            :isAuthMode="isAuthMode"
                            :excludeRole="excludeRole"
                            :bind_type="bind_type"
                            :filter-staff="filterStaff"
                            :group_chat="group_chat"
                            ref="tabRef_3"
                            @setLoading="setLoading"
                            :selectType="selectType"
                            :viewAppointPermissions="viewAppointPermissions"
                          >
                          </new-staff-group>
                        </a-tab-pane> -->
                          <!-- <a-tab-pane :key="4" tab="成员标签">
                            <member-tag
                              :selectData="selectDataAll"
                              @setSelect="setSelect"
                              :isSession="isSession"
                              :isAuthMode="isAuthMode"
                              :excludeRole="excludeRole"
                              :bind_type="bind_type"
                              :filter-staff="filterStaff"
                              :group_chat="group_chat"
                              :hideTagName="hideTagName"
                              ref="tabRef_4"
                              @setLoading="setLoading"
                              :selectType="selectType"
                              :viewAppointPermissions="viewAppointPermissions"
                            >
                            </member-tag>
                          </a-tab-pane> -->
                      </a-tabs>
                  </div>
                  <div class="add-right">
                      <div class="top-btn flex">
                          <div class="active fir">
                              已选择<span>({{ selectDataAll.length }})</span>
                          </div>
                          <a-button type="link" v-if="selectDataAll.length" @click="deleteAll"
                          >一键移除</a-button
                          >
                      </div>
                      <!-- <a-input-search
                                    placeholder="请输入搜索的内容"
                                    style="width: 288px;margin-top: 16px;"
                                    @search="selectOnSearch"
                                    v-model.trim="selectOnSearchVal"
                                /> -->
                      <cu-scroll class="scroll-box" ref="scrollbarRef">
                          <div class="test_wrapper" @dragover="dragover($event)">
                              <div
                                  class="all-select fx-ac"
                                  v-for="item in selectData"
                                  :key="item.id"
                                  :draggable="true"
                                  @dragstart="dragstart(item)"
                                  @dragenter="dragenter(item, $event)"
                                  @dragend="dragend(item, $event)"
                                  @dragover="dragover($event)"
                              >
                                  <div class="drag"></div>
                                  <div class="staff flex" v-if="item.data_type === 1">
                                      <div class="left flex">
                                          <div class="img-wra">
                                              <img
                                                  src="@/assets/default-avatar.png"
                                                  :class="{'img-c': item.avatar == '/static/image/default-avatar.svg'}"
                                                  alt=""
                                              />
                                              <!-- <div class="seat-n-bs"></div> -->
                                          </div>
                                          <!-- <div class="name fx-ac">
                                            <WWOpenData
                                              style="display: inline-block"
                                              type="userName"
                                              :showStatus="showStatus"
                                              :openid="item.user_id"
                                            >
                                              <span class="wk-black-65-text">{{ item.name }}</span>
                                            </WWOpenData>
                                          </div> -->
                                          <span class="name fx-ac">{{ item.name }}</span>
                                      </div>
                                      <div class="icon">
                                          <a-button
                                              type="link"
                                              @click="selectDelete(item, item.data_type)"
                                          >
                                              移除
                                          </a-button>
                                      </div>
                                  </div>
                                  <div class="staff flex" v-if="item.data_type === 2">
                                      <div class="left flex">
                                          <img
                                              src="../../assets/image/select-staff-new/zu-zhi.png"
                                              alt=""
                                              style="width: 36px; height: 36px;"
                                          />
                                          <!-- <div class="name fx-ac">
                                            <WWOpenData
                                              type="departmentName"
                                              :showStatus="showStatus"
                                              :openid="item.department_id"
                                            >
                                              <span class="wk-black-65-text">{{
                                                item.department_name
                                              }}</span>
                                            </WWOpenData>
                                          </div> -->
                                      </div>
                                      <div class="icon">
                                          <a-button
                                              type="link"
                                              @click="selectDelete(item, item.data_type)"
                                          >
                                              移除
                                          </a-button>
                                      </div>
                                  </div>
                                  <div class="staff flex" v-if="item.data_type === 3">
                                      <div class="left flex">
                                          <img
                                              src="../../assets/image/select-staff-new/gro.png"
                                              style="width: 36px; height: 36px;"
                                              alt=""
                                          />
                                          <div class="name">{{ item.name }}</div>
                                      </div>
                                      <div class="icon">
                                          <a-button
                                              type="link"
                                              @click="selectDelete(item, item.data_type)"
                                          >
                                              移除
                                          </a-button>
                                      </div>
                                  </div>
                                  <div class="staff flex" v-if="item.data_type === 4">
                                      <div class="left flex">
                                          <img
                                              src="../../assets/image/select-staff-new/tag.png"
                                              alt=""
                                              style="width: 36px; height: 36px;"
                                          />
                                          <div class="name">{{ item.tag_name }}</div>
                                      </div>
                                      <div class="icon">
                                          <a-button
                                              type="link"
                                              @click="selectDelete(item, item.data_type)"
                                          >
                                              移除
                                          </a-button>
                                      </div>
                                  </div>
                              </div>
                              <!-- <transition-group class="transition-wrapper" name="sort">
                                动画会导致删除的时候页面乱跳
                              </transition-group> -->
                          </div>
                          <!-- <div
                            id="example"
                            v-if="selectData.length > 0 && selectDataShow"
                          >
                            {{ selectData }} -->
                          <a-empty v-if="!selectDataAll.length" />
                          <!-- </div> -->
                      </cu-scroll>
                  </div>
              </div>
          </a-spin>
          <addTag ref="addTagRef" @initFunc="initMemberTag"></addTag>
      </a-modal>
  </div>
</template>

<script>
import allStaff from "./all-staff.vue";
// import framework from "./framework.vue"
import newFramework from "./new-framework.vue";
// import staffGroup from "./staff-group.vue"
// import newStaffGroup from "./new-staff-group.vue";
// import wxInitFunc from "@/utils/initWxConfig.js";
// import WWOpenData from "@/components/wwOpenData/index";
// import { getStaffSeatInfo } from "@/api/workplace.js";
// import memberTag from './member-tag.vue'
import addTag from './add-tag.vue'
export default {
  name: "selectStaffNew",
  components: {
    allStaff,
    // framework,
    newFramework,
    // staffGroup,
    // newStaffGroup,
    // WWOpenData,
    // memberTag,
    addTag
  },
  props: {
    addPeopleShow: {
      type: Boolean,
      default: function () {
        return false;
      },
    },
    addConfirmLoading: {
      type: Boolean,
      default: function () {
        return false;
      },
    },
    title: {
      type: String,
      default: function () {
        return "";
      },
    },
    selectType: {
      type: String,
      default: function () {
        return "";
      },
    },
    isSession: {
      // 已开启过会话存档的员工
      default: "",
    },
    excludeRole: {
      default: "",
    },
    bind_type: {
      default: "",
    },
    //请求完数据,需要过滤
    filterStaff: {
      type: Object,
      default: function () {
        return {}
      },
    },
    group_chat: {
      //已创建群聊的员工
      default: "",
    },
    emptyCanClose: {
      default: false,
    },
    isAuthMode: {
      default: false,
    },
    // 指定员工的员工
    viewAppointPermissions:{
      type: Array,
      default: ()=>[],
    },
    maxStaffNum: {
      type: Number,
      default: -1
    },
  },
  data() {
    return {
      staff_list: [], //全部员工列表
      staff_list_params: {
        //全部员工列表参数
        page: 0,
        size: 10,
        keyword: "",
      },
      staff_list_Loading: false, //全部员工列表
      staff_list_nomore: false, //全部员工列表没有更多了

      pageStaffSize:50,
      selectData: [], //展示：选择的员工列表
      selectDataAll: [], //全部数据源：选择的员工列表
      searchSelectData: [], //搜索选择的员工列表
      selectOnSearchVal: "", //搜索选择的员工列表搜索词
      alreadyAddPeopeList: [], //已经添加的员工

      selectDataShow: true,
      oldData: null, // 开始排序时按住的旧数据
      newData: null, // 拖拽过程的数据

      tabAcitive: 1,

      big_spinning: false,

      funcShow: false,

      hasSetEditData: false,

      hideTagName: '',

      batchDelTime:null, // 记录定时器操作

      // seatInfo: {
      //   has_bind_num: 0,
      // },
      staffNameMap: {},
    };
  },
  computed: {
    // alreadySelectData() {
    //   if (this.selectOnSearchVal) {
    //     let arr = this.selectData.filter(
    //       (i) => i.name.indexOf(this.selectOnSearchVal) !== -1
    //     );
    //     this.searchSelectData = arr;
    //     return arr;
    //   }
    //   this.selectData = this.selectData;
    //   return this.selectData;
    // },
    bind_num() {
      return this.$store.state.user.staff_seat.bind_num || 0;
    },
    unbind_num() {
      return this.$store.state.user.staff_seat.unbind_num || 0;
    },
    dialogShow() {
      return this.addPeopleShow || this.funcShow;
    },
    showStatus() {
    //   let status =
    //     this.$store.state.user.active_corp.setting_status == 0 ||
    //     this.$store.state.user.active_corp.setting_status == 3;
      let status = true
      if (status) {
        // getAgentConfig({ url: location.pathname }).then((res) => {
        //   wxInitFunc("", res.data);
        // });
      }
      return status;
    },
  },
  watch: {
    // dialogShow() {
      // if (this.dialogShow && this.selectData.length) {
      // 	this.deleteAll()
      // }
    // },
    // selectData() {
      // if (this.selectData.length > 0) {
      //   this.$nextTick(() => {
          // this.init();
      //   });
      // }
    // },
    tabAcitive() {
      localStorage.setItem('select-staff-tab', this.tabAcitive)
    }
  },
  created() {
    // this.getSeatInfo()
    let tab = localStorage.getItem('select-staff-tab')
    if (tab) {
      this.tabAcitive = isNaN(tab * 1) ? 1 : tab * 1
    }
  },
  methods: {
    //刷新员工数据
    modalATitle() {
      this.$refs.tabRef_1.addPeopleSearch();
    },
    // getSeatInfo() {
    //   getStaffSeatInfo().then(res => {
    //     this.seatInfo = res.data
    //   })
    // },
    async show(staff) {
      // 清除定时器
      clearInterval(this.batchDelTime)
      this.pageStaffSize = 50
      this.funcShow = true;
      if (staff && Array.isArray(staff)) {
        // if (this.selectData.length !== staff.length) {
        // 	this.hasSetEditData = false
        // }
        //if (!this.hasSetEditData) {
        let time = Date.now();
        this.hasSetEditData = true;
        staff.forEach((i) => {
          i.data_type = 1;
          i.id = time++;
        });
        // this.clearParKey()
        this.selectDataAll = staff;
        this.selectData = this.selectDataAll.slice(0,this.pageStaffSize)
        //}
      }
      // if (!staff || !staff.length) {
      //   this.initSetKey()
      // }
    },
    dragstart(value) {
      this.oldData = value;
    },

    // 记录移动过程中信息
    dragenter(value, e) {
      this.newData = value;
      e.preventDefault();
    },

    // 拖拽最终操作
    dragend(value, e) {
      if (this.oldData !== this.newData) {
        let oldIndex = this.selectData.indexOf(this.oldData);
        let oldIndexAll = this.selectDataAll.indexOf(this.oldData);
        let newIndex = this.selectData.indexOf(this.newData);
        let newIndexAll = this.selectDataAll.indexOf(this.newData);
        let newItems = [...this.selectData];
        let newItemsAll = [...this.selectDataAll];
        // 删除老的节点
        newItems.splice(oldIndex, 1);
        newItemsAll.splice(oldIndexAll, 1);
        // 在列表中目标位置增加新的节点
        newItems.splice(newIndex, 0, this.oldData);
        newItemsAll.splice(newIndexAll, 0, this.oldData);
        this.selectData = [...newItems];
        this.selectDataAll = [...newItemsAll];
      }
    },

    // 拖动事件（主要是为了拖动时鼠标光标不变为禁止）
    dragover(e) {
      e.preventDefault();
    },
    setSelect (data) {
      let time = Date.now();
      if (this.maxStaffNum > -1 && data.length > this.maxStaffNum) {
          data = data.slice(0, this.maxStaffNum)
          this.$emit('limit')
      }
      this.selectDataAll = data.map((el) => {
        el.id = time++;
      });
      if (this.selectOnSearchVal) {
        let arr = this.selectDataAll.filter(
          (i) => i.name.indexOf(this.selectOnSearchVal) !== -1
        );
        this.searchSelectData = arr;
      }
      this.selectDataAll = data;
      this.selectData = data.slice(0,data.length > 50 ? this.selectData.length : this.pageStaffSize);
    },
    setLoading(flag) {
      this.big_spinning = flag;
    },
    // 滚动条置顶，清空数据
    setScrollbarClear(){
      this.$nextTick(()=>{
        if(this.$refs.scrollbarRef && this.$refs.scrollbarRef.wrap){
          this.$refs.scrollbarRef.wrap.scrollTop = 0
        }
      })
      this.selectDataAll = []
      this.batchDelData()
    },
    // 批次删除,避免一口气操作大量数据导致页面卡顿
    batchDelData(){
      // 避免重复调用
      if(this.batchDelTime){
        clearInterval(this.batchDelTime)
      }
      this.batchDelTime = setInterval(() => {
        if(this.selectData.length == 0) {
          clearInterval(this.batchDelTime)
          return
        }
        this.selectData.splice(0,100)
      }, 500);
    },
    //添加成员弹窗关闭
    addPeopleHandleCancel(e) {
      this.setScrollbarClear()
      if (this.funcShow) {
        this.funcShow = false;
      }
      this.$emit("cancel");
      this.$emit("cancelSelect");
    },
    //选择的员工删除一项
    selectDelete(item, type) {
      let index = this.selectData.findIndex((el) => el.id === item.id);
      if (this.searchSelectData.length) {
        this.searchSelectData.splice(index, 1);
        let ids = this.selectData.map((i) => i.userid);
        this.selectData.splice(ids.indexOf(item.userid), 1);

        this.selectDataAll.splice(ids.indexOf(item.userid), 1);
      } else {
        this.selectData.splice(index, 1);
        this.selectDataAll.splice(index, 1);
      }
      this.$refs["tabRef_1"] &&
        this.$refs["tabRef_1"].setCheck(item, index, type);
      // this.$refs["tabRef_2"] &&
      //   this.$refs["tabRef_2"].setCheck(item, index, type);
      // this.$refs["tabRef_3"] &&
      //   this.$refs["tabRef_3"].setCheck(item, index, type);
      // this.$refs["tabRef_4"] &&
      //   this.$refs["tabRef_4"].setCheck(item, index, type);
    },
    deleteAll() {
      this.selectDataAll.forEach((item, index) => {
        this.$refs["tabRef_1"] &&
          this.$refs["tabRef_1"].setCheck(item, index, item.data_type);
        // this.$refs["tabRef_2"] &&
        //   this.$refs["tabRef_2"].setCheck(item, index, item.data_type);
        // this.$refs["tabRef_3"] &&
        //   this.$refs["tabRef_3"].setCheck(item, index, item.data_type);
        // this.$refs["tabRef_2"] && this.$refs["tabRef_2"].clearCheck();
        // this.$refs["tabRef_3"] && this.$refs["tabRef_3"].clearCheck();
        // this.$refs["tabRef_4"] && this.$refs["tabRef_4"].clearCheck();
      });
      this.selectData = [];
      this.selectDataAll = [];
    },
    //选择的员工搜索
    selectOnSearch(e) {
      if (!e) {
        this.searchSelectData = [];
        return;
      }
      let arr = this.selectDataAll.filter((i) => i.name.indexOf(e) !== -1);
      this.searchSelectData = arr;
    },
    addPeopleHandleOk () {
      let selectStaff = [];
      this.selectDataAll.forEach((i) => {
        if (i.data_type === 1) {
          selectStaff.push(i);
        } else if (i.data_type === 2 || i.data_type === 3 || i.data_type === 4) {
          selectStaff = selectStaff.concat(i.staff_list);
        }
      });
      //去重
      let ids = [],
        newArr = [];
      selectStaff.forEach((i) => {
        if (!ids.includes(i.userid)) {
          ids.push(i.userid);
          newArr.push(i);
        }
      });
      // this.setOldData(newArr);
      this.$emit("selectStaff", newArr);
      this.$emit("change", newArr);
      if (this.emptyCanClose) {
        // 没有选择员工 点确定不让关闭modal
        if (newArr.length == 0) {
          return;
        }
      }
      this.setScrollbarClear()
      if (this.funcShow) {
        this.funcShow = false;
      }
      this.$emit("cancleSelect");
    },
    setOldData(newArr) {
      if (!newArr.length) {
        return;
      }
      this.$api
        .setLatelyChooseUser({ user_id: newArr.map((i) => i.userid) })
        .then((res) => {
          this.$refs["tabRef_1"] && this.$refs["tabRef_1"].getOldData();
        });
    },
    createTag() {
      let selectStaff = [];
      this.selectDataAll.forEach((i) => {
        if (i.data_type === 1) {
          selectStaff.push(i);
        } else if (i.data_type === 2 || i.data_type === 3 || i.data_type === 4) {
          selectStaff = selectStaff.concat(i.staff_list);
        }
      });
      //去重
      let ids = [],
        newArr = [];
      selectStaff.forEach((i) => {
        if (!ids.includes(i.userid)) {
          ids.push(i.userid);
          newArr.push(i);
        }
      });
      if (!newArr.length) {
        return this.$message.warning('请选择员工')
      }
      if (newArr.some(i => !i.userid)) {
        return this.$message.warning('选择的员工数据userid为空')
      }
      this.$refs.addTagRef.show(newArr)
    },
    initMemberTag(id) {
      if (id) {
        this.$emit('initFunc', id)
      }
      this.$refs["tabRef_4"] && this.$refs["tabRef_4"].initMember();
      if (this.$route.path == '/staffManage') {
        this.funcShow = false
      }
    },
    hideTag(hideTagName) {
      //员工管理那里,标签下添加员工需过滤当前标签
      this.hideTagName = hideTagName
      setTimeout(() => {
        this.$refs["tabRef_4"] && this.$refs["tabRef_4"].$refs.elTreeRef_2.filter(hideTagName)
      }, 500)
    },
    // initSetKey() {
    //   this.$refs["tabRef_2"] && this.$refs["tabRef_2"].clearCheck();
    //   this.$refs["tabRef_3"] && this.$refs["tabRef_3"].clearCheck();
    //   this.$refs["tabRef_4"] && this.$refs["tabRef_4"].clearCheck()
    // },
    // clearParKey() {
    //   this.$refs["tabRef_2"] && this.$refs["tabRef_2"].clearParKey();
    //   this.$refs["tabRef_3"] && this.$refs["tabRef_3"].clearParKey();
    //   this.$refs["tabRef_4"] && this.$refs["tabRef_4"].clearParKey()
    // },
    // 已选择的滚动加载
    scrollLoad(){
      if(this.selectData.length == this.selectDataAll.length) return
      this.selectData = this.selectData.concat(this.selectDataAll.slice(this.selectData.length,this.selectData.length + this.pageStaffSize))
    },
  },
  beforeUnmount() {
    clearInterval(this.batchDelTime);
  },
};
</script>
<style scoped lang="less">
.ml10{
  margin-left: 10px
}
.alert-msg {
  align-items: center;
  justify-content: space-between;
}
/deep/.ant-modal-body {
  padding-top: 14px;
}
.add-people {
  .sort-move {
    transition: transform 0.3s;
  }
  .add-left {
    width: 400px;
    border-right: 1px solid rgba(0, 0, 0, 0.15);
    padding-left: 10px;
    .datas-item {
      height: 300px;
      margin-top: 16px;
      margin-right: 10px;
      overflow-y: auto;
      .all-memeber {
        align-items: flex-start;
        justify-content: flex-start;
        margin-bottom: 16px;
        .left {
          align-items: center;
          .img {
            width: 48px;
            height: 48px;
            border-radius: 2px;
            margin-left: 12px;
          }
        }
        .right {
          margin-left: 12px;
          flex: 1;
          .info-type {
            align-items: center;
            .type {
              padding: 0 6px;
              height: 16px;
              background: #e9f1fe;
              border-radius: 2px;
              border: 1px solid #99bffd;
              font-size: 10px;
              font-family: PingFangSC-Regular, PingFang SC;
              font-weight: 400;
              color: #2475fc;
              line-height: 14px;
            }
            .pt-type {
              background: rgba(0, 0, 0, 0.04);
              padding: 0 6px;
              border-radius: 2px;
              border: 1px solid rgba(0, 0, 0, 0.15);
              font-size: 10px;
              font-family: PingFangSC-Regular, PingFang SC;
              font-weight: 400;
              color: rgba(0, 0, 0, 0.65);
              line-height: 16px;
            }
            .name {
              font-size: 14px;
              font-family: PingFangSC-Regular, PingFang SC;
              font-weight: 400;
              color: rgba(0, 0, 0, 0.65);
              line-height: 22px;
              margin-left: 8px;
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
              flex: 1;
              width: 126px;
            }
          }
          .elips {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            width: 200px;
          }
        }
      }
    }
  }
  .add-right {
    width: 400px;
    border-right: 1px solid rgba(0, 0, 0, 0.15);
    padding-left: 20px;
    .top-btn {
      justify-content: space-between;
      align-items: center;
      .fir {
        font-size: 14px;
        font-weight: 600;
        color: rgba(0, 0, 0, 0.85);
        line-height: 22px;
        span {
          font-weight: 400;
          color: rgba(0, 0, 0, 0.45);
        }
      }
    }
    .all-select {
      margin-bottom: 16px;
      .drag {
        width: 20px;
        height: 20px;
        background: url("../../assets/image/no-click.png") no-repeat;
        background-size: contain;
        margin-right: 10px;
      }
      .staff {
        align-items: center;
        flex: 1;
        .left {
          flex: 1;
          align-items: center;
          .img-wra{
            position: relative;
            width: 36px;
            height: 36px;
            img {
              width: 36px;
              height: 36px;
              border-radius: 2px;
            }
            .img-c{
              background-color: #5196FF;
            }
            .seat-bs{
              position: absolute;
              right: 0;
              bottom: 0;
              background-image: url('../../assets/image/select-staff-new/seat-has-s.svg');
              background-size: 100% 100%;
              width: 20px;
              height: 20px;
            }
            .seat-n-bs{
              position: absolute;
              right: 0;
              bottom: 0;
              width: 20px;
              height: 20px;
              background-image: url('../../assets/image/select-staff-new/seat-no-s.svg');
              background-size: 100% 100%;
            }
          }
          .name {
            font-size: 14px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(0, 0, 0, 0.65);
            line-height: 22px;
            margin-left: 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
            width: 126px;
          }
        }
        .icon {
          margin-right: 10px;
        }
      }
    }
  }
}
.modal_a_title {
  // position: absolute;
  // top: 17px;
  // left: 100px;
  font-size: 14px;
}
.test_wrapper{
  transform: translate3d(0, 0, 0);
}

.scroll-box {
    padding-right: 24px;
    padding-top: 24px;
}
</style>
