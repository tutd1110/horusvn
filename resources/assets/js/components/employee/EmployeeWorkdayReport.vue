<template>
    <div id="filter-block">
        <el-row :gutter="10" style="margin-bottom: 0px">
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
            <el-col :span="2">
                <el-select
                    v-model="formState.department_id"
                    value-key="id"
                    placeholder="Department"
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in departments"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                        :disabled="disabledOption(item)"
                    />
                </el-select>
            </el-col>
            <!-- <el-col :span="spanCol != undefined ? spanCol : 1">
                <el-button type="primary" v-on:click="search()">Search</el-button>
            </el-col> -->
            <el-col :span="4" class="report-date-week-select">
                <a class="icon-arrow arrow-left" @click="subtractWeekTimesheet"><el-icon><ArrowLeft /></el-icon></a>
                <!-- <el-date-picker
                    v-model="weekTimesheetPicker"
                    :type="range"
                    format="[Week] ww"
                    placeholder="Week"
                    class="time-week"
                    value-format="YYYY-MM-DD"
                    @change="search"
                    :clearable="false"
                /> -->
                <el-date-picker
                    v-model="weekTimesheetPicker"
                    :type="range"
                    class="time-week"
                    value-format="YYYY-MM-DD"
                    @change="search"
                    :clearable="false"
                    style="width:100%"
                />
                <a class="icon-arrow arrow-right" @click="addWeekTimesheet"><el-icon><ArrowRight /></el-icon></a>
            </el-col>
            <el-col :span="5">
                <el-space size="small" spacer="|">
                    <el-button type="primary" v-on:click="search()">Search</el-button>
                    <el-radio-group v-model="mode" @change="search()">
                        <el-radio-button label="Week" />
                        <el-radio-button label="Month" />
                    </el-radio-group>
                </el-space>
            </el-col>
        </el-row>
    </div>
    <!-- Timesheets form from here -->
    <div class="workday-report-scrollbar">
        <el-row :gutter="4">
            <el-col :span="2">
                <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '53px'}">
                    <div class="card-content">
                        Cá nhân
                    </div>
                </el-card>
            </el-col>
            <template v-for="(column) in columns">
                <el-col :span="getColSpan" :style="getCardStyle">
                    <el-card class="timesheet-box" shadow="always" :body-style="{ padding: '0px'}">
                        <div class="timesheets-title" v-html="formatTitle(column.title)"></div>
                    </el-card>
                </el-col>
            </template>
        </el-row>
        <el-scrollbar :height="heightScroll" >
            <template v-for="(item, index1) in tableData">
                <el-row :gutter="4" style="margin-bottom: 12px;" :class="range=='month' ? 'month-report' : ''">
                    <el-col :span="2">
                        <el-card shadow="hover" :body-style="elCardStyleBody">
                            <div class="card-content">
                                {{ item.fullname }}
                            </div>
                        </el-card>
                    </el-col>
                    <template v-for="(column, index2) in columns">
                        <el-col :span="getColSpan" :style="getCardStyle">
                            <template v-if="item.worktime && item.worktime[column.dataKey]">
                                <el-card shadow="hover" :body-style="elCardStyleBody">
                                    <div class="card-content">
                                        <div v-if="item.worktime[column.dataKey].timesheet_detail.is_holiday == true && range == 'week'">
                                            <span class="">{{ item.worktime[column.dataKey].timesheet_detail.holiday_title }}</span>
                                        </div>
                                        <div v-else-if="item.worktime[column.dataKey].timesheet_detail.is_holiday == true && range == 'month'">
                                            <span style="font-size: 12px;">Nghỉ toàn công ty</span>
                                        </div>
                                        <div v-else-if="item.worktime[column.dataKey].timesheet_detail.long_leave">
                                            <span style="font-size: 12px;" v-html="getLeaveLabel(item.worktime[column.dataKey].timesheet_detail.long_leave)"></span>
                                        </div>
                                        <div v-else :class="getTasksTimeStyles(item.worktime[column.dataKey])" >
                                            {{ item.worktime[column.dataKey].tasks }}<span class="tasks-time-text">/{{ item.worktime[column.dataKey].timesheets }}</span>
                                        </div>
                                    </div>
                                </el-card>
                            </template>
                            <template v-else-if="checkDateIsSunday(column.title) && (typeof item.worktime[column.dataKey] === 'undefined')">
                                <el-card shadow="hover" :body-style="elCardStyleBody">
                                    <div class="card-content">
                                        <span style="font-weight: bold" v-html="getHolidaySundayLabel(columns.length > 7)"></span>
                                    </div>
                                </el-card>
                            </template>
                            <template v-else>
                                <el-card
                                    shadow="hover"
                                    :body-style="elCardStyleBody"
                                >
                                    <div class="card-content">
                                        --
                                    </div>
                                </el-card>
                            </template>
                        </el-col>
                    </template>
                </el-row>
            </template>
        </el-scrollbar>
    </div>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { onMounted, computed, ref } from 'vue';
