let wxInitFunc = async (configParams, agentConfigParams) => {
	try {
		// console.info('WWOpenData demo start')
		if (/MicroMessenger/i.test(navigator.userAgent)) {
			await config(configParams)
		}
		await agentConfig(agentConfigParams)

		window.$has_agentConfig = 1

		// 若一切正常，此时可以在 window 上看到 WWOpenData 对象
		// console.info('window.WWOpenData', window.WWOpenData)
		if (WWOpenData.checkSession) {
			WWOpenData.checkSession({
				success() {
					// console.info('open-data 登录态校验成功')
				},
				fail() {
					console.error('open-data 登录态过期')
					window.$has_agentConfig = 0

				},
			})
		}
		// if (WWOpenData.on) {
		// 	/**
		// 	 * ww-open-data 元素数据发生变更时触发
		// 	 */
		// 	WWOpenData.on('update', event => {
		// 		// const openid = event.detail.element.getAttribute('openid')
		// 		// console.info('渲染数据发生变更', openid, event.detail.hasData)
		// 	})
		// 	/**
		// 	 * ww-open-data 获取数据失败时触发
		// 	 */
		// 	WWOpenData.on('error', () => {
		// 		console.error('获取数据失败')
		// 	})
		// }
		/**
		 * 绑定 document 上全部的 ww-open-data 元素
		 */
		// console.info('WWOpenData demo end')
	} catch (error) {
		window.$has_agentConfig = 0
		console.error('WWOpenData demo error', error)
	}

	/**
	 * 调用 wx.config
	 *
	 * @see https://open.work.weixin.qq.com/api/doc/90001/90144/90547
	 */
	async function config(config) {
		return new Promise((resolve, reject) => {
			// console.info('wx.config', config)
			wx.config(config)
			wx.ready(resolve)
			wx.error(reject)
		}).then(() => {
			// console.info('wx.ready')
		}, error => {
			console.error('wx.error', error)
			throw error
		})
	}
	/**
	 * 调用 wx.agentConfig
	 *
	 * @see https://open.work.weixin.qq.com/api/doc/90001/90144/90548
	 */
	async function agentConfig(config) {
		return new Promise((success, fail) => {
			// console.info('wx.agentConfig', config)
			wx.agentConfig({ ...config, success, fail })
		}).then(res => {
			// console.info('wx.agentConfig success', res)
			return res
		}, error => {
			console.error('wx.agentConfig fail', error)
			throw error
		})
	}
}
export default wxInitFunc
