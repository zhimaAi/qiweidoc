<template>
  <a-modal
    class="select-tag-dialog-biubiu"
    v-model="visible"
    @cancel="hide"
    @ok="sure"
    :mask="isMaskModal"
    width="1000px"
  >
    <template #title>
      <div class="title-box">
        {{ title || '给客户群打标签' }}
        <div
          :style="{ width: widthLoading + '%' }"
          class="width-loading"
          v-if="widthLoading != 200"
        ></div>
      </div>
    </template>
    <div class="block translate3d">
      <div class="fx-ac-be mb10">
        <p class="wk-black-85-text title">
          已选择的标签({{ selectTag.length }})
        </p>
        <a-button type="link" @click="clearSelect">清空选择</a-button>
      </div>
      <div v-if="selectTag.length" class="select-tag">
        <a-space :size="10" style="flex-wrap:wrap">
          <a-tag
            v-for="(item, index) in selectTag.slice(0, 50)"
            :key="item.id"
            color="blue"
            closable
            @close="unselect(index)"
            >{{ item.name }}({{item.chat_count || 0}})
            </a-tag
          >
        </a-space>
      </div>
      <div v-else class="empty-img fx-ac-jc">
        <img class="img" src="@/assets/image/cst-task-empty.png" alt="" />
        <span>暂未选择群标签</span>
      </div>
    </div>
    <div class="block translate3d">
      <div class="fx-ac-be mb10">
        <p class="wk-black-85-text title">选择可添加的标签</p>
        <div class="fx-ac">
          <a-button type="link" @click="addBoxShow" class="mr8">
            <PlusOutlined />
            新建群标签组
          </a-button>
          <a-input-search
            style="width:350px"
            placeholder="请输入标签组或标签名搜索"
            v-model="searchValue"
            @search="onSearch"
            :allowClear="true"
          />
        </div>
      </div>
      <div class="spin fx-ac-jc" v-if="loading">
        <a-spin />
      </div>
      <template v-else>
        <div v-for="(item, index) in tagList" :key="item.group_id">
          <div
            class="wk-black-65-text custom-title flex"
            :class="{ 'fir-cut-title': index === 0 }"
          >
            <!-- <a-icon
              type="caret-right"
              :class="['mr10', { 'aret-right-rote': item.is_open }]"
              @click="roteGroup(item, index)"
            /> -->

            <a-checkbox
              v-if="isMultiple"
              :indeterminate="item.indeterminate"
              v-model:checked="item.checkAll"
              @change="onCheckAllChange($event, item)"
            >
              <span v-html="getSearName(item)"></span>
              <!-- {{ item.group_name }} -->
            </a-checkbox>
            <!-- <span v-else> {{ item.group_name }}</span> -->
            <span v-html="getSearName(item)" v-else></span>
          </div>
          <div v-if="item.is_open" class="mt10">
            <a-space :size="10" class="fl-wrap">
              <template v-for="tag in item.tag">
                <a-tag
                  :key="tag.id"
                  :class="{ 'cur-not': disabledTagsList.indexOf(tag.id) != -1 }"
                  :color="isSelect(tag) ? 'blue' : ''"
                  v-if="excludeTags.indexOf(tag.id) == -1"
                  @click="select(tag, item)"
                >
                  <!-- <span>{{ tag.name }}</span> -->
                  <span v-html="getSearName(tag)"></span>
                </a-tag>
              </template>
              <template>
                <a-input
                  placeholder="输入标签名"
                  style="width:100px"
                  v-if="item.showInput"
                  :maxLength="20"
                  class="add-input"
                  v-focus
                  @blur="addTag(item)"
                  @keyup.enter="addTag(item)"
                  :ref="'add_input_' + item.group_id"
                ></a-input>
                <a-button
                  class="add"
                  @click="() => (item.showInput = true)"
                >
                <PlusOutlined />
                </a-button>
              </template>
            </a-space>
          </div>
        </div>
      </template>
    </div>
    <!-- 新建标签弹窗组 -->
    <groupTagGroupAdd
      ref="groupTagGroupAddRef"
      @update="groupTagUpdate"
    ></groupTagGroupAdd>
  </a-modal>
