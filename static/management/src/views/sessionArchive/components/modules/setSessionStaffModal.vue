<template>
    <div>
        <a-modal
            v-model:open="visible"
            title="设置存档员工"
            width="746px"
            :confirm-loading="saving"
            @ok="save"
        >
            <div class="form-item">
                <span class="label">选择角色：</span>
                <div class="cont">
                    <a-button type="dashed" :icon="h(PlusOutlined)"  @click="onShowStaff">
                        选择员工 ({{selectStaff.length}}/{{archiveStfSettings.max_staff_num}})
                    </a-button>
                    <div v-if="archiveStfModule.is_enabled" class="zm-tip-info mt4">
                        如需设置更多，需前往“存档消息管理”插件设置<a class="ml8" @click="linkArchiveStfPlug">去设置</a>
                    </div>
                    <div v-else class="zm-tip-info mt4">
                        如需设置更多，需开启“存档消息管理”插件<a class="ml8" @click="linkPlugHome">去开启</a>
                    </div>
                    <div class="tag-block mt4" @scroll="loadMoreStaff">
                        <div class="tag-box">
                            <a-tag
                                v-for="(item, index) in selectStaff"
                                :key="item.userid"
                                class="zm-customize-tag"
                                closable
                                @close="deleteStaff(index)">{{item.name }}</a-tag>
                        </div>
                        <div v-if="loading" class="zm-relative h44">
                            <LoadingBox/>
                        </div>
                    </div>
                </div>
            </div>
        </a-modal>

        <SelectStaffNew
            @limit="selectStaffLimit"
            :isSession="1"
            :max-staff-num="archiveStfSettings.max_staff_num || 5"
            selectType="multiple"
            ref="setStaff"
            @change="(val) => staffUpdate(val)"></SelectStaffNew>
    </div>
</template>

<script setup>
import {reactive, ref, h, onMounted, computed} from 'vue';
import {useStore} from 'vuex';
import {useRouter} from 'vue-router';
import {message, Modal} from 'ant-design-vue';
import {PlusOutlined} from '@ant-design/icons-vue';
import SelectStaffNew from '@/components/select-staff-new/index'
import {staffList} from "@/api/company";
import {getPluginRouteParams, listScrollPullLoad} from "@/utils/tools";
import LoadingBox from "@/components/loadingBox.vue";
import {setSessionStaffs} from "@/api/session";

const router = useRouter()
const store = useStore()
const emit = defineEmits(['change', 'report'])
const visible = ref(false)
const loading = ref(false)
const finished = ref(false)
const saving = ref(false)
const setStaff = ref(null)
const selectStaff = ref([])
const pagination = reactive({
    page: 1,
    size: 200,
    total: 0
})

const archiveStfModule = computed(() => {
    return store.getters.getArchiveStfInfo || {}
})

const archiveStfSettings = computed(() => {
    return store.getters.getArchiveStfSetting || {}
})

onMounted(() => {
    store.dispatch('updateArchiveStfSetting')
    loadSessionStaff()
})

const staffUpdate = (val) => {
    selectStaff.value = val;
}

const deleteStaff = (index) => {
    selectStaff.value.splice(index, 1);
}

const onShowStaff = () => {
    setStaff.value.show(selectStaff.value)
}

const loadMoreStaff = (e) => {
    listScrollPullLoad(e, loadSessionStaff)
}

const loadSessionStaff = () => {
    if (loading.value || finished.value) {
        return
    }
    loading.value = true
    let params = {
        page: pagination.page,
        limit: pagination.size,
        enable_archive: 1
    }
    staffList(params).then(res => {
        let items = res?.data?.items || []
        if (!items.length) {
            finished.value = true
            return
        }
        selectStaff.value.push(...items)
        pagination.total = res?.data?.total || 0
        emit('report', {total: pagination.total})

    }).finally(() => {
        pagination.page += 1
        loading.value = false
    })
}

const selectStaffLimit = () => {
    if (archiveStfModule.value.is_enabled) {
        Modal.confirm({
            title: '存档员工管理',
            content: `最多设置${archiveStfSettings.value.max_staff_num}名员工，如需更多请前往功能插件「存档员工管理」进行设置!`,
            okText: '去设置',
            onOk: () => {
                linkArchiveStfPlug()
            }
        })
    } else {
        Modal.confirm({
            title: '存档员工管理',
            content: `最多设置5名员工，如需更多需购买「存档消息管理」插件`,
            okText: '去购买',
            onOk: () => {
                linkPlugHome()
            }
        })
    }
}

const linkArchiveStfPlug = () => {
    const link = router.resolve(getPluginRouteParams({name: 'archive_staff'}))
    window.open(link.href)
}

const linkPlugHome = () => {
    const link = router.resolve({
        path: '/plug/index'
    })
    window.open(link.href)
}

const save = () => {
    saving.value = true
    setSessionStaffs({
        staff_userid_list: selectStaff.value.map(i => i.userid)
    }).then(res => {
        message.success('已保存')
        visible.value = false
        refreshStaff()
        emit("change", selectStaff.value);
    }).finally(() => {
        saving.value = false
    })
}

const refreshStaff = () => {
    pagination.page = 1
    pagination.total = 0
    selectStaff.value = []
    finished.value = false
    loading.value = false
    loadSessionStaff()
}

const show = () => {
    visible.value = true
}

defineExpose({
    show,
})
</script>

<style scoped lang="less">
.form-item {
    display: flex;
    align-items: baseline;
    color: #262626;
    font-size: 14px;
    .label {
        white-space: nowrap;
    }
    .cont {
        flex: 1;
    }
}

.tag-block {
    max-height: 500px;
    overflow: hidden;
    overflow-y: auto;
}

.h44 {
    height: 44px;
    line-height: 44px;
}
</style>
