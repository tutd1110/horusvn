<template>
    <el-dialog v-model="dialogVisible" :title="title" width="30%" draggable>
        <el-form
            label-position="left"
            label-width="100px"
            :model="formState"
            style="max-width: 460px"
        >
            <el-form-item label="Deadline">
                <el-date-picker
                    v-model="formState.deadline"
                    type="date"
                    size="default"
                    format="DD/MM/YYYY"
                    value-format="YYYY/MM/DD"
                />
            </el-form-item>
            <el-form-item label="Reason">
                <el-input
                    v-model="formState.reason"
                    :autosize="{ minRows: 2, maxRows: 4 }"
                    type="textarea"
                    placeholder="Please input reason"
                />
            </el-form-item>
        </el-form>
        <template #footer>
            <span class="dialog-footer">
                <el-button @click="dialogVisible = false">Cancel</el-button>
                <el-button type="primary" @click="handleClickConfirm">
                    Confirm
                </el-button>
            </span>
        </template>
    </el-dialog>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { ref } from 'vue'
import { callMessage } from '../Helper/el-message.js';

interface FormState {
    task_id: number,
    task_deadline_id?: number,
    deadline?: string,
    reason?: string,
    type?: number
};
const formState = ref<FormState>({
    task_id: 0
});

const dialogVisible = ref(false);
const title = ref('');
const formClear = () => {
    formState.value.deadline = ''
    formState.value.reason = ''
}
//new mode
const ShowWithAddMode = (task_id: number, task_deadline_id: number | null, deadline: string | null, type: number) => {
    title.value = "Deadline Modification Requested"
    dialogVisible.value = true;
    formClear()

    formState.value.task_id = task_id

    if (task_deadline_id) {
        formState.value.task_deadline_id = task_deadline_id
    }
    if (deadline) {
        formState.value.deadline = deadline
    }

    if (type === 1) {
        formState.value.type = 1
    }
};
const errorMessages = ref('');
const emit = defineEmits(['saved'])
const handleClickConfirm = () => {
    axios.post('/api/deadline-modification/store', formState.value)
    .then(response => {
        callMessage(response.data.success, 'success');

        dialogVisible.value = false;
        emit('saved');
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    });
}

defineExpose({
    ShowWithAddMode,
});
</script>