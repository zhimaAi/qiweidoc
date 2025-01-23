<template>
  <div class="all-staff">
    <a-input-search placeholder="请输入员工名称进行搜索" @search="addPeopleSearch" style="margin-top: -8px;"
      v-model:value.trim="staff_list_params.keyword" />
    <!-- <div class="old-select" v-if="oldSelect.length">
      <div class="tit">最近选择</div>
      <div class="items flex">
        <div class="item fx-ac"
          :class="{ 'oth-dis': oldSelect.length === 2 || oldSelect.length === 4, 'has-select': item.checked }"
          v-for="(item, index) in oldSelect" :key="index" @click="oldSelectTap(item, index)">
          <img class="img" :src="item.avatar" alt="">
          <div class="name">{{item.name}}</div>
          <div class="name-wra fx-ac" :class="{ 'oth-name-wra': oldSelect.length === 2 || oldSelect.length === 4 }">
            <a-tooltip>
              <template #title>
                <WWOpenData style="display:inline-block" type='userName' :showStatus="showStatus"
                  :openid='item.user_id'>
                  <span class="name">{{ item.name }}</span>
                </WWOpenData>
              </template>
              <WWOpenData style="display:inline-block;" class="eliOne" type='userName' :showStatus="showStatus"
                :showEliOne="true" :openid='item.user_id'>
                <span class="name eliOne">{{ item.name }}</span>
              </WWOpenData>
            </a-tooltip>
          </div>
          <img src="../../assets/image/select-staff-new/goux.png" alt="" class="gou-x" v-if="item.checked" />
        </div>
      </div>
    </div> -->
    <div class="sess-check fx-ac">
      <!-- <a-checkbox
				:checked="staff_list_params.has_seat == 1"
				@change="sessionCheck"
			>
				仅展示有席位
				<a-popover  placement="bottom">
					<template #content>
						<div class="sess-check-pro-title">
							<div class=" title fx-ac">
								仅支持选择席位在有效期内的员工
								<a-button type="link" style="margin-left: 20px;" @click="openSeat">
									绑定席位
								</a-button>
							</div>
							<div class="nums fx-ac">
								<div class="num">
									<div class="fir">{{bind_num}}</div>
									<div class="sec">有效席位员工数</div>
								</div>
								<div class="num sc-num">
									<div class="fir">{{unbind_num}}</div>
									<div class="sec">到期未绑定席位员工数</div>
								</div>
							</div>
						</div>
					</template>
					<a-icon type="exclamation-circle" style="margin-top: 4px;" />
				</a-popover>
			</a-checkbox>
			<a-checkbox
				style="margin-left: -6px;"
				:checked="staff_list_params.has_seat === 0"
				@change="notSessionCheck"
			>
				仅展示无席位
			</a-checkbox> -->
      <div class="sel-radio-type fx-ac" style="margin-top: 4px;">
        <!-- <a-radio-group v-model="local_bind_type" size="small" @change="sessionCheck">
          <a-radio-button :value="0">
            全部
          </a-radio-button>
          <a-radio-button :value="1">
            有席位
          </a-radio-button>
          <a-radio-button :value="2">
            无席位
          </a-radio-button>
        </a-radio-group> -->
        <a-popover placement="bottom">
          <template #content>
            <div class="sess-check-pro-title">
              <div class=" title fx-ac">
                建议选择有席位的员工，避免核心业务受干扰
                <a-button type="link" style="margin-left: 20px;" @click="openSeat">
                  绑定席位
                </a-button>
              </div>
              <div class="nums fx-ac">
                <div class="num">
                  <div class="fir">{{ bind_num }}</div>
                  <div class="sec">有效席位员工数</div>
                </div>
                <div class="num sc-num">
                  <div class="fir">{{ unbind_num }}</div>
                  <div class="sec">到期未绑定席位员工数</div>
                </div>
              </div>
            </div>
          </template>
          <!-- <a-icon type="exclamation-circle" style="margin-left: 4px;" /> -->
        </a-popover>
      </div>
      <div class="fx-ac tit" @click="sortTap">
        <!-- <div class="fir">排序：</div> -->
        <div class="sec fx-ac">
          <div>客户数</div>
          <img src="../../assets/image/select-staff-new/px.png" v-if="sortType == 0" alt="">
          <img src="../../assets/image/select-staff-new/px-1.png" v-if="sortType == 1" alt="">
          <img src="../../assets/image/select-staff-new/px-2.png" v-if="sortType == 2" alt="">
        </div>
      </div>
    </div>
    <!-- 全部成员 -->
    <ZmScroll class="datas-item" @load="peopleListLoad" :finished="staff_list_nomore" :loading="loading">
      <div class="all-memeber fx-ac" v-for="(item, index) in staff_list" :key="item.id" @click.stop="wraperTap(item, index)">
        <!-- <div class="i-top flex">
					<div class="top-left flex">
						<img :src="item.avatar" alt="" class="img">
						<div class="left-ri">
							<div class="name fx-ac">
								<WWOpenData style="display:inline-block" type='userName' :showStatus="showStatus" :openid='item.user_id'>
									<span class="wk-black-65-text">{{ item.name }}</span>
								</WWOpenData>
							</div>
							<div class="c-j flex" v-if="item.role == 1">超级管理员</div>
							<div class="p-t flex" v-if="item.role == 2">普通员工</div>
						</div>
					</div>
					<el-checkbox v-model="item.checked" size="small" @change="checkChange(item,$event, index)"></el-checkbox>
				</div>
				<div class="i-bottom flex">
					<div class="left flex">
						客户数：<span>{{item.cst_total}}</span>
					</div>
					<div class="right flex">
						员工组：<span class="eliOne">{{getGroup(item.group)}}</span>
					</div>
				</div> -->
        <div class="i-left fx-ac">
          <input class="check-input" type="checkbox" v-model="item.checked" @change="checkChange(item, $event, index)">
          <!-- <a-checkbox v-model:checked="item.checked" /> -->
          <img src="@/assets/default-avatar.png" alt="" class="img"
            :class="{ 'img-c': item.avatar == '/static/image/default-avatar.svg' }">
          <!-- <div class="seat-bs" v-if="item.is_bind_license"></div>
					<div class="seat-n-bs" v-else></div> -->
        </div>
        <div class="i-right flex">
          <div class="ri-top fx-ac">
            <div class="name eliOne fx-ac">
              <!-- <WWOpenData style="display:inline-block" type='userName' :showStatus="showStatus" :openid='item.user_id'>
								<span class="eliOne">{{ item.name }}</span>
							</WWOpenData> -->
              <!-- <a-tooltip placement="topLeft">
                <template #title>
                  <WWOpenData style="display:inline-block" type='userName' :showStatus="showStatus"
                    :openid='item.user_id'>
                    <span>{{ item.name }}</span>
                  </WWOpenData>
                </template>
                <WWOpenData style="display:inline-block" type='userName' :showStatus="showStatus"
                  :openid='item.user_id'>
                  <span class="eliOne">{{ item.name }}</span>
                </WWOpenData>
              </a-tooltip> -->
              <span class="eliOne">{{ item.name }}</span>
            </div>
            <div class="nums fx-ac">
              <div style="color: rgba(0, 0,0 ,.45)">客户数：</div>
              <div style="color: rgba(0, 0,0 ,.65); font-weight: 500;">{{ item.cst_total }}</div>
            </div>
            <!-- <div class="c-j flex" v-if="item.role == 1">超级管理员</div>
						<div class="p-t flex" v-if="item.role == 2">普通员工</div> -->
          </div>
          <!-- <div class="ri-bot fx-ac">
            <div style="color: rgba(0, 0,0 .45)">客户数：</div>
						<div style="color: rgba(0, 0,0 .65)">{{item.cst_total}}</div>
            <div class="seat-bind-box mr5">
              <div class="bind-no" v-if="!item.is_bind_license"></div>
              <div class="interflow-bind" v-else-if="item.license_type == 2"></div>
              <div class="foundation-bind" v-else-if="item.license_type == 1"></div>
            </div>
            <div class="bm eliOne">{{ item.alias ? item.alias : '' }}</div>
            <div class="c-j flex" v-if="item.role == 1">超级管理员</div>
            <div class="p-t flex" v-if="item.role == 2">普通员工</div>
          </div> -->
        </div>
      </div>
      <a-empty v-if="!staff_list.length" />
    </ZmScroll>
    <div style="text-align: center;height: 18px;">
      <a-spin size="small" v-if="staff_list_Loading" />
      <div v-if="!staff_list_Loading && staff_list.length">{{ staff_list_nomore ? '' : '下滑加载更多' }}</div>
    </div>
  </div>
