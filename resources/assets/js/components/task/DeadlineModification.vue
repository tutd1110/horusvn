<template>
    <el-row :gutter="10">
        <el-col :span="2">
            <el-select
                v-model="formState.user_id"
                value-key="id"
                placeholder="Employees"
                clearable
                filterable
                style="width: 100%"
            >
                <el-option
                    v-for="item in filteredUsers"
                    :key="item.id"
                    :label="item.fullname"
                    :value="item.id"
                />
            </el-select>
        </el-col>
        <el-col :span="2">
            <el-select
                v-model="formState.department_id"
                value-key="value"
                placeholder="Department"
                clearable
                filterable
                style="width: 100%"
            >
                <el-option
                    v-for="item in departments"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                />
            </el-select>
        </el-col>
        <el-col :span="2">
            <el-select
                v-model="formState.status"
                value-key="value"
                placeholder="Approves"
                clearable
                filterable
                style="width: 100%"
            >
                <el-option
                    v-for="item in statuses"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                />
            </el-select>
        </el-col>
        <el-col :span="1">
            <el-button type="primary" v-on:click="search()" :loading="loadingSearch">Search</el-button>
        </el-col>
    </el-row>
    <!-- Table from here -->
    <el-table :data="tableData" height="800" style="width: 100%">
        <el-table-column prop="fullname" label="Name"/>
        <el-table-column prop="task_id" label="Task ID" width="150"/>
        <el-table-column prop="task_name" label="Task"/>
        <el-table-column prop="old_deadline" label="Deadline gần nhất"/>
        <el-table-column label="Info">
            <template #default="scope">
                <span>
                    <template v-if="scope.row.type === 1">
                        Add new deadline - Requested:
                    </template>
                    <template v-else>
                        Original:<span style="color: grey">{{ scope.row.original_deadline }}</span> - Requested:
                    </template>
                    <span style="color: green">{{ scope.row.requested_deadline }}</span>
                </span>
            </template>
        </el-table-column>
        <el-table-column prop="reason" label="Reason"/>
        <el-table-column prop="created_at" label="Created At" width="200"/>
        <el-table-column label="Operations" width="400">
            <template #default="scope">
                <el-button v-if="!scope.row.approved && userInfo!.is_permission" size="small" type="warning" @click="handleUpdateStatus(scope.row.id, 1)">Chậm deadline</el-button>
                <el-button v-if="!scope.row.approved && userInfo!.is_permission" size="small" type="success" @click="handleUpdateStatus(scope.row.id, 3)">Duyệt</el-button>
                <el-button v-if="userInfo!.is_permission" size="small" type="danger" @click="handleReject(scope.row, false)">Từ chối</el-button>
                <el-button v-if="userInfo!.user_id == scope.row.user_id || userInfo!.is_permission" size="small" type="info" @click="handleDelete(scope.row.id)">Xóa</el-button>
            </template>
        </el-table-column>
        
    </el-table>
    <el-col style="margin-top: 20px;display: flex;align-items: center;justify-content: center;">
        <el-pagination
            v-model="formState.current_page"
            :page-size="formState.per_page"
            background
            :total="total"
            @current-change="onChangePage"
        />
    </el-col>
    <el-dialog 
        v-model="dialogFeedback" 
        :title="'Từ chối gia hạn deadline'"
        style="width:30%;padding: 10px; font-weight: bold"
    >
        <el-form label-position="top">
            <el-form-item label="Lí do">
                <el-input v-model="feedback" :rows="2" type="textarea"/>
            </el-form-item>
        </el-form>
        <template #footer>
            <span class="dialog-footer">
                <el-button @click="dialogFeedback = false">Cancel</el-button>
                <el-button type="primary" @click="handleReject(dataReject, true)">Confirm</el-button>
            </span>
        </template>
    </el-dialog>
</template>
<script setup lang="ts">
import axios from 'axios';
import { onMounted, computed, ref } from 'vue';
import { callMessage } from '../Helper/el-message.js';

interface FormState {
    user_id?: number,
    department_id?: number
    status?: number
    current_page: number,
    per_page: number,
};
interface User {
    id?: number,
    fullname: string,
    department_id?: number
};
interface Option {
    value?: number,
    label: string
};
const total = ref(0);
const formState = ref<FormState>({
    status: 0,
    current_page: 1,
    per_page: 50
});
const loadingSearch = ref(false);
const dialogFeedback = ref(false);
const feedback = ref();
const dataReject = ref();
const tableData = ref<Array<Object>>([]);
const users = ref<Array<User>>([]);
const filteredUsers = computed(() => {
    const selectedDepartmentId = formState.value.department_id;
    if (selectedDepartmentId) {
        return users.value.filter(user => user.department_id === selectedDepartmentId);
    } else {
        return users.value;
    }
});
const errorMessages = ref('');
const departments = ref<Array<Option>>([]);
const statuses = ref<Array<Option>>([
    {
        label: 'Unapproved',
        value: 0
    },
    {
        label: 'Approved',
        value: 1
    },
    {
        label: 'Approved without violation',
        value: 3
    },
    {
        label: 'Rejected',
        value: 2
    }
]);
const onChangePage = (page: number) => {
    formState.value.current_page = page

    search()
}
const search = () => {
    loadingSearch.value = true;

    axios.post('/api/deadline-modification/list', formState.value)
    .then(response => {
        loadingSearch.value = false;
        tableData.value = response.data.items
        total.value = response.data.totalItems
    })
    .catch(error => {
        loadingSearch.value = false;
        tableData.value = [];
        total.value = 0
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    });
}
const handleUpdateStatus = (id: number, status: number) => {
    axios.post('/api/deadline-modification/updated-status', {
        id: id,
        status: status
    })
    .then(response => {
        callMessage(response.data.success, 'success');

        search();
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    });
}
const handleReject = (data: any, submit: boolean) => {
    if (submit) {
        axios.post('/api/deadline-modification/updated-status', {
            id: dataReject.value.id,
            status: 2,
            feedback: feedback.value
        })
        .then(response => {
            feedback.value = ''
            dialogFeedback.value = false
            search();
            callMessage(response.data.success, 'success');
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error');
        });
    } else {
        dialogFeedback.value = true
        dataReject.value = data
    }    
}
const handleDelete = (id: number) => {
    axios.delete('/api/deadline-modification/delete', {
        params: {
            id: id
        }
    })
    .then(response => {
        search();
    })
    .catch(error => {
        search();
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
interface User {
    user_id: number,
    is_permission: boolean
}
const userInfo = ref<User>();
onMounted(() => {
    axios.get('/api/deadline-modification/get_selboxes').then(response => {
        users.value = response.data.users
        departments.value = response.data.departments
        userInfo.value = response.data.user_info
    })

    search()
})
</script>