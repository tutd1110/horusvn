<template>
    <DialogDepartment ref="DialogDepartmentRef" @saved="onSaved"></DialogDepartment>
    <el-row :gutter="10">
        <el-col :span="3">
            <el-input
                v-model="formState.name"
                clearable
                style="width: 100%"
                placeholder="Name"
            />
        </el-col>
        <el-col :span="2">
            <el-select
                v-model="formState.company_id"
                value-key="id"
                placeholder="Công ty"
                clearable
                filterable
                style="width: 100%"
            >
                <el-option
                    v-for="item in companies"
                    :key="item.id"
                    :label="item.name"
                    :value="item.id"
                />
            </el-select>
        </el-col>
        <el-col :span="1">
            <el-space size="small" spacer="|">
                <el-button type="primary" v-on:click="search()" >Search</el-button>
                <el-button color="#626aef" :icon="Edit" v-on:click="showAddModal()">Thêm bộ phận</el-button>
            </el-space>
        </el-col> 
    </el-row>
    <!-- Table from here -->
    <el-table :data="tableData" height="800" style="width: 100%" highlight-current-row v-loading="loadingTable" stripe>
        <el-table-column width="100" type="index" label="STT" align="center"/>
        <el-table-column prop="id" label="ID" width="100" />
        <el-table-column prop="name" label="Tên bộ phận" width="400" />
        <el-table-column prop="short_name" label="Tên rút gọn" width="300" align="center"/>
        <el-table-column prop="company_name" label="Thuộc công ty" width="300" align="center"/>
        <el-table-column label="Gán công việc" align="center" width="150">
            <template #default="scope">
                <el-switch
                    v-model="scope.row.active_job"
                    class="ml-2"
                    style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949"
                    @change="update(scope.row.id, 'active_job', scope.row.active_job)"
                    :active-value=1
                    :inactive-value=0
                />
            </template>
        </el-table-column>
        <el-table-column prop="note" label="Ghi chú"/>
        <el-table-column label="Hành động" width="150" align="center">
            <template #default="scope">
                <el-button
                    size="small"
                    type="warning"
                    :icon="Edit"
                    v-on:click="showUpdateModal(scope.row)"
                />
                <el-popconfirm title="Bạn có chắc chắn xóa?" @confirm="deleteDepartment(scope.row)">
                    <template #reference>
                        <el-button
                            size="small"
                            type="danger"
                            :icon="DeleteFilled"
                        />
                    </template>
                </el-popconfirm>
            </template>
        </el-table-column>
    </el-table>
</template>

<script lang="ts" setup>
    import { ref, onMounted } from "vue";
    import dayjs from "dayjs";
    import axios from 'axios';
    import { Edit, View, DeleteFilled, Check, Upload } from '@element-plus/icons-vue'
    import { callMessage } from '../Helper/el-message.js';
    import DialogDepartment from './DialogDepartment.vue';

    
    interface FormState {
        name?: string,
        company_id?: number,
    };

    interface Item {
        id?: number,
        company_id?: number,
        name?: string,
        note?: string,
        created_at?: string,
    }

    const errorMessages = ref('')
    const companies = ref()
    const tableData = ref()
    const loadingTable = ref(false)

    const formState = ref<FormState>({});
    const DialogDepartmentRef = ref()
    const onSaved = () => {
        _fetch();
    };
    const search = () => {
        _fetch();
    };
    const _fetch = () => {
        axios.get('/api/management/department/get_department', {params:formState.value})
        .then(response => {
            tableData.value = response.data
        })
        .catch(error => {
            tableData.value = []
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error')
        })
    }

    const showAddModal = () => {
        DialogDepartmentRef.value.ShowWithAddMode();
    };
    const showUpdateModal = (item:Item) => {
        DialogDepartmentRef.value.ShowWithUpdateMode(item.id);
    };
    const deleteDepartment = (item:Item) => {
        axios.delete('/api/management/department/delete', {
            params: {
                id: item.id
            }
        }).then(response => {
            callMessage(response.data.success, 'success');
            _fetch();
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error')
        })
    };
    const update = (id:number, column:any, value:any) => {
        let submitData = {
            id: id,
            [column]: value
        }

        axios.patch('/api/management/department/quick_update', submitData)
        .then(response => {
            callMessage(response.data.success, 'success');
            search();
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;//put message content in ref
            callMessage(errorMessages.value, 'error');
            search();
        })
    }

    onMounted(() => {
        _fetch();
        axios.get('/api/management/department/get_selectboxes')
        .then(response => {
            companies.value = response.data.companies
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error')
        })
    })
       
  
        
</script>

