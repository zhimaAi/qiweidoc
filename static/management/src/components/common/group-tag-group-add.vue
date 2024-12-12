<template>
  <div>
    <a-modal
      title="新建标签组"
      :visible="tag_visible_group"
      :width="480"
      @ok="tagHandleOkGroup"
      @cancel="tagHandleCancelGroup"
    >
      <div class="add-tag-modal m16">
        <div class="mb8">标签组</div>
        <div class="flex">
          <a-input
            v-model:value="tagGroup.tag_group_name"
            placeholder="请填写标签组名"
            style="width: 89.3%"
            :maxLength="15"
          />
          <!-- <div
            v-if="tagGroup.tag_group_name"
            class="reduce"
            @click.stop="editTagDel"
          >
            -
          </div> -->
        </div>
        <div class="mb8 mt8">
          <template>标签</template>
        </div>
        <template>
          <div
            class="label_add"
            v-for="(item, key) in tagGroup.tag_name"
            :key="key"
          >
            <a-input
              placeholder="请输入标签名"
              v-model:value="item.name"
              style="width: 89.3%;"
              class="list_input"
              :maxLength="15"
              @blur="(e) => tage_list_inpt(e, key)"
              help="标签名不能超过15字，且不能重名"
            />
            <div @click.stop="tage_list_dels(item, key)" class="reduce">
              -
            </div>
          </div>
        </template>
        <a-button
          class="add_tag_btn"
          v-if="tagGroup.tag_name.length < 20"
          @click="add_list_content"
        >
          <PlusOutlined />
          添加标签
        </a-button>
      </div>
    </a-modal>
  </div>
</template>

<script>
import { PlusOutlined } from '@ant-design/icons-vue';

export default {
  name: 'groupTagGroupAdd',
  props: {},
  components: {
    PlusOutlined
  },
  data() {
    return {
      tag_visible_group: false,
      tagGroup: {
        tag_group_name: '',
        tag_name: [],
      },
    }
  },
  computed: {},
  watch: {},
  created() {},
  mounted() {},
  methods: {
    //添加标签组
    addBoxShow() {
      this.tagGroup = {
        tag_group_name: '',
        tag_name: [{ name: '', id: 0 }],
      }
      this.tag_visible_group = true
    },
    editTagDel() {
      let parentTag = this.data.filter(
        (sub) => sub.group_id == this.tagGroup.tag_group_id
      )[0]
      let item = {
        group_id: this.tagGroup.tag_group_id,
        group_name: this.tagGroup.tag_group_name,
        num: parentTag.tag.length,
        tag: parentTag.tag,
      }
      this.tage_del(item, 0, 1, false)
    },
    tage_list_inpt(e, index) {
      // 输入框字体数量
      let value = e.target.value
      if (!value) {
        // this.$message.error('标签名称不能空！')
        return
      }
      let check = this.tagGroup.tag_name.filter((s, i, a) => {
        return i !== index && s.name === value
      })
      if (check.length > 0) {
        this.tagGroup.tag_name[index].name = ''
        this.$message.error('标签名称不能重名！')
      }
      return false
    },
    tage_list_dels(item, index) {
      if (item.num != 0) {
        this.tage_del(item, index, 2)
        return
      }
      this.tagGroup.tag_name.splice(index, 1)
    },
    add_list_content() {
      if (this.tagGroup.tag_name.length > 19) {
        this.$message.error('标签最大可设置20条')
      } else {
        this.tagGroup.tag_name.push({ name: '' })
      }
    },
    //新建标签弹窗确定按钮
    tagHandleOkGroup() {
      let sendData = { ...this.tagGroup }
      let api = ''
      //检测群组名称是否填写
      if (sendData.tag_group_name == '') {
        this.$message.error('请补全群组名称再提交！')
        return
      }
      //检测是否都有标签名
      let check = sendData.tag_name.filter((s, i, a) => {
        return s.name == ''
      })
      if (check.length > 0) {
        this.$message.error('请补全标签名再提交！')
        return
      }
      api = 'addTagGroup'
      sendData.tag_name = sendData.tag_name.map((map) => {
        return map.name
      })
      WK.showLoading()
      this.$api[api](sendData).then(
        (res) => {
          WK.hideLoading()
          if (res.res == 0) {
            this.$message.success(res.msg)
            this.$emit('update')
            this.tag_visible_group = false
          }
        },
        (error) => {
          WK.hideLoading()
        }
      )
    },
    //新建标签弹窗取消按钮
    tagHandleCancelGroup(e) {
      this.tag_visible_group = false
    },
    tage_del(item, index, type, open) {
      this.tagGroup.tag_name.splice(index, 1)
    },
  },
}
</script>

<style scoped lang="less">
.mb8 {
  margin-bottom: 8px;
}
.add-tag-modal {
  height: 500px;
  overflow-y: auto;
  .label_add {
    display: flex;
    width: 100%;
    margin-top: 8px;
  }
  .reduce {
    background: rgba(235, 84, 84, 1);
    color: #ffffff;
    width: 16px;
    cursor: pointer;
    height: 16px;
    text-align: center;
    line-height: 10px;
    font-size: 25px;
    margin-left: 14px;
    margin-top: 7px;
    border-radius: 50%;
  }
  .add_tag_btn {
    width: 385px;
    margin: 8px 20px 20px 0;
  }
}
</style>
