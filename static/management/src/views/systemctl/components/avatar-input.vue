<template>
  <div class="upload-box">
    <div class="avatar-uploader">
      <a-upload class="upload-upload" v-model:file-list="fileList" list-type="picture-card" :show-upload-list="false"
        :before-upload="beforeUpload" accept=".jpg,.jpeg,.png">
        <a-button class="upload-btn">
          <template #icon>
            <img class="upload-icon" alt="example" src="@/assets/svg/upload.svg" />
            <img class="upload-icon-active" alt="example" src="@/assets/svg/upload-active.svg" />
            <!-- <UploadOutlined class="upload-icon"></UploadOutlined> -->
          </template>
          <div class="ant-upload-text">上传logo</div>
        </a-button>
      </a-upload>
      <a-modal :open="previewVisible" :title="previewTitle" :footer="null" @cancel="handleCancel">
        <img alt="example" style="width: 100%" :src="previewImage" />
      </a-modal>
    </div>
    <img class="avatar" :src="value || DEFAULT_ZH_LOGO" alt="avatar" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { message, Form } from 'ant-design-vue'
import {DEFAULT_ZH_LOGO} from "@/constants";

function getBase64 (file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.readAsDataURL(file)
    reader.onload = () => resolve(reader.result)
    reader.onerror = (error) => reject(error)
  })
}

const emit = defineEmits(['update:value', 'change'])
const props = defineProps({
  value: {
    type: String,
    default: ''
  }
})

const fileList = ref([])
const loading = ref(false)
const imageUrl = ref('')

watch(
  () => props.value,
  (val) => {
    imageUrl.value = val
  },
  {
    immediate: true
  }
)

const formItemContext = Form.useInjectFormItemContext()

const triggerChange = () => {
  let data = {
    imageUrl: imageUrl.value,
    file: fileList.value[0].originFileObj
  }

  emit('change', data)
  emit('update:value', data.imageUrl)

  formItemContext.onFieldChange()
}

const beforeUpload = (file) => {
  const isJpgOrPng =
    file.type === 'image/jpg' ||
    file.type === 'image/jpeg' ||
    file.type === 'image/png'

  if (!isJpgOrPng) {
    message.error(`不支持${file.type}格式的图片`)
    return false
  }

  const isLt2M = file.size / 1024 < 1024 * 2
  if (!isLt2M) {
    message.error('图片大小不能超过2M')
    return false
  }
  let formData = new FormData()
  formData.append('file', file)

  emit('change', formData)
  return false
}

const previewVisible = ref(false)
const previewImage = ref('')
const previewTitle = ref('')

const handleCancel = () => {
  previewVisible.value = false
  previewTitle.value = ''
}
const handlePreview = async (file) => {
  if (!file.url && !file.preview) {
    file.preview = await getBase64(file.originFileObj)
  }
  previewImage.value = file.url || file.preview
  previewVisible.value = true
  previewTitle.value = file.name || file.url.substring(file.url.lastIndexOf('/') + 1)
}
</script>

<style lang="less" scoped>
.avatar-uploader::v-deep(.ant-upload.ant-upload-select) {
  height: 31px;
  width: 110px;
}

.ant-upload-select-picture-card i {
  font-size: 32px;
  color: #999;
}

.ant-upload-text {
  color: #595959;
  font-family: "PingFang SC";
  font-size: 14px;
  font-style: normal;
  font-weight: 400;
  line-height: 22px;
}

.upload-btn {
  display: flex;
  align-items: center;
  gap: 4px;

  &:hover {
    .upload-icon {
      display: none;
    }

    .upload-icon-active {
      display: block;
    }

    .ant-upload-text {
      color: #2475FC;
    }
  }
}

.upload-icon {
  width: 16px;
}

.upload-icon-active {
  width: 16px;
  display: none;
}

.avatar-uploader {
  display: flex;
  flex-direction: column;
}

.avatar {
  display: flex;
  width: 108px;
  height: 108px;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  flex-shrink: 0;
  border-radius: 6px;
  border: 1px solid var(--Neutral-5, #D9D9D9);
  margin-bottom: 2px;
  padding: 9px;
}
</style>
