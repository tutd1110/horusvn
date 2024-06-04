<template>
    <el-dialog 
        v-model="visible" 
        style="font-weight: bold" 
        :style="{ width: hideMobile ? '50%' : '80%' }"
        draggable
        :before-close="handleClose"
        :title="title" class="modal-calendar">
        <el-form
            ref="ruleFormRef"
            label-position="top"
            :model="formState"
            :rules="rules"
        >
            <el-row :gutter="20" style="margin-bottom: 0;">
                <el-col :lg="11" :md="22" :xs="22" :offset="1">
                    <el-form-item label="Ngày" prop="date">
                        <el-date-picker
                            v-model="formState.date"
                            clearable
                            type="date"
                            placeholder="Chọn ngày"
                            style="width: 100%;"
                            format="DD/MM/YYYY"
                            value-format="YYYY-MM-DD"
                            :disabled="mode == 'ADD-DETAIL'"
                        />
                    </el-form-item>
                </el-col>
                <el-col :lg="11" :md="22" :xs="22" :offset="hideMobile ? 0 : 1">
                    <el-form-item label="Thời gian">
                        <el-form-item prop="start_time" style="width:50%">
                            <el-time-select
                                v-model="formState.start_time"
                                :max-time="formState.end_time"
                                class="mr-4"
                                placeholder="Start time"
                                start="07:30"
                                step="00:05"
                                end="20:00"
                            />
                        </el-form-item>
                        <el-form-item prop="end_time" style="width:50%">
                            <el-time-select
                                v-model="formState.end_time"
                                :min-time="formState.start_time"
                                placeholder="End time"
                                start="07:30"
                                step="00:05"
                                end="20:00"
                            />
                        </el-form-item>
                    </el-form-item>
                </el-col>
                <el-col :lg="11" :md="22" :xs="22" :offset="1">
                    <el-form-item label="Chọn sự kiện" prop="event_id">
                        <el-select 
                            v-model="formState.event_id" 
                            clearable
                            placeholder="Select"
                            style="width: 100%;"
                        >
                            <el-option
                                v-for="item in listEvent"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id"
                            />
                        </el-select>
                    </el-form-item>
                </el-col>
                <el-col :lg="11" :md="22" :xs="22" :offset="hideMobile ? 0 : 1"> 
                    <el-form-item label="Tên chương trình" prop="name">
                        <el-input v-model="formState.name" placeholder="Nhập tên chương trình" clearable />
                    </el-form-item>
                </el-col>
                <el-col :lg="11" :md="22" :xs="22" :offset="1">
                    <el-form-item label="Hiển thị" prop="status">
                        <el-select 
                            v-model="formState.status" 
                            clearable 
                            placeholder="Select"
                            style="width: 100%;"
                        >
                            <el-option
                                v-for="item in status"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                </el-col>
                <el-col :lg="11" :md="22" :xs="22" :offset="hideMobile ? 0 : 1">
                    <el-form-item label="Dành cho bộ phận" prop="department_id">
                        <el-select 
                            v-model="formState.department_id" 
                            clearable 
                            placeholder="Select"
                            style="width: 100%;"
                        >
                            <el-option
                                v-for="item in departments"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id"
                            />
                        </el-select>
                    </el-form-item>
                </el-col>
                <el-col :span="22" :offset="1">
                    <el-form-item label="Người phụ trách" prop="user_id">
                        <el-select 
                            v-model="formState.user_id"
                            clearable
                            filterable
                            multiple
                            :reserve-keyword="false"
                            placeholder="Select"
                            style="width: 100%;"
                        >
                            <el-option
                                v-for="item in users"
                                :key="item.id"
                                :label="item.fullname"
                                :value="item.id"
                            />
                        </el-select>
                    </el-form-item>
                </el-col>
                <el-col :span="22" :offset="1">
                    <el-form-item label="Người tham dự" prop="user_id">
                        <el-select 
                            v-model="formState.user_join"
                            clearable
                            filterable
                            multiple
                            :reserve-keyword="false"
                            placeholder="Select"
                            style="width: 100%;"
                        >
                            <el-option
                                v-for="item in users"
                                :key="item.id"
                                :label="item.fullname"
                                :value="item.id"
                            />
                        </el-select>
                    </el-form-item>
                </el-col>
                <el-col :span="22" :offset="1">
                    <el-form-item label="Nội dung" prop="description">
                        <el-input 
                            v-model="formState.description" 
                            placeholder="Nhập nội dung"
                            type="textarea"
                            autosize
                            clearable
                        />
                    </el-form-item>
                </el-col>
                <el-col :span="22" :offset="1">
                    <el-form-item style="float: right;">
                        <el-button @click="visible = false">Cancel</el-button>
                        <el-button type="primary" v-if="mode == 'ADD' || mode == 'ADD-DETAIL'" @click="submitForm(ruleFormRef)" >Thêm mới</el-button>
                        <el-button type="primary" v-if="mode == 'UPDATE'" @click="updateForm(ruleFormRef)" >Cập nhật</el-button>
                    </el-form-item>
                </el-col>
            </el-row>
        </el-form>
    </el-dialog>
