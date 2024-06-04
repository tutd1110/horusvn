<template>
    <el-dialog v-model="dialogVisible" :title="title" :width="widthDialog" draggable :before-close="handleClose"> 
        <template #header="{}">
            <span role="heading" class="el-dialog__title">{{ title }}</span>
        </template>
        <el-form :model="formState" label-width="140px" :label-position="labelPosition">
            <el-form-item label="Tên bộ phận">
                <el-input v-model="formState.name"/>
            </el-form-item>
            <el-form-item label="Tên rút gọn">
                <el-input v-model="formState.short_name"/>
            </el-form-item>
            <el-form-item label="Thuộc công ty">
                <el-select
                    v-model="formState.company_id"
                    clearable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in companies"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="Gán công việc">
                <el-select
                    v-model="formState.active_job"
                    clearable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in active"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
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
                        Thêm bộ phận
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
    short_name?: string,
    company_id?: number,
    active_job?: number,
    note?: string,
}
interface submitData {
    id?: number,
    name?: string,
    short_name?: string,
    company_id?: number,
    active_job?: number,
    note?: string,
}

const companies = ref()
const dialogVisible = ref(false)
const mode = ref('')
const title = ref('')
const formState = ref<FormState>({})
const active = [
    {
        'value' : 0,
        'label' : 'Không gán công việc',
    },
    {
        'value' : 1,
        'label' : 'Gán công việc',
    },
]

const errorMessages = ref('')
const formClear = () => {
    // Create a new object with the same structure as the FormState interface
    const clearedFormState: FormState = {};

    // Assign the new object to the formState ref
    formState.value = clearedFormState;

    return clearedFormState;
}

const store = () => {
    axios.post('/api/management/department/store', formState.value)
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
        company_id: formState.value.company_id,
        short_name: formState.value.short_name,
        active_job: formState.value.active_job,
        note: formState.value.note,
    }

    axios.patch('/api/management/department/update', submitData).then(response => {
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
    title.value = 'Thêm bộ phận'
    mode.value = 'ADD'
    dialogVisible.value = true;
    formClear()
}
const selectedRecord = ref()
const ShowWithUpdateMode = (id: number) => {    
    formClear()
    mode.value = "UPDATE";
    title.value = "Chỉnh sửa thông tin bộ phận"
    dialogVisible.value = true;
    selectedRecord.value = id;

    axios.get('/api/management/department/get_department_by_id', {
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
    axios.get('/api/management/department/get_selectboxes')
    .then(response => {
        companies.value = response.data.companies
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error')
    })

});

defineExpose({
    ShowWithAddMode,
    ShowWithUpdateMode
});
</script>