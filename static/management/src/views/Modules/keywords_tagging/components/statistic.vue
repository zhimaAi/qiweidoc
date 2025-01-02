<template>
    <div class="statistic-box">
      <div class="statistic-item">
        <div class="statistic-item-title">打标签数</div>
        <div class="statistic-content-box">
          <div class="statistic-content-item">
            <!-- <img class="statistic-content-item-icon" src="@/assets/image/statistic-client.png" /> -->
            <div class="statistic-content-item-info">
              <div class="statistic-content-item-info-num">{{ listData.total_num }}</div>
            </div>
          </div>
        </div>
      </div>
      <div class="statistic-item">
        <div class="statistic-item-title">今日打标签数</div>
        <div class="statistic-content-box">
          <div class="statistic-content-item">
            <!-- <img class="statistic-content-item-icon" src="@/assets/image/statistic-client.png" /> -->
            <div class="statistic-content-item-info">
              <div class="statistic-content-item-info-num">{{ listData.today_num }}</div>
            </div>
          </div>
        </div>
      </div>
      <div class="statistic-item">
        <div class="statistic-item-title">昨日打标签数</div>
        <div class="statistic-content-box">
          <div class="statistic-content-item">
            <!-- <img class="statistic-content-item-icon" src="@/assets/image/statistic-client.png" /> -->
            <div class="statistic-content-item-info">
              <div class="statistic-content-item-info-num">{{ listData.yesterday_num }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { ref, onMounted, reactive } from 'vue'
  import { taskStatistics } from "@/api/session";
  import { useRoute } from 'vue-router';

  const route = useRoute()
  const loading = ref(false)
  const listData = reactive({
    today_num: 0,
    total_num: 0,
    yesterday_num: 0,
  })

  const loadData = () => {
    loading.value = true
    let params = {}
    if (route.query.task_id) {
        params.task_id = route.query.task_id
    }
    taskStatistics(params).then(res => {
      listData.today_num = res.data.today_num
      listData.total_num = res.data.total_num
      listData.yesterday_num = res.data.yesterday_num
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
    width: 50%;

    .statistic-item {
      width: calc(33% - 12px);
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
          width: 100%;
          height: 76px;
          flex-shrink: 0;
          display: flex;
          justify-content: left;
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
