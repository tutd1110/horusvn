<template>
    <el-row :gutter="10">
        <el-col :span="3">
            <el-radio-group v-model="radioValue">
                <el-radio-button label="Giờ công" />
                <el-radio-button label="Warrior" />
            </el-radio-group>
        </el-col>
        <el-col :span="3">
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
        <el-col :span="3">
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
        <el-col :span="2" v-if="timeOption != 'Option'">
            <el-date-picker
                v-model="datePeriod"
                :type="range"
                :clearable="false"
                size="default"
                style="width: 100%"
            ></el-date-picker>
        </el-col>
        <el-col :span="3" v-if="timeOption === 'Option'">
            <el-date-picker
                v-model="dateRange"
                type="daterange"
                unlink-pannels
                range-separator="To"
                start-placeholder="Start date"
                end-placeholder="End date"
                size="default"
                :clearable="false"
                style="width: 100%"
                format="DD/MM/YYYY"
                value-format="YYYY/MM/DD"
            ></el-date-picker>
        </el-col>
        <el-col :span="3">
            <el-radio-group v-model="timeOption" style="width: 120%;">
                <el-radio-button label="Week"/>
                <el-radio-button label="Month"/>
                <el-radio-button label="Option"/>
            </el-radio-group>
        </el-col>
        <el-col :span="2" v-if="radioValue == 'Warrior'">
            <el-select
                v-model="formState.warrior"
                value-key="id"
                placeholder="Warror"
                clearable
                filterable
                style="width: 100%"
            >
                <el-option
                    v-for="item in filteredWarrior"
                    :key="item.id"
                    :label="item.name"
                    :value="item.name"
                />
            </el-select>
        </el-col>
        <el-col :span="2">
            <el-checkbox v-model="formState.rate_late" label="Vi phạm đi muộn" size="large" />
        </el-col>
        <el-col :span="2">
            <el-space size="small" spacer="|">
                <el-button type="primary" v-on:click="search()">Search</el-button>
                <el-button color="#626aef" v-on:click="doExport()">Export</el-button>
            </el-space>
        </el-col>
    </el-row>
    <el-row style="margin-top: -10px">
        <div class="card-body table-responsive">
            <div class="table-timesheet">
                <el-row :gutter="3" class="table-timesheet-header" style="margin-bottom: 5px;">
                    <el-col :span="6"><el-card class="card-timesheet-header" shadow="hover"><span style="font-weight: 700;">Thời gian thông kê</span></el-card></el-col>
                    <el-col :span="4"><el-card class="card-timesheet-header" shadow="hover"><span style="font-weight: 700;">Công chuẩn</span></el-card></el-col>
                    <el-col :span="7"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(108, 178, 235);"><span style="font-weight: 700;">Thời gian làm việc chính thức dưới 3 năm </span></el-card></el-col>
                    <el-col :span="7"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(38, 193, 224)"><span style="font-weight: 700;">Thời gian làm việc chính thức trên 3 năm </span></el-card></el-col>
                </el-row>
                <el-row :gutter="3" class="table-timesheet-header" style="margin-bottom: 0;">
                    <el-col :span="6"><el-card class="card-timesheet-header" shadow="hover" style="text-align: left;"><span>Công nghỉ tiêu chuẩn theo công ty: <b>{{ work_day.workDayHoliday }}</b> công</span></el-card></el-col>
                    <el-col :span="2"><el-card class="card-timesheet-header" shadow="hover"><span>Chuẩn tháng</span></el-card></el-col>
                    <el-col :span="2"><el-card class="card-timesheet-header" shadow="hover"><span>Thực tế</span></el-card></el-col>
                    <el-col :span="7">
                        <el-row :gutter="3" style="margin-bottom: 5px;">
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(108, 178, 235);"><span>WARRIOR 1</span></el-card></el-col>
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(108, 178, 235);"><span>WARRIOR 2</span></el-card></el-col>
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(108, 178, 235);"><span>WARRIOR 3</span></el-card></el-col>
                        </el-row>
                    </el-col>
                    <el-col :span="7">
                        <el-row :gutter="3" style="margin-bottom: 5px;">
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(38, 193, 224)"><span>WARRIOR 1</span></el-card></el-col>
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(38, 193, 224)"><span>WARRIOR 2</span></el-card></el-col>
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(38, 193, 224)"><span>WARRIOR 3</span></el-card></el-col>
                        </el-row>
                    </el-col>
                </el-row>
                <el-row :gutter="3" class="table-timesheet-header" style="margin-bottom: 0;">
                    <el-col :span="6"><el-card class="card-timesheet-header" shadow="hover" style="text-align: left;"><span> <b>Dự kiến:</b> {{ expect_period_workday }}</span></el-card></el-col>
                    <el-col :span="2"><el-card class="card-timesheet-header" shadow="hover"><span> {{ work_day.expectWorkDays + work_day.workDayHoliday }}</span></el-card></el-col>
                    <el-col :span="2"><el-card class="card-timesheet-header" shadow="hover"><span> {{ work_day.expectWorkDays }}</span></el-card></el-col>
                    <el-col :span="7">
                        <el-row :gutter="3" style="margin-bottom: 5px;">
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(108, 178, 235);"><span>{{ (work_day.expectWorkDays)*2 }}</span></el-card></el-col>
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(108, 178, 235);"><span>{{ (work_day.expectWorkDays)*3 }}</span></el-card></el-col>
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(108, 178, 235);"><span>{{ (work_day.expectWorkDays)*4 }}</span></el-card></el-col>
                        </el-row>
                    </el-col>
                    <el-col :span="7">
                        <el-row :gutter="3" style="margin-bottom: 5px;">
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(38, 193, 224)"><span>{{ (work_day.expectWorkDays)*1 }}</span></el-card></el-col>
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(38, 193, 224)"><span>{{ (work_day.expectWorkDays)*2 }}</span></el-card></el-col>
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover" style="background-color: rgb(38, 193, 224)"><span>{{ (work_day.expectWorkDays)*3 }}</span></el-card></el-col>
                        </el-row>
                    </el-col>
                </el-row>
                <el-row :gutter="3" class="table-timesheet-header" style="margin-bottom: 0;">
                    <el-col :span="6"><el-card class="card-timesheet-header" shadow="hover" style="text-align: left;"><span> <b>Hiện tại:</b> {{ current_period_workday }}</span></el-card></el-col>
                    <el-col :span="2"><el-card class="card-timesheet-header" shadow="hover"><span> {{ current_work_day.expectWorkDays + current_work_day.workDayHoliday }}</span></el-card></el-col>
                    <el-col :span="2"><el-card class="card-timesheet-header" shadow="hover"><span> {{ current_work_day.expectWorkDays }}</span></el-card></el-col>
                    <el-col :span="7">
                        <el-row :gutter="3" style="margin-bottom: 5px;">
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover"><span>{{ (current_work_day.expectWorkDays)*2 }}</span></el-card></el-col>
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover"><span>{{ (current_work_day.expectWorkDays)*3 }}</span></el-card></el-col>
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover"><span>{{ (current_work_day.expectWorkDays)*4 }}</span></el-card></el-col>
                        </el-row>
                    </el-col>
                    <el-col :span="7">
                        <el-row :gutter="3" style="margin-bottom: 5px;">
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover"><span>{{ (current_work_day.expectWorkDays)*1 }}</span></el-card></el-col>
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover"><span>{{ (current_work_day.expectWorkDays)*2 }}</span></el-card></el-col>
                            <el-col :span="8"><el-card class="card-timesheet-header" shadow="hover"><span>{{ (current_work_day.expectWorkDays)*3 }}</span></el-card></el-col>
                        </el-row>
                    </el-col>
                </el-row>
            </div>
            <div style="margin-top: 10px;">
                <timesheet-table :mode="mode" :users="users"></timesheet-table>
            </div>
        </div>
    </el-row>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { onMounted, computed, ref } from 'vue';
