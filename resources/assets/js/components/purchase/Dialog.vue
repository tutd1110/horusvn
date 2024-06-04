<template>
    <el-dialog v-model="dialogVisible" :title="title" :width="widthDialog" draggable :before-close="handleClose"> 
        <template #header="{}">
            <span role="heading" class="el-dialog__title">{{ title }}</span>
        </template>
        <el-form :model="formState" label-width="140px" :label-position="labelPosition">
            <el-form-item label="Tên yêu cầu">
                <el-input v-model="formState.name" :disabled="mode == 'SHOW' "/>
            </el-form-item>
            <el-form-item label="Dự án">
                <el-select
                    v-model="formState.project_id"
                    filterable
                    placeholder=""
                    clearable
                    style="width:100%;"
                >
                    <el-option
                        v-for="item in project"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="Loại yêu cầu">
                <el-select
                    v-model="formState.type"
                    filterable
                    placeholder=""
                    clearable
                    style="width:100%;"
                >
                    <el-option
                        v-for="item in purchaseType"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="Ghi chú">
                <el-input v-model="formState.note" type="textarea" :disabled="mode == 'SHOW'"/>
            </el-form-item>
        </el-form>
        <template #footer>
            <span class="dialog-footer">
                <el-button @click="dialogVisible = false">Cancel</el-button>
                <template v-if="mode === 'ADD'">
                    <el-button type="primary" @click="store">
                        Tạo yêu cầu
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
import { TIME_ZONE } from '../const.js'
import { ElMessageBox } from 'element-plus'
import { callMessage } from '../Helper/el-message.js';
import { Edit, Timer } from '@element-plus/icons-vue'

dayjs.extend(utc);
dayjs.extend(timezone);

interface FormState {
    id?: number,
    name?: string,
    project_id?: number,
    type?: number,
    note?: string,
}
interface submitData {
    id?: number,
    name?: string,
    note?: string,
    project_id?: number,
    type?: number,
}
interface Session {
    id: number,
    is_authority: boolean,
    is_leader: boolean
}

const dialogVisible = ref(false)
const mode = ref('')
const title = ref('')
const formState = ref<FormState>({})
const project = ref()
const purchaseType = ref()


const errorMessages = ref('')
const formClear = () => {
    // Create a new object with the same structure as the FormState interface
    const clearedFormState: FormState = {};

    // Assign the new object to the formState ref
    formState.value = clearedFormState;

    return clearedFormState;
}

const store = () => {
    axios.post('/api/purchase/store', formState.value)
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
        project_id: formState.value.project_id,
        type: formState.value.type,
    }

    axios.patch('/api/purchase/update', submitData).then(response => {
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
    title.value = 'Tạo yêu cầu đặt hàng'
    mode.value = 'ADD'
    dialogVisible.value = true;
    formClear()
}
const selectedRecord = ref()
const ShowWithUpdateMode = (id: number) => {    
    formClear()
    mode.value = "UPDATE";
    title.value = "Chỉnh sửa yêu cầu"
    dialogVisible.value = true;
    selectedRecord.value = id;

    axios.get('/api/purchase/get_purchase_by_id', {
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

    axios.get('/api/purchase/get_selectboxes')
    .then(response => {
        project.value = response.data.projects
        purchaseType.value = response.data.purchase_type
    }).catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error')
    })
});

defineExpose({
    ShowWithAddMode,
    ShowWithUpdateMode
});
</script>