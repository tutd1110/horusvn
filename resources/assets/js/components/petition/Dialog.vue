<template>
    <el-dialog v-model="dialogVisible" :title="title" :width="widthDialog" draggable :before-close="handleClose"> 
        <template #header="{}">
            <span role="heading" class="el-dialog__title">{{ title }}</span>
            <Edit v-if="mode === 'SHOW' " style="width: 20px; vertical-align: top; margin-left: 20px;" @click="ShowWithUpdateMode(selectedRecord, exclusionControl)"/>
        </template>
        <el-form :model="formState" label-width="140px" :label-position="labelPosition">
           
            <el-form-item label="Loại yêu cầu">
                <el-select
                    v-model="formState.type"
                    clearable
                    filterable
                    style="width: 100%"
                    :disabled="mode == 'SHOW' && disabledSelect"
                >
                    <el-option
                        v-for="item in filteredTypeSelectbox"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="Loại nghỉ phép" v-if="formState.type === 2">
                <el-select
                    v-model="formState.type_off"
                    clearable
                    style="width: 100%"
                    :disabled="mode == 'SHOW' && disabledSelect"
                >
                    <el-option
                        v-for="item in timePeriodSelectbox"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="Hình thức nghỉ phép" v-if="formState.type === 2">
                <el-select
                    v-model="formState.type_paid"
                    clearable
                    style="width: 100%"
                    :disabled="mode == 'SHOW' && disabledSelect"
                >
                    <el-option
                        v-for="item in typePaidSelectbox"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="Hình thức ra ngoài" v-if="formState.type === 7">
                <el-select
                    v-model="formState.type_go_out"
                    clearable
                    filterable
                    style="width: 100%"
                    :disabled="mode == 'SHOW' && disabledSelect"
                >
                    <el-option
                        v-for="item in typeGoOutSelectBox"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="Nhân viên">
                <el-select
                    v-model="formState.user_id"
                    clearable
                    filterable
                    style="width: 100%"
                    :disabled="mode == 'SHOW' && disabledSelect"
                >
                    <el-option
                        v-for="item in userSelectbox"
                        :key="item.id"
                        :label="item.fullname"
                        :value="item.id"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="Đi muộn/Về sớm" v-if="formState.type === 1">
                <el-radio-group v-model="radioValue" :disabled="mode == 'SHOW' && disabledSelect">
                    <el-radio-button label="Đi muộn" />
                    <el-radio-button label="Về sớm" />
                </el-radio-group>
                <div class="flex-grow" />
                <el-switch
                    v-if="session && session.id == 161"
                    v-model="activeTime"
                    style="float: right;"
                    :active-icon="Timer"
                />
            </el-form-item>
            <el-form-item label="Thời gian" >
                <el-row style="align-items: flex-start; margin: 0; width: 100%;">
                    <el-col :span="8">
                        <el-date-picker
                            v-model="formState.start_date"
                            type="date"
                            format="DD/MM/YYYY"
                            value-format="YYYY/MM/DD"
                            placeholder="Pick a day"
                            style="width: 100%;"
                            clearable
                            :disabled="mode == 'SHOW' && disabledSelect"
                        />
                    </el-col>
                    <el-col :span="1"></el-col>
                    <template v-if="formState.type === 2 && formState.type_off === 4">
                        <el-col :span="8">
                            <el-date-picker
                                v-model="formState.end_date"
                                type="date"
                                format="DD/MM/YYYY"
                                value-format="YYYY/MM/DD"
                                placeholder="Pick a day"
                                style="width: 100%;"
                                clearable
                                :disabled="mode == 'SHOW' && disabledSelect"
                            />
                        </el-col>
                    </template>
                    <template v-else>
                        <el-col :span="7">
                            <el-time-picker
                                v-model="formState.start_time"
                                v-if="formState.type != 3"
                                placeholder="Start time"
                                style="width: 100%;"
                                :disabled="(isStartDateTimeDisabled && activeTime == false) || (mode == 'SHOW' && disabledSelect )"
                            />
                            <div style="font-size:13px; color: red; line-height: normal;text-align: center;" v-if="formState.type === 1 && (isEndDateTimeDisabled || activeTime == true)">
                                Thời gian bắt đầu rời khỏi công ty
                            </div>
                        </el-col>
                        <el-col :span="1"></el-col>
                        <el-col :span="7">
                            <el-time-picker
                                v-model="formState.end_time"
                                v-if="formState.type != 3"
                                placeholder="End time"
                                style="width: 100%;"
                                :disabled="(isEndDateTimeDisabled && activeTime == false) || (mode == 'SHOW' && disabledSelect )"
                            />
                            <div style="font-size:13px; color: red; line-height: normal;text-align: center;" v-if="formState.type === 1 && (isStartDateTimeDisabled || activeTime == true)">
                                Thời gian có mặt tại công ty
                            </div>
                        </el-col>
                        
                    </template>
                </el-row>
            </el-form-item>
            <el-form-item label="Lý do">
                <el-input v-model="formState.reason" type="textarea" :disabled="mode == 'SHOW' && disabledSelect"/>
            </el-form-item>
            <el-form-item label="Trạng thái" v-if="mode == 'SHOW' && isAuthority">
                <el-select
                    v-model="formState.status"
                    placeholder="Action"
                    clearable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in filteredActions"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="Lí do từ chối" v-if="mode == 'SHOW' && isAuthority && formState.status == 3">
                <el-input v-model="formState.rejected_reason" type="textarea" />
            </el-form-item>
        </el-form>
        <template #footer>
            <span class="dialog-footer">
                <el-button @click="dialogVisible = false">Cancel</el-button>
                <template v-if="mode === 'ADD'">
                    <el-button type="primary" @click="onRequest">
                        Request
                    </el-button>
                </template>
                <template v-else-if="mode === 'SHOW' && isAuthority">
                    <el-button type="primary" @click="performApiAction">
                        Update
                    </el-button>
                </template>
                <template v-else-if="mode === 'UPDATE'">
                    <el-button type="primary" @click="onUpdate">
                        Update
                    </el-button>
                </template>
            </span>
        </template>
    </el-dialog>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { onMounted, computed, defineEmits, ref } from 'vue';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import { TIME_ZONE } from '../const.js'
