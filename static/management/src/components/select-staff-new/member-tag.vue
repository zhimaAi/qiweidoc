<template>
	<div class="framework">
		<a-input-search
			placeholder="请输入标签名称进行搜索"
			v-model.trim="searchParams.keyword"
			style="margin-top: -8px;"
		/>
		<div style="margin-top: 12px;">
			<a-radio-group
					v-model="local_bind_type"
					size="small"
					@change="sessionCheck"
				>
					<a-radio-button :value="0">
						全部
					</a-radio-button>
					<a-radio-button :value="1">
						有席位
					</a-radio-button>
					<a-radio-button :value="2">
						无席位
					</a-radio-button>
				</a-radio-group>
				<a-popover  placement="bottom">
					<template v-slot:content>
						<div class="sess-check-pro-title">
							<div class=" title fx-ac">
								建议选择有席位的员工，避免核心业务受干扰
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
					<a-icon type="exclamation-circle" style="margin-left: 4px;" />
				</a-popover>
		</div>
		<div class="tree-wraper">
			<div
				v-if="!showTree"
				style="justify-content: center;margin-top: 20px"
				class="fx-ac"
			>
				<a-spin />
			</div>
			<a-spin :spinning="spinning">
				<el-tree
					show-checkbox
					:props="props"
					:check-strictly="true"
					ref="elTreeRef_2"
					node-key="_id"
					:load="loadNode"
					@check-change="checkChange"
					:filter-node-method="filterNode"
					lazy
					:expand-on-click-node="false"
					v-if="showTree"
				>
					<div
						class="custom-tree-node"
						slot-scope="{node, data}"
						:class="{'tree-node-30': data.show_more}"
					>
						<div style="max-width: 333px;text-overflow: ellipsis;    overflow: hidden;" v-if="!data.is_staff" @click="itemTap(node, data)">{{data.tag_name}} ({{data.staff_num}})</div>
						<div v-else class="staff-wrape" :class="{'padd-b-30': data.show_more}" @click="itemTap(node, data)">
							<div class="staff-list fx-ac">
								<div class="i-left fx-ac">
									<img
										:src="data.avatar"
										alt=""
										class="img"
										:class="{'img-c': data.avatar == '/static/image/default-avatar.svg'}"
									>
									<!-- <div class="seat-bs" v-if="data.is_bind_license"></div>
									<div class="seat-n-bs" v-else></div> -->
								</div>
								<div class="i-right flex">
									<div class="ri-top fx-ac">
										<div class="name eliOne fx-ac" style=" white-space: normal;">
											<!-- <WWOpenData style="display:inline-block" type='userName' :showStatus="showStatus" :openid='data.user_id'>
												<span class="wk-black-65-text">{{ data.name }}</span>
											</WWOpenData> -->
											<a-tooltip placement="topLeft">
												<template v-slot:title>
													<WWOpenData
														style="display:inline-block"
														type='userName'
														:showStatus="showStatus"
														:openid='data.user_id'
													>
														<span>{{ data.name }}</span>
													</WWOpenData>
												</template>
												<WWOpenData
													style="display:inline-block"
													type='userName'
													:showStatus="showStatus"
													:openid='data.user_id'
												>
													<span class="eliOne">{{ data.name }}</span>
												</WWOpenData>
											</a-tooltip>
										</div>
										<!-- <div class="c-j flex" v-if="data.role == 1">超级管理员</div>
										<div class="p-t flex" v-if="data.role == 2">普通员工</div> -->
										<div class="nums fx-ac">
											<div style="color: rgba(0, 0,0 ,.45)">客户数：</div>
											<div style="color: rgba(0, 0,0 ,.65); font-weight: 500;">{{data.external_total}}</div>
										</div>
									</div>
									<div class="ri-bot fx-ac">
										<!-- <div style="color: rgba(0, 0,0 .45)">客户数：</div>
										<div style="color: rgba(0, 0,0 .65)">{{data.external_total}}</div> -->
                    <div class="seat-bind-box mr5">
                      <div class="bind-no" v-if="!data.is_bind_license"></div>
                      <div class="interflow-bind" v-else-if="data.license_type == 2"></div>
                      <div class="foundation-bind" v-else-if="data.license_type == 1"></div>
                    </div>
										<div class="bm eliOne">{{data.alias ? data.alias : '' }}</div>
										<div class="c-j flex" v-if="data.role == 1">超级管理员</div>
										<div class="p-t flex" v-if="data.role == 2">普通员工</div>
									</div>
								</div>
							</div>
							<div class="loading-btn" v-if="data.show_more">
								<a-button
									type="dashed"
									:ref="'loading_id' + data.loading_id"
									icon="redo"
									@click.stop="loadMoreTap(node, data)"
								>
									点击加载更多
								</a-button>
							</div>
						</div>
					</div>
				</el-tree>
			</a-spin>
		</div>
	</div>
