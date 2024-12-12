<template>
	<div class="framework">
		<a-input-search
			placeholder="请输入员工分组进行搜索"
			:disabled="search_Loading"
			v-model.trim="searchParams.keyword"
		/>
		<div class="title flex">
			<div v-if="!hasSelect" class="fir">请选择一个员工分组</div>
			<div class="bread flex" v-else>
				<div class="left flex" @click="goBackOne">
					<img src="../../assets/image/select-staff-new/bacnk.png" alt="">
					<div>返回</div>
				</div>
				<div class="right-tit">{{selectTitle}}</div>
			</div>
		</div>
		<div class="big-wraper">
			<a-spin :spinning="textures_spinning" v-if="!hasSelect">
				<div class="textures-wraper" v-if="group_list.length">
					<div
						class="textures flex"
						v-for="(item, index) in group_list"
						:key="index"
						@click="selectPush(item, index)"
					>
						<div class="left flex">
							<img src="../../assets/image/select-staff-new/gro.png" alt="">
							<div class="name">{{item.name}}</div>
							<div style="margin-left: 4px;">({{item.staff_count}})</div>
						</div>
						<div class="right flex" @click.stop="">
							<el-checkbox
								v-model="item.checked"
								size="small"
								@change="checkChange(item,$event, 3)"
								v-if="item.staff_count * 1 <= 500 && selectType === 'multiple' && !isSession && !excludeRole && !bind_type && !group_chat"
							>
							</el-checkbox>
							<div class="icon flex" @click="selectPush(item, index)">
								<a-icon type="caret-right" />
							</div>
						</div>
					</div>
				</div>
				<a-empty description="暂无搜索分组" v-if="!group_list.length" />
			</a-spin>
			<div class="one-texture" v-else>
				<div
					class="datas-item"
					v-infinite-scroll="peopleListLoad"
				>
					<div
						class="all-memeber"
						v-for="(item, index) in staff_list"
						:key="item.staff_id"
					>
						<div class="i-top flex">
							<div class="top-left flex">
								<img :src="item.avatar" alt="" class="img">
								<div class="left-ri">
									<div class="name fx-ac">
											<!-- {{item.name}} -->
											<WWOpenData style="display:inline-block" type='userName' :showStatus="showStatus" :openid='item.user_id'>
												<span class="wk-black-65-text">{{ item.name }}</span>
											</WWOpenData>
										</div>
									<div class="c-j flex" v-if="item.role == 1">超级管理员</div>
									<div class="p-t flex" v-if="item.role == 2">普通员工</div>
								</div>
							</div>
							<el-checkbox v-model="item.checked" size="small" @change="checkChange(item,$event,1, index)"></el-checkbox>
						</div>
						<div class="i-bottom flex">
							<div class="left flex">
								客户数：<span>{{item.external_total}}</span>
							</div>
							<div class="right flex">
								员工组：<span class="eliOne">{{getGroup(item.group)}}</span>
							</div>
						</div>
					</div>
					<a-empty description="暂无员工" v-if="!staff_list.length" />
				</div>
				<div style="text-align: center;height: 18px;">
					<a-spin size="small" v-if="staff_list_Loading" />
					<div v-if="!staff_list_Loading && staff_list.length">{{staff_list_nomore ? '没有更多了' : '下滑加载更多'}}</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import api from "@api/staff-manage/index.js";
