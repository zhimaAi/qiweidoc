/*
 * @Description:
 * @Author: chris
 * @Date: 2021-11-24 11:48:52
 * @LastEditTime: 2022-02-21 12:18:00
 * @LastEditors: chris
 */

const USER_AUTH_TOKEN = 'zm:session:archive:login:token';
const USER_INFO_KEY = 'zm:session:archive:login:user';
const CORP_INFO_KEY = 'zm:session:archive:login:corp';

function setAuthToken(token) {
    set(USER_AUTH_TOKEN, {token: token})
}

function setUserInfo(data) {
    set(USER_INFO_KEY, data)
}

function getUserInfo() {
   return get(USER_INFO_KEY)
}

function getAuthToken() {
   return get(USER_AUTH_TOKEN)?.token || ''
}

function setCorpInfo(data) {
    set(CORP_INFO_KEY, data)
}

function getCorpInfo(data) {
    return get(CORP_INFO_KEY)
}

/**
 * 设置浏览器缓存方法
 * @param key
 * @param value
 * @param time 当time不传入时默认为-1 永久保存
 */
function set(key, value, time = -1) {
  if (Array.isArray(value) || typeof (value) === 'string' || typeof (value) === 'number' || typeof (value) === 'boolean') { //兼容数组、字符串类型缓存写法
    let data = value;
    value = {};
    value[time] = data;
  }
  value.cacheTimestamp = time;
  return localStorage.setItem(key, JSON.stringify(value));
}

/**
 * 获取浏览器缓存方法
 * @param key
 */
function get(key) {
  let data = localStorage.getItem(key)
  if (data) {
    try {
      let obj = JSON.parse(data);
      if (Object.keys(obj).length > 0) { //此处是对象缓存
        let now = new Date().getTime() / 1000;
        if (obj.cacheTimestamp && obj.cacheTimestamp > 0 && obj.cacheTimestamp < now) {
          del(key);
          return false
        }
        if (obj.cacheTimestamp && Array.isArray(obj[obj.cacheTimestamp]) || typeof (obj[obj.cacheTimestamp]) === 'string' || typeof (obj[obj.cacheTimestamp]) === 'number' || typeof (value) === 'boolean') { //兼容数组类型、數字、字符串缓存
          return obj[obj.cacheTimestamp];
        }
        return obj;
      }
      return data;
    } catch (e) {
      console.log(e,99999);
      return data;
    }
  } else {
    return false;
  }
}

/**
 * 删除浏览器缓存方法
 * @param key
 */
function del(key) {
  return localStorage.removeItem(key);
}

// 等待DOM完全加载
document.addEventListener('DOMContentLoaded', function() {

  var swiper = new Swiper(".mySwiper", {
    loop: true,
    autoplay: {
      delay: 5000,
    },
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    }
  });

  new WOW().init();

  function handleScroll() {
    const threshold = 150; // 滚动多少像素时改变颜色
    const sdThreshold = 50; // 滚动多少像素时改变颜色
    let scrollTop = window.scrollY;
    const opacity = scrollTop > threshold ? 1 : scrollTop / threshold;
    const sdOpacity = scrollTop > sdThreshold ? 0.11 : 0;
    let bgColor = `rgba(57, 97, 234, ${opacity})`
    let shadow = `0 2px 4px 0 rgba(0, 0, 0, ${sdOpacity})`
    $('.header').css('background-color', bgColor);
    $('.header').css('box-shadow', shadow);

    // “回顶部”按钮默认不显示，当页面向下滑动到20%左右时，才显示“回顶部”按钮。
    const thresholdBtn = 750; // 滚动多少像素时显示
    const scrollTopBtn = window.scrollY;
    scrollTopBtn > thresholdBtn ? $('#goBackTopBox').addClass('goBackTopBoxShow')  :  $('#goBackTopBox').removeClass('goBackTopBoxShow')
  }

  window.addEventListener('scroll', handleScroll);

  $(window).resize(function() {
    var windowWidth = $(window).width();
    // 这里监听的是窗口可是区域不包含滚动条，所以是750，媒体查询加上滚动条是768
    if (windowWidth <= 750) {
      console.log('移动端');
      $('#isPc').hide()
      $('#isMobile').show()
    } else {
      console.log('PC端');
      $('#isMobile').hide()
      $('#isPc').show()
    }
  });

  function isMobile() {
    // 判断是否为移动设备
    return (
        typeof window.orientation !== "undefined" || // 判断是否存在window.orientation属性，此属性在移动设备上一般存在
        navigator.userAgent.indexOf('IEMobile') !== -1 || // 判断是否为Windows Phone
        navigator.userAgent.indexOf('iPhone') !== -1 || // 判断是否为iPhone
        navigator.userAgent.indexOf('Android') !== -1 && navigator.userAgent.indexOf('Mobile') !== -1 || // 判断是否为Android手机
        navigator.userAgent.indexOf('BlackBerry') !== -1 || // 判断是否为BlackBerry
        navigator.userAgent.indexOf('Opera Mini') !== -1 // 判断是否为Opera Mini浏览器
    );
  }

  if (isMobile()) {
    console.log('移动端');
    $('#isPc').hide()
  } else {
    console.log('PC端');
    $('#isMobile').hide()
  }

  function onUse() {
    $('#usePopup').show()
  }
  $('#onUse').on('click', onUse)

  function popCancel() {
    $('#usePopup').hide()
  }
  $('#popCancel').on('click', popCancel)

  function savePicFn() {
    var imageUrl= $("#picurl").attr("src");

    // 创建一个可以下载的链接
    var link = document.createElement("a");
    link.href = imageUrl;
    // 设置下载的文件名
    link.download = "image.jpg";
    // 触发点击下载链接
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  $('#savePic').on('click', savePicFn)

  // 当JavaScript执行完毕，显示页面内容
  document.body.style.opacity = 1;

  // 事件处理函数
  function handleClick (event) {
    event.preventDefault();
    $(this).addClass('main-active');
    $(this).siblings().removeClass('main-active');
    $(`#product${$(this).data('index')}`).addClass('display-flex')
    $(`#product${$(this).data('index')}`).siblings().removeClass('display-flex')
  }

  // setUserInfo({"id":3,"corp_id":"ww5f432b3a24a9b9f1","userid":"LuoYingBinFen","account":"ww5f432b3a24a9b9f1_LuoYingBinFen","password":"","created_at":"2024-11-19 16:38:36","updated_at":"2024-11-19 16:38:36","cacheTimestamp":-1})
  $('#tabParent .main-title-item').hover(handleClick)

  const userInfo = getUserInfo()

  if (userInfo && userInfo.account) {
      $('#loginBtn').hide()
      $('#userName').text(userInfo.account)
      $('#userInfo').show()
  } else {
    $('#loginBtn').show()
    $('#userName').text('')
    $('#userInfo').hide()
  }

  $('#loginOut').on('click', function () {
    del(USER_AUTH_TOKEN)
    del(USER_INFO_KEY)
    del(CORP_INFO_KEY)
    $('#loginBtn').show()
    $('#userName').text('')
    $('#userInfo').hide()
  })
});
