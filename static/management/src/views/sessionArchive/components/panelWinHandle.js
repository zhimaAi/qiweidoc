import {onMounted, reactive} from 'vue';
import {get, set} from "@/utils/cache";

export function panelWinHandle(key = 'zm:session:archive:win:box:width') {
    const panelWin = reactive({
        leftMinWith: 200,//定值
        rightMinWidth: 400,//定值
        centerMinWidth: 200,//定值
        leftMaxWith: 1000,
        centerMaxWidth: 1000,
    })

    onMounted(() => {
        winPanelInit()
    })

    const winPanelInit = () => {
        // 获取上一次设置的宽度
        let cacheData = get(key)
        let leftBoxWidth = cacheData.left_width || panelWin.leftMinWith
        let centerBoxWidth = cacheData.center_width || panelWin.centerMinWidth

        let sessionMainBox = document.getElementById("sessionMainContent")
        let sessionLeftBox = document.getElementById("sessionLeftBlock")
        let sessionCenterBox = document.getElementById("sessionCenterBlock")

        let mainBoxWidth = null
        if (sessionMainBox) {
            mainBoxWidth = sessionMainBox.offsetWidth
        }
        if (sessionLeftBox) {
            sessionLeftBox.style.width = leftBoxWidth + "px"
        }
        if (sessionCenterBox) {
            sessionCenterBox.style.width = centerBoxWidth + "px"
        }
        // 右侧（群聊列表）最小宽度230
        // 中间部分最小宽度326
        // 左侧（聊天窗口）最小宽度400
        if (sessionCenterBox) {
            panelWin.centerMaxWidth = mainBoxWidth - panelWin.rightMinWidth - leftBoxWidth
        } else {
            // 不存在center时，仅左右两模块
            centerBoxWidth = 0
        }
        panelWin.leftMaxWith = mainBoxWidth - panelWin.rightMinWidth - centerBoxWidth
    }

    const panelBlockWidthChange = () => {
        set(key, {
            left_width: document.getElementById("sessionLeftBlock")?.offsetWidth || '',
            center_width: document.getElementById("sessionCenterBlock")?.offsetWidth || '',
        })
        winPanelInit()
    }

    return {
        panelWin,
        panelBlockWidthChange,
    }
}
