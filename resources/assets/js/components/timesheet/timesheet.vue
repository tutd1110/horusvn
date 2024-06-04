<template>
    <timesheet-detail ref="modalRefTimesheet"></timesheet-detail>
    <config ref="modalRef"></config>
    <LogCheckOut ref="modalRefCheckOut" @saved="onSaved"></LogCheckOut>
    <div id="filter-block">
        <el-row :gutter="10">
            <el-col :span="2">
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
            <el-col :span="2">
                <el-date-picker
                    id="select-daterange"
                    v-model="datePeriod"
                    :type="range"
                    unlink-pannels
                    range-separator="To"
                    start-placeholder="Start date"
                    end-placeholder="End date"
                    size="default"
                    style="width: 100%"
                    value-format="YYYY-MM-DD"
                ></el-date-picker>
            </el-col>
            <el-col :span="5">
                <el-space size="small" spacer="|">
                    <el-button type="primary" v-on:click="search()" :loading="loadingSearch">Search</el-button>
                    <el-radio-group v-model="mode" @change="search()">
                        <el-radio-button label="Week" />
                        <el-radio-button label="Month" />
                    </el-radio-group>
                </el-space>
            </el-col>
            <div class="flex-grow" />
             <el-col v-if="is_authority" :span="2">
                <el-button type="primary" style="width: 100%" v-on:click="showCheckOut()">Đồng bộ Check Out</el-button>
            </el-col>
            <el-col v-if="is_authority" :span="1">
                <el-button type="primary" style="width: 100%" v-on:click="showConfigModal()">Cấu hình</el-button>
            </el-col>
            <el-col :span="1">
                <template v-if="is_out">
                    <el-button type="primary" style="width: 100%" v-on:click="employeeGetIn()">Tiếp tục</el-button>
                </template>
                <template v-else>
                    <el-popconfirm
                        width="220"
                        confirm-button-text="OK"
                        cancel-button-text="No, Thanks"
                        :icon="InfoFilled"
                        icon-color="#626AEF"
                        @confirm="employeeGoOut()"
                        title="Are you sure to do this?"
                    >
                        <template #reference>
                            <el-button type="warning" style="width: 100%">Ra ngoài</el-button>
                        </template>
                    </el-popconfirm>
                </template>
            </el-col>
            <el-col :span="1" v-if="is_show_check_in">
                <el-button style="width: 100%" type="primary" v-on:click="employeeCheckin()">Checkin</el-button>
            </el-col>
            <el-col :span="1" v-if="is_show && (onCheckOut || is_show_check_out)">
                <el-popconfirm
                    width="220"
                    confirm-button-text="OK"
                    cancel-button-text="No, Thanks"
                    :icon="InfoFilled"
                    icon-color="#626AEF"
                    @confirm="employeeCheckout()"
                    title="Nếu bạn click vào Ok thì bạn sẽ không được tính thời gian sau thời gian Checkout nữa!"
                >
                    <template #reference>
                        <el-button type="danger" style="width: 100%">Checkout</el-button>
                    </template>
                </el-popconfirm>
            </el-col>
        </el-row>
    </div>
    <!-- Timesheets form from here -->
    <div class="timsheets-scrollbar">
        <el-row :gutter="4">
            <el-col :span="2">
                <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '53px'}">
                    <div class="card-content">
                        Checkin: {{ totalCheckIn }}
                        <br>
                        Checkout: {{ totalCheckOut }}
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
        <el-scrollbar :height="heightScrollbar">
            <template v-for="(item, index1) in tableData">
                <el-row :gutter="4" style="margin-bottom: 12px;">
                    <el-col :span="2">
                        <el-card shadow="hover" :body-style="elCardStyleBody">
                            <div class="card-content">
                                {{ item.fullname }}
                            </div>
                        </el-card>
                    </el-col>
                    <template v-for="(column, index2) in columns">
                        <el-col :span="getColSpan" :style="getCardStyle" @click="showTimesheetDetail(item.id, item.fullname, column.dataKey)">
                            <template v-if="item.timesheets && item.timesheets[column.dataKey]">
                                <el-card
                                    shadow="hover"
                                    :body-style="elCardStyleBody"
                                    :class="{ 'with-background-color': hasBackgroundColor(item.timesheets[column.dataKey], column.dataKey) }"
                                    :style="{ backgroundColor: getBackgroundColor(item.timesheets[column.dataKey], column.dataKey) }"
                                >
                                    <template v-if="columns.length <= 7 && !item.timesheets[column.dataKey].long_leave && !item.timesheets[column.dataKey].is_holiday">
                                        <div class="content-wrapper">
                                            <div class="left-content">
                                                <div class="timehsheets-time" style="display: flex;">
                                                    {{ getTimeshetCheckIn(item.timesheets[column.dataKey]) }} - 
                                                    {{ getTimeshetCheckOut(item.timesheets[column.dataKey]) }} 
                                                    <Checked v-if="item.timesheets[column.dataKey].final_checkout == true " style="width: 16px; height: 16px; margin-left: 5px; color: #fff; " />
                                                </div>
                                                <time class="time" v-html="getTimeSheetStatus(item.timesheets[column.dataKey])"></time>
                                                <time class="time" v-html="getOfficeGoOutInfo(item.timesheets[column.dataKey])"></time>
                                                <time class="time" v-html="getPetitionTitleInfo(item.timesheets[column.dataKey])"></time>
                                            </div>
                                            <h1 class="workday">{{ item.timesheets[column.dataKey].workday ?? '--'}}</h1>
                                        </div>
                                    </template>
                                    <template v-else-if="columns.length > 7 && !item.timesheets[column.dataKey].long_leave && !item.timesheets[column.dataKey].is_holiday">
                                        <div class="content-wrapper-month">
                                            <span>{{ item.timesheets[column.dataKey].workday ?? '--'}}</span>
                                            <span>{{ formatTime(item.timesheets[column.dataKey].check_in ?? item.timesheets[column.dataKey].petition_check_in) }}</span>
                                            <span>{{ formatTime(item.timesheets[column.dataKey].check_out ?? item.timesheets[column.dataKey].petition_check_out) }}</span>
                                        </div>
                                    </template>
                                    <template v-if="item.timesheets[column.dataKey].long_leave || item.timesheets[column.dataKey].is_holiday">
                                        <div class="card-content">
                                            <span style="font-weight: bold" v-html="getLeaveLabel(item.timesheets[column.dataKey], columns.length > 7)"></span>
                                        </div>
                                    </template>
                                </el-card>
                            </template>
                            <template v-else-if="checkDateIsSunday(column.title) && (typeof item.timesheets[column.dataKey] === 'undefined')">
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
                                    :class="{ 'with-background-color': hasBackgroundColor(null, column.dataKey) }"
                                    :style="{ backgroundColor: getBackgroundColor(null, column.dataKey) }"
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
import { onMounted, computed, ref, nextTick } from 'vue';
import { ElLoading } from 'element-plus'
import { InfoFilled, Checked } from '@element-plus/icons-vue'
import TimesheetDetail from './TimesheetDetail.vue';
import LogCheckOut from './LogCheckOut.vue';
import config from './config.vue';
import { callMessage } from '../Helper/el-message.js';
import dayjs from 'dayjs';
import { resizeScreen } from '../Helper/resize-screen.js';