</template>

<script>
// import wxInitFunc from "@/utils/initWxConfig.js"
import ZmScroll from "@/components/zmScroll.vue";
import {staffList} from "@/api/company";
// import WWOpenData from "@/components/wwOpenData/index"
export default {
  name: '',
  props: [
    'selectData',
    'selectType',
    'isSession',
    'isAuthMode',
    'excludeRole',
    'bind_type',
    'filterStaff',
    'group_chat',
    'viewAppointPermissions',
  ],
  data () {
    return {
      loading: false,
      staff_list: [], //全部员工列表
      staff_list_params: { //全部员工列表参数
        page: 1,
        size: 10,
        keyword: '',
      },
      // local_bind_type: 0, //席位绑定状态
      staff_list_Loading: false,//全部员工列表
      staff_list_nomore: false,//全部员工列表没有更多了

      oldSelect: [],

      sortType: 1,

      searchTimer: '',
    };
  },
  components: {
    // WWOpenData,
    ZmScroll
  },
  computed: {
    showStatus () {
      // let status = this.$store.state.user.active_corp.setting_status == 0 || this.$store.state.user.active_corp.setting_status == 3
      let status = true
      if (status) {
        // getAgentConfig({url: location.pathname}).then(res => {
        //   wxInitFunc("", res.data)
        // })
      }
      return status
    },
    bind_num () {
      return this.$store.state.user.staff_seat.bind_num || 0;
    },
    unbind_num () {
      return this.$store.state.user.staff_seat.unbind_num || 0;
    },
  },
  created () {
    if (!this.staff_list.length) {
      this.loading = false
      this.getUser_get_staff_list()
    }
    this.getOldData()
  },
  watch: {
    selectData () {
      let staffIds = this.selectData.map(staff => {
        return staff.userid
      })
      for (let i = 0; i < this.staff_list.length; i++) {
        let staff = this.staff_list[i]
        if (staffIds.includes(staff.userid)) {
          staff.checked = true
        } else {
          staff.checked = false
        }
        this.staff_list[i] = staff
      }
      // for (let index = 0; index < this.oldSelect.length; index++) {
      //   let staff = this.oldSelect[index]
      //   if (staffIds.includes(staff.userid)) {
      //     staff.checked = true
      //   } else {
      //     staff.checked = false
      //   }
      //   this.oldSelect[index] = staff
      // }
    },
    "staff_list_params.keyword" () {
      clearInterval(this.searchTimer)
      this.searchTimer = setTimeout(() => {
        this.addPeopleSearch()
      }, 500)
    },
  },
  methods: {
    //员工搜索
    addPeopleSearch (e) {
      this.staff_list = []
      this.staff_list_nomore = false
      this.staff_list_params.page = 1
      this.loading = false
      this.getUser_get_staff_list()
    },
    //获取员工列表
    getUser_get_staff_list () {
      if (this.loading) return
      if (this.staff_list_nomore) return
      if (this.staff_list_Loading) return
      this.loading = true
      this.staff_list_Loading = true
      if (this.isSession) {
        this.staff_list_params.chat_status = this.isSession
      }
      if (this.isAuthMode) {
        this.staff_list_params.is_auth_mode = 1
      }
      if (this.excludeRole) {
        this.staff_list_params.exclude_role = this.excludeRole
      }
      // 仅显示没有席位的
      // if (this.bind_type) {
      //   this.staff_list_params.has_seat = this.bind_type == 2 ? 0 : this.bind_type
      // } else {
      //   this.staff_list_params.has_seat = this.local_bind_type == 0 ? '' : this.local_bind_type == 1 ? 1 : 0
      // }
      if (this.group_chat) {
        this.staff_list_params.group_chat = this.group_chat
      }
      if (this.sortType) {
        this.staff_list_params.order_by = this.sortType == 1 ? 'DESC' : 'ASC'
      } else {
        this.staff_list_params.order_by = ''
      }
      this.staff_list_params.order_fields = 'cst_total'
      // 指定员工的员工
      if (this.viewAppointPermissions) {
        this.staff_list_params.permission_userids = this.viewAppointPermissions
      }
      staffList(this.staff_list_params).then(res => {
        this.staff_list_Loading = false
        //判断是否有数据
        if (!res.data.items.length) {
          this.staff_list_nomore = true
          this.loading = false
          return false
        }
        if (res.data && res.data.items && res.data.items.length) {
            //如果当前返回数据的length小于当前页所需页大小, 表示没有更多了
            if (res.data.items.length < this.staff_list_params.size) {
              this.staff_list_nomore = true
            }
            if (this.filterStaff.key) {
            res.data.items = res.data.items.filter(i => {
                return i[this.filterStaff.key] == this.filterStaff.value
            })
            }
            this.staff_list = this.staff_list.concat(res.data.items)
            //设置返回数据的当前选中
            let setCheck = (arr) => {
              this.staff_list.forEach((i, ind) => {
                  if (arr.includes(i.userid)) {
                  i.checked = true
                  }
              })
            }
            setCheck(this.selectData.map(i => i.userid))
        }
        this.loading = false
      })
    },
    //全部员工下滑加载更多
    peopleListLoad () {
      if (this.staff_list_Loading) return
      this.staff_list_params.page++
      this.loading = false
      this.getUser_get_staff_list()
    },
    //处理员工分组
    getGroup (arr) {
      if (arr && arr.length) {
        return arr.map(i => i.name).join(",")
      }
      return "--"
    },
    setCheck (item, index) {
      let ids = this.staff_list.map(i => i.userid)
      let flag = ids.indexOf(item.userid)
      if (this.staff_list[flag]) {
        this.staff_list[flag].checked = false
        this.staff_list[flag] = this.staff_list[flag]
      }
    },
    wraperTap (item, index) {
      this.checkChange(item, !item.checked, index)
    },
    //全部员工选择改变
    checkChange (item, e, ind) {
      let dataList = JSON.parse(JSON.stringify(this.selectData))
      item.data_type = 1
      item.checked = e
      this.staff_list[ind] = item
      //单选
      if (this.selectType !== 'multiple') {
        this.staff_list.forEach((it, index) => {
          this.staff_list[index].checked = false
          if (it.userid === item.userid) {
            this.staff_list[index].checked = true
          }
        })
        dataList = [item]

      } else {
        //多选
        let ids = dataList.filter(i => i.data_type === 1).map(i => i.userid)
        if (e && !ids.includes(item.userid)) {
          dataList.push(item)

        } else if (!e && ids.includes(item.userid)) {
          let flag
          dataList.forEach((i, ind) => {
            if (i.userid === item.userid) {
              flag = ind
            }
          })
          dataList.splice(flag, 1)

        }
      }
      this.$emit('setSelect', dataList)
    },
    sortTap () {
      if (this.staff_list_Loading) return
      this.sortType++
      if (this.sortType > 2) {
        this.sortType = 0
      }
      this.staff_list = []
      this.staff_list_nomore = false
      this.staff_list_params.page = 1
      this.loading = false
      this.getUser_get_staff_list()
    },
    getOldData () {
      let params = {
        is_archive: this.isSession,
      }
      if (this.isAuthMode) {
        params.is_auth_mode = 1
      }
      // 	getLatelyChooseUser(params).then(res => {
      // 		if (res && res.data && Array.isArray(res.data)) {
      // 			let time = Date.now()
      // 			res.data.forEach(i => {
      // 				i.id = time ++
      // 			})
      // 			if (this.filterStaff.key) {
      // 				res.data = res.data.filter(i => {
      // 					return i[this.filterStaff.key] == this.filterStaff.value
      // 				})
      // 			}
      // 			let staffIds = this.selectData.map(staff => {
      // 				return staff.userid
      // 			})
      // 			for (let index = 0; index < res.data.length; index++) {
      // 				let staff =  res.data[index]
      // 				if (staffIds.includes(staff.userid)) {
      // 					staff.checked = true
      // 				} else {
      // 					staff.checked = false
      // 				}
      // 			}
      // 			this.oldSelect = res.data;
      // 			if(this.bind_type == '1'){
      // 				// 过滤一下 只要绑定席位的员工
      // 				this.oldSelect = this.oldSelect.filter((item)=>{
      // 					return item.is_bind_license == true;
      // 				})
      // 			}
      //   // 过滤一下 只要指定员工的员工
      //   if(this.viewAppointPermissions.length){
      //     this.oldSelect = this.oldSelect.filter(sub => this.viewAppointPermissions.includes(sub.userid))
      //   }
      // 		}
      // 	})
    },
    oldSelectTap (item, index) {
      item.checked = !item.checked
      this.oldSelect[index] = item

      let dataList = JSON.parse(JSON.stringify(this.selectData))
      let ids = dataList.filter(i => i.data_type === 1).map(i => i.userid)

      if (!ids.includes(item.userid)) {
        item.data_type = 1
        //单选
        if (this.selectType !== 'multiple') {
          dataList = [item]
        } else {
          dataList.push(item)
        }
      } else {
        let flag = ids.indexOf(item.userid)
        if (flag !== -1) {
          dataList.splice(flag, 1)
        }
      }
      this.$emit('setSelect', dataList)

    },
    sessionCheck (e) {
      // let flag = e.target.checked
      this.staff_list = []
      this.staff_list_nomore = false
      this.staff_list_params.page = 1
      this.loading = false
      this.getUser_get_staff_list()
    },
    // notSessionCheck(e) {
    // 	let flag = e.target.checked
    // 	this.staff_list = []
    //   this.staff_list_nomore = false
    //   this.staff_list_params.page  = 1
    // 	if (flag) {
    // 		this.staff_list_params.has_seat = 0
    // 	} else {
    // 		this.staff_list_params.has_seat = ''
    // 	}

    // 	this.getUser_get_staff_list()
    // },
    openSeat () {
      window.open('/manage/index#/seatManage')
    },
  },
};
</script>

