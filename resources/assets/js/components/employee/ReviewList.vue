<template>
    <list-review-by-employee-id ref="modal"></list-review-by-employee-id>
    <review-editor ref="modalReviewEditor"></review-editor>
    <!-- review period modal -->
    <el-dialog v-model="period_visible" width="30%" align-center>
        <el-radio-group v-model="formState.period">
            <el-radio :label="4">Hết học việc</el-radio>
            <el-radio :label="0">2 Weeks</el-radio>
            <el-radio :label="1">2 Months</el-radio>
            <el-radio :label="2">6 Months</el-radio>
            <el-radio :label="3">1 Year</el-radio>
        </el-radio-group>
        <template #footer>
            <span class="dialog-footer">
                <el-button @click="period_visible = false">Cancel</el-button>
                <el-button type="primary" @click="handleOkSendReview()">
                Send
                </el-button>
            </span>
        </template>
    </el-dialog>
    <!-- undo review modal -->
    <el-dialog v-model="undo_visible" width="40%" align-center>
        <el-radio-group v-model="formState.progress">
            <el-radio :label="0">Member</el-radio>
            <el-radio :label="0.5">Mentor</el-radio>
            <el-radio :label="1">Leader</el-radio>
            <el-radio :label="2">Project Manager</el-radio>
            <el-radio :label="3">Director</el-radio>
            <el-radio :label="4">Done</el-radio>
        </el-radio-group>
        <template #footer>
            <span class="dialog-footer">
                <el-button @click="undo_visible = false">Cancel</el-button>
                <el-button type="primary" @click="handleOkUndoReview()">
                Send
                </el-button>
            </span>
        </template>
    </el-dialog>
    <!-- note review modal -->
    <el-dialog v-model="note_visible" width="30%" align-center>
        <el-input
            v-model="formState.note"
            :autosize="{ minRows: 5, maxRows: 20 }"
            type="textarea"
            placeholder="Please input note"
        />
        <template #footer>
            <span class="dialog-footer">
                <el-button @click="note_visible = false">Cancel</el-button>
                <el-button type="primary" @click="handleOkNoteReview()">
                Send
                </el-button>
            </span>
        </template>
    </el-dialog>
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
                value-key="id"
                placeholder="Department"
                clearable
                filterable
                style="width: 100%"
            >
                <el-option
                    v-for="item in departments"
                    :key="item.id"
                    :label="item.name"
                    :value="item.id"
                />
            </el-select>
        </el-col>
        <el-col :span="1">
            <el-space size="small" spacer="|">
                <el-button type="primary" v-on:click="search()" :loading="loadingSearch">Search</el-button>
                <el-button color="#626aef" :icon="Edit" v-on:click="addReview()"></el-button>
            </el-space>
        </el-col>
    </el-row>
    <!-- Table from here -->
    <el-table :data="tableData" height="1050" style="width: 100%" highlight-current-row v-loading="loadingTable">
        <el-table-column type="index" width="50" />
        <el-table-column label="Nhân viên" width="300">
            <template #default="scope">
                <div class="user-info">
                    <el-avatar shape="square" size="default" :src="scope.row.avatar" />
                    <div class="user-details">
                        <span class="fullname">{{ scope.row.fullname }}</span>
                        <span class="email">{{ scope.row.email }}</span>
                    </div>
                </div>
            </template>
        </el-table-column>
        <el-table-column prop="phone" label="Điện thoại"/>
        <el-table-column label="Ngày sinh">
            <template #default="scope">
                <span :style="scope.row.is_birthday ? 'color: red' : ''">{{ scope.row.birthday }}</span>
            </template>
        </el-table-column>
        <el-table-column prop="department_id" label="Phòng ban"/>
        <el-table-column prop="position" label="Chức danh"/>
        <el-table-column prop="created_at" label="Ngày bắt đầu làm việc"/>
        <el-table-column prop="date_official" label="Ngày chính thức"/>
        <el-table-column prop="review_progress" label="Tiến độ" width="150"/>
        <!-- <el-table-column prop="review_progress" label="Tiến trình" width="280">
            <template #default="scope">
                <el-tooltip :content=scope.row.review_progress placement="top" effect="light">
                    <div class="">
                        <el-button style="margin-left:3px" size="small" :icon="Loading" type="info" />
                        <el-button style="margin-left:3px" size="small" :icon="Check" type="primary" />
                        <el-button style="margin-left:3px" size="small" :icon="Check" type="success" />
                        <el-button style="margin-left:3px" size="small" :icon="Check" type="warning" />
                    </div>
                </el-tooltip>
            </template>
        </el-table-column> -->
        <el-table-column prop="review_start_date" label="Ngày đánh giá mới nhất"/>
        <el-table-column prop="review_period" label="Loại đánh giá"/>
        <el-table-column prop="time_reviewed" label="Thời gian được đánh giá"/>
        <el-table-column prop="review_next_date" label="Ngày đánh giá tiếp theo"/>
        <el-table-column label="Hành động" width="280">
            <template #default="scope">
                <el-button
                    size="small"
                    type="success"
                    v-if="scope.row.review_id"
                    @click="showNoteReviewModal(scope.row.review_id)"
                    :icon="Notebook"
                />
                <el-button size="small" :icon="View" type="primary" @click="showEmployeeReviewModal(scope.row.id)" />
                <el-button
                    size="small"
                    type="success"
                    v-if="scope.row.review_progress == 'Completed' || !scope.row.review_progress"
                    @click="showReviewPeriodModal(scope.row.id)"
                    :icon="Promotion"
                />
                <el-button
                    size="small"
                    type="warning"
                    v-if="scope.row.review_id"
                    @click="showUndoReviewModal(scope.row.review_id)"
                    :icon="RefreshLeft"
                />
                <el-button
                    size="small"
                    type="danger"
                    v-if="scope.row.review_progress && scope.row.review_progress != 'Completed'"
                    @click="onClickDeleteButton(scope.row.review_id)"
                    :icon="DeleteFilled"
                />
            </template>
        </el-table-column>
    </el-table>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { onMounted, computed, ref } from 'vue';