</template>

<script>
import api from "@api/staff-manage/index.js";
// import wxInitFunc from "@/utils/initWxConfig.js"
import WWOpenData from "@/components/wwOpenData/index"
export default {
	name:'',
	props: [
		'selectData',
		'selectType',
		'isSession',
		'isAuthMode',
		'excludeRole',
		'bind_type',
		'filterStaff',
		'group_chat',
		'hideTagName',
		'viewAppointPermissions',
	],
	data() {
		return {
			searchParams: {
				keyword: ''
			},

			props: {
				children: 'child',
				isLeaf: function(data, node) {
					return data.child && data.child.length === 0
				}
			},

			staff_list_params: { //全部员工列表参数
				page: 0,
				size: 20,
			},
			local_bind_type: 0, //席位绑定状态
			staff_list_Loading: false,//全部员工列表
			staff_list_nomore: true,//全部员工列表没有更多了

			textures_list: [],

			currTotal: 0,

			currSelectKeys: [],

			group_ids: [],

			spinning: false,

			showTree: true,

		};
	},
	watch: {
		// currSelectKeys() {
		// 	// console.log(this.currSelectKeys);
		// 	// console.log(this.$refs.elTreeRef_2.getCheckedNodes());
		// },
		'searchParams.keyword'() {
			this.$refs.elTreeRef_2.filter(this.searchParams.keyword);
		},
		selectData() {
			let ids = this.selectData.filter(i => i.data_type === 1).map(i => i.staff_id)
			ids.forEach(i => {
				if (!this.currSelectKeys.includes(i)) {
					this.currSelectKeys.push(i)
					this.setTreeKeys()
				}
			})
		},
	},
	computed: {
		showStatus() {
      let status = this.$store.state.user.active_corp.setting_status == 0 || this.$store.state.user.active_corp.setting_status == 3
      if (status) {
        // getAgentConfig({url: location.pathname}).then(res => {
        //   wxInitFunc("", res.data)
        // })
      }
      return status
    },
		bind_num() {
      return this.$store.state.user.staff_seat.bind_num || 0;
    },
    unbind_num() {
      return this.$store.state.user.staff_seat.unbind_num || 0;
    },
	},
	components: {
		WWOpenData
	},
	created() {
	},
	methods: {
		clearCheck() {
			this.currSelectKeys = []
			this.setTreeKeys()
		},
		clearParKey() {
			this.currSelectKeys = this.currSelectKeys.filter(i => i.indexOf('_') != -1)
			setTimeout(() => {
				this.setTreeKeys()
			}, 500)
		},
		setCheck(item, index, type) {
			if (type === 1) {
				this.group_ids.forEach(it => {
					let flag = this.currSelectKeys.findIndex(i => i === it + "_" + item.staff_id)
					if (flag !== -1) {
						this.currSelectKeys.splice(flag, 1)
					}
				})
				this.setTreeKeys()
			} else if (type === 4) {
				let flag = this.currSelectKeys.findIndex(i => i === item._id)
				if (flag !== - 1) {
					this.currSelectKeys.splice(flag, 1)
					this.setTreeKeys()
				}
			}
		},
		filterNode(value, data) {
			if (!data.tag_name) {
				return true
			}
			if (!this.hideTagName && !this.searchParams.keyword) {
				return true
			}
			if (this.hideTagName && !this.searchParams.keyword) {
				return data.tag_name.indexOf(this.hideTagName ) === -1
			}
			if (!this.hideTagName && this.searchParams.keyword) {
				return data.tag_name.indexOf(this.searchParams.keyword) !== -1
			}
			if (this.hideTagName && this.searchParams.keyword) {
					return data.tag_name.indexOf(this.searchParams.keyword) !== -1 && data.tag_name.indexOf(this.hideTagName) === -1
			}
		},
		//节点一项的点击
		itemTap(node, data) {
			if (data.disabled) {
				return
			}
      let {_id} = data
      //单选
			if (this.selectType !== 'multiple') {
				this.currSelectKeys = [_id]
        this.setTreeKeys()
			} else {
				//多选
        if (this.currSelectKeys.includes(_id)) {
          let flag = this.currSelectKeys.findIndex(i => i === _id)
          if (flag !== -1) {
            this.currSelectKeys.splice(flag, 1)
            this.setTreeKeys()
          }
        } else {
          this.currSelectKeys.push(_id)
          this.setTreeKeys()
        }
      }
		},
		//设置树形选中
		setTreeKeys() {
			this.$refs.elTreeRef_2.setCheckedKeys(this.currSelectKeys)
		},
		checkChange(item, status) {
			// console.log(item);
			// console.log(status);
			if (status) {
				if (!this.currSelectKeys.includes(item._id)) {
					this.currSelectKeys.push(item._id)
				}
			} else {
				let flag = this.currSelectKeys.findIndex(i => i === item._id)
				if (flag !== -1) {
					this.currSelectKeys.splice(flag, 1)
				}
			}
			let dataList = JSON.parse(JSON.stringify(this.selectData))
			let {is_staff} = item

			if (is_staff) {
				item.data_type = 1
				//单选
				if (this.selectType !== 'multiple') {
					dataList = [item]
				} else {
					//多选
					let ids = dataList.filter(i => i.data_type === 1).map(i => i.staff_id)
					if (status && !ids.includes(item.staff_id)) {
						dataList.push(item)

					} else if (!status && ids.includes(item.staff_id)) {
						dataList.splice(dataList.findIndex(i => i.staff_id == item.staff_id), 1)
					}
				}
				this.$emit('setSelect', dataList)

			} else {
				item.data_type = 4

				let ids = dataList.filter(i => i.data_type === 4).map(i => i._id)
				if (status && !ids.includes(item._id)) {
					this.$emit('setLoading', true)
					this.getCurrStaffList(item).then(res => {
						item.staff_list = res
						dataList.push(item)
						this.$emit('setLoading', false)
						this.$emit('setSelect', dataList)
					})

				} else if (!status && ids.includes(item._id)) {
					let flag
					dataList.forEach((i, ind) => {
						if (i._id === item._id && i.data_type == 4) {
							flag = ind
						}
					})
					dataList.splice(flag, 1)
					this.$emit('setSelect', dataList)
				}
			}

		},
		getFrameWorkList() {
			this.spinning = true
			let params = {
				page: 1,
				size: 3000
			}
      if (this.isSession) {
				params.is_session = this.isSession
			}
			if (this.isAuthMode) {
				params.is_auth_mode = 1
			}
			if (this.excludeRole) {
				params.exclude_role = this.excludeRole
			}
			if (this.bind_type) {
				params.bind_type = this.bind_type
			} else {
				params.bind_type = this.local_bind_type
			}
			if (this.group_chat) {
				params.group_chat = this.group_chat
			}
      // 指定员工的员工
      if(this.viewAppointPermissions){
        params.permission_staff_ids = this.viewAppointPermissions
      }
			return api.bookTagGetLocalTagList(params).then(res => {
				this.spinning = false
				if (res && res.data && Array.isArray(res.data.list)) {
					// this.textures_list = res.data
					this.group_ids = res.data.list.map(i => i._id)
					let setDis = (item) => {
						item.forEach(i => {
							// this.isSession || this.excludeRole || this.group_chat || this.bind_type  去掉这四个条件
							i.disabled = i.staff_num * 1 > 500 || this.selectType !== 'multiple'
							if (i.child) {
								setDis(i.child)
							}
						})
					}
					setDis(res.data.list)
					return Promise.resolve(res.data.list)
				}
			}).catch(err => {
				this.spinning = false
				return Promise.resolve([])
			})
		},
		//加载更多节点
		async loadNode(node, resolve) {
			if (node.level === 0) {
				let res = await this.getFrameWorkList()
				this.$nextTick(() => {
					this.setTreeKeys()
					if (this.hideTagName) {
						this.$refs.elTreeRef_2.filter(this.hideTagName)
					}
				})
				return resolve(res);
			} else {
				this.staff_list_nomore = false
				this.staff_list_params.tag_id = node.data.tag_id

				this.staff_list_params.page = 1
				let res = await this.getUser_get_staff_list()
				res.forEach((i, index) => {
					i.child = []
					i.is_staff = true
					i.par_department_id = node.data._id
					i.tag_id = node.data.tag_id
					i.cur_page = 1
					i._id = node.data._id + '_' + i.staff_id
					i.loading_id = i.staff_id
				})
				if (res[res.length -1]) {
					res[res.length -1].show_more = !this.staff_list_nomore
				}
				resolve([...res])
				this.setNodeSty()
			}
		},
		//加载更多按钮点击
		async loadMoreTap(node, data) {
			this.$refs['loading_id' + data.loading_id].$el.__vue__.sLoading = true
			let data_arr = node.parent.childNodes.map(i => i.data)
			data_arr.forEach(i => i.show_more = false)
			this.staff_list_nomore = false
			this.staff_list_params.tag_id = data.tag_id
			this.staff_list_params.page = data.cur_page + 1
			// console.log(data);
			let res = await this.getUser_get_staff_list()
			res.forEach((i, index) => {
				i.child = []
				i.is_staff = true
				i.par_department_id = data.par_department_id
				i.tag_id = data.tag_id
				i.cur_page = data.cur_page + 1
				i._id = data.par_department_id + '_' + i.staff_id
				i.loading_id = i.staff_id
			})
			if (res[res.length -1]) {
				res[res.length -1].show_more = !this.staff_list_nomore
			}
			let all_arr = [...data_arr, ...res]
			this.$refs.elTreeRef_2.updateKeyChildren(data.par_department_id, all_arr)
			this.setNodeSty()
		},
		//设置最后一个节点的样式
		setNodeSty() {
			this.$nextTick(() => {
				//先重置
				let conts = document.querySelectorAll('.el-tree-node__content')
				conts.forEach(i => {
					i.getElementsByTagName('label')[0].style.marginTop = 0
				})
				//再设置
				let arr = document.querySelectorAll('.tree-node-30')
				arr.forEach(i => {
					let par = i.parentNode.getElementsByTagName('label')
					par[0].style.marginTop = '-40px'
				})
			})
		},
		//获取员工列表
    getUser_get_staff_list() {
      if (this.staff_list_nomore ) return
      if (this.staff_list_Loading ) return
      this.staff_list_Loading = true
			if (this.isSession) {
				this.staff_list_params.is_session = this.isSession
			}
			if (this.isAuthMode) {
				this.staff_list_params.is_auth_mode = 1
			}
			if (this.excludeRole) {
				this.staff_list_params.exclude_role = this.excludeRole
			}
			if (this.bind_type) {
				this.staff_list_params.bind_type = this.bind_type
			} else {
				this.staff_list_params.bind_type = this.local_bind_type
			}
			if (this.group_chat) {
				this.staff_list_params.group_chat = this.group_chat
			}
      // 指定员工的员工
      if(this.viewAppointPermissions){
        this.staff_list_params.permission_staff_ids = this.viewAppointPermissions
      }
      return api.bookTagGetTagStaff(this.staff_list_params).then(res => {
        this.staff_list_Loading = false
				//判断是否有数据
        if (res.data && res.data.list && res.data.list.length) {
					//如果当前返回数据的length小于当前页所需页大小, 表示没有更多了
          if (res.data.list.length < this.staff_list_params.size) {
            this.staff_list_nomore = true
          }
					if (this.filterStaff.key) {
						res.data.list = res.data.list.filter(i => {
							return i[this.filterStaff.key] == this.filterStaff.value
						})
					}
					//设置返回数据的当前选中
					let setCheck = (arr) => {
            res.data.list.forEach(i => {
              if (arr.includes(i.staff_id)) {
								this.group_ids.forEach(it => {
									let id = it + '_' + i.staff_id
									if (!this.currSelectKeys.includes(id)) {
                		this.currSelectKeys.push(id)
									}
								})
								this.setTreeKeys()
              }
            })
          }
					setCheck(this.selectData.map(i => i.staff_id))

					this.currTotal = res.data.pagination.total
					return Promise.resolve(res.data.list)
        } else {
					return Promise.resolve([])
				}
      }).catch(err => {
				return Promise.resolve([])
			})
    },
		getCurrStaffList(item) {
      let params = {
        page: 1,
				size: 1000,
				tag_id: item.tag_id,
      }
      if (this.isSession) {
				params.is_session = this.isSession
			}
			if (this.isAuthMode) {
				params.is_auth_mode = 1
			}
			if (this.excludeRole) {
				params.exclude_role = this.excludeRole
			}
			if (this.bind_type) {
				params.bind_type = this.bind_type
			} else {
				params.bind_type = this.local_bind_type
			}
			if (this.group_chat) {
				params.group_chat = this.group_chat
			}
      // 指定员工的员工
      if(this.viewAppointPermissions){
        params.permission_staff_ids = this.viewAppointPermissions
      }
			return api.bookTagGetTagStaff(params).then(res => {
				return Promise.resolve(res.data.list)
			}).catch(err => {
				return Promise.resolve([])
			})
		},
		initMember() {
			this.showTree = false
			setTimeout(() => {
				this.showTree = true
			}, 500)
		},
		sessionCheck(e) {
			this.initMember()
		},
		openSeat() {
			window.open('/manage/index#/seatManage')
		},
	},
};
</script>

