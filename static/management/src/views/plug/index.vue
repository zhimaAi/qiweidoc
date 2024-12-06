<template>
  <MainLayout title="功能插件">
    <div class="zm-main-content func-plug-box">
      <a-alert show-icon message="插件启用后,即可到功能中心去使用新的功能,若不需要使用,禁用即可"></a-alert>
      <div class="plug-box">
        <div class="plug-item" v-for="(item, index) in lists" :key="index">
          <div class="plug-item-top">
            <img class="customer-label" :src="defaultImg" alt="">
            <div class="status-box" v-if="item.enable">
              <img class="enable" src="../../assets/svg/enable.svg" alt="">
              已启用
            </div>
            <div class="status-box not-status-box" v-else>
              <img class="enable" src="../../assets/svg/not-enable.svg" alt="">
              待启用
            </div>
          </div>
          <div class="plug-item-midden">
            <div class="midden-title">{{ item.title }}</div>
            <div class="midden-info" @click="onGoInfo(item)">
              详情
              <img class="right" src="../../assets/svg/right.svg" alt="">
              <img class="right-active" src="../../assets/svg/right-active.svg" alt="">
            </div>
          </div>
          <div class="plug-item-bottom">
            <div class="bottom-label">{{ item.description }}</div>
            <a-switch v-model:checked="item.enable" checked-children="开" un-checked-children="关"
              @change="statusChange(item)" />
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import {useStore} from 'vuex';
import MainLayout from "@/components/mainLayout.vue";
import { Modal, message } from 'ant-design-vue'
import { useRouter } from 'vue-router';
import { getModules, enableModules, disableModules } from "@/api/company";
import defaultImg from '@/assets/customer-label.png'

const store = useStore()
const router = useRouter()
const loading = ref(false)
const lists = computed(() => {
    return store.getters.getModules
})

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
        name: item.name
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
  loading.value = true
  let params = {}
  getModules(params).then(res => {
    if (res.status === 'success') {
      let data = res.data
      data.map(item => {
        item.enable = !item.paused
      })
      store.commit('setModules', data)
    }
  }).finally(() => {
    loading.value = false
  })
}

const onGoInfo = (item) => {
  router.push({
    path: '/plug/info',
    query: {
      name: item.name
    }
  })
}

onMounted(() => {
  // 全局获取配置
  // loadData()
})
</script>

<style scoped lang="less">
.func-plug-box {
  min-height: calc(100vh - 128px);

  .plug-box {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 16px;

    .plug-item {
      font-family: "PingFang SC";
      width: 358px;
      height: 150px;
      border-radius: 2px;
      border: 1px solid var(--06, #D9D9D9);
      background: #FFF;
      padding: 24px;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;

      &:hover {
        box-shadow: 0 2px 4px 1px rgba(0, 0, 0, 0.12);
      }

      .plug-item-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;

        .customer-label {
          width: 40px;
          height: 40px;
        }

        .status-box {
          display: inline-flex;
          padding: 0 6px;
          align-items: center;
          gap: 2px;
          border-radius: 6px;
          background: #E8FCF3;
          height: 22px;
          color: #21a665;
          font-family: "PingFang SC";
          font-size: 14px;
          font-style: normal;
          font-weight: 500;
          line-height: 22px;

          .enable {
            width: 16px;
            height: 16px;
          }
        }

        .not-status-box {
          background: #EDEFF2;
          color: #3a4559;
        }
      }

      .plug-item-midden {
        display: flex;
        align-items: center;
        margin-bottom: 4px;

        .midden-title {
          margin-right: 16px;
          color: #000000;
          font-size: 16px;
          font-style: normal;
          font-weight: 600;
          line-height: 24px;
        }

        .midden-info {
          display: inline-flex;
          cursor: pointer;
          color: #8c8c8c;
          font-size: 14px;
          font-style: normal;
          font-weight: 400;

          .right {
            width: 16px;
            height: 16px;
            margin-left: 4px;
          }

          .right-active {
            display: none;
          }

          &:hover {
            color: #1677ff;

            .right {
              display: none;
            }

            .right-active {
              margin-left: 4px;
              display: block
            }
          }
        }
      }

      .plug-item-bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;

        .bottom-label {
          color: #595959;
          font-size: 14px;
          font-style: normal;
          font-weight: 400;
          line-height: 22px;
        }
      }
    }
  }
}
</style>
