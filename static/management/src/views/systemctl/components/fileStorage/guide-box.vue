<template>
    <div>
        <div class="guide-box">
            <div class="left-block">
                <div class="title">文件存储OSS地址配置</div>
                <div class="desc">
                    配置OSS地址后，聊天记录的文件将存储在OSS服务中，本地数据也会同时保存，默认保存7天，7天后自动清除
                </div>
                <div class="oss-item" @click="goConfig">
                    <span>去配置</span>
                    <IconRightArrow class="icon"/>
                </div>
<!--                <div class="oss-item">-->
<!--                    <img class="icon" src="@/assets/image/file-storage/aliyun-icon.png"/>-->
<!--                    <span>阿里云配置</span>-->
<!--                    <IconRightArrow class="icon"/>-->
<!--                </div>-->
<!--                <div class="oss-item">-->
<!--                    <img class="icon" src="@/assets/image/file-storage/tencentyuan-icon.png"/>-->
<!--                    <span>腾讯云配置</span>-->
<!--                    <IconRightArrow class="icon"/>-->
<!--                </div>-->
            </div>
            <div class="right-block">
                <img class="cover" src="@/assets/image/file-storage/file-storage-cover.png"/>
            </div>
        </div>
    </div>
</template>

<script setup>
import IconRightArrow from "@/components/icon/iconRightArrow.vue";

const emit = defineEmits(['showConfig'])
function goConfig () {
    emit('showConfig')
}
</script>

<style scoped lang="less">
.guide-box {
    width: 584px;
    height: 292px;
    padding: 0 58px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 6px;
    background: #FFF;
    box-shadow: 0 4px 24px 0 #00000014;
    margin: 120px auto 0;

    .left-block {
        margin-right: 32px;

        .title {
            color: #262626;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .desc {
            color: #595959;
            font-size: 14px;
            font-weight: 400;
            margin-bottom: 10px;
        }

        .oss-item {
            cursor: pointer;
            font-size: 14px;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            color: #2475fc;
            padding: 5px 16px;
            background: #E5EFFF;
            margin-bottom: 8px;
            border-radius: 6px;

            &:hover {
                color: #FFF;
                background: #2475FC;
            }

            span {
                margin: 0 4px;
            }

            .icon {
                width: 16px;
                height: 16px;
                font-size: 16px;
            }
        }
    }

    .right-block {
        width: 210px;
        flex-shrink: 0;

        .cover {
            width: 210px;
            height: 210px;
        }
    }
}
</style>
