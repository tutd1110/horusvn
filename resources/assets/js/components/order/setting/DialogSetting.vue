<template>
    <el-dialog
        v-model="visible"
        style="font-weight: bold"
        draggable
        :closable="false"
        :style="{ width: '50%' }"
        :title="title"
    >
    <el-form
    ref="ruleFormRef"
    :model="ruleForm"
    :rules="rules"
    label-width="120px"
    class="demo-ruleForm"
    :size="formSize"
    status-icon
  >
    <el-form-item label="Cửa hàng" prop="store_id">
      <el-select style="width: 100%;" v-model="ruleForm.store_id" placeholder="Chọn cửa hàng">
        <el-option v-for="(store, indx) in stores" :key="indx" :label="store.name" :value="store.id" />
      </el-select>
    </el-form-item>
    <el-form-item label="Cho phép đặt">
        <el-switch
            v-model="ruleForm.is_active"
            class="mt-2"
            style="margin-left: 24px"
            inline-prompt
            :active-icon="Check"
            :inactive-icon="Close"
        />
    </el-form-item>
    
    <el-form-item label="Thời gian đặt" required>
      <el-col :span="11">
        <el-form-item prop="start_time">
            <el-time-picker
            v-model="ruleForm.start_time"
            label="Pick a time"
            placeholder="Giờ bắt đầu"
            style="width: 100%"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
        </el-form-item>
      </el-col>
      <el-col style="display: flex; justify-content: center;" class="text-center" :span="2">
        <span class="text-gray-500">-</span>
      </el-col>
      <el-col :span="11">
        <el-form-item prop="end_time">
          <el-time-picker
            v-model="ruleForm.end_time"
            label="Pick a time"
            placeholder="Giờ kết thúc"
            style="width: 100%"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
        </el-form-item>
      </el-col>
    </el-form-item>

    <!-- config noti -->
    <!-- <h4 style="font-weight: bold; text-align: center; margin: 10px 0;">Cấu hình thông báo</h4> -->

    <!-- <el-form-item label="Thời gian" prop="time_alert">
        <el-date-picker
          v-model="ruleForm.time_alert"
          type="datetime"
          label="Pick a date"
          placeholder="Chọn thời gian thông báo"
          style="width: 100%"
          value-format="YYYY-MM-DD HH:mm:ss"
          :default-time="defaultTime"
        />
    </el-form-item> -->
    <!-- <el-form-item label="Tiêu đề" prop="content_alert">
      <el-input v-model="ruleForm.content_alert" />
    </el-form-item> -->
    <!-- <el-form-item label="Nội dung" prop="content_alert">
        <el-input v-model="ruleForm.content_alert" placeholder="Nhập nội dung thông báo" type="textarea" />
      </el-form-item> -->
    <el-form-item>
      <el-button type="primary" @click="submitForm(ruleFormRef)">
        Submit
      </el-button>
      <el-button @click="resetForm(ruleFormRef)">Reset</el-button>
    </el-form-item>
  </el-form>
    </el-dialog>
</template>

<script lang="ts" setup>
import axios from "axios";
import { ref, reactive } from "vue";
import type { FormInstance, FormRules } from 'element-plus'
import { callMessage } from "../../Helper/el-message";
import { Check, Close } from "@element-plus/icons-vue";

const emit = defineEmits(["savedConfig"]);

interface RuleForm {
  id: number | undefined,
  store_id: string
  time_alert: string
  start_time: string
  end_time: string
  content_alert: string,
  is_active: boolean
}
const formSize = ref('default')
const ruleFormRef = ref<FormInstance>()
const ruleForm = reactive<RuleForm>({
  id:undefined,
  store_id: '',
  time_alert: '',
  start_time: '',
  end_time: '',
  content_alert: '',
  is_active: true
})

const rules = reactive<FormRules<RuleForm>>({
  store_id: [
    {
      required: true,
      message: 'Chọn cửa hàng',
      trigger: 'change',
    },
  ],
  start_time: [
    {
      required: true,
      message: 'Chọn thời gian',
      trigger: 'change',
    },
  ],
  end_time: [
    {
      required: true,
      message: 'Chọn t  hời gian',
      trigger: 'change',
    },
  ],
  content_alert: [
    { max: 255, message: 'Nội dung tối đa 255 kí tự', trigger: 'blur' },
  ],
})

const stores = ref();
const defaultTime = new Date(2000, 1, 1, 9, 0, 0);
const errorMessages = ref("");
const visible = ref(false);

const getCurrentDate = () => {
    const date = new Date();
    let day = date.getDate();
    let month = date.getMonth() + 1;
    let year = date.getFullYear();

    return `${day}-${month}-${year}`;
}

let title = ref("Cấu hình lên món hôm nay " + getCurrentDate());

const callStoreSetting = async () => {
    try{
        console.log(ruleForm)
        const resp = await axios.post('/api/order/setting',ruleForm);
        if(resp.status == 200){
            callMessage(resp.data.message, "success");
            emit('savedConfig');
            visible.value = false;
        }
    }catch(error : any){
        if(error.response.status == 422){
            errorMessages.value = error.response.data.errors;
            callMessage(error.response.data.message, "error");
        }
    }
};

const SettingModalMode = async () => {
    visible.value = true;

    try {
        fetchStores();

        const resp = await axios.get("/api/order/setting");

        const dataResp = resp.data.setting;
        ruleForm.id = dataResp.id ?? null;
        ruleForm.store_id = dataResp.store.id ?? '';
        ruleForm.time_alert = dataResp.time_alert ?? '';
        ruleForm.start_time = dataResp.start_time ?? '';
        ruleForm.end_time = dataResp.end_time ?? '';
        ruleForm.content_alert = dataResp.content_alert ?? '';
        ruleForm.is_active = dataResp.is_active ?? ruleForm.is_active;
    } catch (error: any) {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, "error");
    }
};

const fetchStores = async ()=>{
    const resp = await axios('/api/order/stores');
    stores.value = resp.data.order_store.data;
}

const submitForm = async (formEl: FormInstance | undefined) => {
  if (!formEl) return
  await formEl.validate((valid, fields) => {
    if (valid) {
        callStoreSetting();
    } else {
      console.log('error submit!', fields)
    }
  })
}

const resetForm = (formEl: FormInstance | undefined) => {
  if (!formEl) return
  formEl.resetFields()
}

defineExpose({
    SettingModalMode,
});
</script>
<style lang="scss" scoped>
.flex-row{
    display: flex;
    justify-content: space-between;
    gap: 10px;
}
</style>