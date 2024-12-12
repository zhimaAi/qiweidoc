<template>
  <MainLayout>
    <template #navbar>
      <a-breadcrumb v-if="links.length > 1" class="nav-tabs">
        <a-breadcrumb-item v-for="(item, index) in links" :key="item.name">
          <template v-if="index !== links.length - 1">
            <router-link v-if="item.to" :to="{ path: item.to }">
              {{ item.name }}
            </router-link>
            <a v-else @click="$router.back(-1)">{{ item.name }}</a>
          </template>
          <span v-else>{{ item.name }}</span>
        </a-breadcrumb-item>
      </a-breadcrumb>
    </template>
    <div class="sensitive-panel">
      <LoadingBox v-if="loading" />
      <div class="info-box">
        <div class="client">
          <img class="client-img" :src="query.name === 'hint_keywords' ? defaultSensitiveWordsAvatar : defaultAvatar" alt="">
          <div class="client-box">
            <div class="client-box-top">
              <div class="top-title">{{ detailData.title }}</div>
              <div class="top-enable" v-if="detailData.enable">
                <img class="enable-img" src="../../assets/svg/enable.svg" alt="">
                <div class="top-enable-label">已启用</div>
              </div>
              <div class="top-enable not-status-box" v-else>
                <img class="enable-img" src="../../assets/svg/not-enable.svg" alt="">
                <div class="top-enable-label">待启用</div>
              </div>
            </div>
            <div class="client-box-midden">{{ detailData.description }}</div>
            <div class="client-box-bottom">
              <a-button @click="statusChange({enable: true})" v-if="!detailData.enable" type="primary">立即启用</a-button>
              <a-button @click="statusChange({enable: false})" v-else>禁 用</a-button>
            </div>
          </div>
        </div>
        <div class="banner-box">
          <Swiper
            :loop="true"
            :modules="modules"
            :autoplay="{ delay: 3000 }"
            :slidesPerView="2"
            :style="{
              '--swiper-navigation-size': '30px',
              '--swiper-pagination-size': '30px'
            }"
            :pagination="{ clickable: true }"
            :navigation="true"
            :spaceBetween="16"
            class="info-swiper-container"
          >
            <SwiperSlide v-for="(image, index) in defaultImgs" :key="index">
              <div class="img-preview" @click="() => setVisible(true, image)">预览</div>
              <img :src="image" class="swiper-slide-img" />
              <a-image
                :style="{ display: 'none' }"
                :preview="{
                  visible,
                  onVisibleChange: setVisible,
                }"
                :src="currentImg"
                class="swiper-slide-img"
              />
            </SwiperSlide>
          </Swiper>
        </div>
        <div class="intro">
          <div class="intro-title">功能简介</div>
          <div class="intro-info">{{ detailData.intro }}</div>
        </div>
        <div class="detail-list">
          <div class="detail-title">功能清单</div>
          <div class="detail-info-box">
            <div class="detail-info-item" v-for="(item, index) in detailData.features" :key="index">{{ item }}</div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import {useStore} from 'vuex';
import { Pagination, Navigation } from 'swiper'
import { Swiper, SwiperSlide } from 'vue-awesome-swiper'
import 'swiper/css'
import 'swiper/css/pagination'
import 'swiper/css/navigation'
import MainLayout from "@/components/mainLayout.vue";
import { useRoute } from 'vue-router';
import LoadingBox from "@/components/loadingBox.vue";
import { Modal, message } from 'ant-design-vue'
import { getModulesInfo, enableModules, disableModules } from "@/api/company";
import defaultAvatar from "@/assets/customer-label.png"
import defaultSensitiveWordsAvatar from "@/assets/sensitive-words.png"
import defaultImg1 from "@/assets/image/func-plug-1.png"
import defaultImg2 from "@/assets/image/func-plug-2.png"
import defaultImg3 from "@/assets/image/func-plug-3.png"
import defaultImg21 from "@/assets/image/func-plug-2-1.png"
import defaultImg22 from "@/assets/image/func-plug-2-2.png"
import defaultImg23 from "@/assets/image/func-plug-2-3.png"
import defaultImg24 from "@/assets/image/func-plug-2-4.png"

const defaultImgs = ref([
    defaultImg1,
    defaultImg2,
    defaultImg3
])
const store = useStore()
const route = useRoute()
const query = route.query
const links = computed(() => route.meta.links || [])
const loading = ref(false)
const detailData = ref({})
const modules = ref([Pagination, Navigation])

const statusChange = (item) => {
  let key = item.enable ? '开启' : '关闭'
  const cancel = () => {
    item.enable = !item.enable
  }
  Modal.confirm({
    title: `确认${key}么`,
    content: item.enable ? '启用后，可到功能插件-客户标签使用该功能' : '禁用后，功能不可再使用，禁用后可重新启用',
    okText: '确定',
    cancelText: '取消',
    onOk: () => {
      const loadClose = message.loading(`正在${key}`)
      let apiUrl = item.enable ? enableModules : disableModules
      apiUrl({
        name: query.name
      }).then(() => {
        message.success('操作完成')
        loadData()
      }).finally(() => {
        loadClose()
      }).catch(() => cancel())
    },
    onCancel: cancel
  })
}