// import wxInitFunc from "@/utils/initWxConfig.js"
import WWOpenData from "@/components/wwOpenData/index"
export default {
	name:'',
	props: ['selectData','selectType','isSession','excludeRole','bind_type','group_chat'],
	data() {
		return {
			searchParams: {
				keyword: ''
			},
			search_Loading: false,

			hasSelect: false,
			selectTitle: '',

			staff_list: [], //全部员工列表
			staff_list_params: { //全部员工列表参数
				page: 0,
				size: 10,
			},
			staff_list_Loading: false,//全部员工列表
			staff_list_nomore: true,//全部员工列表没有更多了

			textures_spinning: false,
			textures_list: [],

			group_list: [],

			selectItems: [],
		};
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
	},
	components: {
		WWOpenData
	},
	created() {
		this.getFrameWorkList()
	},
	watch: {
		'searchParams.keyword'() {
			this.onSearch()
		},
    selectData() {
      let staffIds = this.selectData.map(staff => {
        return staff.staff_id
      })
      for (let i=0;i < this.staff_list.length;i++) {
        let staff =  this.staff_list[i]
        if (staffIds.includes(staff.staff_id)) {
          staff.checked = true
        } else {
          staff.checked = false
        }
        this.$set(this.staff_list, i, staff)
      }
    }
	},
	methods: {
		getFrameWorkList() {
			this.textures_spinning = true
			let params = {}
			if (this.isSession) {
				params.is_session = this.isSession
			}
			api.getStaffGroupList(params).then(res => {
				this.textures_spinning = false
				if (res && res.data && Array.isArray(res.data)) {
					this.textures_list = res.data
					this.group_list = this.textures_list
				}
			}).catch(err => {
				this.textures_spinning = false
			})
		},
		setCheck(item, index, type) {
			if (type === 1) {
				let ids = this.staff_list.map(i => i.staff_id)
				let flag = ids.indexOf(item.staff_id)
				if (this.staff_list[flag]) {
					this.staff_list[flag].checked = false
					this.$set(this.staff_list, flag, this.staff_list[flag])
				}

			} else if (type === 3) {

				if (this.group_list.length) {
					let ids = this.group_list.map(i => i._id)
					let flag = ids.indexOf(item._id)
					if (this.group_list[flag]) {
						this.group_list[flag].checked = false
						this.$set(this.group_list, flag, this.group_list[flag])
					}
				}

				if (this.textures_list.length) {
					let ids = this.textures_list.map(i => i._id)
					let flag = ids.indexOf(item._id)
					if (this.textures_list[flag]) {
						this.textures_list[flag].checked = false
						this.$set(this.textures_list, flag, this.textures_list[flag])
					}
				}

			}
		},
		selectPush(item, index) {
			this.hasSelect = true

			this.staff_list = []

			this.selectTitle = item.name
			this.selectItems.push(item)


			this.staff_list_nomore = false
			this.staff_list_params.group_id = item._id

			this.staff_list_params.page = 1
			this.getUser_get_staff_list()
		},
		goBackOne() {
			this.selectItems.splice(this.selectItems.length - 1, 1)

			if (!this.selectItems.length) {
				this.hasSelect = false

			} else {
				let item = this.selectItems[this.selectItems.length -1]

				this.staff_list = []

				this.selectTitle = item.name

				this.staff_list_nomore = false
				this.staff_list_params.group_id = item._id

				this.staff_list_params.page = 1
				this.getUser_get_staff_list()

			}
		},
		onSearch() {
			this.hasSelect = false

			if (!this.searchParams.keyword) {
				this.group_list = this.textures_list
			} else {
				this.group_list = this.textures_list.filter(i => i.name.indexOf(this.searchParams.keyword) !== -1)
			}
		},
		//处理员工分组
    getGroup(arr) {
      if (arr && arr.length) {
        return arr.map(i => i.name ).join(",")
      }
      return "--"
    },
		//全部员工下滑加载更多
    peopleListLoad() {
      if (this.staff_list_Loading) return
      this.staff_list_params.page ++
      this.getUser_get_staff_list()
    },
		//获取员工列表
    getUser_get_staff_list() {
      if (this.staff_list_nomore ) return
      if (this.staff_list_Loading ) return
      this.staff_list_Loading = true
			if (this.isSession) {
				this.staff_list_params.is_session = this.isSession
			}
			if (this.excludeRole) {
				this.staff_list_params.exclude_role = this.excludeRole
			}
			if (this.bind_type) {
				this.staff_list_params.bind_type = this.bind_type
			}
			if (this.group_chat) {
				this.staff_list_params.group_chat = this.group_chat
			}
      this.$api.user_get_staff_list(this.staff_list_params).then(res => {
        this.staff_list_Loading = false
				//判断是否有数据
        if (res.data && res.data.list && res.data.list.length) {
					//如果当前返回数据的length小于当前页所需页大小, 表示没有更多了
          if (res.data.list.length < this.staff_list_params.size) {
            this.staff_list_nomore = true
          }
          this.staff_list = this.staff_list.concat(res.data.list)
					//设置返回数据的当前选中
					let setCheck = (arr) => {
            this.staff_list.forEach(i => {
              if (arr.includes(i.staff_id)) {
                i.checked = true
              }
            })
          }
					setCheck(this.selectData.map(i => i.staff_id))
        }
      })
    },
		checkChange(item, e, type, ind) {
			let dataList = JSON.parse(JSON.stringify(this.selectData))
			if (ind || ind === 0) {
				item.checked = e
				this.$set(this.staff_list,ind, item)
			}
			if (type === 1) {
				item.data_type = 1
				//单选
				if (this.selectType !== 'multiple') {
					this.staff_list.forEach((it, index) => {
						this.$set(this.staff_list[index],'checked', false)
						if (it.staff_id === item.staff_id) {
							this.$set(this.staff_list[index],'checked', true)
						}
					})
					dataList = [item]

				} else {
					//多选
					let ids = dataList.filter(i => i.data_type === 1).map(i => i.staff_id)
					if (e && !ids.includes(item.staff_id)) {
						dataList.push(item)

					} else if (!e && ids.includes(item.staff_id)) {
						dataList.splice(ids.indexOf(item.staff_id), 1)

					}
				}

				this.$emit('setSelect', dataList)

			} else if (type === 3) {
				item.data_type = 3

				let ids = dataList.filter(i => i.data_type === 3).map(i => i._id)
				if (e && !ids.includes(item._id)) {

					this.$emit('setLoading', true)
					this.getCurrStaffList(item).then(res => {
						item.staff_list = res
						dataList.push(item)
						this.$emit('setLoading', false)
					})

				} else if (!e && ids.includes(item._id)) {

					let flag
					dataList.forEach((i, ind) => {
						if (i._id === item._id) {
							flag = ind
						}
					})
					dataList.splice(flag, 1)

				}
				this.$emit('setSelect', dataList)
			}
		},
		getCurrStaffList(item) {
      let params = {
        page: 1,
				size: 1000,
				group_id: item._id,
      }
      if (this.isSession) {
				params.is_session = this.isSession
			}
			if (this.excludeRole) {
				params.exclude_role = this.excludeRole
			}
			if (this.bind_type) {
				params.bind_type = this.bind_type
			}
			if (this.group_chat) {
				params.group_chat = this.group_chat
			}
			// return user_get_staff_list(params).then(res => {
			// 	return Promise.resolve(res.data.list)
			// }).catch(err => {
			// 	return Promise.resolve([])
			// })
		},
	},
};
</script>

