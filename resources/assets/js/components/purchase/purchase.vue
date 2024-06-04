<template>
    <Dialog ref="dialogRef" @saved="onSaved"></Dialog>
    <DialogSupplier ref="dialogSupplierRef" @saved="onSaved"></DialogSupplier>
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
                v-model="formState.user_created"
                value-key="id"
                placeholder="Employees"
                clearable
                filterable
                style="width: 100%"
            >
                <el-option
                    v-for="item in users"
                    :key="item.id"
                    :label="item.fullname"
                    :value="item.id"
                />
            </el-select>
        </el-col>
        <el-col :span="2">
            <el-select
                v-model="formState.project_id"
                placeholder="Project"
                clearable
                filterable
                style="width: 100%"
            >
                <el-option
                    v-for="item in project"
                    :key="item.id"
                    :label="item.name"
                    :value="item.id"
                />
            </el-select>
        </el-col>
        <el-col :span="2">
            <el-select
                v-model="formState.type"
                placeholder="Loại yêu cầu"
                clearable
                filterable
                style="width: 100%"
            >
                <el-option
                    v-for="item in purchaseType"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                />
            </el-select>
        </el-col>
        <el-col :span="1">
            <el-space size="small" spacer="|">
                <el-button type="primary" v-on:click="search()" >Search</el-button>
                <el-button color="#626aef" :icon="Edit" v-on:click="showAddModal()">Tạo yêu cầu</el-button>
            </el-space>
        </el-col> 
    </el-row>
    <!-- Table from here -->
    <el-table :data="tableData" height="800" style="width: 100%" highlight-current-row v-loading="loadingTable" stripe>
        <el-table-column width="50" type="expand" >
            <template #default="scope">
                <div m="4" style="padding: 20px;">
                    <p m="t-0 b-2">Tên yêu cầu: {{ scope.row.name }}</p>
                    <!-- <p m="t-0 b-2">Người tạo: {{ scope.row.user_created }}</p>
                    <p m="t-0 b-2">Ghi chú: {{ scope.row.note }}</p> -->
                    <div style="display: flex; margin-bottom: 20px;">
                        <h3 style="margin: 0 20px 0 0;">Danh sách nhà cung ứng</h3>
                        <el-button v-on:click="showAddSupplierModal(scope.row)" type="primary">Thêm nhà cung ứng</el-button>
                    </div>
                    <el-table :data="scope.row.purchase_suppliers" border >
                        <el-table-column label="STT" type="index" width="60" align="center"/>
                        <el-table-column label="Tên nhà cung ứng" prop="name" width="300" />
                        <el-table-column label="SĐT" prop="phone" width="150" align="center"/>
                        <el-table-column label="MST" prop="tax_code" width="150" align="center"/>
                        <el-table-column label="Giá trị" prop="price" width="150" align="center"/>
                        <el-table-column label="Thời gian giao" prop="delivery_time" width="150" :formatter="formatDate" align="center"/>
                        <el-table-column label="Địa chỉ" prop="address" width="250"/>
                        <el-table-column label="Ghi chú" prop="note" width="300"/>
                        <el-table-column label="Báo giá" width="120" align="center">
                            <template #default="scope">
                                <el-button
                                    v-if="scope.row.path"
                                    size="small"
                                    type="success"
                                    :icon="View"
                                    v-on:click="showModalPDF(scope.row.path, scope.row.name)"
                                >
                                    Xem
                                </el-button>
                            </template>
                        </el-table-column>
                        <el-table-column label="PO" width="120" align="center">
                            <template #default="scope">
                                <el-button
                                    v-if="scope.row.path_po && scope.row.status == 1"
                                    size="small"
                                    type="success"
                                    :icon="View"
                                    v-on:click="showModalPDF(scope.row.path_po, scope.row.name)"
                                >
                                    Xem
                                </el-button>
                                <el-button
                                    v-else-if="scope.row.status == 0"
                                    size="small"
                                    type="success"
                                    :icon="Check"
                                    plain
                                    v-on:click="changeStatus(scope.row)"
                                >
                                    Duyệt
                                </el-button>
                                <el-button
                                    v-else-if="scope.row.status == 1"
                                    size="small"
                                    type="warning"
                                    :icon="Upload"
                                    plain
                                    v-on:click="showUpdateSupplierModal(scope.row)"
                                >
                                    Gửi PO
                                </el-button>
                            </template>
                        </el-table-column>
                        <el-table-column label="Hành động" align="center">
                            <template #default="scope">
                                <el-button
                                    size="small"
                                    type="warning"
                                    :icon="Edit"
                                    v-on:click="showUpdateSupplierModal(scope.row)"
                                />
                                <el-popconfirm title="Bạn có chắc chắn xóa?" @confirm="deleteSupplier(scope.row)">
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
                </div>
            </template>
        </el-table-column>
        <el-table-column width="55" type="index" label="STT" align="center"/>
        <el-table-column prop="name" label="Tên yêu cầu" width="400" />
        <el-table-column prop="type" label="Loại yêu cầu" width="200" align="center"/>
        <el-table-column prop="project_name" label="Dự án" width="200" align="center"/>
        <el-table-column prop="user_created" label="Người tạo" align="center"/>
        <el-table-column prop="created_at" label="Ngày tạo" :formatter="formatTime" align="center"/>
        <el-table-column prop="purchase_supplier_count" label="Số nhà cung ứng" align="center"/>
        <el-table-column prop="note" label="Ghi chú" width="300"/>
        <el-table-column label="Hành động" width="150" align="center">
            <template #default="scope">
                <el-button
                    size="small"
                    type="warning"
                    :icon="Edit"
                    v-on:click="showUpdateModal(scope.row)"
                />
                <el-popconfirm title="Bạn có chắc chắn xóa?" @confirm="deletePurchase(scope.row)">
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
    <el-dialog
        v-model="openPdf"
        :title="namePdf"
        width="85%"
        height="100%"
        destroy-on-close
        center
    >
         <embed :src="urlPdf" type="application/pdf" width="100%" height="700px" />
    </el-dialog>
