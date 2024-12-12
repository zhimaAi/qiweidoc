<template>
	<div
		class="flex"
		:class="{
			'margin-top-2': showMarginTop2 && !isHttps,
			'margin-top-4': showMarginTop4 && !isHttps,
		}"
	>
		<ww-open-data
			:id="id"
			class="flex"
			:class="{'eliOne': showEliOne}"
			:type='type'
			v-if="showStatus || show"
			:openid="openid"
			:style="{width}"
		></ww-open-data>
		<slot v-else></slot>
	</div>
</template>

<script>
import wxInitFunc from "@/utils/initWxConfig";

export default {
	name:'',
	props: [
		'showStatus',
		'type',
		'openid',
		'minHeight',
		'showMarginTop2',
		'showMarginTop4',
		"showEliOne"
	],
	data() {
		return {
			width: '100%',
		};
	},
	computed: {
		id() {
			return Date.now() + 'ww-open-data'
		},
		isHttps() {
			return location.origin.indexOf('https') !== -1
		},
    show() {
      let status = [0,3,'0','3'].includes(this.$store.state.user.active_corp.setting_status);
      if (status && window.$has_agentConfig != 1) {
				this.$api.getAgentConfig({url: location.pathname}).then(res => {
					wxInitFunc("", res.data)
				})
      }
      return status
    },
	},
	updated() {
		if (location.origin.indexOf('https') !== -1) {
			return
		}
		let ele = document.getElementById(this.id)
		if (ele) {
			let ifa = ele.getElementsByTagName('iframe')[0]
			let width = parseInt(this.width)
			if (width && width > 0 && this.width !== '100%') {
				ifa.style.minWidth = parseInt(ifa.style.width) > 0 ? ifa.style.width : this.width
				ifa.style.minHeight = '19px'
				if (parseInt(ifa.style.width) > 0) {
					this.width = ifa.style.width
				}
			}
		}
	},
	mounted() {
    window.WWOpenData.bind(document.getElementById(this.id))
		if (location.origin.indexOf('https') !== -1) {
			return
		}
		this.$nextTick(() => {
			let timer = setInterval(() => {
				if (!document.getElementById(this.id)) {
					clearInterval(timer)
					return
				}
				if (this.minHeight) {
					document.getElementById(this.id).style.minHeight = this.minHeight
				}
				let ele = document.getElementById(this.id).getElementsByTagName('iframe')[0]
				if (ele) {
					let width = parseInt(ele.style.width)
					if ( width && width > 0 ) {
						this.width = ele.style.width
						clearInterval(timer)
					}
				}
			}, 200)
		})
	},
};
</script>

<style scoped lang="less">
.eliOne {
  overflow: hidden !important;
  display: -webkit-box !important;
  text-overflow: ellipsis !important;
  -webkit-line-clamp: 1 !important; /*要显示的行数*/
  -webkit-box-orient: vertical !important;
  word-break: break-all !important;
}
.flex{
	align-items: center;
	min-height: 19px;
}
.margin-top-2{
	margin-top: 2px;
}
.margin-top-4{
	margin-top: 4px;
}
</style>