interface FormState {
    user_id?: number,
    department_id?: number,
    start_date?: string,
    end_date?: string
};
interface User {
    id?: number,
    fullname: string,
    department_id?: number
};
interface Department {
    id: number,
    name: string
};
interface TimeSheet {
    check_in: string,
    check_out: string,
    petition_check_in?: string,
    petition_check_out?: string,
    petition_type?: number[],
    petition_title: string,
    long_leave: number,
    early_total: number,
    go_early_total: number,
    late_total: number,
    leave_late_total: number,
    office_goouts: number,
    office_time_goouts: number,
    non_office_goouts: number,
    non_office_time_goouts: number,
    is_going_out?: boolean,
    is_out_a_day?: boolean,
    workday: number,
    is_holiday?: boolean,
    on_business_trip?: boolean,
    holiday_title?: string
    final_checkout?: boolean
}
interface Employee {
    id: number,
    fullname: string,
    timesheets: { [date: string]: TimeSheet },
}
let loadingInstance: ReturnType<typeof ElLoading.service>;
const openLoading = () => {
    // Find the target element for the loading indicator
    const targetElement = document.querySelector('.timsheets-scrollbar') as HTMLElement;

    // Show the loading indicator before loading data
    loadingInstance = ElLoading.service({
        target: targetElement,
        fullscreen: true,
    });

    // Return the loading instance so it can be used later to close the loading
    return loadingInstance;
};
const closeLoading = () => {
    // Close the loading instance asynchronously after the DOM update cycle
    nextTick(() => {
        loadingInstance.close();
    });
};
const currentDate = dayjs();
const startDate = currentDate.startOf('week').format('YYYY/MM/DD');
const endDate = currentDate.endOf('week').format('YYYY/MM/DD');
const datePeriod = ref(startDate);
const auth_id = ref();
const mode = ref('Week');
const heightScrollbar = ref();
const range = computed(() => (mode.value === 'Week' ? 'week' : 'month'));
const errorMessages = ref('');
interface ElCardStyle {
    padding: string,
    height: string
}
const elCardStyleBody = ref<ElCardStyle>({padding: '0px', height: '120px'});
const getBackgroundColor = (item: TimeSheet | null, dateYMD: string) => {
    let color = '';
    const currentDate = dayjs();
    const parsedDate = dayjs(dateYMD, 'YYYYMMDD');

    if (!item) {
        // Compare date with the current date and check if check-in or check-out is not available
        if (parsedDate.isBefore(currentDate, 'day')) {
            color = '#FF3838';
        }
    } else {
        // Compare date with the current date and check if check-in or check-out is not available
        if (parsedDate.isBefore(currentDate, 'day') &&
            ((!item.check_out && !item.petition_check_out)) &&
            !item.long_leave
        ) {
            color = '#FF3838';
        }

        // Grey background-color if employees have petition's leaving
        if (item.petition_type && item.petition_type.includes(2) || item.long_leave && item.long_leave == 2) {
            color = '#9EA7AD';
        }

        if (item.late_total == 0 && item.early_total == 0) {
            color = '#47AF0E';
        }

        if (item.petition_type && item.petition_type.includes(9)) {
            color = 'slategrey';
        }

        if (item.late_total > 0 || item.early_total > 0) {
            color = '#FF3838';
        }

        if (item.petition_type && item.petition_type.includes(1) || item.is_going_out || item.is_out_a_day) {
            color = '#F0AD11';
        }

        if (item.is_holiday) {
            color = '#9EA7AD';
        }
    }

    return color;
};
const hasBackgroundColor = (item: TimeSheet | null, dateYMD: string) => {
    return !!getBackgroundColor(item, dateYMD);
};
const departments = ref<Array<Department>>([]);
const formState = ref<FormState>({
    start_date: startDate,
    end_date: endDate,
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
const loadingSearch = ref(false);
const tableData = ref<Array<Employee>>([]);
const countCheckIns = (tableData: Array<Employee>, key: string): number => {
    if (!Array.isArray(tableData)) {
        return 0; // or handle the non-array case in a different way if needed.
    }
    const currentDate = dayjs().format('YYYYMMDD');

    const totalCheckIns = tableData.reduce((count, employee) => {
        const timesheets = employee.timesheets;
        if (timesheets && timesheets[currentDate] && timesheets[currentDate].hasOwnProperty(key)) {
            return count + 1;
        }
        return count;
    }, 0);

    return totalCheckIns;
}
const totalCheckIn = computed(() => {
    return countCheckIns(tableData.value, 'check_in');
})
const totalCheckOut = computed(() => {
    return countCheckIns(tableData.value, 'check_out');
})
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
const is_authority = ref(false);
const is_out = ref(false);
const is_show = ref(false);
const is_show_check_in = ref(false);
const is_show_check_out = ref(false);

const onCheckOut = ref(false)
const fetchIpAddress = async () => {
    try {
        const response = await fetch('https://api.ipify.org?format=json');
        const data = await response.json();
        if(data.ip == '14.248.85.119') {
            onCheckOut.value = true
        }
        
    } catch (error) {
        console.error('Error fetching IP address:', error);
    }
};
onMounted(() => {

    fetchIpAddress()

    axios.get('/api/common/departments').then(response => {
        departments.value = response.data
    })
    axios.get('/api/common/get_employees_working').then(response => {
        users.value = response.data
    })
    //get session
    axios.get('/api/timesheet/get_session')
    .then(response => {
        is_authority.value = response.data.is_authority
        is_show.value = response.data.is_show
        is_out.value = response.data.is_out
        is_show_check_in.value = response.data.is_button_check_in
        is_show_check_out.value = response.data.is_show_check_out
        auth_id.value = response.data.is_user_id

        if (auth_id.value == 51) {
            mode.value = 'Month'
            const range = (mode.value == 'Week' ? 'week' : 'month')
            const startDate = currentDate.startOf('month').format('YYYY/MM/DD');
            const endDate = currentDate.endOf('month').format('YYYY/MM/DD');
            formState.value = {
                start_date: startDate,
                end_date: endDate,
            };
            search(); 
        } else {
            search();
        }
        
    })
    .catch((error) => {
        errorMessages.value = error.response.data.errors;//put message content in ref
        callMessage(errorMessages.value, 'error');
    });
    // openLoading(); // Open the loading indicator before loading data
    
    // axios.post('/api/timesheet/get_timesheet_list', formState.value).then(response => {
    //     closeLoading(); // Close the loading indicator
    //     tableData.value = response.data;
    //     setTimeout(() => { 
    //         heightScrollbar.value = resizeScreen()
    //         heightScrollbar.value = heightScrollbar.value - 80
    //     }, 0);
        
    // })
    // .catch((error) => {
    //     closeLoading(); // Close the loading indicator
    //     errorMessages.value = error.response.data.errors;
    //     callMessage(errorMessages.value, 'error');
    //     //When search target data does not exist
    //     tableData.value = [];
    // });
})
const search = () => {
    const startDate = dayjs(datePeriod.value).startOf(range.value).format('YYYY/MM/DD');
    const endDate = dayjs(datePeriod.value).endOf(range.value).format('YYYY/MM/DD');

    formState.value.start_date = startDate
    formState.value.end_date = endDate;

    loadingSearch.value = true;
    openLoading(); // Open the loading indicator before loading data
    axios.post('/api/timesheet/get_timesheet_list', formState.value)
    .then(response => {
        columns.value = generateColumns(new Date(startDate), new Date(endDate));
        // Update elCardStyleBody inside the search function
        elCardStyleBody.value.height = range.value === 'week' ? '120px' : '76px';
        loadingSearch.value = false;
        tableData.value = response.data;
        closeLoading(); // Close the loading indicator

        setTimeout(() => { 
            heightScrollbar.value = resizeScreen()
            heightScrollbar.value = heightScrollbar.value - 60
        }, 0);
    })
    .catch(error => {
        loadingSearch.value = false;
        //When search target data does not exist
        tableData.value = [];
        closeLoading(); // Close the loading indicator
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    });
}
const modalRefTimesheet = ref();
const modalRef = ref();
const modalRefCheckOut = ref();
const showTimesheetDetail = (id: number, fullname: string, dateTitle: string) => {
    const formattedDate = dateTitle.replace(/(\d{4})(\d{2})(\d{2})/, "$1-$2-$3");
    modalRefTimesheet.value.ShowWithDetailMode(id, fullname, formattedDate)
}
const showConfigModal = () => {
    modalRef.value.ShowWithConfigMode();
}
const onSaved = () => {
    search();
};
const showCheckOut = () => {
    modalRefCheckOut.value.ShowUpdateCheckOut();
}
const employeeGetIn = () => {
    axios.post('/api/employee/get_in')
    .then(response => {
        is_out.value = false
        search()
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const employeeGoOut = () => {
    axios.post('/api/employee/go_out')
    .then(response => {
        is_out.value = true
        search()
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const employeeCheckin = () => {
    axios.post('/api/employee/check_in_by_hand')
    .then(response => {
        is_show_check_in.value = false
        search()
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const employeeCheckout = () => {
    axios.post('/api/employee/check_out')
    .then(response => {
        is_show.value = false
        search()
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const getTimeshetCheckIn = (item: TimeSheet) => {
    return item.check_in ?? item.petition_check_in;
}
const getTimeshetCheckOut = (item: TimeSheet) => {
    return item.check_out || item.petition_check_out || '-:-';
}
const formatTime = (timeString: string) => {
    if (!timeString || typeof timeString !== 'string') return '-:-';

    // Use a regular expression to extract the hours and minutes from the time string
    const timePattern = /^(\d{2}:\d{2})/; // Matches "hh:mm" in the beginning of the string
    const matches = timeString.match(timePattern);

    // Return the formatted time or the original time string if no match is found
    return matches ? matches[1] : timeString;
}
const getColSpan = computed(() => {
    return columns.value.length > 7 ? 1 : 4;
});
const getCardStyle = computed(() => {
    const width = (100-8.3333333333) / (columns.value.length);
    return { maxWidth: `${width}%` };
});
const getTimeSheetStatus = (item: TimeSheet) => {
    let earlyInfo = item.go_early_total >= 0 && item.late_total == 0
        ? `Đi sớm: <span class="timesheets-status">${roundValue(item.go_early_total ?? 0, 60)}</span>`
        : `Đi muộn: <span class="timesheets-status">${roundValue(item.late_total ?? 0, 60)}</span>`;

    let leaveInfo = item.early_total > 0
        ? `Về sớm: <span class="timesheets-status">${roundValue(item.early_total ?? 0, 60)}</span>`
        : `Về muộn: <span class="timesheets-status">${roundValue(item.leave_late_total ?? 0, 60)}</span>`;

    return `${earlyInfo} - ${leaveInfo}`;
}
const getOfficeGoOutInfo = (item: TimeSheet) => {
    let officeOut = `Ra ngoài: <span class="timesheets-status">${(item.office_goouts ?? 0) + (item.non_office_goouts ?? 0)}</span>`;
    let officeTime = `Thời gian: <span class="timesheets-status">${roundValue(
        (item.office_time_goouts ?? 0) + (item.non_office_time_goouts ?? 0),
        60
    )}</span>`;

    return `${officeOut} - ${officeTime}`;
};
const getPetitionTitleInfo = (item : TimeSheet) => {
    let title = `Yêu cầu: <span class="timesheets-status">${item.petition_title??0}</span>`;

    return `${title}`;
}
const roundValue = (value: number, factor: number): number => {
    return Math.round(value / factor);
};
const formatTitle = (title: string) => {
    const [firstLine, secondLine] = title.split(',');
    const isSunday = ['Sunday', 'Sun'].includes(firstLine); // Check if firstLine is 'Sunday' or 'Sun'

    let dateLabel = `<span class="timesheets-date-label ${isSunday ? 'timesheets-red-text' : ''}">${firstLine}</span>`;
    let dateMD = `<span class="timesheets-date-md ${isSunday ? 'timesheets-red-text' : ''}">${secondLine}</span>`;

    return `${dateLabel} ${dateMD}`;
};
const getHolidaySundayLabel = (holiday: boolean) => {
    if (holiday) {
        return '<span>Ngày</span><br /><span>Nghỉ</span>';
    }
    
    return 'Ngày nghỉ';
}
const getLeaveLabel = (item: TimeSheet, month: boolean) => {
    if (item.is_holiday) {
        if (month) {
            // return `<span>Du</span><br /><span>Lịch</span>`;
            return `<span>Nghỉ toàn công ty</span>`;
        }
        return item.holiday_title;
    }

    let leaveType = (item.long_leave && item.long_leave) === 1 ? 'Một' : 'Nhiều';

    if (month) {
        return `<span>Nghỉ</span><br /><span>${leaveType}</span><br /><span>Ngày</span>`;
    }

    return `Nghỉ ${leaveType.toLowerCase()} ngày`;
};
const checkDateIsSunday = (title: string) => {
    return title.includes('Sunday') || title.includes('Sun');
}
</script>
<style lang="scss">
.timesheets-title {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.timesheets-date-md {
    font-size: 20px;
}
.timesheets-date-label,
.timesheets-date-md {
    &.timesheets-red-text {
        color: red;
    }
}
.time {
    font-size: 12px;
}
.timehsheets-time {
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 13px;
}
.timesheets-status {
    font-weight: bold;
    font-size: 12px;
}
.content-wrapper {
    padding: 14px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}
.left-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    margin-right: auto;
}
.workday {
    font-weight: bold;
    font-size: 18px;
    align-self: center;
}
.el-card.with-background-color .content-wrapper,
.el-card.with-background-color .workday {
    color: #FFFFFF;
}
.card-content {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
    text-align: center; /* Center the text horizontally */
}
.card-content span {
    text-align: center; /* Center the text horizontally */
}
.el-card.with-background-color .card-content {
    color: #FFFFFF;
}
.content-wrapper-month {
    /* Adjust the styles for the content wrapper */
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.content-wrapper-month span {
  /* Adjust the styles for the span elements */
  font-size: 14px; /* Change the font size as needed */
  display: block;
  margin: 0; /* Add some margin between each span element */
}
.content-wrapper-month span:first-child {
  /* Style the first span element differently */
  font-size: 18px; /* Change the font size for the first span element */
  font-weight: bold; /* Add font-weight for emphasis */
}
.el-card.with-background-color .content-wrapper-month {
    color: #FFFFFF;
}
.el-card.timesheet-box {
    box-shadow: 0px 0px 12px rgba(0, 0, 0, 0.06);
}
.flex-grow {
  flex-grow: 1;
}
</style>