<style scoped lang="less">
.framework{
	margin-right: 10px;
	.title{
		height: 38px;
		align-items: center;
		.fir{
			font-size: 14px;
			font-weight: 400;
			color: rgba(0,0,0,0.4500);
			line-height: 22px;
		}
		.bread{
			.left{
				align-items: center;
				cursor: pointer;
				img{
					width: 16px;
					height: 16px;
				}
				div{
					font-size: 14px;
					font-weight: 400;
					color: #2475FC;
					line-height: 22px;
					margin-left: 3px;
				}
			}
			.right-tit{
				font-size: 14px;
				font-weight: 400;
				color: rgba(0,0,0,0.4500);
				line-height: 22px;
				margin-left: 16px;
			}
		}
	}
	.big-wraper{
		margin-left: 8px;
	}
	.textures-wraper{
		padding-right: 6px;
		height: 344px;
		overflow: auto;
	}
	.textures{
		height: 40px;
		cursor: pointer;
		align-items: center;
		.left{
			flex: 1;
			img{
				width: 24px;
				height: 24px;
			}
			.name{
				font-size: 14px;
				font-weight: 400;
				color: rgba(0,0,0,0.8500);
				line-height: 22px;
				margin-left: 8px;
			}
		}
		.right{
			align-items: center;
			.icon{
				cursor: pointer;
				width: 16px;
				height: 16px;
				margin-left: 8px;
				justify-content: center;
				align-items: center;
			}
		}
	}
	.one-texture{
		.datas-item{
			height:344px;
			overflow-y: auto;
			padding-right: 6px;
			.all-memeber{
				padding: 8px 0;
				padding-right: 2px;
				.i-top{
					.top-left{
						flex: 1;
						align-items: flex-start;
						.img{
							width: 44px;
							height: 44px;
							border-radius: 2px;
						}
						.left-ri{
							margin-left: 8px;
							.name{
								font-size: 14px;
								font-weight: 400;
								color: rgba(0,0,0,0.8500);
								line-height: 22px;
							}
							.c-j{
								width: 76px;
								height: 22px;
								background: #E9F1FE;
								border-radius: 2px;
								border: 1px solid #99BFFD;
								font-size: 12px;
								font-weight: 600;
								color: #2475FC;
								align-items: center;
								justify-content: center;
							}
							.p-t{
								width: 76px;
								height: 22px;
								background: rgba(0,0,0,0.0400);
								border-radius: 2px;
								border: 1px solid rgba(0,0,0,0.1500);
								font-size: 12px;
								font-weight: 600;
								align-items: center;
								justify-content: center;
								font-size: 12px;
								color: rgba(0,0,0,0.6500);
							}
						}
					}
				}
				.i-bottom{
					margin-top: 4px;
					font-size: 14px;
					font-weight: 400;
					color: rgba(0,0,0,0.4500);
					line-height: 22px;
					justify-content: space-between;
					span{
						color: rgba(0,0,0,0.6500);
						max-width: 120px;
					}
					.right{
						flex: 1;
						margin-left: 20px;
						justify-content: flex-end;
					}
				}
			}
			.select{
				border-radius: 2px;
				opacity: 1;
				border: 1px solid #2475FC;
			}
		}
	}
}
</style>
