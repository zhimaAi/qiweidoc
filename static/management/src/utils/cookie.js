const USER_INFO_KEY = 'zm:session:archive:login:user';

// 定义一个Cookie对象
var Cookie = {
    // 设置cookie
    set: function(name, value, expire) {
      var cookie = name + "=" + encodeURIComponent(value);
      if (expire) {
        cookie += "; expires=" + expire.toUTCString();
      }
      document.cookie = cookie;
    },

    // 获取cookie
    get: function(name) {
      var cookies = document.cookie.split(';');
      for (var i = 0; i < cookies.length; i++) {
        var parts = cookies[i].split('=');
        if (parts[0].trim() === name) {
          return decodeURIComponent(parts[1]);
        }
      }
      return '';
    },

    // 指定域名存储
    setCookieAcrossSubdomains(name, value, days, domain) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; domain=" + domain + "; path=/";
    },

    // 删除cookie
    remove: function(name) {
      this.set(name, '', new Date(0));
    }
};

function formatLocation (domain) {
    if (domain === 'zhimahuihua.com' || domain === 'demo.zhimahuihua.com') {
        return 'zhimahuihua.com'
    } else {
        return domain
    }
}

export function setCookieAcrossSubdomain(data) {
    Cookie.setCookieAcrossSubdomains(USER_INFO_KEY, data, 1, formatLocation(window.location.host))
}

export function getCookieUserInfo() {
    return Cookie.get(USER_INFO_KEY)
}

export default Cookie