<style scoped lang="less">
.sess-check-pro-title {
  .title {
    font-size: 14px;
    font-weight: 400;
    color: rgba(0, 0, 0, 0.85);
  }

  .nums {
    margin-top: 20px;

    .sc-num {
      margin-left: 60px;
    }

    .num {
      .fir {
        font-size: 30px;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.85);
      }

      .sec {
        font-size: 14px;
        font-weight: 400;
        color: rgba(0, 0, 0, 0.45);
      }
    }
  }
}

// 文字省略
.eliOne {
  overflow: hidden;
  display: -webkit-box;
  text-overflow: ellipsis;
  -webkit-line-clamp: 1;
  /*要显示的行数*/
  -webkit-box-orient: vertical;
  word-break: break-all;
}

.all-staff {
  margin-right: 10px;
}

.sess-check {
  margin-top: 8px;
  justify-content: space-between;

  .tit {
    margin-right: 22px;
    cursor: pointer;

    .fir {
      font-size: 14px;
      font-weight: 400;
      color: rgba(0, 0, 0, 0.45);
    }

    img {
      width: 12px;
      height: 12px;
      margin-top: 2px;
      margin-left: 4px;
    }
  }
}

.old-select {
  margin-top: 8px;

  .tit {
    font-size: 14px;
    font-weight: 400;
    color: #000000;
    line-height: 22px;
  }

  .items {
    flex-wrap: wrap;
    margin-top: 4px;
    margin-bottom: -8px;

    .item {
      background: rgba(0, 0, 0, 0.04);
      border-radius: 2px;
      opacity: 1;
      width: 102px;
      padding: 3px 0;
      cursor: pointer;
      justify-content: flex-start;
      align-items: center;
      border: 1px solid rgba(0, 0, 0, 0.15);
      margin-right: 8px;
      margin-bottom: 8px;
      align-items: center;
      position: relative;

      .img {
        width: 20px;
        height: 20px;
        margin-right: 8px;
        margin-left: 16px;
      }

      .name-wra {
        width: 50px;
      }

      .name {
        font-size: 14px;
        font-weight: 400;
        color: rgba(0, 0, 0, 0.65);
        line-height: 22px;
      }

      .gou-x {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 20px;
        height: 20px;
      }
    }

    .oth-dis {
      width: 170px !important;
    }

    .oth-name-wra {
      width: 120px !important;
    }

    .has-select {
      border-color: #2475FC;
    }

  }
}

