import {weekToText} from "@/utils/tools";

export function timeDataFormat(timeData) {
    return timeData.map(item => {
        let ranges = item.time_period_list.map(time => {
            return {times: [time.start, time.end]}
        })
        return {
            week: item.week_day_list,
            ranges: ranges,
        }
    })
}

export function formatCheckTime(data) {
    switch (Number(data.inspect_time_type.value)) {
        case 1:
            return "全天"
        case 2:
            return "工作时间"
        case 3:
            let res = []
            let weeks, times
            data.custom_time_list.map(item => {
                weeks = [], times = []
                item.week_day_list.map(w => weeks.push(weekToText(w)))
                item.time_period_list.map(r => times.push(`${r.start}~${r.end}`))
                res.push(`${weeks.join("、")} ${times.join(" ")}`)
            })
            return res
    }
}
