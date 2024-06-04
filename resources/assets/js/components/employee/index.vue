<template>
    <creat-or-update ref="modalRef" @saved="onSaved"></creat-or-update>
    <profile-drawer ref="modalProfileDrawer" @saved="onSaved"></profile-drawer>
    <div id="filter-block">
        <el-row :gutter="10">
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Tên</label>
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
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Bộ phận</label>
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
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Type of User</label>
                <el-select
                    v-model="formState.user_type"
                    value-key="id"
                    placeholder="Type of User"
                    clearable
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in user_type"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                    />
                </el-select>
            </el-col>
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Giới tính</label>
                <el-select
                    v-model="formState.gender"
                    value-key="id"
                    placeholder="Gender"
                    clearable
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in user_gender"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-col>
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Ngày chính thức</label>
                <el-date-picker
                    v-model="formState.date_official"
                    type="date"
                    placeholder="Official working date"
                    size="default"
                    format="DD/MM/YYYY"
                    value-format="YYYY-MM-DD"
                />
            </el-col>
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Bắt đầu làm việc</label>
                <el-date-picker
                    v-model="formState.created_at"
                    type="date"
                    placeholder="Start working date"
                    size="default"
                    format="DD/MM/YYYY"
                    value-format="YYYY-MM-DD"
                />
            </el-col>
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Trạng thái</label>
                <el-select
                    v-model="formState.user_status"
                    value-key="value"
                    placeholder="Status"
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
            <el-col :span="2" class="custom-filter" style="padding-top: 22px;">
                <el-space size="small" spacer="|">
                    <el-button type="primary" v-on:click="search()" :loading="loadingSearch">Search</el-button>
                    <el-button color="#626aef" v-if="is_administrator" :icon="Edit" v-on:click="showRegisterModal()"></el-button>
                    <el-button color="#626aef" @click="handleExport" :loading="loadingExport" v-if="is_administrator && is_viewphone">Export</el-button>
                </el-space>
            </el-col>
        </el-row>
    </div>
    <!-- Table from here -->
    <el-table :data="tableData" :height="heightScrollbar" style="width: 100%" highlight-current-row v-loading="loadingTable">
        <el-table-column type="index" width="50"/>
        <el-table-column prop="id" label="ID" width="50"/>
        <el-table-column label="Employee" width="350" sortable :sort-method="sortLastName">
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
        <el-table-column prop="gender" label="Gender"/>
        <el-table-column prop="phone" label="Phone" v-if="is_viewphone"/>
        <el-table-column label="Birthday" v-if="is_viewphone">
            <template #default="scope">
                <span :style="scope.row.is_birthday ? 'color: red' : ''">{{ scope.row.birthday }}</span>
            </template>
        </el-table-column>
        <el-table-column prop="department_id" label="Department"/>
        <el-table-column prop="position" label="Job title"/>
        <el-table-column prop="type" label="Type of User"/>
        <el-table-column prop="date_official" label="Official working date" v-if="is_viewphone"/>
        <el-table-column prop="created_at" label="Start working date" v-if="is_viewphone"/>
        <el-table-column label="Operations" v-if="is_administrator">
            <template #default="scope">
                <el-button v-if="is_viewphone" size="small" type="primary" @click="showProfileDrawer(scope.row.id)"
                >Profile</el-button
                >
                <el-button
                    size="small"
                    type="success"
                    @click="showEditModal(scope.row.id, scope.row.updated_at)"
                >Edit</el-button
                >
            </template>
        </el-table-column>
    </el-table>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { onMounted, computed, ref } from 'vue';
import { Edit } from '@element-plus/icons-vue'
import { callMessage } from '../Helper/el-message.js';
import CreatOrUpdate from './CreatOrUpdate.vue';
import ProfileDrawer from './profile/ProfileDrawer.vue';
import { useI18n } from 'vue-i18n';
import { downloadFile } from '../Helper/export';
import { resizeScreen } from '../Helper/resize-screen.js';