import { ElMessageBox } from 'element-plus'
import { callMessage } from '../Helper/el-message.js';
import { Edit, Timer } from '@element-plus/icons-vue'

dayjs.extend(utc);
dayjs.extend(timezone);

interface FormState {
    type?: number,
    status?: number,
    type_off?: number,
    type_paid?: number,
    type_go_out?: number,
    user_id?: number,
    reason?: string,
    start_date?: string,
    end_date?: string,
    start_time?: string,
    end_time?: string,
    rejected_reason?: string
}
interface submitData {
    id: number,
    check_updated_at: string,//exclusion control
    type?: number,
    type_off?: number,
    type_paid?: number,
    type_go_out?: number,
    user_id?: number,
    start_time?: string,
    end_time?: string,
    start_date?: string,
    end_date?: string,
    reason?: string,
    start_time_change?: string,
    end_time_change?: string
}
interface User {
    id: number,
    fullname: string
}
interface Type {
    id: number,
    name: string
}
interface Session {
    id: number,
    is_authority: boolean,
    is_leader: boolean
}

const actions = ref([
    // { label: "Sửa", value: 0 },
    { label: "Duyệt", value: 1 },
    { label: "Vi phạm",value: 2 },
    { label: "Từ chối",value: 3 },
    { label: "Xoá",value: 4 }
]);
const isAuthority = ref<any>(false);
const session = ref<Session>()
const dialogVisible = ref(false)
const activeTime = ref(false)
const mode = ref('')
const title = ref('')
const formState = ref<FormState>({})
const typeSelectbox = ref<Array<Type>>([])
const userSelectbox = ref<Array<User>>([])
const typeGoOutSelectBox = ref<Array<Type>>([])
const timePeriodSelectbox = ref<Array<Type>>([])
const typePaidSelectbox = ref<Array<Type>>([])
const filteredTypeSelectbox = computed(() => {
    if (mode.value === 'ADD') {
        // Filter out items with id 4 and 8
        return typeSelectbox.value.filter((item) => ![4, 8].includes(item.id));
    }

    return typeSelectbox.value;
})
// Define a type for the possible values of radioValue
type RadioValue = 'Đi muộn' | 'Về sớm' | '';
const radioValue = ref<RadioValue>('Đi muộn')
// Define a computed property based on the ref
const computedRadioValue = computed(() => formState.value.type === 1 ? radioValue.value : '')
const computedTypeOff = computed(() => formState.value.type === 2 ? formState.value.type_off : '')
const isStartDateTimeDisabled = computed(() => {
    if (computedRadioValue.value === 'Đi muộn' || computedTypeOff.value === 1 || computedTypeOff.value === 3) {
        formState.value.start_time = dayjs().format('YYYY/MM/DD 08:00:00')

        return true
    }
    else if (computedTypeOff.value === 2) {
        formState.value.start_time = dayjs().format('YYYY/MM/DD 13:30:00')

        return true
    }
    // formState.value.start_time = ''
    return false
})
const isEndDateTimeDisabled = computed(() => {
    if (computedRadioValue.value === 'Về sớm' || computedTypeOff.value === 3) {
        const endDateTime = dayjs(formState.value.start_date).day() === 6
            ? dayjs().format('YYYY/MM/DD 12:00:00')
            : dayjs().format('YYYY/MM/DD 17:30:00');

        formState.value.end_time = endDateTime;

        return true
    }
    else if (computedTypeOff.value === 1) {
        formState.value.end_time = dayjs().format('YYYY/MM/DD 12:00:00');

        return true
    }
    else if (computedTypeOff.value === 2) {
        formState.value.end_time = dayjs().format('YYYY/MM/DD 17:30:00')

        return true
    }
    // formState.value.end_time = ''
    return false
})
// Computed property for computed formState
const computedFormState = computed<FormState>(() => {
    const newFormState: FormState = {
        ...formState.value,
    };

    if (formState.value.start_time) {
        newFormState.start_time = dayjs(formState.value.start_time).format('HH:mm:ss');
    }
    if (formState.value.end_time) {
        newFormState.end_time = dayjs(formState.value.end_time).format('HH:mm:ss');
    }

    return newFormState;
})
const errorMessages = ref('')
const formClear = () => {
    // Create a new object with the same structure as the FormState interface
    const clearedFormState: FormState = {};

    // Assign the new object to the formState ref
    formState.value = clearedFormState;

    return clearedFormState;
}
const onRequest = () => {
    // dialogVisible.value = false
    axios.post('/api/petition/check_petition_infringe', computedFormState.value)
    .then(response => {
        let message = response.data;
        if (message) {
            ElMessageBox.confirm(
                response.data,
                'Warning',
                {
                    confirmButtonText: 'OK',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            ).then(() => {
                store()
            }).catch(() => {
            })
        } else {
            store()
        }
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error')
    })
}
const store = () => {
    axios.post('/api/petition/store', computedFormState.value)
    .then(response => {
        formClear()
        _close();
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error')
    })
}
const emit = defineEmits(['saved'])
const _close = () => {
    dialogVisible.value = false;
    emit('saved');
}
const onUpdate = () => {
    let submitData: submitData = {
        id: selectedRecord.value,
        check_updated_at: exclusionControl.value,//exclusion control
        type: computedFormState.value.type,
        type_off: computedFormState.value.type_off,
        type_paid: computedFormState.value.type_paid,
        type_go_out: computedFormState.value.type_go_out,
        user_id: computedFormState.value.user_id,
        start_time: computedFormState.value.start_time,
        end_time: computedFormState.value.end_time,
        start_date: computedFormState.value.start_date,
        end_date: computedFormState.value.end_date ? computedFormState.value.end_date : undefined,
        reason: computedFormState.value.reason
    }
    if (computedFormState.value.type && [4, 8].includes(computedFormState.value.type)) {
        delete submitData.start_time
        delete submitData.end_time

        submitData.start_time_change = computedFormState.value.start_time
        submitData.end_time_change = computedFormState.value.end_time
    }

    axios.patch('/api/petition/update', submitData).then(response => {
        formClear()
        _close()
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error')
    })
}
const ShowWithAddMode = () => {
    title.value = 'Tạo yêu cầu'
    mode.value = 'ADD'
    dialogVisible.value = true;
    formClear()
}
const ShowWithAddModeTimeSheet = (user_id: number, date: string) => {
    title.value = 'Tạo yêu cầu'
    mode.value = 'ADD'
    dialogVisible.value = true;
    formClear()
    formState.value.start_date = dayjs(date).format('YYYY/MM/DD')
    formState.value.user_id = user_id
}
const selectedRecord = ref()
const exclusionControl = ref()
const ShowWithUpdateMode = (id: number, updated_at: string) => {    
    formClear()
    mode.value = "UPDATE";
    title.value = "Chỉnh sửa yêu cầu"
    dialogVisible.value = true;
    selectedRecord.value = id;
    exclusionControl.value = dayjs(updated_at).tz(TIME_ZONE.ZONE).format("YYYY/MM/DD HH:mm:ss");

    axios.get('/api/petition/get_petition_by_id', {
        params: {
            id: selectedRecord.value
        }
    }).then(response => {
        formState.value = response.data
        if (response.data.start_time) {
            formState.value.start_time = dayjs().format('YYYY/MM/DD ' + response.data.start_time)
        }
        if (formState.value.end_time) {
            formState.value.end_time = dayjs().format('YYYY/MM/DD ' + response.data.end_time)
        }
    }).catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error')
    })
}
const disabledSelect = ref(false)
const ShowDetail = (id: number, updated_at: string) => {
    formClear()
    mode.value = "SHOW";
    title.value = "Chi tiết yêu cầu"
    dialogVisible.value = true;
    selectedRecord.value = id;
    exclusionControl.value = dayjs(updated_at).tz(TIME_ZONE.ZONE).format("YYYY/MM/DD HH:mm:ss");
    disabledSelect.value = true

    axios.get('/api/petition/get_petition_by_id', {
        params: {
            id: selectedRecord.value
        }
    }).then(response => {
        formState.value = response.data
        if (response.data.start_time) {
            formState.value.start_time = dayjs().format('YYYY/MM/DD ' + response.data.start_time)
        }
        if (formState.value.end_time) {
            formState.value.end_time = dayjs().format('YYYY/MM/DD ' + response.data.end_time)
        }
    }).catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error')
    })
}
const filteredActions = computed(() => {
    const allowedValues = session.value?.is_authority ? [0, 1, 2, 3, 4] : [0];
    return actions.value.filter(action => allowedValues.includes(action.value));
});
const performApiAction = () => {
    if ( formState.value.status == 4) {
        axios.delete('/api/petition/delete', {
            params: {
                id: selectedRecord.value,
                key: formState.value.status,
                check_updated_at: exclusionControl.value
            }
        })
        .then(response => {
            formClear()
            _close()
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error');
        })
    } else {
        axios.patch('/api/petition/action', {
            id: selectedRecord.value,
            key: formState.value.status,
            rejected_reason: formState.value.status == 3 ? formState.value.rejected_reason : '',
            check_updated_at: exclusionControl.value
        })
        .then(response => {
            formClear()
            _close()
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error');
        })
    }
}

