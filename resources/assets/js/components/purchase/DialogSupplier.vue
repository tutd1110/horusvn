<template>
    <el-dialog v-model="dialogVisible" :title="title" draggable :before-close="handleClose"> 
        <template #header="{}">
            <span role="heading" class="el-dialog__title">{{ title }}</span>
        </template>
        <el-form :model="formState" label-width="140px" :label-position="labelPosition" style="display: flex; justify-content: space-around;">
            <el-card>
                <template #header>
                    <div class="card-header">
                        <span>Thông tin nhà cung ứng</span>
                    </div>
                </template>
                <el-form-item label="Nhà cung ứng mới">
                    <el-switch
                        v-model="formState.newCompany"
                        class="ml-2"
                        inline-prompt
                        style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949"
                        active-text=""
                        inactive-text=""
                    />    
                </el-form-item>
                <el-form-item label="Tên nhà cung ứng">
                    <el-input v-model="formState.name" clearable v-if="formState.newCompany"/>
                    <el-select
                        v-else
                        v-model="formState.company_id"
                        filterable
                        placeholder=""
                        clearable
                        style="width:100%;"
                        @change="handleCompany"
                    >
                        <el-option
                            v-for="item in companySupplier"
                            :key="item.id"
                            :label="item.name"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="Mã số thuế">
                    <el-input v-model="formState.tax_code" clearable @change="searchCompany"/>
                </el-form-item>
                <el-form-item label="Số điện thoại">
                    <el-input v-model="formState.phone" clearable/>
                </el-form-item>
                <el-form-item label="Địa chỉ">
                    <el-input v-model="formState.address" clearable/>
                </el-form-item>
            </el-card>
            <el-card>
                <template #header>
                    <div class="card-header">
                        <span>Thông tin đơn hàng</span>
                    </div>
                </template>
                <el-form-item label="Giá trị">
                    <el-input v-model="formState.price" clearable/>
                </el-form-item>
                <el-form-item label="Thời gian giao">
                    <el-date-picker
                        v-model="formState.delivery_time"
                        clearable
                        type="date"
                        placeholder="Chọn ngày"
                        style="width: 100%;"
                        format="DD/MM/YYYY"
                        value-format="YYYY-MM-DD"
                    />
                </el-form-item>
                <el-form-item label="Ghi chú">
                    <el-input v-model="formState.note" type="textarea"/>
                </el-form-item>
                <el-form-item label="Báo giá">
                    <el-upload
                        ref="uploadRef"
                        v-model="fileList"
                        class="upload-demo"
                        :auto-upload="false"
                        :limit="1"
                        @change="handleFileChange"
                    >
                        <el-button type="primary">Click to upload</el-button>
                        <template #tip>
                        <div class="el-upload__tip" v-if="formState.path && formState.path != ''" style="color: red;">
                            Bạn đã gửi báo giá, vui lòng gửi lại nếu muốn thay đổi.
                        </div>
                        </template>
                    </el-upload>
                </el-form-item>
                <el-form-item label="PO" v-if="formState.status == 1">
                    <el-upload
                        ref="uploadPORef"
                        v-model="fileListPO"
                        class="upload-demo"
                        :auto-upload="false"
                        :limit="1"
                        @change="handleFilePOChange"
                    >
                        <el-button type="primary">Click to upload</el-button>
                        <template #tip>
                        <div class="el-upload__tip" v-if="formState.path_po && formState.path_po != ''" style="color: red;">
                            Bạn đã gửi PO, vui lòng gửi lại nếu muốn thay đổi.
                        </div>
                        </template>
                    </el-upload>
                </el-form-item>
            </el-card>
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
import { onMounted, defineEmits, ref } from 'vue';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import { ElMessageBox, ElMessage } from 'element-plus'
import { callMessage } from '../Helper/el-message.js';
import { Edit, Timer, Delete, Download, Plus, ZoomIn } from '@element-plus/icons-vue'
import type {  UploadProps, UploadInstance, UploadUserFile } from 'element-plus'

const uploadRef = ref<UploadInstance>()
const uploadPORef = ref<UploadInstance>()

dayjs.extend(utc);
dayjs.extend(timezone);

