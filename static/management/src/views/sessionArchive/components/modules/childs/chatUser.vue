<template>
     <span class="user">
        {{ renderRole() }}
        <span class="info">
            <span>{{ user?.name || '...' }}</span>
            <span v-if="user?.zm_user_type === 'CUSTOMER'" class="is-wx-tag">@微信</span>
            <!--<span v-if="user?.zm_user_type === 'STAFF'" class="is-corp-tag">@{{user.name}}</span>-->
        </span>
     </span>
</template>

<script setup>
const props = defineProps({
    user: {
        type: Object
    }
})

const renderRole = () => {
    switch (props?.user.zm_user_type) {
        case 'CUSTOMER':
            return '客户'
        case 'STAFF':
            return '员工'
        default:
            return ''
    }
}
</script>

<style scoped lang="less">
.user {
    .info {
        display: inline-block;
        background: #F2F4F7;
        border-radius: 2px;
        padding: 2px 6px;
    }

    margin: 0 8px;
}
</style>
