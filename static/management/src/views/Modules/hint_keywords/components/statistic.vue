<template>
  <div class="statistic-box">
    <div class="statistic-item">
      <div class="statistic-item-title">今日数据</div>
      <div class="statistic-content-box">
        <div class="statistic-content-item">
          <img class="statistic-content-item-icon" src="@/assets/image/statistic-client.png" />
          <div class="statistic-content-item-info">
            <div class="statistic-content-item-info-num">{{ listData.dayTotalData.statistic_staff_keywords }}</div>
            <div class="statistic-content-item-info-label">敏感词</div>
          </div>
          <div class="statistic-content-item-info">
            <div class="statistic-content-item-info-num">{{ listData.dayTotalData.statistic_staff_msg }}</div>
            <div class="statistic-content-item-info-label">敏感行为</div>
          </div>
        </div>
        <div class="statistic-content-item">
          <img class="statistic-content-item-icon" src="@/assets/image/statistic-employee.png" />
          <div class="statistic-content-item-info">
            <div class="statistic-content-item-info-num">{{ listData.dayTotalData.statistic_cst_keywords }}</div>
            <div class="statistic-content-item-info-label">敏感词</div>
          </div>
          <div class="statistic-content-item-info">
            <div class="statistic-content-item-info-num">{{ listData.dayTotalData.statistic_cst_msg }}</div>
            <div class="statistic-content-item-info-label">敏感行为</div>
          </div>
        </div>
      </div>
    </div>
    <div class="statistic-item">
      <div class="statistic-item-title">累计数据</div>
      <div class="statistic-content-box">
        <div class="statistic-content-item">
          <img class="statistic-content-item-icon" src="@/assets/image/statistic-client.png" />
          <div class="statistic-content-item-info">
            <div class="statistic-content-item-info-num">{{ listData.totalData.statistic_staff_keywords }}</div>
            <div class="statistic-content-item-info-label">敏感词</div>
          </div>
          <div class="statistic-content-item-info">
            <div class="statistic-content-item-info-num">{{ listData.totalData.statistic_staff_msg }}</div>
            <div class="statistic-content-item-info-label">敏感行为</div>
          </div>
        </div>
        <div class="statistic-content-item">
          <img class="statistic-content-item-icon" src="@/assets/image/statistic-employee.png" />
          <div class="statistic-content-item-info">
            <div class="statistic-content-item-info-num">{{ listData.totalData.statistic_cst_keywords }}</div>
            <div class="statistic-content-item-info-label">敏感词</div>
          </div>
          <div class="statistic-content-item-info">
            <div class="statistic-content-item-info-num">{{ listData.totalData.statistic_cst_msg }}</div>
            <div class="statistic-content-item-info-label">敏感行为</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { ruleStatistics } from "@/api/sensitive";

const loading = ref(false)
const listData = ref({
    totalData: {
      "statistic_staff_keywords": 0,
      "statistic_staff_msg": 0,
      "statistic_cst_keywords": 0,
      "statistic_cst_msg": 0
    },
    dayTotalData: {
      "statistic_staff_keywords": 0,
      "statistic_staff_msg": 0,
      "statistic_cst_keywords": 0,
      "statistic_cst_msg": 0
    }
})

const loadData = () => {
  loading.value = true
  let params = {}
  ruleStatistics(params).then(res => {
    listData.value = res.data
  }).finally(() => {
    loading.value = false
  })
}

onMounted(() => {
  loadData()
})
</script>

<style scoped lang="less">
.statistic-box {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;

  .statistic-item {
    width: calc(50% - 12px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 8px;
    height: 156px;
    flex-shrink: 0;
    border-radius: 6px;
    padding: 16px;
    background: var(--01-, #E5EFFF);

    .statistic-item-title {
      color: #000000;
      font-family: "PingFang SC";
      font-size: 16px;
      font-style: normal;
      font-weight: 600;
      line-height: 24px;
    }

    .statistic-content-box {
      display: flex;
      align-items: center;
      justify-content: space-between;

      .statistic-content-item {
        position: relative;
        width: calc(50% - 8px);
        height: 76px;
        flex-shrink: 0;
        display: flex;
        justify-content: right;
        align-items: center;
        border-radius: 8px;
        background-color: #F7F9FF;
        border: 1px solid #fff;

        .statistic-content-item-icon {
          position: relative;
          bottom: 8px;
          left: -1px;
          width: 74px;
          height: 92px;
        }

        .statistic-content-item-info {
          width: 50%;
          display: flex;
          flex-direction: column;
          gap: 2px;

          .statistic-content-item-info-num {
            align-self: stretch;
            color: #242933;
            text-align: center;
            font-family: "PingFang SC";
            font-size: 20px;
            font-style: normal;
            font-weight: 600;
            line-height: 28px;
          }

          .statistic-content-item-info-label {
            align-self: stretch;
            color: #7a8699;
            text-align: center;
            font-family: "PingFang SC";
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
          }
        }
      }
    }
  }
}
</style>