</template>
<script>
import { PlusOutlined } from '@ant-design/icons-vue';
import groupTagGroupAdd from './group-tag-group-add.vue'
export default {
  components: {
    groupTagGroupAdd,
    PlusOutlined
  },
  data() {
    return {
      visible: false,
      tagList: [],
      selectTag: [],
      excludeTags: [], //排除的标签
      disabledTags: [], //禁用的标签
      searchValue: '', //搜索框内容
      loading: false,

      widthLoading: 0,
      numIndex: 0,
      son_index: 0,
      fatherList: [], //记录所有父标签
      sonList: [], // 记录所有子标签
      newTagList: [], // 数据列表

      time_val: null, //定时器
      tempList: [],
      tag_visible_group: false,
    }
  },
  props: {
    title: {
      type: String,
    },
    disabled: {
      type: Array,
      default() {
        return []
      },
    },
    isMultiple: {
      type: Boolean,
      default: true,
    },
    isMaskModal: {
      type: Boolean,
      default: true,
    },
  },
  computed: {
    role() {
      return this.$store.state.user.role
    },
    disabledTagsList() {
      // 合并两个数组 props传入，show传入
      let arr = this.disabled.concat(this.disabledTags)
      return this.uniqueFun(arr)
    },
  },
  directives: {
    focus: {
      inserted(el) {
        el.focus()
      },
    },
  },
  methods: {
    clear() {
      clearInterval(this.time_val)
      this.selectTag = []
      this.excludeTags = []
      this.disabledTags = []
      this.time_val = null
      this.searchValue = ''
      this.tempList = []
      this.tagList = []
      this.fatherList = []
      this.sonList = []
      this.newTagList = []
      this.numIndex = 0
      this.son_index = 0
      this.widthLoading = 0
    },
    hide() {
      this.visible = false
      this.clear()
      this.$emit('cancel', this.visible)
    },
    // tag已选择的标签   excludeTags 排除的标签 , disabledTags设置禁用标签
    show(tag = [], excludeTags = [], disabledTags = []) {
      this.visible = true
      this.selectTag = [...tag]
      // 设置排除标签
      if (excludeTags.length > 0) {
        this.excludeTags = excludeTags.map((item) => item.id)
      }
      // 设置禁用标签
      if (disabledTags.length > 0) {
        this.disabledTags = disabledTags.map((item) => item.id)
      }
      // if (this.tempList.length == 0) {
      this.search()
      // return
      // }else{
      //   this.againData(this.tempList)
      // }
      this.getChecked()
    },
    againData(arr) {
      // 数据列表
      this.newTagList = []
      // 所有父标签
      this.fatherList = []
      // 所有子标签
      this.sonList = []
      // 展示的数据量50个子标签
      this.numIndex = 50
      this.son_index = 0
      this.getNewTag(this.numIndex, arr)
      this.tagList = this.newTagList
      this.getTimeVal()
    },
    async search() {
      this.loading = true
      await this.$api
        .searchGroupTagList({ name: this.searchValue })
        .then((res) => {
          let list = JSON.parse(JSON.stringify(res.data)) || []
          list.forEach((item) => {
            item.showInput = false
            item.is_open = true
            item.indeterminate = false
            item.checkAll = false
            item.checkedList = []
          })
          // this.tagList = list
          this.tempList = JSON.parse(JSON.stringify(list))
          this.againData(list)
          this.getChecked()
          this.loading = false
        })
        .catch((e) => {
          this.loading = false
        })
    },
    getTimeVal() {
      clearInterval(this.time_val)
      this.time_val = setInterval(() => {
        // 进度条
        let loadNum = parseInt((this.numIndex / this.sonList.length) * 100)
        this.widthLoading = loadNum || 0
        this.loadScroll()
      }, 600)
    },
    // 批量加载数据
    getNewTag(quantity, arr) {
      if (this.fatherList.length === 0 || this.sonList.length === 0) {
        // 记录所有父标签
        let fatherList = []
        // 记录所有子标签
        let sonList = []
        // 数据源
        let list = []
        if (arr) {
          list = JSON.parse(JSON.stringify(arr))
          // list = arr
        } else {
          list = JSON.parse(JSON.stringify(this.tempList))
        }
        // 遍历数据源
        for (let i = 0; i < list.length; i++) {
          // 保存父标签
          fatherList.push(list[i])
          for (let j = 0; j < list[i].tag.length; j++) {
            // 保存子标签
            sonList.push(list[i].tag[j])
          }
          // 然后清空当前父标签中的子标签
          list[i].tag = []
        }
        this.fatherList = JSON.parse(JSON.stringify(fatherList))
        this.sonList = JSON.parse(JSON.stringify(sonList))
      }
      for (let i = 0; i < this.fatherList.length; i++) {
        let item = this.fatherList[i]
        // 遍历部分子标签
        let son = this.sonList.slice(this.son_index, quantity)
        // 上次标数>总子标签数   || 上次标数>下一次加载的标签数
        if (this.son_index > this.sonList.length || this.son_index > quantity) {
          return
        }
        for (let j = 0; j < son.length; j++) {
          let i = son[j]
          // 如果子标签的group_id和父标签一样，当前子标签属于这个父标签
          if (i.group_id === item.group_id) {
            let is_tag = JSON.stringify(item.tag).indexOf(JSON.stringify(i))
            if (is_tag == -1) {
              item.tag.push(i)
            }
          }
        }
        // 列表中没有标签才添加
        let isTag = JSON.stringify(this.newTagList).indexOf(
          JSON.stringify(item)
        )
        if (isTag == -1) {
          // 有子标签了就添加当前父标签
          if (item.tag.length > 0) {
            this.newTagList.push(item)
          }
        }
      }
    },
    // 下拉加载数据
    loadScroll() {
      if (this.sonList.length < this.numIndex) {
        clearInterval(this.time_val)
        if (this.widthLoading == 200) return
        // 避免进度条跑不满
        this.widthLoading = 105.1
        // 1.5秒之后隐藏
        setTimeout(() => {
          this.widthLoading = 200
        }, 1500)
        return
      }
      // 每次拿100个子标签
      this.numIndex += 100
      this.getNewTag(this.numIndex)
      this.tagList = this.newTagList
      this.son_index += 100
    },
    select(tag, item) {
      // 禁止禁用的标签
      if (this.disabledTagsList.indexOf(tag.id) != -1) return
      // 记录多选的数据
      let check_index = item.checkedList.findIndex((sub) => sub.id == tag.id)
      if (check_index === -1) {
        item.checkedList.push(tag)
      } else {
        item.checkedList.splice(check_index, 1)
      }
      // 数据源
      let index = this.selectTag.findIndex((sub) => sub.id == tag.id)
      if (index === -1) {
        if (this.isMultiple) {
          this.selectTag.push(tag)
        } else {
          this.selectTag = [tag]
        }
      } else {
        this.selectTag.splice(index, 1)
      }
      // 记录多选框的状态
      item.indeterminate =
        !!item.checkedList.length && item.checkedList.length < item.tag.length
      item.checkAll = item.checkedList.length === item.tag.length
    },
    unselect(index) {
      this.selectTag.splice(index, 1)
      this.getChecked()
    },
    isSelect(item) {
      let index = this.selectTag.findIndex((sub) => sub.id == item.id)
      return index != -1
    },
    sure() {
      this.$emit('change', this.selectTag)
      this.hide()
    },
    addTag(group) {
      //添加标签
      const me = this
      let input = me.$refs['add_input_' + group.group_id][0].$el
      let value = input.value

      if (!value) {
        group.showInput = false
        return
      }
      let check = group.tag.filter((s, i, a) => {
        return s.name == value
      })
      if (check.length > 0) {
        me.$message.error('标签名称不能重复！')
        return
      }
      me.$api
        .addTag({
          tag_group_id: group.group_id,
          tag_name: [value],
        })
        .then(async (res) => {
          group.showInput = false
          input.value = ''
          me.$message.success('添加成功')
          await me.search()
          await this.$emit('update', this.tempList)
        })
    },
    roteGroup(item, index) {
      item.is_open = !item.is_open
      this.$set(this.tagList, index, item)
    },
    onCheckAllChange(e, item) {
      // 避免修改checkedList，影响到tag的数据源
      const map = new Map()
      let tag = JSON.parse(JSON.stringify(item.tag))
      Object.assign(item, {
        checkedList: e.target.checked ? tag : [],
        indeterminate: false,
        checkAll: e.target.checked,
      })

      // 全选
      if (!item.checkAll) {
        // 遍历tag数据源，找到已选数据源里面的一样的删除
        for (let i = 0; i < item.tag.length; i++) {
          let index = this.selectTag.findIndex(
            (sub) => sub.id == item.tag[i].id
          )
          if (index != -1) {
            this.selectTag.splice(index, 1)
          }
        }
      } else {
        // 全选时，排除禁用的
        for (let i = 0; i < this.disabledTagsList.length; i++) {
          let dItem = this.disabledTagsList[i]
          // let index = this.selectTag.findIndex((sub) => sub.id == dItem)
          // if (index != -1) {
          //   this.selectTag.splice(index, 1)
          // }
          let dIndex = item.checkedList.findIndex((sub) => sub.id == dItem)
          if (dIndex != -1) {
            item.checkedList.splice(dIndex, 1)
          }
        }
      }
      let data = [...this.selectTag, ...item.checkedList]
      this.selectTag = data.filter((v) => !map.has(v.id) && map.set(v.id, 1))
    },
    getChecked() {
      // 回显，处理多选框状态
      // 数据源
      for (let i = 0; i < this.tagList.length; i++) {
        let it = this.tagList[i]
        it.checkedList = []
        // 子级
        if (this.selectTag.length !== 0) {
          for (let j = 0; j < it.tag.length; j++) {
            let jt = it.tag[j]
            // 已选中的数据
            for (let k = 0; k < this.selectTag.length; k++) {
              let st = this.selectTag[k]
              if (jt.id == st.id) {
                it.checkedList.push(jt)
              }
            }
          }
        }
        // 如果已选中的子级和tag里面的一样的
        if (it.checkedList.length === it.tag.length) {
          it.checkAll = true
          it.indeterminate = false
        } else {
          it.checkAll = false
          it.indeterminate = false
          if (it.checkedList.length > 0) {
            it.indeterminate = true
          }
        }
      }
    },
    uniqueFun(arr) {
      const map = new Map()
      return arr.filter((item) => {
        return !map.has(item) && map.set(item, 1)
      })
    },
    // 搜索框change
    searchChange() {
      clearInterval(this.time_val)
      this.onSearch(this.searchValue)
    },
    onSearch(val) {
      if (val == '') {
        this.getChecked()
        this.againData()
        return
      }
      this.widthLoading = 0
      let list = []
      for (let i = 0; i < this.tempList.length; i++) {
        let item = this.tempList[i]
        list[i] = {
          group_id: item.group_id,
          group_name: item.group_name,
          showInput: item.showInput,
          is_open: item.is_open,
          tag: [],
          indeterminate: false,
          checkAll: false,
          checkedList: [],
        }
        for (let j = 0; j < item.tag.length; j++) {
          let ele = this.tempList[i].tag[j]
          if (ele.name.indexOf(val) > -1) {
            list[i].tag.push({
              group_id: ele.group_id,
              id: ele.id,
              name: ele.name,
              chat_count:ele?.chat_count,
            })
          }
        }
      }

      let arr = []
      for (let i = 0; i < list.length; i++) {
        let item = list[i]
        item.is_open = true
        // 分组优先
        if (item.group_name.indexOf(val) > -1){
          let filsub = this.tempList.filter(
            (sub) => sub.group_id == item.group_id
          )[0]
          arr.push(filsub)
        }else if(item.tag.length > 0){
          arr.push(item)
        }

        // 子级优先
        // if (item.tag.length > 0) {
        //   arr.push(item)
        // } else {
        //   if (item.group_name.indexOf(val) > -1) {
        //     let filsub = this.tempList.filter(
        //       (sub) => sub.group_id == item.group_id
        //     )[0]
        //     arr.push(filsub)
        //   }
        // }
      }
      this.againData(arr)
      this.getChecked()
    },
    getSearName(item) {
      let name = item.group_name || item.name
      let count = item.chat_count || 0
      let str = this.searchValue
      let names = name.split(str)
      let nameStr = ''
      if (names.length > 1) {
        nameStr = names.join(`<span style="color: #ED744A;">${str}</span>`)
      }
      return (this.searchValue && nameStr ? nameStr : name ) + `(${count})`
    },
    clearSelect() {
      this.selectTag = []
      for (let i = 0; i < this.tagList.length; i++) {
        let it = this.tagList[i]
        it.checkedList = []
        it.checkAll = false
        it.indeterminate = false
      }
    },
    // //添加标签组
    addBoxShow() {
      this.$refs.groupTagGroupAddRef.addBoxShow()
    },
    async groupTagUpdate() {
      await this.clear()
      await this.search()
      await this.getChecked()
      await this.$emit('update', this.tempList)
    },
  },
  beforeUnmount() {
    clearInterval(this.time_val)
  },
}
</script>
<style lang="less" scoped>
.fl-wrap {
  flex-wrap: wrap;
}
.mr10 {
  margin-right: 10px;
}
.mt10 {
  margin-top: 10px;
}
.mb10 {
  margin-bottom: 10px;
}
.spin {
  width: 100%;
  height: 250px;
}
.translate3d {
  transform: translate3d(0, 0, 0);
}
.block {
  margin-bottom: 28px;
  &:last-child {
    margin-bottom: 0;
  }
  .title {
    font-size: 14px;
    font-weight: 600;
    color: #262626;
  }
  .select-tag {
    min-height: 121px;
    max-height: 121px;
    overflow-y: auto;
  }
  .empty-img {
    flex-direction: column;
    .img {
      width: 100px;
    }
  }
  .custom-title {
    color: #333;
    margin-top: 10px;
    font-weight: 600;
    align-items: center;
    cursor: pointer;
    .aret-right-rote {
      transform: rotate(90deg);
    }
    span {
      margin-left: 4px;
    }
  }
  .fir-cut-title {
    margin-top: 0;
  }
  .cur-not {
    color: #d9d9d9;
    border: 1px solid #e7e7e7;
    cursor: not-allowed !important;
  }
  .add,
  .add-input {
    line-height: 30px;
    margin-bottom: 8px;
  }
}
.title-box {
  position: relative;
  .title-loading {
    line-height: 18px;
    right: 50%;
    bottom: 0;
    position: absolute;
    color: #bfbfbf;
    font-size: 12px;
  }
  .width-loading {
    position: absolute;
    top: -16px;
    left: -24px;
    border-top: 2px solid #2475fc;
    width: 1%;
    transition: all 0.2s;
    max-width: 105.1%;
  }
}
</style>
<style lang="less">
.select-tag-dialog-biubiu {
  .ant-modal {
    top: 30px;
  }
  .ant-tag {
    line-height: 30px;
    cursor: pointer;
    border-radius: 2px;
    &.disabled-tag {
      cursor: not-allowed;
      background: #f5f5f5;
      color: rgba(0, 0, 0, 0.25);
    }
  }
  .ant-modal-body {
    min-height: 660px;
    max-height: 660px;
    overflow-y: auto;
  }
  .ant-space-item {
    .ant-tag {
      margin: 0 0 8px 0;
    }
  }
}
</style>
