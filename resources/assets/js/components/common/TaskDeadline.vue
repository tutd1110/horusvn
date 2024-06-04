<template>
    <deadline-mod-form ref="modalDeadlineModFormRef" @saved="onSaved"></deadline-mod-form>
    <label name="name">Thông tin deadline</label> <el-icon style="cursor: pointer; color: green"><Plus @click="onAddDeadline"/></el-icon>
    <el-table
        :data="deadlines"
        style="width: 100%"
        border
    >
        <el-table-column type="index" width="50" align="center"/>
        <el-table-column label="Estimate" align="center" width="125">
            <template #default="scope">
                <el-date-picker
                    v-model="scope.row.estimate_date"
                    type="date"
                    format="DD-MM-YYYY"
                    value-format="YYYY-MM-DD"
                    class="none-border"
                    style="width: 100%;"
                    @change="onChangeEstimate(scope.row.id, $event)"
                    :disabled="scope.row.request_status == 0"
                />
            </template>
        </el-table-column>
        <el-table-column label="Actual" align="center" width="125">
            <template #default="scope">
                <el-date-picker
                    v-model="scope.row.actual_date"
                    type="date"
                    format="DD-MM-YYYY"
                    value-format="YYYY-MM-DD"
                    class="none-border"
                    style="width: 100%;"
                    @change="onChangeActual(scope.row.id, $event)"
                    :disabled="scope.row.request_status == 0"
                />
            </template>
        </el-table-column>
        <el-table-column label="Task status" align="center" width="130">
            <template #default="scope">
                <el-select 
                    v-model="scope.row.task_status"
                    class="none-border" 
                    placeholder="Select"
                    style="width:100%;"
                    @change="onChangeStatus(scope.row.id, $event)"
                    :class="[scope.row.status ? 'task-status-' + scope.row.status : 'no-select']"
                    :disabled="scope.row.request_status == 2 || scope.row.request_status == 0"
                >
                    <el-option
                        v-for="item in status"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                        :class="['task-status-' + item.value]"
                    />
                </el-select>
            </template>
        </el-table-column>
        <el-table-column label="Request info" align="center" width="240">
            <template #default="scope">
                <div class="request-info" v-if="scope.row.request_status_text">
                    <div class="requested-deadline">Requested date: {{ scope.row.requested_deadline }}</div>
                    <div class="request-status">Status: {{ scope.row.request_status_text }}</div>
                </div>
            </template>
        </el-table-column>
        <el-table-column label="Reason" align="center" width="240">
            <template #default="scope">
                <div class="request-info" v-if="scope.row.reason">
                    <div class="requested-deadline" style="font-weight: 400;">{{ scope.row.reason }}</div>
                </div>
            </template>
        </el-table-column>
        <el-table-column label="Feedback" align="center" width="240">
            <template #default="scope">
                <div class="request-info" v-if="scope.row.feedback">
                    <div class="requested-deadline" style="font-weight: 400;">{{ scope.row.feedback }}</div>
                </div>
            </template>
        </el-table-column>
        <el-table-column align="center">
            <template #default="scope">
                <el-icon style="color: red" v-if="userPosition > 0">
                    <Delete @click="onDeleteDeadline(scope.row.id)"/>
                </el-icon>
            </template>
        </el-table-column>
    </el-table>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { ref, computed, defineProps, watch, onMounted } from 'vue';
import { Plus, Delete } from '@element-plus/icons-vue'
import DeadlineModForm from '../task/DeadlineModForm.vue';
import { callMessage } from '../Helper/el-message.js';

interface User {
    id?: number,
    position?: number
}
interface Deadline {
    id: number,
    estimate_date: string;
    actual_date: string;
    status: number;
}
interface Status {
    value: number,
    label: string
}

const user = ref<User>();
const props = defineProps({
    id: Number,
    status: Array as () => Status[],
    visible: Boolean
});
const deadlines = ref<Deadline[]>([]);
const errorMessages = ref('');
const onAddDeadline = () => {
    axios.post('/api/task-deadline/store', {
        id: props.id
    })
    .then(response => {
        if (response.data.errors) {
            modalDeadlineModFormRef.value.ShowWithAddMode(props.id, null, null, 1);
        } else {
            callMessage(response.data.success, 'success');
        }

        fetch()
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;//put message content in ref
        callMessage(errorMessages.value, 'error');

        fetch()
    })
}
const onSaved = () => {
    fetch();
}
const onChangeEstimate = (id: number, value: string) => {
    update(id, 'estimate_date', value)
}
const onChangeActual = (id: number, value: string) => {
    update(id, 'actual_date', value)
}
const onChangeStatus = (id: number, value: number) => {
    update(id, 'status', value)
}
const modalDeadlineModFormRef = ref();
const update = (id: number, column: string, value: string | number | number[]) => {
    let submitData = {
        id: id,
        [column]: value ? value : ""
    }

    axios.patch('/api/task-deadline/quick_update', submitData)
    .then(response => {
        if ((column == 'estimate_date' || column == 'actual_date') && userPosition.value < 1 && response.data.warning) {
            modalDeadlineModFormRef.value.ShowWithAddMode(props.id, id, value, 0);
            fetch()

            return;
        }

        // Success
        callMessage(response.data.success, 'success');

        fetch()
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;//put message content in ref
        callMessage(errorMessages.value, 'error');

        fetch()
    })
}
const onDeleteDeadline = (id: number) => {
    axios.delete('/api/task-deadline/delete', {
        params: {
            id: id
        }
    })
    .then(response => {
        fetch()
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');

        fetch()
    })
}
const fetch = () => {
    //get list task deadlines
    axios.get('/api/task-deadline/list', {
        params: {
            id: props.id
        }
    }).then(response => {
        deadlines.value = response.data
    }).catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const userPosition = computed(() => {
    return user?.value?.position ?? 0
})
onMounted(() => {
    watch(() => props.visible, (newVal, oldVal) => {
        if (newVal === true) {
            fetch()

            axios.get('/api/task-deadline/employee-info').then(response => {
                user.value = response.data
            }).catch(error => {
                errorMessages.value = error.response.data.errors;
                callMessage(errorMessages.value, 'error');
            })
        }
    },
    {
        immediate: true
    })
});
</script>
<style lang="scss">
.request-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}
  
.requested-deadline {
    /* Apply styles for requested deadline here */
    font-weight: bold; /* For example */
}
</style>