.datas-item {
  height: 420px;
  margin-top: 8px;
  overflow-y: auto;

  .all-memeber {
    padding: 8px;
    padding-right: 10px;
    padding-left: 0;
    cursor: pointer;

    .i-left {
      position: relative;

      .img {
        width: 48px;
        height: 48px;
        margin-left: 8px;
        border-radius: 2px;
      }

      .img-c {
        background-color: #5196FF;
      }

      .seat-bs {
        position: absolute;
        right: 0;
        bottom: 0;
        background-image: url('../../assets/image/select-staff-new/seat-has.svg');
        background-size: 100% 100%;
        width: 26px;
        height: 26px;
      }

      .seat-n-bs {
        position: absolute;
        right: 0;
        bottom: 0;
        width: 26px;
        height: 26px;
        background-image: url('../../assets/image/select-staff-new/seat-no.svg');
        background-size: 100% 100%;
      }
    }

    .i-right {
      margin-left: 8px;
      height: 48px;
      flex: 1;
      flex-direction: column;
      // justify-content: space-between;
      justify-content: center;

      .ri-top {

        // position: relative;
        .name {
          color: rgba(0, 0, 0, 0.85);
          font-weight: 600;
          flex: 1;
          margin-right: 10px;
        }

        .nums {
          // position: absolute;
          // left:112px;
          // top: 0;
          margin-right: 10px;
        }
      }

      .ri-bot {
        .bm {
          flex: 1;
          margin-right: 10px;
          font-size: 14px;
          font-weight: 400;
          color: rgba(0, 0, 0, 0.85);
          line-height: 22px;
        }

        .c-j {
          // width: 76px;
          height: 22px;
          // background: #E9F1FE;
          // border-radius: 2px;
          // border: 1px solid #99BFFD;
          font-size: 12px;
          // font-weight: 600;
          color: #2475FC;
          align-items: center;
          justify-content: center;
          margin-right: 10px;
        }

        .p-t {
          // width: 76px;
          height: 22px;
          // background: rgba(0,0,0,0.0400);
          // border-radius: 2px;
          // border: 1px solid rgba(0,0,0,0.1500);
          font-size: 12px;
          // font-weight: 600;
          align-items: center;
          justify-content: center;
          font-size: 12px;
          margin-right: 10px;
          color: rgba(0, 0, 0, 0.6500);
        }

        .seat-bind-box {
          .bind-no {
            width: 18px;
            height: 18px;
            background-image: url('../../assets/image/select-staff-new/bind-no.svg');
            background-size: 100% 100%;
          }

          .foundation-bind {
            width: 50px;
            height: 20px;
            background-image: url('../../assets/image/select-staff-new/foundation-bind.svg');
            background-size: 100% 100%;
          }

          .interflow-bind {
            width: 50px;
            height: 20px;
            background-image: url('../../assets/image/select-staff-new/interflow-bind.svg');
            background-size: 100% 100%;
          }
        }
      }
    }
  }

  .select {
    border-radius: 2px;
    opacity: 1;
    border: 1px solid #2475FC;
  }

  .check-input {
    width: 16px;
    height: 16px;
  }
}
</style>
