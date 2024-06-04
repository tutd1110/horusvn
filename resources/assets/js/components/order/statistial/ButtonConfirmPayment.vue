<template>
   <div>
      <span style="margin-right: 10px;" v-if="props.countOrderWeek != undefined">Tổng suất tuần: {{props.countOrderWeek}}(suất)</span>
      <!-- <el-button type="primary" v-if="is_not_paid && is_not_clicked" @click="openConfirm">
        <span style="margin-right: 5px;">Báo admin đã thanh toán</span>
        <el-icon><SuccessFilled /></el-icon>
      </el-button> -->
      <el-button type="info" @click="emit('eventReload')">
        <span style="margin-right: 5px;">Reload</span>
      <el-icon><Refresh /></el-icon>
     </el-button>
  </div>
</template>
<script lang="ts" setup>
import {ref, onMounted} from 'vue';
import { SuccessFilled, Refresh  } from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import axios from 'axios';
import { callMessage } from '../../Helper/el-message';

const props = defineProps<{
  is_administrator: boolean,
  countOrderWeek: number
}>();

const emit = defineEmits(['eventReload','eventClickReport']);
const is_not_clicked = ref(true); // chưa click
const is_not_paid = ref(true); // chưa thanh toán hết khoản nợ
const openConfirm = () => {
  ElMessageBox.confirm(
    'Bạn chắc chắn xác nhận là đã thanh toán tiền. Chờ admin duyệt thông báo. Tiếp tục?',
    'Xác nhận',
    {
      confirmButtonText: 'OK',
      cancelButtonText: 'Cancel',
      type: 'success',
    }
  ).then(() => {
      handleReportPaid();
    })
    .catch(() => {})
}

const handleReportPaid = async ()=>{
  try{
    const resp = await axios.patch('/api/order/user/quick-update',{
      paid_report_status: 'SENT'
    });
    callMessage(resp.data.success,'success');
    emit('eventClickReport');
    checkIsPaidPayment();
    is_not_clicked.value = false;
  }catch(error : any){
    callMessage(error.response.data.message, "error");
  }
}

const checkIsPaidPayment = async () =>{
  try{
    const resp = await axios.get('/api/order/payment/is-paid');
    is_not_paid.value = resp.data.is_not_paid;
  }catch(error : any){
    callMessage(error.response.data.message, "error");
  }
}

onMounted(()=>{
  // checkIsPaidPayment();
})
</script>
<style lang="scss" scoped>
    
</style>