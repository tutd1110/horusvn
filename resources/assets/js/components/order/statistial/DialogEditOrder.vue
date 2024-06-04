<template>
    <el-dialog
        v-model="visible"
        class="edit-modal"
        draggable
        border
        align-center
        :closable="false"
        :style="{ width: '50%' }"
        title="Chi tiết"
    >
        <el-form
        ref="formRef"
        :model="numberValidateForm"
        label-width="100px"
        class="demo-ruleForm"
        >
            <el-form-item
            label="Giá tiền"
            prop="amount"
            :rules="[]"
            >
                <el-input
                    v-model="formData.total_amount"
                    type="text"
                    autocomplete="off"
                />
            </el-form-item>
            <el-form-item
            label="Ghi chú"
            prop="admin_note"
            :rules="[]"
            >
                <el-input
                    v-model="formData.admin_note"
                    type="textarea"
                    autocomplete="off"
                />
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="submitOrder">Submit</el-button>
                <el-button @click="resetForm(formRef)">Reset</el-button>
            </el-form-item>
        </el-form>
    </el-dialog>
</template>

<script lang="ts" setup>
import axios from "axios";
import { onMounted, ref, watch, reactive } from "vue";
import { emitChangeFn, type FormInstance } from 'element-plus'
import { callMessage } from "../../Helper/el-message";

const emit = defineEmits(['updatedOrder']);

const props = defineProps<{
    isAdministrator: boolean,
}>();

const visible = ref(false);
const errorMessages = ref();

interface RuleFormData{
    admin_note:string|null,
    total_amount:number,
    id:number|undefined
}

const formRef = ref<FormInstance>()
const numberValidateForm = reactive({
  amount: '',
  admin_note: '',
})
const formData = reactive<RuleFormData>({
    id: undefined,
    total_amount: 0,
    admin_note: null
});

const fetchOrder = async (id:number)=>{
    try{
        const resp = await axios.get('/api/order/'+id);
        formData.total_amount = resp.data.order.total_amount;
        formData.admin_note = resp.data.order.admin_note;
        formData.id = resp.data.order.id;
        
    }catch(error: any){
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    }
}

const submitOrder = async ()=>{
    try{
        const resp = await axios.patch('/api/order/quick-update',formData);
        callMessage(resp.data.success,'success');
        emit('updatedOrder');
        visible.value = false;
    }catch(error: any){
        console.log(error)
        if(error.response.status == 422){
            errorMessages.value = error.response.data.errors;
            callMessage(error.response.data.message, "error");
        }else{
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error');
        }
    }
}

const resetForm = (formEl: FormInstance | undefined) => {
  if (!formEl) return
  formEl.resetFields()
}

const showEditModal = (id:number) => {
    if(typeof id == 'number' && props.isAdministrator){
        fetchOrder(id);
        visible.value = true;
    }
};

defineExpose({
    showEditModal,
});
</script>
<style lang="scss">

</style>
