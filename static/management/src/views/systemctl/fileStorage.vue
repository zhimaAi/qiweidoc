<template>
    <MainLayout title="文件存储配置">
        <div class="zm-main-content">
            <LoadingBox v-if="loading"/>
            <OssConfig v-else-if="configFinished || configVisible"/>
            <GuideBox v-else @show-config="showConfig"/>
        </div>
    </MainLayout>
</template>

<script setup>
import {ref, onMounted} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import MainLayout from "@/components/mainLayout.vue";
import GuideBox from "./components/fileStorage/guide-box.vue";
import OssConfig from "./components/fileStorage/oss-config.vue";
import {getSettings} from "@/api/file-storage";
import LoadingBox from "@/components/loadingBox.vue";

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const configVisible = ref(false)
const configFinished = ref(false)

onMounted(() => {
    if (route.query.config == 1) {
        configVisible.value = true
    }
    loadConfig()
})

function loadConfig () {
  try {
    loading.value = true
    getSettings().then(res => {
      let data = res?.data || {}
      if (data.access_key && data.secret_key) {
        configFinished.value = true
      }
    }).finally(() => {
      loading.value = false
    })
  } catch (e) {
  }
}

function showConfig() {
    configVisible.value = true
    router.replace({
        path: route.path,
        query: {
            ...route.query,
            config: 1
        }
    });
}
</script>

<style scoped lang="less">
.zm-main-content {
    min-height: calc(100vh - 126px);
    position: relative;
}
</style>
