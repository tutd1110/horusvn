<template>
    <el-dialog 
        v-model="visible" 
        style="font-weight: bold; width: 30%" 
        draggable
        :title="title" class="modal-calendar">
        <el-form
            ref="ruleFormRef"
            label-position="top"
            :model="formState"
        >
            <el-row :gutter="20" style="margin-bottom: 0;">
                <el-col :span="22" :offset="1">
                    <el-form-item label="Thời gian" >
                        <el-date-picker
                            v-model="formState.date"
                            clearable
                            type="date"
                            placeholder="Chọn ngày"
                            style="width: 100%;"
                            format="DD/MM/YYYY"
                            value-format="YYYY-MM-DD"
                        />
                    </el-form-item>
                </el-col>
                <el-col :span="22" :offset="1">
                    <el-form-item label="Toàn công ty" >
                        <el-switch
                            v-model="formState.all_log"
                        />  
                    </el-form-item>
                </el-col>
                <el-col :span="22" :offset="1">
                    <el-form-item label="Nhân viên" >
                        <el-select 
                            v-model="formState.user_code"
                            clearable
                            filterable
                            multiple
                            :reserve-keyword="false"
                            placeholder="Select"
                            style="width: 100%;"
                            :disabled="formState.all_log == true"
                        >
                            <el-option
                                v-for="item in users"
                                :key="item.user_code"
                                :label="item.fullname"
                                :value="item.user_code"
                            />
                        </el-select>
                    </el-form-item>
                </el-col>
                <el-col :span="22" :offset="1">
                    <el-form-item style="float: right;">
                        <el-button @click="visible = false">Cancel</el-button>
                        <el-button type="primary" @click="updateLog()" >Cập nhật</el-button>
                    </el-form-item>
                </el-col>
            </el-row>
        </el-form>
    </el-dialog>
</template>

<script lang="ts" setup>
    import { ref, onMounted} from 'vue';
    import axios from 'axios';
    import { callMessage } from '../Helper/el-message.js';

    interface FormState {
        date?: string,
        user_code?: number,
        all_log?: boolean,
    };

    const formState = ref<FormState>({});
    const visible = ref(false)
    const listEvent = ref();
    const errorMessages = ref('');
    const users = ref();
    const title = ref()

    const ShowUpdateCheckOut = () => {   
        title.value = 'Cập Nhật Log Check Out'
        visible.value = true;       
    };
    
    const updateLog = () => {
        axios.post('/api/timesheet/update_checkout', formState.value)
        .then(response => {
            callMessage(response.data.success, 'success');
            _close()
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error');
        })
    }
    const emit = defineEmits(['saved'])
    const _close = () => {
        emit('saved');
        visible.value = false;
    }
    onMounted (() => {
        axios.get('/api/common/get_employees_working')
        .then(response => {
            users.value = response.data
        })
        .catch((error) => {
            errorMessages.value = error.response.data.errors;//put message content in ref
            //When search target data does not exist
            listEvent.value = []; //dataSource empty
            callMessage(errorMessages.value, 'error');
        });
    })
    
    defineExpose({
        ShowUpdateCheckOut
    });    
</script>