const loadData = () => {
  // 获取详情接口，渲染数据
  loading.value = true
  let params = {name: query.name}
  getModulesInfo(params).then(res => {
    if (res.status === 'success') {
      let data = res.data
      data.enable = !data.paused
      detailData.value = data
      store.commit('setModules', [data])
    }

    if (query.name === 'hint_keywords') {
        defaultImgs.value = [
            defaultImg21,
            defaultImg22,
            defaultImg23,
            defaultImg24
        ]
    }
  }).finally(() => {
    loading.value = false
  })
}

const visible = ref(false);
const currentImg = ref('')
const setVisible = (value, img) => {
    visible.value = value
    currentImg.value = img
}

onMounted(() => {
  query.name && loadData()
})
</script>

<style scoped lang="less">
:deep(.nav-tabs.ant-breadcrumb) {
  background: #FFF;
  height: 56px;
  padding: 17px 24px;
  box-sizing: border-box;

  li {
    font-size: 14px;
    color: #262626;

    a {
      font-weight: 400;

      &:hover {
        background-color: transparent;
        color: #2475FC;
      }
    }
  }
}

.info-box {
  margin: 16px;
  background-color: white;
  padding: 24px;
  overflow: hidden;

  .client {
    display: flex;
    align-items: center;
    padding-bottom: 24px;
    border-bottom: 1px solid #D9D9D9;

    .client-img {
      height: 100px;
      width: 100px;
    }

    .client-box {
      display: flex;
      flex-direction: column;
      margin-left: 16px;

      .client-box-top {
        display: flex;
        gap: 4px;
        font-family: "PingFang SC";

        .top-title {
          color: #000000;
          font-size: 16px;
          font-style: normal;
          font-weight: 600;
          line-height: 24px;
        }

        .top-enable {
          display: flex;
          padding: 0 6px;
          align-items: center;
          gap: 2px;
          border-radius: 6px;
          background: #E8FCF3;
          color: #21a665;
          font-size: 14px;
          font-style: normal;
          font-weight: 500;
          line-height: 22px;

          .enable-img {
            width: 16px;
            height: 16px;
          }
        }

        .not-status-box {
          background: #EDEFF2;
          color: #3a4559;
        }
      }

      .client-box-midden {
        color: #595959;
        font-size: 14px;
        font-style: normal;
        font-weight: 400;
        line-height: 22px;
        margin: 6px 0px 16px;
      }

      .client-box-bottom {
        display: flex;
        align-items: center;
        gap: 33px;
      }
    }
  }

  .banner-box {
    width: 100%;
    margin-top: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    overflow: hidden;
    padding-bottom: 20px;

    .banner-item-box {
      display: flex;
      width: 100%;
      gap: 8px;

      .banner-item {
        display: none;
        flex: 1;

        .item-img {
          width: 100%;
        }
      }

      .active {
        display: block;
      }
    }

    .banner-item-box-small {
      display: flex;
      height: 47px;
      gap: 8px;

      .banner-item {
        cursor: pointer;

        .item-img {
          height: 45px;
        }
      }

      .active {
        border: 1px solid #2475FC;
      }
    }
  }

  .intro {
    display: flex;
    flex-direction: column;
    font-family: "PingFang SC";
    gap: 8px;
    margin-top: 24px;

    .intro-title {
      align-self: stretch;
      color: #000000;
      font-size: 16px;
      font-style: normal;
      font-weight: 600;
      line-height: 24px;
    }

    .intro-info {
      align-self: stretch;
      color: #595959;
      font-size: 14px;
      font-style: normal;
      font-weight: 400;
      line-height: 22px;
    }
  }

  .detail-list {
    margin-top: 24px;
    display: flex;
    flex-direction: column;
    font-family: "PingFang SC";
    gap: 8px;

    .detail-title {
      align-self: stretch;
      color: #000000;
      font-size: 16px;
      font-style: normal;
      font-weight: 600;
      line-height: 24px;
    }

    .detail-info-box {
      display: flex;
      flex-direction: column;
      align-self: stretch;
      color: #595959;
      font-size: 14px;
      font-style: normal;
      font-weight: 400;
      line-height: 22px;
    }
  }
}

.img-preview {
  background: rgba(0, 0, 0, 0.3);
  width: 50px;
  height: 50px;
  position: absolute;
  top: 50%;
  left: 50%;
  color: white;
  align-items: center;
  justify-content: center;
  display: none;
  cursor: pointer;
  border-radius: 2px;
  transform: translate(-50%, -50%);
}

.swiper-slide {
  &:hover {
    .img-preview {
      display: inline-flex;
    }
  }
}

.swiper-slide-img {
    border: 1px solid #D9D9D9;
    border-radius: 6px;
}


</style>

<style lang="less">
.info-swiper-container {
  overflow: visible;
  width: 100%;
  /* height: 600px; */

  .swiper-pagination-horizontal {
    bottom: -22px;
  }

  .swiper-button-next:after, .swiper-button-prev:after {
    line-height: 2;
    font-weight: 600;
  }

  .swiper-button-prev, .swiper-button-next {
    opacity: 0.5;

    &:hover {
      opacity: 1;
    }
  }
}

.swiper-slide-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