import dayjs from 'dayjs';
import { useI18n } from 'vue-i18n';
import { downloadFile } from '../Helper/export.js';
import { callMessage } from '../Helper/el-message.js';
import TimesheetTable from './TimesheetTable.vue';
import { handleUserTimesheet } from '../Helper/handle-user-timesheet.js';

interface FormState {
    department_id?: number,
    user_id?: number,
    start_date?: string,
    end_date?: string,
    warrior?:string
    rate_late?:string
}
interface Department {
    id: number,
    name: string
}
interface User {
    id?: number,
    fullname: string,
    department_id?: number
}
interface Warrior {
    id?: number,
    name?: string,
}
interface WorkDay {
    expectWorkDays: number,
    workDayHoliday: number
}
interface CurrentWorkDay {
    workDayHoliday: number,
    expectWorkDays: number
}
interface Item {
    id: number,
    fullname: string,
    date_official: string,
    total_work_date: string,
    go_early_sum: number,
    late_sum: number,
    late_sum_none_petition: number,
    late_count: number,
    pe_late_count: number,
    early_sum: number,
    early_count: number,
    leave_late_sum: number,
    pe_early_count: number,
    total_late_nd_early: number,
    workday_late_nd_early: number,
    office_goouts: number,
    non_office_goouts: number,
    office_time_goouts: number,
    non_office_time_goouts: number,
    paid_leave: number,
    un_paid_leave: number,
    extra_warrior_time: number,
    workday_extra_warrior_time: number,
    extra_workday: number,
    origin_workday: number,
    paid_workday: number,
    rate_late: number,
    current_title: string,
    time_keep_title: string,
    avg_hold_title: string,
    next_title: string,
    time_next_title: string,
    avg_next_title: string
}

