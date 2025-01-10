<template>
  <div class="brand-box zm-main-content">
    <a-card>
      <template #title>
        <div class="brand-title">企业高级设置</div>
      </template>
      <a-form ref="ruleFormRef" style="flex: 1;" autocomplete="off" :model="formState" :rules="rules">
        <a-form-item name="title" label="网页标题">
          <div class="content-name">
            <span v-if="isEdit" class="content-text mt6">{{ editData.title }}</span>
            <a-input v-else v-model:value="formState.title" placeholder="请输入网页标题" class="gray-text"></a-input>
          </div>
        </a-form-item>
        <a-form-item name="logo" label="企业logo">
          <div v-if="isEdit" class="logi-img">
            <img class="img" :src="editData.logo || DEFAULT_ZH_LOGO" alt="">
          </div>
          <div class="logo-box" v-else>
            <AvatarInput v-model:value="formState.logo" @change="onAvatarChange" />
            <div class="form-item-tip">建议设置logo比例为1:1</div>
          </div>
        </a-form-item>
        <a-form-item name="navigation_bar_title" label="企业名称">
          <div class="content-name">
            <span v-if="isEdit" class="content-text">{{ editData.navigation_bar_title }}</span>
            <a-input v-else v-model:value="formState.navigation_bar_title" placeholder="请输入导航栏" class="gray-text" :maxlength="12"></a-input>
          </div>
        </a-form-item>
        <a-form-item name="login_page_title" label="登录页标题">
          <div class="content-name">
            <span v-if="isEdit" class="content-text">{{ editData.login_page_title }}</span>
            <a-input v-else v-model:value="formState.login_page_title" placeholder="请输入登录页标题" class="gray-text" :maxlength="12"></a-input>
          </div>
        </a-form-item>
        <a-form-item name="login_page_description" label="登录页描述">
          <div class="content-name">
            <span v-if="isEdit" class="content-text">{{ editData.login_page_description }}</span>
            <a-input v-else v-model:value="formState.login_page_description" placeholder="请输入登录页描述" class="gray-text"></a-input>
          </div>
        </a-form-item>
        <a-form-item name="copyright" label="版权信息">
          <div class="content-name">
            <span v-if="isEdit" class="content-text mt6">{{ editData.copyright }}</span>
            <a-input v-else v-model:value="formState.copyright" placeholder="请输入版权信息" class="gray-text"></a-input>
          </div>
        </a-form-item>
        <a-form-item class="empty-item">
          <template #label>
            <div></div>
          </template>
          <a-button v-if="isEdit" class="save-btn" type="primary" @click="onEdit">修 改</a-button>
          <a-button v-if="!isEdit" @click="onCancel">取 消</a-button>
          <a-button v-if="!isEdit" :disabled="loading" class="save-btn" type="primary" @click="onSave">保 存</a-button>
        </a-form-item>
      </a-form>
      <div class="web-bg">
        <div class="web-title-box">
          <div class="web-title-img">
            <img class="img" :src="DEFAULT_ZH_LOGO" alt="">
          </div>
          <div class="web-title">{{ editData.title }}</div>
        </div>
        <div class="web-logo-box">
          <div class="web-logo-img">
            <img class="img" :src="editData.logo || DEFAULT_ZH_LOGO" alt="">
          </div>
          <div class="web-logo-title">{{ editData.navigation_bar_title }}</div>
        </div>
        <div class="web-logo-title-box">
          <div class="web-logo-title">{{ editData.login_page_title }}</div>
          <div class="web-logo-info">{{ editData.login_page_description }}</div>
        </div>
        <div class="copyright-box">{{ editData.copyright }}</div>
        <div class="bottom-tip">
          <img class="img" src="@/assets/svg/top-tip.svg" alt="">
          <span class="text">设置完成后动态显示效果</span>
          <img class="img" src="@/assets/svg/top-tip.svg" alt="">
        </div>
        <img class="img" src="@/assets/image/web-bg.png" alt="">
      </div>
    </a-card>
    <LoadingBox v-if="loading"></LoadingBox>
  </div>
</template>