interface FormState {
    user_id?: number,
    user_status: number,
    department_id?: number,
    date_official?: string,
    created_at?: string,
    user_type?: number,
    gender?: string,
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
interface Department {
    id: number,
    name: string
};
const formState = ref<FormState>({
    user_status: 1
});
const statuses = ref<Array<Option>>([
    {
        label: 'Working',
        value: 1
    },
    {
        label: 'Quit',
        value: 2
    }
]);
const users = ref<Array<User>>([]);
const filteredUsers = computed(() => {
    const selectedDepartmentId = formState.value.department_id;
    if (selectedDepartmentId) {
        return users.value.filter(user => user.department_id === selectedDepartmentId);
    } else {
        return users.value;
    }
});
const heightScrollbar = ref();
const errorMessages = ref('');
const departments = ref<Array<Department>>([]);
const loadingSearch = ref(false);
const loadingTable = ref(false);
const tableData = ref<Array<Object>>([]);
const user_type = ref<Array<Department>>([]);
const user_gender = ref<Array<Option>>([]);
const loadingExport = ref(false); 
const search = () => {
    loadingSearch.value = true;
    loadingTable.value = true;

    axios.post('/api/employee/get_employee_list', formState.value)
    .then(response => {
        loadingSearch.value = false;
        loadingTable.value = false;
        tableData.value = response.data.employee;
        is_administrator.value = response.data.is_administrator;
        is_viewphone.value = response.data.is_viewphone;

        setTimeout(() => { heightScrollbar.value = resizeScreen() }, 0);
    })
    .catch(error => {
        loadingSearch.value = false;
        loadingTable.value = false;
        tableData.value = [];
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    });
}
const is_administrator = ref(false);
const is_viewphone = ref(false);
const modalProfileDrawer = ref();
const modalRef = ref();
const showProfileDrawer = (id: number) => {
    modalProfileDrawer.value.ShowWithProfileDrawerMode(id);
}
const showEditModal = (id: number, updated_at: string) => {
    modalRef.value.ShowWithUpdateMode(id, updated_at);
};
const showRegisterModal = () => {
    modalRef.value.ShowWithAddMode();
};
const onSaved = () => {
    search();
};
onMounted(() => {
    axios.get('/api/common/departments')
    .then(response => {
        departments.value = response.data
    })
    axios.get('/api/common/user_type')
    .then(response => {
        user_type.value = response.data
    })
    axios.get('/api/common/get_user_gender')
    .then(response => {
        user_gender.value = response.data
    })
    axios.get('/api/common/get_employees')
    .then(response => {
        users.value = response.data
    })

    loadingTable.value = true;
    axios.post('/api/employee/get_employee_list', formState.value).then(response => {
        //stop loading icon
        loadingTable.value = false;

        tableData.value = response.data.employee;
        is_administrator.value = response.data.is_administrator;
        is_viewphone.value = response.data.is_viewphone;

        setTimeout(() => { heightScrollbar.value = resizeScreen() }, 0);
    })
    .catch((error) => {
        loadingTable.value = false;
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
        //When search target data does not exist
        tableData.value = [];
    });
})

const sortLastName = (a:any, b:any) => {
  const extractLastName = (fullName:any) => {
    let nameParts = fullName.replace(/\(\d+\)/, '').trim().split(' ');
    return nameParts.pop().toLowerCase();
  };

  const lastNameA = extractLastName(a.fullname);
  const lastNameB = extractLastName(b.fullname);

  return lastNameA.localeCompare(lastNameB, 'vi');
};
const { t } = useI18n();
const handleExport = async () => {
    loadingExport.value = true;
    
    try{
        await downloadFile('/api/employee/list/export', formState.value, errorMessages, t);
        loadingExport.value = false;
    }catch(error: any){
        loadingExport.value = false;
    }
}
</script>
<style lang="scss">
.user-info {
    display: flex;
    align-items: center; /* Vertically align items */
}

.user-details {
    display: flex;
    flex-direction: column;
    margin-left: 10px; /* Adjust margin as needed */
}

.fullname,
.email {
    height: 100%; /* Set the height to match the avatar */
}
</style>