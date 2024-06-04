<template>
    <div class="alert-dialog-wrapper">
        <el-dialog
        v-model="visible"
        style="font-weight: bold"
        draggable
        align-center
        :closable="false"
        :style="{ width: '50%' }"
        title="Thông báo đóng tiền ăn"
        >
            <div style="display: flex; justify-content: space-between;">
                <span>Số nợ tiền ăn của bạn là 150.000VND</span>
                <el-button @click="handleViewStatistial">Xem chi tiết thống kê</el-button>
            </div>
            <OrderSettingEmpty/>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup>
import { ref } from "vue";
import OrderSettingEmpty from "./OrderSettingEmpty.vue";

const emit = defineEmits(['redirectStatistialTab']);
const visible = ref(false);

const showAlertModalMode = ()=>{
    visible.value = true;
}

const handleViewStatistial = ()=>{
    emit('redirectStatistialTab');
    visible.value = false;
}

defineExpose({
    showAlertModalMode,
});
</script>
<style lang="scss" scoped>
.alert-dialog-wrapper{
    
}
</style>