import { Notebook, Edit, View, Promotion, RefreshLeft, DeleteFilled, Check, Loading } from '@element-plus/icons-vue'
import { callMessage } from '../Helper/el-message.js';
import ListReviewByEmployeeId from './ListReviewByEmployeeId.vue';
import ReviewEditor from './ReviewEditor.vue';

interface FormState {
    user_id?: number,
    department_id?: number,
    period?: number | null,
    progress?: number | null,
    review_id? : number | null,
    employee_id? : number | null,
    note?: string
};
interface User {
    id?: number,
    fullname: string,
    department_id?: number
};
interface Department {
    id: number,
    name: string
};
const formState = ref<FormState>({});
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
const departments = ref<Array<Department>>([]);
const loadingSearch = ref(false);
const loadingTable = ref(false);
const modal = ref();
const modalReviewEditor = ref();
const tableData = ref<Array<Object>>([]);
const search = () => {
    _fetch()
}
const addReview = () => {
    modalReviewEditor.value.showRegisterReviewModal();
};
const showEmployeeReviewModal = (employee_id: number) => {
    modal.value.showListReviewByEmployeeIdModal(employee_id);
}
const period_visible = ref(false);
const note_visible = ref(false);
const showNoteReviewModal = (review_id: number) => {
    note_visible.value = true
    formState.value.review_id = review_id;

    axios.get('/api/employee/review/get_note', {
        params: {
            review_id: review_id
        }
    })
    .then(response => {
        formState.value.note = response.data.note
    })
    .catch(error => {
        formState.value.note = ""
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const handleOkNoteReview = () => {
    let submitData = {
        id: formState.value.review_id,
        note: formState.value.note
    }

    axios.patch('/api/employee/review/update-note', submitData)
    .then(response => {
        note_visible.value = false
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;//put message content in ref
        callMessage(errorMessages.value, 'error');
    })
}
const showReviewPeriodModal = (employee_id: number) => {
    formState.value.period = null;
    formState.value.progress = null;
    formState.value.review_id = null;
    period_visible.value = true;

    formState.value.employee_id = employee_id;
}
const undo_visible = ref(false);
const showUndoReviewModal = (review_id: number) => {
    formState.value.period = null;
    formState.value.progress = null;
    formState.value.employee_id = null;
    undo_visible.value = true;

    formState.value.review_id = review_id;
}
const onClickDeleteButton = (review_id: number) => {
    axios.delete('/api/employee/review/delete', {
        params: {
            review_id: review_id
        }
    })
    .then(response => {
        _fetch()
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const handleOkSendReview = () => {
    let submitData = {
        employee_id: formState.value.employee_id,
        period: formState.value.period
    };

    axios.post('/api/review/send_review', submitData)
    .then(response => {
        period_visible.value = false;

        _fetch()
    })
    .catch((error) => {
        errorMessages.value = error.response.data.errors;//put message content in ref
        callMessage(errorMessages.value, 'error');
    });
}
const handleOkUndoReview = () => {
    let submitData = {
        review_id: formState.value.review_id,
        progress: formState.value.progress
    };

    axios.patch('/api/employee/review/undo', submitData)
    .then(response => {
        undo_visible.value = false;

        _fetch()
    })
    .catch((error) => {
        errorMessages.value = error.response.data.errors;//put message content in ref
        callMessage(errorMessages.value, 'error');
    });
}
const _fetch = () => {
    loadingSearch.value = true;
    loadingTable.value = true;
    axios.get('/api/employee/review/get_employee_list_with_review', {
        params: {
            department_id: formState.value.department_id,
            employee_id: formState.value.user_id
        }
    })
    .then(response => {
        loadingSearch.value = false;
        loadingTable.value = false;
        tableData.value = response.data;
    })
    .catch((error) => {
        loadingSearch.value = false;
        loadingTable.value = false;
        //When search target data does not exist
        tableData.value = []; //dataSource empty
        errorMessages.value = error.response.data.errors;//put message content in ref
        callMessage(errorMessages.value, 'error');

    });
}
onMounted(() => {
    axios.get('/api/common/departments')
    .then(response => {
        departments.value = response.data
    })
    axios.get('/api/common/get_employees')
    .then(response => {
        users.value = response.data
    })

    _fetch()
})
</script>