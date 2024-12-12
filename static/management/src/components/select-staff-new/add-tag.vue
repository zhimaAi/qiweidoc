<template>
	<a-modal
        v-model:open="visible"
		:confirm-loading="confirmLoading"
		@ok="handleOk"
		width="472px"
		@cancel="handleCancel"
	>
		<div slot="title" class="fx-ac">
			<div class="fir-tit">创建标签</div>
			<div class="sec-tit">确定将当前已选员工新建标签？</div>
		</div>
		<div class="cont fx-ac" style="margin-top: 10px;">
			<div class="fir fx-ac"><span>*</span>标签名称：</div>
			<a-input :maxLength="32" style="width: 262px;" v-model.trim="name" placeholder="请输入标签名称" />
		</div>
	</a-modal>
</template>

<script>
import api from '@/api/staff-manage/index.js'
export default {
	name:'',
	data() {
		return {
			visible: false,
			name: '',
			confirmLoading: false,
			staff: [],
		};
	},
	mounted() {

	},
	methods: {
		show(arr) {
			this.staff = arr
			this.name = ''
			this.visible = true
		},
		handleOk() {
			// this.$emit('initFunc')
			// return
			if (!this.name) {
				return this.$message.error('请输入标签名称')
			}
			this.createName().then(id => {
				this.confirmLoading = true
				api.bookTagAddStaff({
					tag_id: id,
					staff_id_list: this.staff.map(i => i.staff_id).join(',')
				}).then(res => {
					this.confirmLoading = false
					if (res.res == 0) {
						this.$message.success('操作成功')
						this.handleCancel()
						this.$emit('initFunc', id)
						return
					}
					this.$message.error(res.msg)
				}).catch(err => {
					this.confirmLoading = false
				})
			})
		},
		createName() {
			return new Promise((resj, rej) => {
				this.confirmLoading = true
				api.bookTagAddTag({
					tag_name: this.name
				}).then(res => {
					this.confirmLoading = false
					if (res.res == 0) {
						resj(res.data)
						return
					}
					rej()
					this.$message.error(res.msg)
				}).catch(err => {
					rej()
					this.confirmLoading = false
				})
			})
		},
		handleCancel() {
			this.visible = false
		}
	},
};
</script>

<style scoped lang="less">
.fir-tit{
	font-size: 16px;
	font-weight: 600;
	color: rgba(0,0,0,0.85);
	line-height: 24px;
}
.sec-tit{
	font-size: 12px;
	font-weight: 400;
	color: #8C8C8C;
	line-height: 22px;
	margin-left: 8px;
}
.cont{
	.fir{
		font-size: 14px;
		font-weight: 400;
		color: #262626;
		span{
			color: #FB363F;
			margin-right: 2px;
		}
	}
}
</style>
