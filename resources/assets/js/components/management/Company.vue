<template>
    <DialogCompany ref="DialogCompanyRef" @saved="onSaved"></DialogCompany>
    <el-row :gutter="10">
        <el-col :span="3">
            <el-input
                v-model="formState.name"
                clearable
                style="width: 100%"
                placeholder="Name"
            />
        </el-col>
        <el-col :span="1">
            <el-space size="small" spacer="|">
                <el-button type="primary" v-on:click="search()" >Search</el-button>
                <el-button color="#626aef" :icon="Edit" v-on:click="showAddModal()">Thêm công ty</el-button>
            </el-space>
        </el-col> 
    </el-row>
    <!-- Table from here -->
    <el-table :data="tableData" height="800" style="width: 100%" highlight-current-row v-loading="loadingTable" stripe>
        <el-table-column width="100" type="index" label="STT" align="center"/>
        <el-table-column prop="name" label="Tên công ty" width="400" />
        <el-table-column prop="tax_code" label="Mã số thuế" width="300" align="center"/>
        <el-table-column prop="db_connection" label="DB kết nối" width="300" align="center"/>
        <el-table-column prop="date_established" label="Ngày thành lập" :formatter="formatDate" align="center"/>
        <el-table-column prop="note" label="Ghi chú" width="300"/>
        <el-table-column label="Hành động" width="150" align="center">
            <template #default="scope">
                <el-button
                    size="small"
                    type="warning"
                    :icon="Edit"
                    v-on:click="showUpdateModal(scope.row)"
                />
                <el-popconfirm title="Bạn có chắc chắn xóa?" @confirm="deleteCompany(scope.row)">
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
    import DialogCompany from './DialogCompany.vue';

    
    interface FormState {
        name?: string,
    };

    interface Item {
        id?: number,
        purchase_id?: number,
        name?: string,
        note?: string,
        created_at?: string,
        date_established?: string,
    }

    const formatDate = (row: Item) => { return dayjs(row.date_established).format('DD/MM/YYYY');}
    const errorMessages = ref('')
    const tableData = ref()
    const loadingTable = ref(false)
    const users = ref()

    const formState = ref<FormState>({});
    const DialogCompanyRef = ref()
    const onSaved = () => {
        _fetch();
    };
    const search = () => {
        _fetch();
    };
    const _fetch = () => {
        axios.get('/api/management/company/get_company', {params:formState.value})
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
        DialogCompanyRef.value.ShowWithAddMode();
    };
    const showUpdateModal = (item:Item) => {
        DialogCompanyRef.value.ShowWithUpdateMode(item.id);
    };
    const deleteCompany = (item:Item) => {
        axios.delete('/api/management/company/delete', {
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

    onMounted(() => {
        _fetch();
    })
       
  
        
</script>

