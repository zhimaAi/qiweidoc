/*
 * @Description:
 * @Author: chris
 * @Date: 2022-01-17 11:35:39
 * @LastEditTime: 2023-03-07 11:57:39
 * @LastEditors: chris
 */
import moment from "moment";
/**
 * 时间转换工具函数,把时间戳转换为可读的时间
 *
 * @param {*} date  时间
 * @returns
 */

export const formatDateTime = (times, type) => {
  let date = new Date(times * 1000);
  let fmt = "yyyy-MM-dd hh:mm:ss";
  const o = {
    "M+": date.getMonth() + 1, // 月份
    "d+": date.getDate(), // 日
    "h+": date.getHours(), // 小时
    "m+": date.getMinutes(), // 分钟
    "s+": date.getSeconds(), // 秒
  };
  // 年
  if (/(y+)/.test(fmt)) {
    fmt = fmt.replace(RegExp.$1, date.getFullYear());
  }
  for (let k in o) {
    if (new RegExp("(" + k + ")").test(fmt)) {
      fmt = fmt.replace(RegExp.$1, String(o[k]).padStart(2, "0"));
    }
  }
  if (type === "month") {
    fmt = fmt.substring(5, 16);
  }
  if (type === "sun") {
    fmt = fmt.substring(0, 10);
  }
  return fmt;
};

//时间显示问题（几天前、几分钟前）
export const fomatTime = (valueTime) => {
  valueTime = valueTime.length === 10 ? valueTime * 1000 : valueTime;
  if (valueTime) {
    var newData = Date.parse(new Date());
    var diffTime = Math.abs(newData - valueTime);
    if (diffTime > 7 * 24 * 3600 * 1000) {
      var date = new Date(valueTime);
      var y = date.getFullYear();
      var m = date.getMonth() + 1;
      m = m < 10 ? "0" + m : m;
      var d = date.getDate();
      d = d < 10 ? "0" + d : d;
      var h = date.getHours();
      h = h < 10 ? "0" + h : h;
      var minute = date.getMinutes();
      var second = date.getSeconds();
      minute = minute < 10 ? "1" + minute : minute;
      second = second < 10 ? "0" + second : second;
      return m + "-" + d + " " + h + ":" + minute;
    } else if (diffTime < 7 * 24 * 3600 * 1000 && diffTime > 24 * 3600 * 1000) {
      // //注释("一周之内");

      // var time = newData - diffTime;
      var dayNum = Math.floor(diffTime / (24 * 60 * 60 * 1000));
      return dayNum + "天前";
    } else if (diffTime < 24 * 3600 * 1000 && diffTime > 3600 * 1000) {
      // //注释("一天之内");
      // var time = newData - diffTime;
      var dayNum = Math.floor(diffTime / (60 * 60 * 1000));
      return dayNum + "小时前";
    } else if (diffTime < 3600 * 1000 && diffTime > 0) {
      // //注释("一小时之内");
      // var time = newData - diffTime;
      var dayNum = Math.floor(diffTime / (60 * 1000));
      if (dayNum === 0) return "刚刚";
      return dayNum + "分钟前";
    }
  }
};


export const formatMassTime = (massTime) => {
  // 时间单位，minute：分钟，hour：小时，day：天
  switch (massTime.unit) {
    case "minute":
      return massTime.data + "分钟后";
    case "hour":
      return massTime.data + "小时后";
    case "day":
      return massTime.data + `天后 ${massTime.time}`;
  }
  return "--";
}

//时间显示问题（几天前、几分钟前）
export const fomatTimeNew = (valueTime) => {
  valueTime = valueTime.length === 10 ? valueTime * 1000 : valueTime;
  if (valueTime) {
    var newData = Date.parse(new Date());
    var diffTime = Math.abs(newData - valueTime);
    if (diffTime > 24 * 3600 * 1000) {
      var dayNum = Math.floor(diffTime / (24 * 60 * 60 * 1000));
      return dayNum + "天前";
    } else if (diffTime < 24 * 3600 * 1000 && diffTime > 3600 * 1000) {
      // //注释("一天之内");
      // var time = newData - diffTime;
      var dayNum = Math.floor(diffTime / (60 * 60 * 1000));
      return dayNum + "小时前";
    } else if (diffTime < 3600 * 1000 && diffTime > 0) {
      // //注释("一小时之内");
      // var time = newData - diffTime;
      var dayNum = Math.floor(diffTime / (60 * 1000));
      if (dayNum === 0) return "刚刚";
      return dayNum + "分钟前";
    }
  }
};

export const formatDateTextTime = (timestamp) => {
  const targetTime = moment(timestamp);
  const now = moment();
  // 获取今天的开始和结束时间的时间戳
  const todayStart = now.startOf('day').valueOf();
  const todayEnd = now.endOf('day').valueOf();
  // 获取昨天的开始和结束时间的时间戳
  const yesterdayStart = now.clone().subtract(1, 'days').startOf('day').valueOf();
  const yesterdayEnd = now.clone().subtract(1, 'days').endOf('day').valueOf();
  // 获取前天的开始和结束时间的时间戳
  const dayBeforeYesterdayStart = now.clone().subtract(2, 'days').startOf('day').valueOf();
  const dayBeforeYesterdayEnd = now.clone().subtract(2, 'days').endOf('day').valueOf();
  const targetTimestamp = targetTime.valueOf();
  if (targetTimestamp >= todayStart && targetTimestamp <= todayEnd) {
    return `今天 ${targetTime.format('HH:mm')}`;
  } else if (targetTimestamp >= yesterdayStart && targetTimestamp <= yesterdayEnd) {
    return `昨天 ${targetTime.format('HH:mm')}`;
  } else if (targetTimestamp >= dayBeforeYesterdayStart && targetTimestamp <= dayBeforeYesterdayEnd) {
    return `前天 ${targetTime.format('HH:mm')}`;
  } else {
    return targetTime.format('YYYY-MM-DD HH:mm');
  }
};