<script setup>
import AvatarInput from './avatar-input.vue'
import { onMounted, ref, reactive, computed } from 'vue';
import { useStore } from 'vuex';
import dayjs from 'dayjs';
import { PlusOutlined, QuestionCircleOutlined } from '@ant-design/icons-vue';
import { Form, Modal, message } from 'ant-design-vue'
import { DEFAULT_ZH_LOGO } from '@/constants/index'
import LoadingBox from "@/components/loadingBox.vue";
import { useRouter } from 'vue-router';
import { getSettings, settings, uploadImage } from "@/api/auth-login";
import { taskList, taskChangeSwitch, taskDelete } from "@/api/session"

const ruleFormRef = ref(null)
const isEdit = ref(false)
const loading = ref(false)
const formState = reactive({
  title: '',
  logo: DEFAULT_ZH_LOGO,
  navigation_bar_title: '',
  login_page_title: '',
  login_page_description: '',
  copyright: ''
})

const editData = reactive({
  title: '',
  logo: DEFAULT_ZH_LOGO,
  navigation_bar_title: '',
  login_page_title: '',
  login_page_description: '',
  copyright: ''
})

const rules = reactive({
  title: [{ required: true, message: '请输入网页标题', trigger: 'blur' }],
  navigation_bar_title: [{ required: true, message: '请输入导航栏', trigger: 'blur' }],
  login_page_title: [{ required: true, message: '请输入登录页标题', trigger: 'blur' }],
  login_page_description: [{ required: true, message: '请输入登录页描述', trigger: 'blur' }]
//   copyright: [{ required: true, message: '请输入版权信息', trigger: 'blur' }]
})

const useForm = Form.useForm
const { validate, validateInfos } = useForm(formState, rules)
// import { saveCompany } from '@/api/user/index.js'
// import { message } from 'ant-design-vue'
const store = useStore()
const { navigation_bar_title,  } = computed(() => store.getters.getCompany)
// const handleGetCompany = () => {
//   // 获取企业信息
// }
// handleGetCompany()

const hash = ref('')

const onAvatarChange = (formData) => {
  loading.value = true
  uploadImage(formData).then((res) => {
    if (res.status == 'success') {
      // res.data.url 拿不到图片，用本地的
      hash.value = res.data.hash
      formState.logo = URL.createObjectURL(formData.get('file')) || DEFAULT_ZH_LOGO
      loading.value = false
    }
    res.error_message && message.error(res.error_message)
  })
}

const onCancel = () => {
    isEdit.value = true
}

const onEdit = () => {
  isEdit.value = false
  Object.assign(formState, editData)
}

const onSave = () => {
  ruleFormRef.value.validate().then((valid) => {
    if (valid) {
      loading.value = true
      let params = {}
      Object.assign(params, formState)
      if (hash.value) {
        params.logo = hash.value
      } else {
        delete params.logo
      }
      settings(params).then((res) => {
        if (res.status == 'success') {
          message.success('操作成功')
          // 刷新当前页面
          getCUrrentCorpData()
          // router.go(0)
          // return
        }
        res.error_message && message.error(res.error_message)
      }).finally(() => {
        loading.value = false
      })
    } else {
        return false
    }
  })
}

const getCUrrentCorpData = async () => {
  hash.value = ''
  try {
    const currentCorp = await getSettings()
    if (currentCorp.data) {
        store.commit('setCompany', currentCorp.data)
        const { title, logo, navigation_bar_title, login_page_title, login_page_description, copyright } = currentCorp.data
        if (title || logo || navigation_bar_title || login_page_title || login_page_description || copyright) {
            isEdit.value = true
        }
        Object.assign(editData, currentCorp.data)
    } else {
        store.commit('setCompany', {
            title: '',
            logo: '',
            navigation_bar_title: '',
            login_page_title: '',
            login_page_description: '',
            copyright: ''
        })
    }
  } catch (e) {

  }
}

onMounted(() => {
  getCUrrentCorpData()
})
</script>