interface FormState {
    id?: number,
    purchase_id?: number,
    name?: string,
    phone?: string,
    tax_code?: string,
    price?: string,
    delivery_time?: string,
    address?: string,
    note?: string,
    path?: string,
    path_po?: string,
    status?: number,
    newCompany?: boolean,
    company_id?: number,
}
const fileList = ref<UploadUserFile>()
const fileListPO = ref<UploadUserFile>()

const dialogVisible = ref(false)
const mode = ref('')
const title = ref('')
const formState = ref<FormState>({
    newCompany: false,
})
const purchaseId = ref()
const companySupplier = ref()

const errorMessages = ref('')
const formClear = () => {
    const clearedFormState: FormState = {};
    clearedFormState.newCompany = formState.value.newCompany
    formState.value = clearedFormState;
    return clearedFormState;
}
const selectedFile = ref();
const selectedPOFile = ref();
const handleFileChange = (file:any) => {
    if (file.raw.type !== 'application/pdf') {
        ElMessage.error('Bạn phải chọn đúng định dạng pdf')
        // file.raw = null
        file.remove();
    }else {
        selectedFile.value = file.raw;
    }
}
const handleFilePOChange = (file:any) => {
    if (file.raw.type !== 'application/pdf') {
        ElMessage.error('Bạn phải chọn đúng định dạng pdf')
        file.raw = null
    }else {
        selectedPOFile.value = file.raw;
    }
}
const store = () => {
    const formData = new FormData();
    Object.entries(formState.value).forEach(([key, value]) => {
        formData.append(key, String(value));
    });
    selectedFile.value ? formData.append('file', selectedFile.value) : '';
    selectedPOFile.value ? formData.append('filePO', selectedPOFile.value) : '';
    formData.append('purchase_id', purchaseId.value);
    
    axios.post('/api/purchase/store_supplier', formData)
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

const update = () => {
    const formData = new FormData();
    formData.append('_method', 'PATCH');
    formState.value.id = selectedRecord.value;
    Object.entries(formState.value).forEach(([key, value]) => {
        formData.append(key, String(value));
    });
    selectedFile.value ? formData.append('file', selectedFile.value) : '';
    selectedPOFile.value ? formData.append('filePO', selectedPOFile.value) : '';

    axios.post('/api/purchase/update_supplier', formData, {
    }).then(response => {
        formClear()
        _close()
        callMessage(response.data.success, 'success');
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error')
    })

}
const ShowWithAddMode = (id: number) => {
    purchaseId.value = id
    title.value = 'Thêm nhà cung ứng'
    mode.value = 'ADD'
    dialogVisible.value = true;
    formClear()
}
const selectedRecord = ref()
const ShowWithUpdateMode = (id: number, purchase_id:number) => {
    purchaseId.value = purchase_id    
    mode.value = "UPDATE";
    title.value = "Chỉnh sửa nhà cung ứng"
    dialogVisible.value = true;
    selectedRecord.value = id;
    formClear()

    axios.get('/api/purchase/get_purchase_supplier_by_id', {
        params: {
            id: selectedRecord.value
        }
    }).then(response => {
        formState.value = { ...formState.value, ...response.data };
        
    }).catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error')
    })
}
const handleCompany = () => {
    axios.get('/api/purchase/get_company_data', {
        params: {
            id: formState.value.company_id
        }
    }).then(response => {
        formState.value.phone = response.data.phone 
        formState.value.tax_code = response.data.tax_code 
        formState.value.address = response.data.address 
    }).catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error')
    })
}
const searchCompany = () => {
        console.log(formState.value.tax_code); 
    
    
    axios.get('/api/purchase/get_company_data', {
        params: {
            tax_code: formState.value.tax_code
        }
    }).then(response => {
        formState.value.company_id = response.data.id 
        formState.value.phone = response.data.phone 
        formState.value.address = response.data.address 
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

const emit = defineEmits(['saved'])
const _close = () => {
    dialogVisible.value = false;
    emit('saved');
}
const labelPosition = ref()
onMounted(() => {
    axios.get('/api/purchase/get_selectboxes')
    .then(response => {
        companySupplier.value = response.data.company_supplier
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