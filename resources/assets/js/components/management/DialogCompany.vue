<template>
    <el-dialog v-model="dialogVisible" :title="title" :width="widthDialog" draggable :before-close="handleClose"> 
        <template #header="{}">
            <span role="heading" class="el-dialog__title">{{ title }}</span>
        </template>
        <el-form :model="formState" label-width="140px" :label-position="labelPosition">
            <el-form-item label="Tên công ty">
                <el-input v-model="formState.name"/>
            </el-form-item>
            <el-form-item label="Mã số thuế">
                <el-input v-model="formState.tax_code"/>
            </el-form-item>
            <el-form-item label="DB kết nối">
                <el-input v-model="formState.db_connection"/>
            </el-form-item>
            <el-form-item label="Ngày thành lập">
                <el-date-picker
                    v-model="formState.date_established"
                    clearable
                    type="date"
                    placeholder="Chọn ngày"
                    style="width: 100%;"
                    format="DD/MM/YYYY"
                    value-format="YYYY-MM-DD"
                />
            </el-form-item>
            <el-form-item label="Ghi chú">
                <el-input v-model="formState.note" type="textarea"/>
            </el-form-item>
        </el-form>
        <template #footer>
            <span class="dialog-footer">
                <el-button @click="dialogVisible = false">Cancel</el-button>
                <template v-if="mode === 'ADD'">
                    <el-button type="primary" @click="store">
                        Thêm công ty
                    </el-button>
                </template>
                <template v-else-if="mode === 'UPDATE'">
                    <el-button type="primary" @click="update">
                        Update
                    </el-button>
                </template>
            </span>
        </template>
    </el-dialog>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { onMounted, computed, defineEmits, ref } from 'vue';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import { ElMessageBox } from 'element-plus'
import { callMessage } from '../Helper/el-message.js';
import { Edit, Timer } from '@element-plus/icons-vue'

dayjs.extend(utc);
dayjs.extend(timezone);

interface FormState {
    id?: number,
    name?: string,
    tax_code?: string,
    date_established?: string,
    db_connection?: string,
    note?: string,
}
interface submitData {
    id?: number,
    name?: string,
    tax_code?: string,
    date_established?: string,
    db_connection?: string,
    note?: string,
}

const dialogVisible = ref(false)
const mode = ref('')
const title = ref('')
const formState = ref<FormState>({})


const errorMessages = ref('')
const formClear = () => {
    // Create a new object with the same structure as the FormState interface
    const clearedFormState: FormState = {};

    // Assign the new object to the formState ref
    formState.value = clearedFormState;

    return clearedFormState;
}

const store = () => {
    axios.post('/api/management/company/store', formState.value)
    .then(response => {
        formClear()
        _close();
        callMessage(response.data.success, 'success');
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error')
    })
}
const emit = defineEmits(['saved'])
const _close = () => {
    dialogVisible.value = false;
    emit('saved');
}
const update = () => {
    let submitData: submitData = {
        id: selectedRecord.value,
        name: formState.value.name,
        note: formState.value.note,
        tax_code : formState.value.tax_code,
        date_established : formState.value.date_established,
        db_connection : formState.value.db_connection,
    }

    axios.patch('/api/management/company/update', submitData).then(response => {
        formClear()
        _close()
        callMessage(response.data.success, 'success');
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error')
    })
}
const ShowWithAddMode = () => {
    title.value = 'Thêm công ty'
    mode.value = 'ADD'
    dialogVisible.value = true;
    formClear()
}
const selectedRecord = ref()
const ShowWithUpdateMode = (id: number) => {    
    formClear()
    mode.value = "UPDATE";
    title.value = "Chỉnh sửa thông tin công ty"
    dialogVisible.value = true;
    selectedRecord.value = id;

    axios.get('/api/management/company/get_company_by_id', {
        params: {
            id: selectedRecord.value
        }
    }).then(response => {
        formState.value = response.data
    }).catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error')
    })
}
const handleClose = (done: () => void) => {
        ElMessageBox.confirm(
            'Bạn có chắc chắn muốn thoát?',
            'Cảnh báo',
        {
            confirmButtonText: 'OK',
            cancelButtonText: 'Cancel',
            type: 'warning',
            draggable: true,
        }
        )
        .then(() => {
            done()
        })
    }
const widthDialog = ref()
const labelPosition = ref()
onMounted(() => {
    var screenWidth = window.screen.width;
    if (screenWidth >= 1080) {
        widthDialog.value = '30%'
        labelPosition.value = 'left'
    } else {
        widthDialog.value = '90%'
        labelPosition.value = 'top'
    }

});

defineExpose({
    ShowWithAddMode,
    ShowWithUpdateMode
});
</script>