<template>
    <a-modal v-model="visible"
             :title="formState.id > 0 ? '编辑白名单' : '添加白名单'"
              on-ok="save"
             :confirm-loading="saving"
             width="472px">
        <a-form :labelCol="{span: 4}" :wrapperCol="{span: 20}">
            <a-form-item label="触发类型">
                <a-select v-model="formState.white_type" disabled>
                    <a-select-option :value="1">手机号</a-select-option>
                    <a-select-option :value="2">银行卡号</a-select-option>
                    <a-select-option :value="3">邮箱</a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="白名单">
                <a-textarea v-if="formState.id > 0"
                            v-model="formState.white"
                            @input="inputFormat"
                            :placeholder="`请输入${triggerTypeMap[formState.white_type].name}`"
                            :auto-size="{ minRows: 3, maxRows: 3 }"></a-textarea>
                <a-textarea v-else
                            v-model="formState.white"
                            @blur="inputFormat"
                            :placeholder="`请输入${triggerTypeMap[formState.white_type].name}，换行添加多个，最多10个`"
                            :auto-size="{ minRows: 3, maxRows: 3 }"></a-textarea>
                <div class="c8C8C8C">{{ triggerTypeMap[formState.white_type].desc }}</div>
            </a-form-item>
        </a-form>
    </a-modal>
</template>

<script>
import {copyObj, isMobile, isEmail, isCardNumber} from "@/utils/tools";
// import {whiteSave} from "../../../../api/auth-mode/sensitive-words";

export default {
    name: "whitelist-modal",
    data() {
        return {
            visible: false,
            saving: false,
            triggerTypeMap: {
                1: {
                    name: "手机号",
                    desc: "发送的手机号存在白名单内即不接收通知"
                },
                2: {
                    name: "银行卡号",
                    desc: "发送的银行卡号存在白名单内即不接收通知"
                },
                3: {
                    name: "邮箱",
                    desc: "发送的邮箱号存在白名单内即不接收通知"
                },
            },
            formState: {
                id: "",
                white_type: 1,
                white: "",
            }
        }
    },
    methods: {
        show(type, record = {}) {
            this.formState.white_type = type
            this.formState.white = record.white || ""
            if (record._id > 0) {
                this.formState.id = record._id
            } else {
                this.formState.id = ""
            }
            this.visible = true
            this.saving = false
        },
        inputFormat() {
            this.formState.white = this.formState.white.trim()
            if (this.formState.id > 0) {
                this.formState.white = this.formState.white.replace(/\n/g, "")
                return
            }
            let words = this.formState.white.split(/\n/)
            words = words.filter(i => i)
            if (words.length > 10) {
                words = words.slice(0, 10)
                this.$message.warning("一次最多添加10个关键词")
            }
            this.formState.white = words.join('\n')
        },
        save() {
            try {
                this.saving = true
                this.formState.white = this.formState.white.trim()
                if (!this.formState.white) {
                    throw "请输入白名单"
                }
                let whites = this.formState.white.split(/\n/g)
                let _whites = []
                let vaildFunc, err
                switch (this.formState.white_type) {
                    case 1:
                        vaildFunc = isMobile
                        err = "请输入正确的手机号"
                        break
                    case 2:
                        vaildFunc = isCardNumber
                        err = "请输入正确的银行卡号"
                        break
                    case 3:
                        vaildFunc = isEmail
                        err = "请输入正确的邮箱"
                        break
                }
                for (let str of whites) {
                    if (vaildFunc(str)) _whites.push(str)
                }
                if (!_whites.length) {
                    throw err
                }
                let params = copyObj(this.formState)
                params.white = _whites.toString()
                // whiteSave(params).then(res => {
                    this.visible = false
                    this.saving = false
                    this.$message.success("已保存")
                    this.$emit("ok")
                // }).catch(() => {
                //     this.saving = false
                // })
            } catch (e) {
                console.log("Err:", e)
                this.$message.error(e)
                this.saving = false
            }
        }
    }
}
</script>

<style scoped lang="less">
/deep/ .ant-form-item-label {
  line-height: 28px;
}

/deep/ .ant-form-item-control {
  line-height: 28px;
}

.c8C8C8C {
  color: #8C8C8C
}
</style>
