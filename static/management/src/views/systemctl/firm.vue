<template>
    <MainLayout title="企业设置">
        <div class="zm-main-box">
            <LoadingBox v-if="loading"/>
            <a-form
              style="padding: 24px 32px;"
              autocomplete="off"
            >
              <a-form-item ref="logo" label="企业logo">
                <AvatarInput v-model:value="formState.logo" @change="onAvatarChange" />
                <div class="form-item-tip">建议设置logo比例为1:1</div>
              </a-form-item>
              <a-form-item ref="name" label="企业名称">
                <div class="content-name">
                    <span v-if="navigation_bar_title">{{ navigation_bar_title }}</span>
                    <a-input v-else v-model:value="formState.name" placeholder="请输入企业名称" class="gray-text" :maxlength="12"></a-input>
                    <div class="company_tip">长度不超过12个字，企业名称将会显示在左上角logo后面、登录窗口等
                      <a-popover title="" placement="bottom">
                        <template #content>
                          <img clss="company-tip-img" src="@/assets/image/company-tip.png" alt="" style="width: 832px;">
                        </template>
                        <a class="company_tip_view">查看示例</a>
                      </a-popover>
                    </div>
                </div>
                <!-- <a class="edit-btn" @click="openCompanyModal">修改</a> -->
                 <a-button class="save-btn" type="primary" @click="onSave">保存设置</a-button>
              </a-form-item>
            </a-form>
        </div>
    </MainLayout>
</template>

<script setup>
import MainLayout from "@/components/mainLayout.vue";
import LoadingBox from "@/components/loadingBox.vue";
import { ref, reactive, computed, onMounted } from 'vue'
import { useStore } from 'vuex';
import { Form, message } from 'ant-design-vue'
import { DEFAULT_ZH_LOGO } from '@/constants/index'
import AvatarInput from './components/avatar-input.vue'
import {getSettings, setNameLogoSave, uploadImage} from "@/api/auth-login";

const loading = ref(false)
const formState = reactive({
  name: '',
  logo: DEFAULT_ZH_LOGO,
  id: ''
})

const rules = reactive({
  name: [{ required: true, message: '请输入企业名称', trigger: 'change' }]
})

const useForm = Form.useForm
const { validate, validateInfos } = useForm(formState, rules)
// import { saveCompany } from '@/api/user/index.js'
// import { message } from 'ant-design-vue'
const store = useStore()
const { navigation_bar_title, logo } = computed(() =>  store.getters.getCompany)
// const handleGetCompany = () => {
//   // 获取企业信息
// }
// handleGetCompany()

const open = ref(false)

const onAvatarChange = (formData) => {
  uploadImage(formData).then((res) => {
    if (res.status == 'success') {
      // res.data.url 拿不到图片，用本地的
      formState.logo = URL.createObjectURL(formData.get('file')) || DEFAULT_ZH_LOGO
    }
    res.error_message && message.error(res.error_message)
  })
}

const openCompanyModal = () => {
  formState.name = navigation_bar_title;
  open.value = true
}

const onSave = () => {
  loading.value = true
  let { name, logo } = formState
  let params = {
    navigation_bar_title: name,
    logo: logo
  }
  setNameLogoSave(params).then((res) => {
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
}

const handleSetCompany = () => {
  // saveCompany({
  //   ...formState
  // }).then((res) => {
  //   message.success('保存成功')
  //   handleGetCompany()
  //   let title = document.title.split('')
  //   document.title = title[0] + formState.name
  //   open.value = false
  // })
}

const getCUrrentCorpData = async () => {
  try {
    const currentCorp = await getSettings()
    if (currentCorp.data) {
      store.commit('setCompany', currentCorp.data)
      const {navigation_bar_title, logo} = currentCorp.data
      formState.name = navigation_bar_title
      formState.logo = logo
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
.zm-main-box {
  background: #FFF;
  padding-bottom: 40px;
  min-height: calc(100vh - 130px);

  :deep(.label-height-unset .ant-form-item-label >label ) {
      height: unset;
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
    width: 518px;
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
    margin-top: 48px;
  }
}
</style>