</template>

<script lang="ts" setup>
    import {reactive, ref, onMounted} from 'vue';
    import axios from 'axios';
    import { callMessage } from '../Helper/el-message.js';
    import type { FormInstance, FormRules } from 'element-plus'
    import { ElMessageBox } from 'element-plus'

    interface FormState {
        name?: string,
        date?: string,
        time?: string[],
        department_id?: number,
        user_id?: number[],
        description?: string,
        sub_event?: number,
        event_id?: number,
        start_time?: string,
        end_time?: string,
        status?: number,
        user_join?: number,
    };

    const formState = ref<FormState>({});
    const visible = ref(false)
    const listEvent = ref();
    const errorMessages = ref('');
    const departments = ref();
    const users = ref();
    const title = ref()
    const status = ref()
    const mode = ref()

    const AddCalendarModalMode = () => {   
        title.value = 'Tạo sự kiện'
        mode.value = 'ADD'
        visible.value = true;
        formState.value = {};

    };
    const AddCalendarModalDetailMode = (date:string) => {   
        title.value = 'Tạo sự kiện'
        mode.value = 'ADD-DETAIL'
        formState.value = {};
        formState.value.date = date
        visible.value = true;
    };
    const UpdateCalendarModalMode = (id:number) => {   
        title.value = 'Sửa sự kiện'
        mode.value = 'UPDATE'
        visible.value = true;
        axios.post('/api/calendar_event/get_calendar_by_id', {id:id})
            .then(response => {
                formState.value = response.data;
                formState.value.user_id = response.data.user_id != '{NULL}' ? response.data.user_id.replace(/[{}"]/g, '').split(',').map(Number) : [];
                formState.value.user_join = response.data.user_join != '{NULL}' ? response.data.user_join.replace(/[{}"]/g, '').split(',').map(Number) : [];
            })
            .catch((error) => {
                // formState.value = [];
                errorMessages.value = error.response.data.errors;
                callMessage(errorMessages.value, 'error');
            });
        
    };
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
    
    const ruleFormRef = ref<FormInstance>()
    const rules = reactive<FormRules>({
        name: [
            { 
                required: true, 
                message: 'Bạn chưa điền tên chương trình', 
                trigger: 'blur' 
            },
        ],
        date: [
            {
                type: 'date',
                required: true,
                message: 'Bạn chưa chọn ngày',
                trigger: 'change',
            },
        ],
        start_time: [
            {
                required: true,
                message: 'Bạn chưa chọn thời gian bắt đầu.',
                trigger: 'change',
            },
        ],
        end_time: [
            {
                required: true,
                message: 'Bạn chưa chọn thời gian kết thúc.',
                trigger: 'change',
            },
        ],
        event_id: [
            {
            required: true,
            message: 'Bạn chưa chọn sự kiện.',
            trigger: 'blur',
            },
        ],
        status: [
            {
            required: true,
            message: 'Bạn chưa chọn đối tượng hiển thị.',
            trigger: 'blur',
            },
        ],
    })

    const submitForm = async (formEl: FormInstance | undefined) => {
        if (!formEl) return
        await formEl.validate((valid, fields) => {
            if (valid) {
                axios.post('/api/calendar_event/store_calendar', formState.value)
                .then(response => {
                    callMessage(response.data.success, 'success');
                    _close()
                })
                .catch(error => {
                    errorMessages.value = error.response.data.errors;
                    callMessage(errorMessages.value, 'error');
                })
                
            } else {
                console.log('error submit!', fields)
            }
        })
    }
    const updateForm = async (formEl: FormInstance | undefined) => {
        if (!formEl) return
        await formEl.validate((valid, fields) => {
            if (valid) {
                axios.post('/api/calendar_event/update_calendar', formState.value)
                .then(response => {
                    callMessage(response.data.success, 'success');
                    _close()
                })
                .catch(error => {
                    errorMessages.value = error.response.data.errors;
                    callMessage(errorMessages.value, 'error');
                })
                
            } else {
                console.log('error submit!', fields)
            }
        })
    }
    const emit = defineEmits(['saved'])
    const _close = () => {
        visible.value = false;
        emit('saved');
    }
    const hideMobile = ref(true)
    onMounted (() => {
        var screenWidth = window.screen.width;
        if (screenWidth >= 1080) {
            hideMobile.value = true
        } else {
            hideMobile.value = false
        } 
        
        axios.get('/api/calendar_event/get_event_list')
        .then(response => {
            listEvent.value = response.data;
        })
        axios.get('/api/calendar_event/get_selectboxes')
        .then(response => {
            status.value = response.data.status;
        })

        axios.get('/api/common/departments')
        .then(response => {
            departments.value = response.data
        })

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
        AddCalendarModalMode,
        AddCalendarModalDetailMode,
        UpdateCalendarModalMode,
    });    
</script>