<style scoped lang="less">
.sess-check-pro-title{
	.title{
		font-size: 14px;
		font-weight: 400;
		color: rgba(0,0,0,0.85);
	}
	.nums{
		margin-top: 20px;
		.sc-num{
			margin-left: 60px;
		}
		.num{
			.fir{
				font-size: 30px;
				font-weight: 500;
				color: rgba(0,0,0,0.85);
			}
			.sec{
				font-size: 14px;
				font-weight: 400;
				color: rgba(0,0,0,0.45);
			}
		}
	}
}
.framework{
	margin-right: 10px;
}
.tree-wraper{
	margin-top: 10px;
	max-height: 450px;
	overflow-y: auto;
}
.staff-list{
	padding: 8px;
	padding-left: 0;
	padding-right: 10px;
	cursor: pointer;
	.i-left{
		position: relative;
		.img{
			width: 44px;
			height: 44px;
			border-radius: 2px;
			// margin-left: 8px;
		}
		.img-c{
			background-color: #5196FF;
		}
		.seat-bs{
			position: absolute;
			right: 0;
			bottom: 0;
			background-image: url('../../assets/image/select-staff-new/seat-has.svg');
			background-size: 100% 100%;
			width: 26px;
			height: 26px;
		}
		.seat-n-bs{
			position: absolute;
			right: 0;
			bottom: 0;
			width: 26px;
			height: 26px;
			background-image: url('../../assets/image/select-staff-new/seat-no.svg');
			background-size: 100% 100%;
		}
	}
	.i-right{
		margin-left: 8px;
		height: 48px;
		flex: 1;
		flex-direction: column;
		overflow: hidden;
		justify-content: space-between;
		.ri-top{
			position: relative;;
			.name{
				margin-right: 4px;
				color: rgba(0,0,0,0.85);
				font-weight: 600;
				flex: 1;
			}
			.nums{
				// position: absolute;
				// left:112px;
				// top: 0;
				margin-right: 10px;
			}
		}
		.ri-bot{
			.bm{
				flex: 1;
				margin-right: 10px;
				display: block;
				font-size: 14px;
				font-weight: 400;
				color: rgba(0,0,0,0.85);
				line-height: 22px;
			}
			.c-j{
				// width: 76px;
				height: 22px;
				// background: #E9F1FE;
				// border-radius: 2px;
				// border: 1px solid #99BFFD;
				font-size: 12px;
				margin-right: 10px;
				// font-weight: 600;
				color: #2475FC;
				align-items: center;
				justify-content: center;
			}
			.p-t{
				// width: 76px;
				height: 22px;
				// background: rgba(0,0,0,0.0400);
				// border-radius: 2px;
				// border: 1px solid rgba(0,0,0,0.1500);
				font-size: 12px;
				margin-right: 10px;
				// font-weight: 600;
				align-items: center;
				justify-content: center;
				font-size: 12px;
				color: rgba(0,0,0,0.6500);
			}
      .seat-bind-box{
        .bind-no{
          width: 18px;
          height: 18px;
          background-image: url('../../assets/image/select-staff-new/bind-no.svg');
			    background-size: 100% 100%;
        }
        .foundation-bind{
          width: 50px;
          height: 20px;
          background-image: url('../../assets/image/select-staff-new/foundation-bind.svg');
			    background-size: 100% 100%;
        }
        .interflow-bind{
          width: 50px;
          height: 20px;
          background-image: url('../../assets/image/select-staff-new/interflow-bind.svg');
			    background-size: 100% 100%;
        }
      }
		}
	}
}
.padd-b-30{
	padding-bottom: 40px;
}
.staff-wrape{
	position: relative;
	.loading-btn{
		position: absolute;
		left: 0;
		bottom: 8px;
	}
}
/deep/.el-tree-node__content{
	height: auto;
	min-height: 26px;
	width: 100%;
	.custom-tree-node{
		flex: 1;
		overflow: hidden;
	}
}
// 文字省略
.eliOne {
  overflow: hidden;
  display: -webkit-box;
  text-overflow: ellipsis;
  -webkit-line-clamp: 1; /*要显示的行数*/
  -webkit-box-orient: vertical;
  word-break: break-all;
}
</style>