</template>

<script lang="ts" setup>
    import { ref, onMounted } from "vue";
    import dayjs from "dayjs";
    import axios from 'axios';
    import { Edit, View, DeleteFilled, Check, Upload } from '@element-plus/icons-vue'
    import { callMessage } from '../Helper/el-message.js';
    import Dialog from './Dialog.vue';
    import DialogSupplier from './DialogSupplier.vue';

    
    interface FormState {
        name?: string,
        date?: string,
        type?: number,
        project_id?: number,
        user_created?: number,
    };

    interface Item {
        id?: number,
        purchase_id?: number,
        name?: string,
        note?: string,
        created_at?: string,
        delivery_time?: string,
    }

    const errorMessages = ref('')
    const tableData = ref()
    const loadingTable = ref(false)

    const openPdf = ref(false)
    const urlPdf = ref()
    const namePdf = ref()

    const project = ref()
    const purchaseType = ref()
    const users = ref()
    const expandedRowKeys = ref()

    const formatTime = (row: Item) => { return dayjs(row.created_at).format('HH:mm:ss DD/MM/YYYY');}
    const formatDate = (row: Item) => { return dayjs(row.delivery_time).format('DD/MM/YYYY');}

    const formState = ref<FormState>({});
    const dialogRef = ref()
    const dialogSupplierRef = ref()
    const onSaved = () => {
        _fetch();
    };
    const search = () => {
        _fetch();
    };
    const _fetch = () => {
        axios.get('/api/purchase/get_purchase', {params:formState.value})
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
        dialogRef.value.ShowWithAddMode();
    };
    const showUpdateModal = (item:Item) => {
        dialogRef.value.ShowWithUpdateMode(item.id);
    };
    const deletePurchase = (item:Item) => {
        axios.delete('/api/purchase/delete', {
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
    const deleteSupplier = (item:Item) => {
        axios.delete('/api/purchase/delete_supplier', {
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

    const showAddSupplierModal = (item:Item) => {
        dialogSupplierRef.value.ShowWithAddMode(item.id);
    };
    const showUpdateSupplierModal = (item:Item) => {
        dialogSupplierRef.value.ShowWithUpdateMode(item.id, item.purchase_id);
    };
    const showModalPDF = (url:String, name:String) => {
        openPdf.value = true
        urlPdf.value = url
        namePdf.value = name
        
    };
    const changeStatus = (item:Item) => {
        console.log(item);
        let submitData = {
            id: item.id,
        }
        
        axios.post('/api/purchase/update_supplier_status', submitData, {
        }).then(response => {
            callMessage(response.data.success, 'success');
            _fetch();
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error')
        })

    }

    onMounted(() => {
        _fetch();
        axios.get('/api/purchase/get_selectboxes')
        .then(response => {
            project.value = response.data.projects
            purchaseType.value = response.data.purchase_type
            users.value = response.data.users
        }).catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error')
        })
    })
       
  
        
</script>