<style scoped lang="less">
.brand-box {
  padding: 0;
  border-radius: 6px;
  background: #FFF;

  :deep(.label-height-unset .ant-form-item-label >label) {
    height: unset;
  }

  .logo-box {
    display: flex;
    align-items: end;
  }

  .form-item-tip {
    color: #8c8c8c;
    font-family: "PingFang SC";
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 22px;
  }

  .gray-text {
    width: 278px;
    color: #8c8c8c;
    font-weight: 400;
  }

  .company_tip {
    align-self: stretch;
    color: #8c8c8c;
    font-family: "PingFang SC";
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 22px;
    margin-top: 2px;

    .company_tip_view {
      color: #2475fc;
      margin-left: 12px;
    }
  }

  .save-btn {
    margin-top: 24px;
    margin-left: 8px;
  }

  .brand-title {
    color: #262626;
    font-family: "PingFang SC";
    font-size: 14px;
    font-style: normal;
    font-weight: 600;
    line-height: 22px;
  }

  .content-text {
    color: #595959;
    font-family: "PingFang SC";
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 22px;
    max-width: 278px;
    display: flex;
  }

  .web-bg {
    position: relative;
    width: 688px;
    height: 450px;

    .web-title-box {
      position: absolute;
      left: 76px;
      top: 4px;
      height: 20px;
      line-height: 20px;
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 4px;
      background: #FFF;

      .web-title-img {
        display: flex;
        width: 12px;
        height: 12px;

        .img {
          width: 12px;
          height: 12px;
        }
      }

      .web-title {
        max-width: 93px;
        color: #262626;
        font-family: "PingFang SC";
        font-size: 8px;
        font-style: normal;
        font-weight: 400;
        word-break: break-all;
        text-overflow: ellipsis;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
      }
    }

    .web-logo-box {
      position: absolute;
      left: 12px;
      top: 48px;
      height: 25px;
      line-height: 25px;
      display: flex;
      align-items: center;
      gap: 4px;

      .web-logo-img {
        display: flex;
        width: 16px;
        height: 16px;

        .img {
          width: 16px;
          height: 16px;
        }
      }

      .web-logo-title {
        color: #000000;
        font-family: "PingFang SC";
        font-size: 10px;
        font-style: normal;
        font-weight: 600;
        line-height: 12px;
        max-width: 300px;
        word-break: break-all;
        text-overflow: ellipsis;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
      }
    }

    .web-logo-title-box {
      font-family: "PingFang SC";
      position: absolute;
      top: 120px;
      left: 162px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 4px;

      .web-logo-title {
        color: #262626;
        text-align: center;
        font-size: 12px;
        font-style: normal;
        font-weight: 600;
        line-height: 15.31px;
      }

      .web-logo-info {
        color: #3a4559;
        font-size: 8px;
        font-style: normal;
        font-weight: 400;
        line-height: 11.48px;
        opacity: 0.85;
      }
    }

    .copyright-box {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      bottom: 16px;
      color: #8c8c8c;
      text-align: center;
      font-family: "PingFang SC";
      font-size: 12px;
      font-style: normal;
      font-weight: 400;
      line-height: 20px;
    }

    .bottom-tip{
      transform: translateX(-50%);
      position: absolute;
      left: 50%;
      bottom: -30px;
      display: flex;
      align-items: center;
      gap: 4px;

      .img {
        width: 16px;
        height: 16px;
      }

      .text {
        color: #8c8c8c;
        font-family: "PingFang SC";
        font-size: 14px;
        font-style: normal;
        font-weight: 400;
        line-height: 22px;
      }
    }

    .img {
      width: 688px;
      height: 450px;
    }
  }

  .logi-img {
    display: flex;
    width: 104px;
    height: 104px;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    flex-shrink: 0;
    border-radius: 6px;
    border: 1px solid var(--Neutral-5, #D9D9D9);

    .img {
      height: 86px;
      width: 86px;
    }
  }

  .mt6 {
    margin-top: 6px;
  }
}
</style>
<style lang="less">
.brand-box {
  .ant-card {

    .ant-card-head {
      min-height: 46px;
    }

    .ant-card-body {
      width: 100%;
      display: flex;
      justify-content: space-between;
    }
  }

  .ant-form-item {
    .ant-form-item-label >label {
      min-width: 95px;
      display: flex;
      justify-content: right;
    }
  }

  .empty-item {
    .ant-form-item-label >label::after {
      display: none !important;
    }
  }
}
</style>