// const performApiAction = () => {
//     const params = {
//         id: selectedRecord.value,
//         key: formState.value.status,
//         check_updated_at: exclusionControl.value,
//         rejected_reason: formState.value.status == 3 ? formState.value.rejected_reason : '',
//     }
//    if (formState.value.status == 4) {
//         axios.delete('/api/petition/delete', {
//             params: params
//         })
//         .then(response => {
//             formClear()
//             _close()
//         })
//         .catch(error => {
//             errorMessages.value = error.response.data.errors;
//             callMessage(errorMessages.value, 'error');
//         })
//     }
//     else {
//         axios.patch('/api/petition/action', { params })
//         .then(response => {
//             formClear()
//             _close()
//         })
//         .catch(error => {
//             errorMessages.value = error.response.data.errors;
//             callMessage(errorMessages.value, 'error');
//         })
//     }
// }
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
const widthDialog = ref()
const labelPosition = ref()
onMounted(() => {
    var screenWidth = window.screen.width;
    if (screenWidth >= 1080) {
        widthDialog.value = '30%'
        labelPosition.value = 'left'
    } else {
        widthDialog.value = '90%'
        labelPosition.value = 'top'
    }
    axios.get('/api/petition/get_selectboxes').then(response => {
        typeSelectbox.value = response.data.petition_type;
        userSelectbox.value = response.data.users;
        timePeriodSelectbox.value = response.data.time_period;
        typeGoOutSelectBox.value = response.data.type_go_out;
        typePaidSelectbox.value = response.data.type_paid;
        session.value = response.data.user_login
        isAuthority.value = session.value?.is_authority;
    })
});

defineExpose({
    ShowWithAddMode,
    ShowWithUpdateMode,
    ShowDetail,
    ShowWithAddModeTimeSheet
});
</script>