<template>
  <a-modal
    v-model:open="visible"
    centered
    @ok="handleOk"
    wrapClassName="select-group-chat"
    :destroyOnClose="true"
    @cancel="clear"
    :width="746"
  >
    <template v-slot:title>
      <a-tabs v-model="active" @change="tabChange">
        <a-tab-pane :key="item.value" :tab="item.label" v-for="item in tabs" :disabled="disabledTabSwitch">
        </a-tab-pane>
      </a-tabs>
    </template>
    <template v-slot:footer>
      <div class="fl-jsb-ac">
        <div>
          {{ promptTitle }}
          <slot name="footerLeft"></slot>
        </div>
        <div>
          <a-button key="back" @click="handleCancel"> 取消 </a-button>
          <a-button
            key="submit"
            type="primary"
            :loading="loading"
            @click="handleOk"
          >
            保存
          </a-button>
        </div>
      </div>
    </template>
    <slot name="alert"></slot>
    <div class="main flex">
      <div class="left-box">
        <a-input-search
          placeholder="请输入群名称搜索"
          v-model:value="name"
          @search="onSearch"
          :allowClear="true"
        />
        <!-- 全部群聊 -->
        <!-- <RecentChoose
          :list="recentChooseGroup"
          :disabledIds="disabledIds"
          @selected="selected"
          v-show="active === '1'"
        ></RecentChoose> -->

        <!-- 按群主选择 -->
        <AccordingGroupManager
          :active="active"
          :getStaffList="staffList"
          :disabledIds="disabledIds"
          :viewAppointPermissions="viewAppointPermissions"
          @search="search"
          v-show="active === '2'"
        ></AccordingGroupManager>

        <!-- 按群标签选择 -->
        <AccordingGroupTags
          :active="active"
          :getTags="tags"
          :disabledIds="disabledIds"
          @search="search"
          v-show="active === '3'"
        ></AccordingGroupTags>
        <div class="list-header fx-ac-be">
          <div class="list-total">
            <a-checkbox
              v-if="isMuti"
              :disabled="disabledIds.length > 0"
              :indeterminate="indeterminate"
              :checked="checkAll"
              @change="onCheckAllChange"
              >共<span>{{ total }}</span
              >个群聊
            </a-checkbox>
            <span v-else>
              共<span>{{ total }}</span
              >个群聊
            </span>
          </div>
          <div class="screen fx-ac">
            排序：
            <!-- <div class="fx-ac pointer" @click="filtrate('groupMembers')">
              <span>群成员</span> -->
              <!-- <div class="sort-box">
                <icon-svg
                  :iconClass="orderBy == 6 ? 'upYes' : 'upNo'"
                  class="icon sort-icon"
                ></icon-svg>
                <icon-svg
                  :iconClass="orderBy == 1 ? 'downYes' : 'downNo'"
                  class="icon sort-icon"
                ></icon-svg>
              </div> -->
            <!-- </div> -->
            <div
              class="fx-ac margin-left-16 pointer"
              @click="filtrate('buildGroupTime')"
            >
              <span>建群时间</span>
              <!-- <div class="sort-box">
                <icon-svg
                  :iconClass="orderBy == 5 ? 'upYes' : 'upNo'"
                  class="icon sort-icon"
                ></icon-svg>
                <icon-svg
                  :iconClass="orderBy == 4 ? 'downYes' : 'downNo'"
                  class="icon sort-icon"
                ></icon-svg>
              </div> -->
            </div>
          </div>
        </div>
        <!-- :infinite-scroll-distance="10"
        v-infinite-scroll="peopleListLoad" -->
        <div class="list" style="text-align: center;" v-if="dataLoading" >
          <a-spin :spinning="dataLoading"/>
        </div>
        <div class="list" v-else>
          <!-- 多选 -->
          <template v-if="isMuti">
            <div class="item" v-for="(item, index) in dataList" :key="index">
              <a-checkbox
                :value="item.chat_id"
                :disabled="disabledIds.includes(item.chat_id)"
                :checked="selectedChatIds.includes(item.chat_id)"
                @change="(e) => onChange(e, item)"
              >
                <a-tooltip>
                  <template v-slot:title v-if="item.name.length > 15">
                    <div class="group-name">{{ item.name }}</div>
                  </template>
                  <div class="group-name">{{ item.name }}</div>
                </a-tooltip>
                <div class="fx-ac">
                  <div class="fx-ac">
                    <!-- <img
                      :src="item.owner_avatar"
                      alt=""
                      class="head-portrait"
                    /> -->
                    <span class="leader">群主：</span>
                    <div class="name omit">{{ item.owner_name }}</div>
                  </div>
                  <div class="member">
                    <span class="leader">群成员：</span><span class="leader-num">{{ item.total_member }}</span>
                  </div>
                </div>
              </a-checkbox>
            </div>
          </template>

          <!-- 单选 -->
          <template v-else>
            <div class="item" v-for="(item, index) in dataList" :key="index">
              <a-radio
                :value="item.chat_id"
                :checked="selectedChatIds === item.chat_id"
                @change="(e) => onChange(e, item)"
              >
                <a-tooltip>
                  <template v-slot:title v-if="item.name.length > 15">
                    <div class="group-name">{{ item.name }}</div>
                  </template>
                  <div class="group-name">{{ item.name }}</div>
                </a-tooltip>
                <div class="fx-ac">
                  <div class="fx-ac">
                    <!-- <img
                      :src="item.owner_avatar"
                      alt=""
                      class="head-portrait"
                    /> -->
                    <span class="leader">群主：</span>
                    <div class="name omit">{{ item.owner_name }}</div>
                  </div>
                  <div class="member">
                    <span class="leader">群成员：</span><span class="leader-num">{{ item.total_member }}</span>
                  </div>
                </div>
              </a-radio>
            </div>
          </template>
          <!-- <div style="text-align: center; height: 18px">
            <a-spin size="small" v-if="dataLoading" />
            <div v-if="!dataLoading">
              {{ loadMore ? "下滑加载更多" : "没有更多了" }}
            </div>
          </div> -->
        </div>
        <div class="group-page">
          <a-pagination
            v-model="page.page"
            :page-size-options="['20', '50', '100']"
            :total="total"
            size="small"
            show-less-items
            show-size-changer
            :page-size="page.size"
            @change="pageChange"
            @showSizeChange="onShowSizeChange"
          >
          </a-pagination>
        </div>
      </div>
      <div class="right-box flex1">
        <div class="right-header fx-ac-be">
          <div class="title">
            已选群聊<span>（{{ selectedGroup.length }}）</span>
          </div>
          <a-button type="link" @click="clear" :disabled="selectedGroup.length < 1 || disabledIds.length > 0"> 清空选择 </a-button>
        </div>
        <!-- <div class="right-list-spin" v-if="allSpinLoading"><a-spin /></div> -->
        <div class="right-list">
          <!-- <transition-group class="transition-wrapper" name="sort"> -->
            <div
              class="fx-ac-be right-item pointer"
              v-for="item in selectedGroup"
              :key="item._id"
              :draggable="true"
              @dragstart="dragstart(item)"
              @dragenter="dragenter(item, $event)"
              @dragend="dragend(item, $event)"
              @dragover="dragover($event)"
            >
              <div class="item-left fx-ac">
                <div class="sort" v-if="isMuti"></div>
                <div>
                  <a-tooltip>
                    <template v-slot:title v-if="item.name.length > 15">
                      <div class="title">{{ item.name }}</div>
                    </template>
                    <div class="title">{{ item.name }}</div>
                  </a-tooltip>
                  <div class="fx-ac">
                    <div class="fx-ac">
                      <!-- <img
                        :src="item.owner_avatar"
                        alt=""
                        class="head-portrait"
                      /> -->
                      <span class="leader">群主：</span>
                      <div class="name omit">{{ item.owner_name }}</div>
                    </div>
                    <div class="margin-left-18">
                      <!-- 群成员：<span>{{ item.total_member }}</span> -->
                      <span class="leader">群成员：</span><span class="leader-num">{{ item.total_member }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <a-button type="link"
                        @click="del(item.chat_id)"
                        :disabled="disabledIds.includes(item.chat_id)"> 移除 </a-button>
            </div>
          <!-- </transition-group> -->
        </div>
      </div>
    </div>
  </a-modal>
</template>

<script>
import RecentChoose from "./components/recent-choose.vue";
import AccordingGroupManager from "./components/according-group-manager.vue";
import AccordingGroupTags from "./components/according-group-tags.vue";
import {groupsList} from "@/api/company";

export default {
  name: "SelectGroupChat",
  props: {
    //永久群活码只能选能从企微同步下来的群
    'groupLiveCode':{
      type:Boolean,
      default:false
    },
    //一客一群拿群里没有客户的群
    'aGroupGuests':{
      type:Boolean,
      default:false
    },
    //提示文案
    promptTitle:{
      type:String,
      default:''
    },
    disabledIds:{
      type:Array,
      default:() => []
    },
    disabledTabSwitch: {
      type:Boolean,
      default:false
    },
    // 其它搜索参数
    otherParams: {
      type: Object,
      default: function() {
        return {}
      }
    },
    viewAppointPermissions: {
      type: Array,
      default: function() {
        return []
      }
    }
  },
  components: {
    RecentChoose,
    AccordingGroupManager,
    AccordingGroupTags,
  },
  data() {
    return {
      visible: false,
      loading: false,
      isMuti: true, //是否多选
      page: {
        page: 1,
        size: 100,
      },
      total: 0,
      name: "", //群名称
      orderBy: 0,
      active: "1",
      tabs: [
        {
          label: "全部群聊",
          value: "1",
        },
        // {
        //   label: "按群主选择",
        //   value: "2",
        // },
        // {
        //   label: "按群标签选择",
        //   value: "3",
        // },
      ],
      dataLoading: false,
      // loadMore: true, //是否还能加载
      dataList: [],
      // dataListAll: [],
      // dataListAlls: [],
      selectedChatIds: null, //已选择的群id
      selectedGroup: [], //已选择的群信息
      recentChooseGroup: [], //最近选择的群聊
      staffList: [], //已选择的群主列表
      tags: [], //已选择的群标签
      type: "",
      oldData: null, // 开始排序时按住的旧数据
      newData: null, // 拖拽过程的数据
      indeterminate:false,
      checkAll:false,
      // allSpinLoading:false,
    };
  },
  methods: {
    show(keys = [], isMuti = true) {
      this.visible = true;
      this.reset(isMuti);
      this.isMuti = isMuti;
      let chatIds = keys.map((el) => {
        return el.chat_id;
      });
      this.selectedChatIds = chatIds;
      this.selectedGroup = keys.concat();
      if (!this.isMuti) {
        this.selectedChatIds = chatIds[0];
      }
    //   this.getLatelyChooseGroup().then(async() => {
        // await this.onSearch();
        this.onSearch();
    //   });
    },
    reset(isMuti) {
      this.selectedChatIds = null; //已选择的群id
      this.selectedGroup = []; //已选择的群信息
      if (isMuti) {
        this.selectedChatIds = [];
      }
    },
    // 获取最近选择群聊
    // async getLatelyChooseGroup() {
    //   try {
    //     // let res = await this.$api.getLatelyChooseGroup({
    //     //   ...this.otherParams,
    //     // });

    //     this.recentChooseGroup = res.data.map(el=>{
    //       return {
    //         selected: false,
    //         ...el,
    //         };
    //       })
    //     return true;
    //   } catch (error) {
    //   }
    // },
    // 设置历史选择群聊
    // async setLatelyChooseGroup() {
    //   try {
    //     // let res = await this.$api.setLatelyChooseGroup({
    //     //   chat_id: this.selectedGroup
    //     //     .map((el) => {
    //     //       return el._id;
    //     //     })
    //     //     .join(","),
    //     // });
    //   } catch (error) {
    //   }
    // },
    // 选中最近选择的群聊
    selected(item) {
      let index = this.recentChooseGroup.findIndex(
        (el) => el.chat_id === item.chat_id
      );
      if (!this.isMuti) {
          if (!item.selected) {
              for (let index in this.recentChooseGroup) {
                  this.recentChooseGroup[index].selected = false
              }
              this.recentChooseGroup[index].selected = true
              this.selectedGroup = []
              this.selectedGroup.push(item)
          }
      } else {
          this.recentChooseGroup[index].selected = !this.recentChooseGroup[index].selected;
          if (this.recentChooseGroup[index].selected) {
              this.isMuti
                  ? this.selectedChatIds.push(item.chat_id)
                  : (this.selectedChatIds = item.chat_id);
              // this.onChange("recentChoose");
              this.selectedGroup.push(item)
              this.checkAllStatus()
          } else {
              this.del(item.chat_id);
              this.checkAllStatus()
          }
      }
    },
    // 搜索
    search(type, list) {
      if (type === "2") {
        this.staffList = list;
      }
      if (type === "3") {
        this.tags = list;
      }
      this.onSearch();
    },
    // 下滑加载
    // peopleListLoad() {
    //   if (this.dataLoading || !this.loadMore || this.page.page === 1) return;
      // this.getList();
    // },
    // 列表排序条件
    filtrate(type) {
      let obj = {};
      if (type === "groupMembers") {
        obj = {
          1: 6,
          6: 0,
          0: 1,
          4: 1,
          5: 1,
        };
      }
      if (type === "buildGroupTime") {
        obj = {
          4: 5,
          5: 0,
          0: 4,
          1: 4,
          6: 4,
        };
      }
      this.orderBy = obj[this.orderBy];
      this.page.page = 1;
      // this.loadMore = true;
      this.getList();
    },
    // 获取列表数据
    async getList() {
      try {
        let obj = {
          ...this.page,
          keyword: this.name,
          status: 1,
          ...this.otherParams,
        };
        if (this.orderBy != 0) {
          obj.order_by = this.orderBy;
        }
        if (this.active === "2") {
          obj.owner_user_id = this.staffList
            .map((el) => {
              return el.user_id;
            })
            .join(",");
        }
        if (this.active === "3") {
          obj.tag_ids = this.tags
            .map((el) => {
              return el.id;
            })
            .join(",");
        }
        if(this.groupLiveCode){
          obj.scene = 1
        }
        if(this.aGroupGuests){
          obj.has_cst = 0
        }
        this.dataLoading = true;
        groupsList(obj).then(res => {
          let data = res.data || {}
          let {items, total} = data
          total = Number(total)
          // if (!items || !items?.length || list.value.length === total) {
          //   finished.value = true
          // }
          this.total = total
          this.dataList = items;
          this.dataLoading = false;
        })
          // let res = await this.$api.getCustomerGroupList(obj);


        // if (this.page.page === 1) {
        // } else {
        //   this.dataList = this.dataList.concat(res.data.list);
        // }
        // if (this.dataList.length === this.total) {
        //   this.loadMore = false;
        // } else {
        //   this.page.page++;
        // }
        this.onChange("recentChoose");
      } catch (error) {
        this.dataLoading = false;
      }
    },
    // 清空选择
    clear() {
      this.page.page = 1
      // this.loadMore = true;
      this.selectedGroup = [];
      this.selectedChatIds = [];
      this.indeterminate = false
      this.checkAll = false
      this.recentChooseGroup.map((el) => {
        el.selected = false;
      });
    },
    // 移除
    del(chatId) {
      // 单选
      if (!this.isMuti) {
        this.selectedChatIds = null;
        this.selectedGroup = [];
        this.recentChooseGroup.map((el) => {
          el.selected = false;
        });
        return;
      }
      // 多选
      let idIndex = this.selectedChatIds.findIndex((el) => el === chatId);
      let groupIndex = this.selectedGroup.findIndex(
        (el) => el.chat_id === chatId
      );
      let recentChooseIndex = this.recentChooseGroup.findIndex(
        (el) => el.chat_id === chatId
      );
      this.selectedChatIds.splice(idIndex, 1);
      this.selectedGroup.splice(groupIndex, 1);
      if(recentChooseIndex != -1){
        this.recentChooseGroup[recentChooseIndex].selected = false;
      }
      this.checkAllStatus()
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
        let oldIndex = this.selectedGroup.indexOf(this.oldData);
        let newIndex = this.selectedGroup.indexOf(this.newData);
        let newItems = [...this.selectedGroup];
        // 删除老的节点
        newItems.splice(oldIndex, 1);
        // 在列表中目标位置增加新的节点
        newItems.splice(newIndex, 0, this.oldData);
        this.selectedGroup = [...newItems];

        this.selectedChatIds = [];
        this.selectedGroup.map((item) => {
          this.selectedChatIds.push(item.chat_id);
        });
      }
    },
    // 拖动事件（主要是为了拖动时鼠标光标不变为禁止）
    dragover(e) {
      e.preventDefault();
    },
    handleOk() {
      // if (this.selectedGroup.length === 0) {
      //   this.$message.error("请选择群聊");
      //   return;
      // }
    //   if (this.selectedGroup.length > 0) {
    //     this.setLatelyChooseGroup();
    //   }
      this.$emit("selectedGroupChat", this.selectedGroup);
      this.visible = false;
    },
    handleCancel() {
      this.visible = false;
    },
    // 搜索
    async onSearch() {
      this.page.page = 1;
      // this.loadMore = true;
      await this.getList();
      // if(this.total < 200){
      //   await this.getListAll(1)
      // }
    },
    // 选择事件
    onChange(e, item) {
      if (!e) return;
      if (e !== "recentChoose") {
        if (e.target.checked) {
          this.isMuti
            ? this.selectedChatIds.push(item.chat_id)
            : (this.selectedChatIds = item.chat_id);
        } else {
          let index = this.selectedChatIds.findIndex(
            (el) => el === item.chat_id
          );
          this.selectedChatIds.splice(index, 1);
        }
      }

      // 单选
      if (!this.isMuti) {
        let items = this.dataList.find(
          (el) => el.chat_id === this.selectedChatIds
        );
        this.selectedGroup = [];
        items ? this.selectedGroup.push(items) : (this.selectedGroup = []);
        // 回显最近选择的选中状态
        let recentChooseIndex = this.recentChooseGroup.findIndex(
          (query) => query.chat_id === this.selectedChatIds
        );
        this.recentChooseGroup.map((el) => {
          el.selected = false;
        });
        if (recentChooseIndex === -1) return;
        this.recentChooseGroup[recentChooseIndex].selected = true;
        return;
      }
      // 多选
      let groupArray = this.selectedGroup.map((el) => {
        return el.chat_id;
      });
      // 将选中信息添加到右侧已选择群聊
      this.selectedChatIds.map((el) => {
        if (!groupArray.includes(el)) {
          let items = this.dataList.find((query) => query.chat_id === el);
          items ? this.selectedGroup.push(items) : "";
        }
        // 当有最近选择群聊时回显选中状态
        if (this.recentChooseGroup.length > 0) {
          this.recentChooseGroup.map((query) => {
            if (el === query.chat_id) {
              query.selected = true;
            }
          });
        }
      });
      // 将取消选择的信息从已选择群聊移除
      if (this.selectedChatIds.length < this.selectedGroup.length) {
        groupArray.map((el) => {
          if (!this.selectedChatIds.includes(el)) {
            let index = this.selectedGroup.findIndex(
              (query) => query.chat_id === el
            );
            this.selectedGroup.splice(index, 1);

            // 取消最近选择的选中状态
            let recentChooseIndex = this.recentChooseGroup.findIndex(
              (query) => query.chat_id === el
            );
            if (recentChooseIndex === -1) return;
            this.recentChooseGroup[recentChooseIndex].selected = false;
          }
        });
      }
      this.checkAllStatus()
    },
    async checkAllStatus(){
      if(!this.isMuti) return
      let indeterminate = this.dataList.some(item=>{
        return this.selectedChatIds.includes(item.chat_id)
      })
      let checkAll =  this.dataList.every(item=>{
        return this.selectedChatIds.includes(item.chat_id)
      })
      this.indeterminate = !checkAll ? indeterminate : false
      this.checkAll = !!this.dataList.length && checkAll
    },
    async onCheckAllChange(e){
      // 已选中+当前列表全部数据
      let list = [...this.selectedGroup,...this.dataList]
      list = this.uniqueFun(list)
      // 取消全选，则从选中列表中删除当前的列表的数据
      if(!e.target.checked){
        this.dataList.forEach(item=>{
          let index =  list.findIndex(el=>el.chat_id == item.chat_id)
          list.splice(index,1)
        })
      }

      Object.assign(this, {
        selectedGroup: list,
        indeterminate: false,
        checkAll: e.target.checked,
      });
      // 已选中的id
      let idsList = list.map(m=>m.chat_id)
      // 处理最近选择状态
      if(e.target.checked){
        idsList.forEach(item=>{
          this.recentChooseGroup.forEach(sup => {
            if(item == sup.chat_id){
              sup.selected = true
            }
          })
        })
      }else{
        this.dataList.forEach(item=>{
          this.recentChooseGroup.forEach(sup => {
            if(item.chat_id == sup.chat_id){
              sup.selected = false
            }
          })
        })
      }

      this.selectedChatIds = idsList
    },
    uniqueFun(arr) {
      const map = new Map()
        return arr.filter(item => {
            return !map.has(item.chat_id) && map.set(item.chat_id, 1)
        })
    },
    // async getListAll(val) {
    //   try {
    //     let obj = {
    //       page:1,
    //       size:this.total,
    //       name: this.name,
    //     };
    //     if (this.orderBy != 0) {
    //       obj.order_by = this.orderBy;
    //     }
    //     if (this.active === "2") {
    //       obj.owner_user_id = this.staffList
    //         .map((el) => {
    //           return el.user_id;
    //         })
    //         .join(",");
    //     }
    //     if (this.active === "3") {
    //       obj.tag_ids = this.tags
    //         .map((el) => {
    //           return el.id;
    //         })
    //         .join(",");
    //     }
    //     if(this.groupLiveCode){
    //       obj.scene = 1
    //     }
    //     if(this.aGroupGuests){
    //       obj.has_cst = 0
    //     }
    //     let res = await this.$api.getCustomerGroupList(obj);
    //     // this.dataListAll = res.data.list;
    //     if(val == 1){
    //       // 保存全部的列表
    //       // this.dataListAlls = res.data.list;
    //     }
    //     this.checkAllStatus();
    //     // this.allSpinLoading = false
    //   } catch (error) {
    //     // this.allSpinLoading = false
    //   }
    // },
    pageChange(page, pageSize){
      this.page.page = page
      this.getList()
    },
    onShowSizeChange(current, pageSize) {
      this.page.size = pageSize;
      this.getList()
    },
    tabChange(){
      this.tags = []
      this.staffList = []
      this.onSearch()
      // this.clear()
    },
  },
};
</script>