import { ArrowLeft, ArrowRight } from '@element-plus/icons-vue'
import { callMessage } from '../Helper/el-message.js';
import dayjs from 'dayjs';
import { openLoading, closeLoading } from '../Helper/el-loading';

import { resizeScreen } from '../Helper/resize-screen.js';

interface FormState {
    user_id?: number,
    department_id?: number,
    start_date: string,
    end_date: string
};
interface User {
    id?: number,
    fullname: string,
    department_id?: number
};
interface Session {
    id?: number,
    department_id?: number,
    position?: number
}
interface Department {
    value: number,
    label: string
};
interface Work {
    tasks: number,
    timesheets: number,
    timesheet_detail: any,
}
interface Employee {
    id: number,
    fullname: string,
    worktime: { [date: string]: Work },
}
interface ElCardStyle {
    padding: string,
    height: string
}

const getLeaveLabel = (item: any) => {
    let leaveType = (item && item) === 1 ? 'Một' : 'Nhiều';
    return `Nghỉ ${leaveType.toLowerCase()} ngày`;
};
const mode = ref('Week');
const range = computed(() => (mode.value === 'Week' ? 'week' : 'month'));

const currentDate = dayjs();
const startDate = currentDate.startOf('week').format('YYYY/MM/DD');
const endDate = currentDate.endOf('week').format('YYYY/MM/DD');

const datePeriod = ref(startDate);