const { t } = useI18n();
const errorMessages = ref('')
const formState = ref<FormState>({})
const radioValue = ref('Giờ công')
const mode = computed(() => (radioValue.value === 'Giờ công' ? true : false));
const currentDate = dayjs()
const startWeek = currentDate.startOf('week').format('YYYY/MM/DD')
const startMonth = currentDate.startOf('month').format('YYYY/MM/DD')
const endMonth = currentDate.endOf('month').format('YYYY/MM/DD')
const timeOption = ref('Month')
const datePeriod = ref<string>(startMonth)
const dateRange = ref<string[]>([startMonth, endMonth])
const range = computed(() => {
    if (timeOption.value === 'Week') {
        datePeriod.value = startWeek

        return 'week';
    } else {
        datePeriod.value = startMonth

        return 'month';
    }
})
// Computed property for computed formState
const computedFormState = computed<FormState>(() => {
    const newFormState: FormState = {
        ...formState.value,
    };

    if (timeOption.value === 'Option') {
        newFormState.start_date = dateRange.value[0];
        newFormState.end_date = dateRange.value[1];
    } else {
        let rangeValue: "month" | "week" = 'month'; // Default to 'month'
        if (timeOption.value === 'Week') {
            rangeValue = 'week';
        }

        newFormState.start_date = dayjs(datePeriod.value).startOf(rangeValue).format('YYYY/MM/DD');
        newFormState.end_date = dayjs(datePeriod.value).endOf(rangeValue).format('YYYY/MM/DD');
    }

    return newFormState;
});
const departments = ref<Array<Department>>([]);
const users = ref<Array<Item>>([])
const employees = ref<Array<User>>([]);
const filteredUsers = computed(() => {
    const selectedDepartmentId = formState.value.department_id;
    if (selectedDepartmentId) {
        return employees.value.filter(employee => employee.department_id === selectedDepartmentId);
    } else {
        return employees.value;
    }
});
const filteredWarrior = ref<Array<Warrior>>([])
//expect period workday
const expect_period_workday = ref(" Từ ngày "+dayjs(dayjs().startOf('month')).format("DD/MM/YYYY") +
            " đến ngày " + dayjs(dayjs().endOf('month')).format("DD/MM/YYYY"));
const current_period_workday = ref(" Từ ngày "+dayjs(dayjs().startOf('month')).format("DD/MM/YYYY") +
                " đến ngày " + dayjs(dayjs()).format("DD/MM/YYYY"));
const work_day = ref<WorkDay>({
    expectWorkDays: 0,
    workDayHoliday: 0
});
const current_work_day = ref<CurrentWorkDay>({
    workDayHoliday: 0,
    expectWorkDays: 0
});
const search = () => {
    expect_period_workday.value = getPeriodTitle(computedFormState.value.start_date, computedFormState.value.end_date)
    
    //get timesheet report all users
    axios.post('/api/timesheet/get_report', computedFormState.value).then(response => {
        work_day.value = response.data.work_day
        current_work_day.value = response.data.current_work_day
        
        const args = [
            response.data, 'YYYY/MM/DD', computedFormState, startMonth, endMonth, "timesheet"
        ]
        
        const getUserList = handleUserTimesheet(...args) as Item[];
        const firstUser = getUserList[0];        
        if (computedFormState.value?.warrior && computedFormState.value?.rate_late) {
            // users.value = [firstUser, ...getUserList.slice(1).filter(user => {
            //     return user.current_title === computedFormState.value.warrior && (user.rate_late > 12.5 || user.late_sum >= 0.5);
            // }).map((user, index) => ({ ...user, key: index + 1 }))];
            users.value = getUserList.slice(1).filter(user => {
                return user.current_title === computedFormState.value.warrior && (user.rate_late > 12.5 || user.late_sum_none_petition >= 0.5);
            });
        } else if (computedFormState.value?.warrior) {
            // users.value = [firstUser, ...getUserList.slice(1).filter(user => user.current_title === computedFormState.value.warrior)
            //     .map((user, index) => ({ ...user, key: index + 1 }))];
            users.value = getUserList.slice(1).filter(user => user.current_title === computedFormState.value.warrior);
        } else if (computedFormState.value?.rate_late) {
            // users.value = [firstUser, ...getUserList.slice(1).filter(user => user.rate_late > 12.5 || user.late_sum >= 0.5)
            //     .map((user, index) => ({ ...user, key: index + 1 }))];
            users.value = getUserList.slice(1).filter(user => user.rate_late > 12.5 || user.late_sum_none_petition >= 0.5);
        } else {
            users.value = getUserList
        }
        
    }).catch((error) => {
        errorMessages.value = error.response.data.errors;//put message content in ref
        //When search target data does not exist
        users.value = []; //dataSource empty
        callMessage(errorMessages.value, 'error');
    });
}
const getPeriodTitle = (startDate: string | undefined, endDate: string | undefined) => {
    let title = " Từ ngày "+startDate +
        " đến ngày " + endDate
    
    return title
}
const doExport = () => {
    let submitData = {
        employees: users.value,
        work_day: work_day.value,
        current_work_day: current_work_day.value,
        expect_period_workday: expect_period_workday.value,
        current_period_workday: current_period_workday.value
    }
    downloadFile('/api/timesheet/report/export', submitData, errorMessages, t)
    .then(() => {

    })
    .catch(() => {

    });
}
onMounted(() => {
    search()
    axios.get('/api/common/departments').then(response => {
        departments.value = response.data
    })
    axios.get('/api/common/get_employees_working').then(response => {
        employees.value = response.data
    })
    axios.get('/api/common/get_warrior_name').then(response => {
        filteredWarrior.value = response.data
    })
});
</script>