<style lang="less">
.select-group-chat {
  .sort-move {
    transition: transform 0.3s;
  }
  .ant-modal-header {
    border-bottom: 0;
    padding: 16px 16px 0 24px;
    margin-bottom: 0;
  }
  .ant-modal-content {
    padding: 0;
  }
  .ant-tabs-bar {
    margin-bottom: 0;
  }
  .ant-modal-title {
    font-weight: 400;
  }
  .ant-tabs-nav {
    margin-bottom: 0;
  }
  .main {
    height: 630px;
  }
  .ant-modal-footer {
    padding: 10px 16px;
    border-top: 1px solid #e8e8e8;
    margin-top: 0;
  }
  .left-box {
    width: 371px;
    height: 100%;
    border-right: 1px solid rgba(0, 0, 0, 0.15);
    padding: 16px 24px;
    display: flex;
    flex-direction: column;
    .list-header {
      margin-top: 16px;
      font-size: 14px;
      color: rgba(0, 0, 0, 0.45);
      span {
        color: rgba(0, 0, 0, 0.65);
      }
      .list-total {
      }
      .sort-box {
        .sort-icon {
          display: block;
          height: 8px;
        }
      }
    }
    .list {
      margin-top: 16px;
      flex: 1;
      overflow-y: auto;
      .ant-checkbox-group {
        width: 100%;
      }
      .item {
        transform: translate3d(0, 0, 0);
        padding: 8px 5px;
        font-size: 14px;
        width: 100%;
        &:hover {
          background-color: rgba(239, 244, 252, 1);
        }
        .ant-checkbox-wrapper,
        .ant-radio-wrapper {
          display: flex;
          align-items: center;
        }
        .group-name {
          font-weight: 500;
          font-size: 14px;
          color: #000000;
          margin-bottom: 4px;
          width: 270px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .head-portrait {
          width: 20px;
          height: 20px;
          border-radius: 2px;
          border-radius: 50%;
        }
        .leader{
          font-size: 12px;
          font-weight: 400;
          color: rgba(0,0,0,0.35);
        }
        .name {
          margin-left: 4px;
          width: 98px;
          font-size: 12px;
          font-weight: 400;
          color: #989898;
        }
        .leader-num{
          font-size: 12px;
          font-weight: 500;
          color: #989898;
        }
        .member {
          margin-left: 18px;
          color: rgba(0, 0, 0, 0.45);
          // span {
          //   color: rgba(0, 0, 0, 0.65);
          // }
        }
      }
    }
  }
  .right-box {
    font-size: 14px;
    padding: 16px 10px;
    height: 100%;
    display: flex;
    flex-direction: column;
    .right-header {
      padding: 0 5px 8px 13px;
      .title {
        color: rgba(0, 0, 0, 0.85);
        span {
          color: rgba(0, 0, 0, 0.45);
        }
      }
    }
    // .right-list-spin{
    //   display: flex;
    //   justify-content: center;
    //   align-items: center;
    //   height: 100%;
    // }
    .right-list {
      flex: 1;
      overflow-y: auto;
      .right-item {
        padding: 8px 5px;
        &:hover {
          background-color: rgba(239, 244, 252, 1);
        }
        .item-left {
          .sort {
            width: 16px;
            height: 16px;
            background: url("../../assets/image/no-click.png") no-repeat;
            background-size: contain;
            margin-right: 8px;
          }
          .title {
            color: #000000;
            margin-bottom: 4px;
            font-weight: 500;
            width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }
          .head-portrait {
            width: 20px;
            height: 20px;
            border-radius: 2px;
            border-radius: 50%;
          }
          .leader{
            font-size: 12px;
            font-weight: 400;
            color: rgba(0,0,0,0.35);
          }
          .name {
            margin-left: 4px;
            width: 98px;
            font-size: 12px;
            font-weight: 400;
            color: #989898;
          }
          .leader-num{
            font-size: 12px;
            font-weight: 500;
            color: #989898;
          }
        }
      }
    }
  }
}
</style>

<style lang="less" scoped>
  .fl-jsb-ac{
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .group-page{
    margin-top: 10px;
    text-align: end;
    white-space: nowrap;
    /deep/.ant-pagination-jump-next, /deep/.ant-pagination-jump-prev, /deep/.ant-pagination-next, /deep/.ant-pagination-prev{
      min-width: 23px;
      height: 23px;
      line-height: 23px;
    }
    /deep/.ant-pagination-item{
      min-width: 23px;
    }
    /deep/.ant-pagination-item, /deep/.ant-pagination-total-text{
      height: 23px;
      margin-right: 2px;
      line-height: 23px;
    }
    /deep/.ant-pagination-options{
      margin-left: 8px;
    }
    /deep/.ant-select-selection--single{
      height: 23px;
    }
    /deep/.ant-select-selection__rendered{
      margin-left: 3px;
      line-height: 23px;
    }
    /deep/.ant-pagination-options-size-changer.ant-select{
      margin-right:0;
    }
    /deep/.ant-select-arrow{
      right: 3px;
    }
    /deep/.ant-select-selection--single /deep/.ant-select-selection__rendered{
      margin-right:16px
    }
  }
</style>