const departments = ref<Array<Department>>([]);
const formState = ref<FormState>({
    start_date: startDate,
    end_date: endDate,
});
// Computed property for computed formState
const computedFormState = computed<FormState>(() => {
    const newFormState: FormState = {
        ...formState.value,
    };

    if (weekTimesheetPicker.value) {
        newFormState.start_date = dayjs(weekTimesheetPicker.value).startOf(range.value).format('YYYY/MM/DD');
        newFormState.end_date = dayjs(weekTimesheetPicker.value).endOf(range.value).format('YYYY/MM/DD');
    }

    return newFormState;
});
const users = ref<Array<User>>([]);
const filteredUsers = computed(() => {
    const selectedDepartmentId = formState.value.department_id;
    if (selectedDepartmentId) {
        return users.value.filter(user => user.department_id === selectedDepartmentId);
    } else {
        return users.value;
    }
});
const weekTimesheetPicker = ref(currentDate.startOf('week').format('YYYY/MM/DD'));
const generateColumns = (startDate: Date, endDate: Date, props?: any) => {
    const diffInDays = Math.floor((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24)) + 1;

    const dateFormats: Record<string, Intl.DateTimeFormatOptions> = {
        week: {
            weekday: "long",
            day: "numeric",
            month: "numeric",
        },
        month: {
            weekday: "short",
            day: "numeric", // Change the order to day/month
            month: "numeric",
        },
    };

    const columns = Array.from({ length: diffInDays }, (_, index) => {
        const currentDate = new Date(startDate);
        currentDate.setDate(startDate.getDate() + index);
        const year = currentDate.getFullYear().toString();
        const month = (currentDate.getMonth() + 1).toString().padStart(2, '0');
        const day = currentDate.getDate().toString().padStart(2, '0');
        const formattedKey = `${year}${month}${day}`;
        // const formattedDate = currentDate.toLocaleDateString('en-TT', dateFormats['week']);
        const formattedDate = currentDate.toLocaleDateString('en-TT', dateFormats[range.value as keyof typeof dateFormats]);

        return {
            ...props,
            dataKey: formattedKey,
            title: formattedDate,
        };
    });

    return columns;
}
const columns = ref(generateColumns(new Date(startDate), new Date(endDate)));
const getColSpan = computed(() => {
    return columns.value.length > 7 ? 1 : 4;
});
const getCardStyle = computed(() => {
    const width = (100-8.3333333333) / (columns.value.length);
    return { maxWidth: `${width}%` };
});
const formatTitle = (title: string) => {
    const [firstLine, secondLine] = title.split(',');
    const isSunday = ['Sunday', 'Sun'].includes(firstLine); // Check if firstLine is 'Sunday' or 'Sun'

    let dateLabel = `<span class="timesheets-date-label ${isSunday ? 'timesheets-red-text' : ''}">${firstLine}</span>`;
    let dateMD = `<span class="timesheets-date-md ${isSunday ? 'timesheets-red-text' : ''}">${secondLine}</span>`;

    return `${dateLabel} ${dateMD}`;
};
const tableData = ref<Array<Employee>>([]);
const errorMessages = ref('');
const elCardStyleBody = ref<ElCardStyle>({padding: '0px', height: '50px'});
const session = ref<Session>({})
const search = () => {
    openLoading('workday-report-scrollbar'); // Open the loading indicator before loading data
    axios.post('/api/report/get_workday_reports', computedFormState.value)
    .then(response => {
        columns.value = generateColumns(new Date(computedFormState.value.start_date), new Date(computedFormState.value.end_date));
        tableData.value = response.data;
        closeLoading(); // Close the loading indicator

        setTimeout(() => { 
            heightScroll.value = resizeScreen()
            heightScroll.value = heightScroll.value - 60
        }, 0);


    })
    .catch((error) => {
        closeLoading(); // Close the loading indicator
        errorMessages.value = error.response.data.errors;//put message content in ref
        //When search target data does not exist
        tableData.value = []; //dataSource empty
        callMessage(errorMessages.value, 'error');
    });
}
// Function to subtract one week from weekTimesheetPicker
const subtractWeekTimesheet = () => {
    const newDate = dayjs(weekTimesheetPicker.value).subtract(1, range.value);
    weekTimesheetPicker.value = newDate.format('YYYY-MM-DD');

    search()
};
// Function to add one week to weekTimesheetPicker
const addWeekTimesheet = () => {
    const newDate = dayjs(weekTimesheetPicker.value).add(1, range.value);
    weekTimesheetPicker.value = newDate.format('YYYY-MM-DD');

    search()
};
const checkDateIsSunday = (title: string) => {
    return title.includes('Sunday') || title.includes('Sun');
}
const getHolidaySundayLabel = (holiday: boolean) => {
    if (holiday) {
        return '<span>Ngày</span><br /><span>Nghỉ</span>';
    }
    
    return 'Ngày nghỉ';
}
const getTasksTimeStyles = (item: Work): string  => {
    const tasks = item.tasks;
    const timesheets = item.timesheets;

    const diff = Math.abs(tasks - timesheets);
    // if (diff > 0.5) {
    if (diff > 0.5 || tasks > timesheets) {
        return 'tasks-time-red-text';
    }
    
    return 'tasks-time-green-text';
}
const disabledOption = (item: Department) => {
    const departmentId = session.value.department_id ?? 0;
    const id = session.value.id ?? 0;
    const position = session.value.position ?? 0;

    return item.value !== departmentId && ![107,161,63].includes(id) && position < 2;
}
const heightScroll = ref()
const spanCol = ref()
onMounted(() => {
    axios.get('/api/report/get_workday_selboxes').then(response => {
        departments.value = response.data.departments;
        users.value = response.data.users;
        session.value = response.data.session;
        formState.value.department_id = response.data.session.department_id;
    })

    search()

    var screenWidth = window.screen.width; // Screen width in pixels
    var screenHeight = window.screen.height; // Screen height in pixels
    console.log(screenWidth, screenHeight)

    if (screenWidth === 2560 && screenHeight === 1440) {
        // heightScroll.value = '750px'
        spanCol.value = '1'
    } else if (screenWidth === 1920 && screenHeight === 1080) {
        // heightScroll.value = '650px'
        spanCol.value = '1'
    } else if (screenWidth === 1080 && screenHeight === 1920) {
        // heightScroll.value = '1750px'
        spanCol.value = '2'
    } else if (screenWidth >= 1080 && screenHeight >= 1920) {
        // heightScroll.value = '1600px'
        spanCol.value = '2'
    }
})

</script>
<style lang="scss">
.tasks-time-red-text {
    font-size: 20px;
    font-weight: bold;
    line-height: normal;
    color: red;
}

.tasks-time-green-text {
    font-size: 20px;
    font-weight: bold;
    line-height: normal;
    color: green;
}
.tasks-time-text{
    font-size: 15px;
    line-height: normal;
    color: #909399;
}

.month-report .tasks-time-red-text {
    font-size: 15px;
}

.month-report .tasks-time-green-text {
    font-size: 15px;
}

.month-report .tasks-time-text{
    font-size: 13px;
}
.el-input__wrapper{
    width: 100%;
}
</style>