<template>
    <el-radio-group class="custom-tabs" v-model="mode" v-if="user && (user.is_pm || user.position === 1)">
        <el-radio-button :label="hideMobile ? 'Quản Lý' : 'QL'" />
        <el-radio-button :label="hideMobile ? 'Cá Nhân' : 'CN'" />
    </el-radio-group>
    <template v-if="(mode == 'Quản Lý' || mode == 'QL') && user && (user.is_pm || user.position === 1)">
        <el-row class="dashboard-container dashboard-container-admin" :class="!hideMobile ? 'dashboard-mobile' : ''"> 
            <el-col :span="24" v-if="!hideMobile">
                <div class="panel-infomation">
                    <el-row :gutter="!hideMobile ? 8 : 16" class="panel-head" :style="!hideMobile ? 'padding: 20px 20px 0px 20px' : ''">
                        <el-col :lg="24" :sm="5" :xs="5" class="avatar-home">
                            <el-avatar shape="square" :size="!hideMobile ? 50 : 115" fit="cover" :src="avatarSrc" />
                        </el-col>
                        <el-col :lg="24" :sm="12" :xs="12" class="name-staff" :style="!hideMobile ? 'text-align: left' : ''">
                            {{ user.fullname }}
                            <span class="position-staff" v-if="!hideMobile">
                                {{ employeeTitle }}
                            </span>
                        </el-col>
                        <el-col :lg="24" :sm="12" :xs="12" class="position-staff" v-if="hideMobile">
                            {{ employeeTitle }}
                        </el-col>
                    </el-row>
                </div>
            </el-col>
            <manager></manager>
        </el-row>
    </template>
    <template v-else>
        <el-row class="dashboard-container" v-if="user" :class="!hideMobile ? 'dashboard-mobile' : ''">
            <el-col :lg="5" :md="24">
                <div class="panel-infomation">
                    <el-row :gutter="!hideMobile ? 8 : 16" class="panel-head" :style="!hideMobile ? 'padding: 20px 20px 0px 20px' : ''">
                        <el-col :lg="24" :sm="5" :xs="5" class="avatar-home">
                            <el-avatar shape="square" :size="!hideMobile ? 50 : 115" fit="cover" :src="avatarSrc" />
                        </el-col>
                        <el-col :lg="24" :sm="12" :xs="12" class="name-staff" :style="!hideMobile ? 'text-align: left' : ''">
                            {{ user.fullname }}
                            <span class="position-staff" v-if="!hideMobile">
                                {{ employeeTitle }}
                            </span>
                        </el-col>
                        <el-col :lg="24" :sm="12" :xs="12" class="position-staff" v-if="hideMobile">
                            {{ employeeTitle }}
                        </el-col>
                        <el-col :span="24" class="official-day">
                            <el-card shadow="hover" class="official-day-item"> 
                                <div class="start-day">Ngày làm việc chính thức: <span>{{ user.date_official_DMY }}</span></div>
                                <div class="count-day">Số ngày làm việc chính thức: <span>{{ user.total_date_official }}</span></div>
                            </el-card>
                        </el-col>
                        <el-col :span="8" :offset="16" class="date-year-select" v-if="hideMobile">
                            <el-date-picker
                                v-model="yearWorkPicker"
                                type="year"
                                placeholder="Chọn Năm"
                                @change="getWork"
                                value-format="YYYY-MM-DD"
                                :clearable="false"
                            />
                        </el-col>
                        <el-col v-for="(card, index) in elCardWork" :key="index" :span="8">
                            <el-card shadow="hover" class="card-item" :style="{ backgroundColor: card.color }">
                                <div class="card-top-item" :style="{'font-size': !hideMobile ? '22px' : '', color: card.text_color }">{{ card.value }}</div>
                                <el-tooltip
                                    effect="light"
                                    class="box-item"
                                    placement="bottom-start"
                                >
                                <template #content>
                                    <span v-html="card.tooltip"></span>
                                </template>
                                <div class="card-bottom-item" :style="{'font-size': !hideMobile ? '12px' : ''}">
                                        {{ card.label }}
                                        <Warning style="width: 12px; vertical-align: top; transform: rotate(180deg);"/>
                                    </div>
                                </el-tooltip>           
                            </el-card>
                        </el-col>
                    </el-row>
                    <el-row class="panel-footer" v-if="hideMobile">
                        <el-col :span="24" class="title-head">
                            Sự Kiện {{ currentDate.format('DD/MM/YYYY') }}
                        </el-col>
                        <el-scrollbar height="180px" style="width:100%;height: auto;">
                            <template v-for="(item) in eventUser">
                                <el-col :span="24" class="event-item">
                                    <div class="sub-title">
                                        {{ item.name_event }}
                                        {{item.department_id ? ' - '+item.department_id : ''}}
                                        ({{ dayjs(item.start_time, 'HH:mm').format('HH:mm')}} - 
                                        {{ dayjs(item.end_time, 'HH:mm').format('HH:mm')}})
                                    </div>
                                    <div class="description"><span style="font-weight: 700; font-size: 12px; font-style:italic">{{ item.name }}</span>{{ item.fullnames ? ' - Người phụ trách: '+item.fullnames : ''}}</div>
                                </el-col>
                            </template>
                        </el-scrollbar>
                        <el-col :span="24" class="title-footer">
                            <a href="/calendar">Xem thêm</a>
                        </el-col>
                    </el-row>
                </div>
            </el-col>
            <el-col :lg="19" :md="24">
                <el-row class="panel-dashboard">
                    <el-col :span="24" class="title-dashboard" v-if="hideMobile">
                        Dashboard
                    </el-col>
                    <el-col :span="24" class="main-dashboard">
                        <el-row :gutter="20">
                            <el-col :lg="17" :md="24" class="panel-left">
                                <el-card shadow="hover" class="panel-timekeep">
                                    <el-row :gutter="!hideMobile ? 6 : 10">
                                        <el-col :lg="12" :sm="10" :xs="10" class="title-timekeep">Chấm công</el-col>
                                        <div class="flex-grow" />
                                        <el-col :span="12" class="btn-readmore" v-if="hideMobile">
                                            <a href="/timesheets" target="_blank">Xem chi tiết</a>
                                        </el-col>
                                        <el-col :lg="24" :sm="14" :xs="14" class="date-week-select" :style="{'margin-top': !hideMobile ? '0px' : '5px'}">
                                            <a class="icon-arrow arrow-left" @click="subtractWeekTimesheet"><el-icon><ArrowLeft /></el-icon></a>
                                            <el-date-picker
                                                v-model="weekTimesheetPicker"
                                                type="week"
                                                format="[Week] ww"
                                                placeholder="Week"
                                                class="time-week"
                                                value-format="YYYY-MM-DD"
                                                @change="getTimesheet"
                                                :clearable="false"
                                            />
                                            <a class="icon-arrow arrow-right" @click="addWeekTimesheet"><el-icon><ArrowRight /></el-icon></a>
                                        </el-col>
                                        <el-col v-for="(column, idx2) in columnsTimesheet" :key="idx2"  :span="3" class="day-item">
                                            <div 
                                                v-if="!hideMobile"
                                                :class=" ' title-day main-day ' + getTitleClass(column) + ' ' + (isSelectDateMobile == column.dataKey ? ' day-mobile-select ' : '') + getTimesheetsClass(column, timesheets[column.dataKey])"
                                                :style="{
                                                    'font-size': !hideMobile ? '12px' : '', 
                                                }" 
                                                @click="handleTimesheetMobile(column, timesheets[column.dataKey])"
                                            >
                                                {{ column.title }}
                                            </div>
                                            <div v-else :class="getTitleClass(column) + ' title-day '">
                                                {{ column.title }}
                                            </div>
                                            <el-card v-if="timesheets && hideMobile" shadow="hover" :class="'main-day ' + getTimesheetsClass(column, timesheets[column.dataKey])">
                                                <div v-html="generateTimekeepingContent(column, timesheets[column.dataKey])"></div>
                                            </el-card>
                                        </el-col>
                                        <el-col :span="24" v-if="!hideMobile" class="mobile-day-item">
                                            <el-card shadow="hover" style="margin-top: 5px;" :class="' custom-main-day main-day ' + classMobile">
                                                <el-row style="display: flex; align-items: center;">
                                                    <el-col :span="19">
                                                        <div style="font-size: 16px; font-weight: 700;"> {{ mobileTime }}</div>
                                                        <div> 
                                                            <span style="font-size: 12px; margin-right: 5px;">Ra ngoài: <strong>{{ mobileGoOut }}</strong></span>    
                                                            <span style="font-size: 12px;">Yêu cầu: <strong>{{ mobilePetition }}</strong></span>    
                                                        </div>
                                                    </el-col>
                                                    <el-col :span="5">
                                                        <span style="font-size: 20px; font-weight: 700; float: right;"> {{ mobileWorkday }}</span>
                                                    </el-col>
                                                </el-row>
                                            </el-card>
                                        </el-col>
                                    </el-row>
                                </el-card>
                                <el-row :gutter="20" class="panel-work-detail" v-if="hideMobile">
                                    <el-col :span="19" :lg="19" :md="24">
                                        <el-card shadow="hover" class="">
                                            <el-row :gutter="20" style="margin-bottom: 0;">
                                                <el-col :span="16" class="title" style="margin-bottom:20px">
                                                    Công việc
                                                </el-col>
                                                <div class="flex-grow" />
                                                <el-col :span="8" style="margin-bottom:20px">
                                                    <el-date-picker
                                                        v-model="waitingWorkDetailDateRangePicker"
                                                        type="daterange"
                                                        range-separator="-"
                                                        start-placeholder="Start"
                                                        end-placeholder="End"
                                                        format="DD/MM/YY"
                                                        value-format="YYYY/MM/DD"
                                                        @change="getWorkDetail"
                                                        style="width:100%"
                                                        :clearable="false"
                                                    />
                                                </el-col>
                                                <el-col class="work-item">
                                                    <el-card shadow="hover" class="work-item-card">
                                                        <div class="sub-title">Đang chờ</div>
                                                        <div class="description" style="color: #909399;">{{ waitingTasks }}</div>
                                                    </el-card>
                                                </el-col>
                                                <el-col class="work-item">
                                                    <el-card shadow="hover" class="work-item-card">
                                                        <div class="sub-title">Đang tiến hành</div>
                                                        <div class="description" style="color: #409EFF;">{{ tasksInProgress }}</div>
                                                    </el-card>
                                                </el-col>
                                                <el-col class="work-item">
                                                    <el-card shadow="hover" class="work-item-card">
                                                        <div class="sub-title">Chậm deadline</div>
                                                        <el-space class="description-custom">
                                                            <div class="description" style="color: #FF3838;">{{ overdueTasks.overdue }}<span style="font-size: 20px; color: #909399; font-weight: 400;">/{{ overdueTasks.total }}</span></div>
                                                            <div class="progress">
                                                                <el-progress type="circle" :width="55" :percentage="calculatePercentageTasks(overdueTasks)" :stroke-width="8"  color='#FF3838'/>
                                                            </div>
                                                        </el-space>
                                                    </el-card>
                                                </el-col>
                                                <el-col class="work-item">
                                                    <el-card shadow="hover" class="work-item-card">
                                                        <div class="sub-title">Giờ làm việc đã nhập</div>
                                                        <div class="description" style="color: #909399;">{{ workTime }}</div>
                                                    </el-card>
                                                </el-col>
                                            </el-row>
                                        </el-card>
                                    </el-col>
                                    <el-col :span="5" class="work-item-count">
                                        <el-card shadow="hover" class="work-item-card">
                                            <div class="sub-title">Tổng giờ nỗ lực</div>
                                            <el-space class="description-custom">
                                                <div class="time-work">
                                                    <span>{{ effortTime.request_effort_time }}</span>
                                                </div>
                                                <div v-if="showTopPercent" class="percent" style="color: #47AF0E;">
                                                    <Top style="width: 1em; height: 1em; vertical-align: text-bottom;" />{{ percentValue }}%
                                                </div>
                                                <div v-else class="percent" style="color: #FF3838;">
                                                    <Bottom style="width: 1em; height: 1em; vertical-align: text-top;" />{{ percentValue }}%
                                                </div>
                                            </el-space>
                                            <div class="date-work">
                                                <el-space class="footer-warrior">
                                                    <div v-if="effortTime.title == 'Warrior 1'" class="level-warrior" style="color: #47AF0E;">
                                                        {{ effortTime.title }}
                                                    </div>
                                                    <div v-else-if="effortTime.title == 'Warrior 2'" class="level-warrior" style="color: #FFA500;">
                                                        {{ effortTime.title }}
                                                    </div>
                                                    <div v-else-if="effortTime.title == 'Warrior 3'" class="level-warrior" style="color: #800000;">
                                                        {{ effortTime.title }}
                                                    </div>
                                                    <div v-else class="level-warrior">
                                                        {{ effortTime.title }}
                                                    </div>
                                                    <el-date-picker
                                                        v-model="monthEffortPicker"
                                                        type="month"
                                                        placeholder="Month"
                                                        format="MM/YY"
                                                        @change="getEffortTime"
                                                        value-format="YYYY-MM-DD"
                                                        style="width: 50%;"
                                                        :clearable="false"
                                                    />
                                                </el-space>
                                            </div>
                                        </el-card>
                                    </el-col>
                                </el-row>
                                <el-row :gutter="20" class="panel-footer-main-dashboard" v-if="hideMobile">
                                    <el-col :span="12" class="panel-forum">
                                        <el-card shadow="hover">
                                            <div class="title">Bài viết mới nhất trên Forum</div>
                                            <el-scrollbar height="215px">
                                                <div v-for="post in latestPosts" :key="post.thread_id" class="forum-item">
                                                    <div class="forum-image">
                                                        <el-avatar shape="square" :size="40" fit="cover" :src="post.thread_avatar_user" />
                                                    </div>
                                                    <div class="forum-content">
                                                        <a class="sub-title" :href="post.thread_url" target="_blank">{{ post.thread_title }}</a>
                                                        <div class="description">Latest: {{ post.last_post_username }} - {{ post.last_post_date }}</div>
                                                        <div class="description">{{ post.forum_title }}</div>
                                                    </div>
                                                </div>
                                            </el-scrollbar>
                                            <div class="forum-item" style="text-align: center;display: block; padding: 5px; font-weight: 700;">
                                                <a href="https://forum.horusvn.com/index.php?whats-new/" target="_blank">Xem thêm</a>
                                            </div>
                                        </el-card>
                                    </el-col>
                                    <el-col :span="12" class="panel-rules">
                                        <el-card shadow="hover">
                                            <el-row class="panel-head">
                                                <el-col :span="18" class="title">Vi phạm nội quy</el-col>
                                                <el-col :span="6" class="date-year-select">
                                                    <el-date-picker
                                                        v-model="yearViolationsPicker"
                                                        type="year"
                                                        placeholder="Chọn Năm"
                                                        @change="getViolations"
                                                        value-format="YYYY-MM-DD"
                                                        :clearable="false"
                                                    />
                                                </el-col>
                                            </el-row>
                                            <el-scrollbar height="245px">
                                                <template v-for="(violation, idx2) in violations" :key="idx2">
                                                    <div class="rules-item">
                                                        <div class="sub-title">Vi phạm lần {{ idx2+1 }}: {{ violation.type }}</div>
                                                        <div class="description">{{ violation.description }}</div>
                                                    </div>
                                                </template>
                                            </el-scrollbar>
                                        </el-card>
                                    </el-col>
                                </el-row>
                            </el-col>
                            <el-col :lg="7" :md="24" class="panel-right">
                                <el-card shadow="hover" class="panel-progress">
                                    <div class="title">
                                        <span class="title">Thời gian</span>
                                        <el-date-picker
                                            v-if="!hideMobile"
                                            class="date-chart"
                                            v-model="monthAttendancePicker"
                                            type="month"
                                            placeholder="Month"
                                            format="MM/YY"
                                            @change="getProcessAttendance"
                                            value-format="YYYY-MM-DD"
                                            :clearable="false"
                                            style="width: auto; float: right;"
                                        />
                                    </div>
                                    
                                    <el-space class="panel-chart">
                                        <div class="chart">
                                            <Pie
                                                id="pie-chart"
                                                :options="chartOptions"
                                                :data="chartData"
                                                :style="pieStyle"
                                            />
                                        </div>
                                    </el-space>
                                    <el-date-picker
                                        v-if="hideMobile"
                                        class="date-chart"
                                        v-model="monthAttendancePicker"
                                        type="month"
                                        placeholder="Month"
                                        format="MM/YY"
                                        @change="getProcessAttendance"
                                        value-format="YYYY-MM-DD"
                                        :clearable="false"
                                    />
                                </el-card>
                                <el-card shadow="hover" class="panel-notification" v-if="hideMobile">
                                    <div class="title">Thông báo</div>
                                    <el-scrollbar height="425px">
                                        <div class="notifi-item" v-for="(alert, inx3) in alerts" :key="inx3">
                                            <div class="notifi-image">
                                                <el-avatar shape="square" :size="40" fit="cover" :src="avatarSrc" />
                                            </div>
                                            <div class="notifi-content">
                                                <template v-if="alert.type != 99">
                                                    <a class="sub-title" v-if="alert.status === 1">
                                                        {{ alert.description }} được duyệt
                                                    </a>
                                                    <a class="sub-title" v-else-if="alert.status === 2">
                                                        {{ alert.description }} <span>từ chối</span> <br><strong>Lí do: {{ alert.rejected_reason }}</strong>
                                                    </a>
                                                    <a class="sub-title" v-else>
                                                        Bạn đã tạo yêu cầu {{ alert.label }}
                                                    </a>
                                                </template>
                                                <template v-else>
                                                    <a class="sub-title">{{ alert.description }} <strong>{{ formatDateTime(alert.datetime) }}</strong></a>
                                                </template>
                                                <div class="description is-active">{{ formattedFromNow(alert.datetime) }}</div>
                                            </div>
                                        </div>
                                    </el-scrollbar>
                                </el-card>
                            </el-col>
                        </el-row>
                    </el-col>
                </el-row>
            </el-col>
        </el-row>
    </template>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { onMounted, computed, ref } from 'vue';
import { ArrowLeft, ArrowRight, Bottom, Top, Warning } from '@element-plus/icons-vue'
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Pie, Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement, CategoryScale, BarElement, LinearScale} from 'chart.js'
import type { TabsPaneContext } from 'element-plus'
import manager from './manager.vue';

dayjs.Ls.en.weekStart = 1
dayjs.extend(relativeTime)
ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale, BarElement, LinearScale)
const hideMobile = ref(true)
const mode = ref('Quản Lý');

const attendanceChart = ref([0, 0, 0]);
const percentAttendanceChart = ref([0, 0, 0]);
const chartData = computed(() => ({
    labels: ["Đúng giờ", "Quên chấm công", "Đi muộn"],
    datasets: [{
        backgroundColor: [
            '#47AF0E',
            '#FE763C',
            '#FF3838',
        ],
        data: attendanceChart.value,
        percent: percentAttendanceChart.value,
        borderWidth: 1,
    }]
}));
const chartOptions = ref({
    responsive: false,
    plugins: {
        legend: {
            position: 'right'
        },
        tooltip:{
            callbacks:{
                label: (context : any) => {
                    const data = context.dataset.data[context.dataIndex]
                    const percent = context.dataset.percent[context.dataIndex]
                    const labelText = data + ' (' + percent + '%)'
                    return labelText;
                }
            }
        }
    },
    tooltips: {
        enabled: false
    }
})
const pieStyle = ref({
    height: '185px'
})

interface User {
    id: number,
    fullname: string,
    avatar: string,
    position: number,
    is_pm: boolean,
    department_id: number,
    date_official_DMY: string,
    total_date_official: string
    is_leader: number
};
interface Work {
    total_day: number,
    total_hour: number,
    total_effort_hour: number,
    warrior_1: number,
    warrior_2: number,
    warrior_3: number
};
interface Post {
    thread_avatar_user: string,
    forum_title: string,
    last_post_date: string,
    last_post_username: string,
    thread_title: string,
    thread_url: string,
    thread_id: number
};
interface TimesheetObject {
    [key: string]: Timesheet,
}
interface Timesheet {
    time: string;
    go_out: number;
    petitions: number[];
    is_holiday?: boolean;
    long_leave?: number;
    punctuality_issue: boolean,
    is_going_out?: boolean,
    is_out_a_day?: boolean,
    workday?: number
}
interface Column {
    title: string,
    dataKey: number,
    isFuture: boolean
}
interface Violation {
    type: string,
    description: string
}
interface Alert {
    name?: string,
    datetime?: string,
    description?: string,
    type: number,
    status: number,
    rejected_reason?: string,
    label?: string
}
interface OverDue {
    total: number,
    overdue: number
}
const defaultEmptyOverDue: OverDue = {
    total: 0,
    overdue: 0
};
interface EffortTime {
    last_effort_time: number,
    request_effort_time: number,
    title: string
}
const defaultEmptyEffortTime: EffortTime = {
    last_effort_time: 0,
    request_effort_time: 0,
    title: 'Soldier'
};
interface Department {
    id: number,
    name: string
};
const currentDate = dayjs()
const startOfMonth = currentDate.startOf('month').format('YYYY/MM/DD')
const endOfMonth = currentDate.endOf('month').format('YYYY/MM/DD')
const yearWorkPicker = ref(currentDate.startOf('year').format('YYYY/MM/DD'))
const yearViolationsPicker = ref(currentDate.startOf('year').format('YYYY/MM/DD'))
const monthEffortPicker = ref(startOfMonth)
const monthAttendancePicker = ref(startOfMonth)
const weekTimesheetPicker = ref(currentDate.startOf('week').format('YYYY/MM/DD'));

const users = ref<Array<User>>([]);
const departments = ref<Array<Department>>([]);
const projects = ref<Array<Department>>([]);

// Computed property to derive formStateWork based on yearWorkPicker
const formStateWork = computed(() => {
    return {
        start_date: dayjs(yearWorkPicker.value).startOf('year').format('YYYY/MM/DD'),
        end_date: dayjs(yearWorkPicker.value).endOf('year').format('YYYY/MM/DD')
    };
});
// Computed property to derive formStateTimesheet based on weekTimesheetPicker
const formStateTimesheet = computed(() => {
    return {
        start_date: dayjs(weekTimesheetPicker.value).startOf('week').format('YYYY/MM/DD'),
        end_date: dayjs(weekTimesheetPicker.value).endOf('week').format('YYYY/MM/DD'),
        user_id: user.value?.id
    };
});
const waitingWorkDetailDateRangePicker = ref<[string, string]>([
    startOfMonth,
    endOfMonth
]);
const waitingWorkTotalDateRangePicker = ref<[string, string]>([
    startOfMonth,
    endOfMonth
]);

// Computed property to derive formStateWaitingTask based on waitingTaskDateRangePicker
const formStateWorkDetail = computed(() => {
    return {
        start_date: waitingWorkDetailDateRangePicker.value[0],
        end_date: waitingWorkDetailDateRangePicker.value[1],
        user_id: user.value?.id
    };
});
interface formStateWorkTotal {
    user_id?: number,
    user_status?: number,
    department_id?: number,
    project_id?: number,
    start_date?: string,
    end_date?: string,
    created_at?: string
};
const formStateWorkTotal = ref<formStateWorkTotal>({
    start_date: waitingWorkTotalDateRangePicker.value[0],
    end_date: waitingWorkTotalDateRangePicker.value[1],
});

const formStateEffort = computed(() => {
    return {
        start_date: dayjs(monthEffortPicker.value).startOf('month').format('YYYY/MM/DD'),
        end_date: dayjs(monthEffortPicker.value).endOf('month').format('YYYY/MM/DD'),
        user_id: user.value?.id
    };
});
const formStateViolations = computed(() => {
    return {
        start_date: dayjs(yearViolationsPicker.value).startOf('year').format('YYYY/MM/DD'),
        end_date: dayjs(yearViolationsPicker.value).endOf('year').format('YYYY/MM/DD'),
        user_id: user.value?.id
    };
});
const formStateAttendance = computed(() => {
    return {
        start_date: dayjs(monthAttendancePicker.value).startOf('month').format('YYYY/MM/DD'),
        end_date: dayjs(monthAttendancePicker.value).endOf('month').format('YYYY/MM/DD'),
        user_id: user.value?.id
    };
});
const user = ref<User>();
const work = ref<Work>();
// Array of card data
const elCardWork = computed(() =>  [
  { color: '#D7B5EC', text_color: '#7A0BC7', label: 'Công thực tế', value: work.value?.total_day || 0, tooltip: 'Tổng số ngày công thực tế' },
  { color: '#FFD6D6', text_color: '#FF6060', label: 'Giờ làm việc', value: work.value?.total_hour || 0, tooltip: 'Tổng số giờ làm việc' },
  { color: '#FFF4C4', text_color: '#DAB422', label: 'Giờ nỗ lực', value: work.value?.total_effort_hour || 0, tooltip: 'Tổng số giờ nỗ lực' },
  { color: '#C0F6C8', text_color: '#47AF0E', label: 'Warrior 1', value: work.value?.warrior_1 || 0, tooltip: 'Số tháng đạt <span style="color:#47AF0E">Warrior 1</span> ' },
  { color: '#FFE2AD', text_color: '#FFA500', label: 'Warrior 2', value: work.value?.warrior_2 || 0, tooltip: 'Số tháng đạt <span style="color:#FFA500">Warrior 2</span> ' },
  { color: '#E7C2C2', text_color: '#800000', label: 'Warrior 3', value: work.value?.warrior_3 || 0, tooltip: 'Số tháng đạt <span style="color:#800000">Warrior 3</span> ' }
]);
// Computed property to generate the column object
const columnsTimesheet = computed(() => {
    const startDate = dayjs(formStateTimesheet.value.start_date);
    const endDate = dayjs(formStateTimesheet.value.end_date);

    // Initialize an array to hold the column objects
    const columns = [];

    // Loop through the range of dates between the start and end dates
    let currentDateColumn = startDate;
    while (currentDateColumn.isBefore(endDate.add(1, 'day'), 'day')) {
        // Generate the title for each date
        const dayOfWeek = currentDateColumn.day();
        const dayOfMonth = currentDateColumn.format('DD/MM');
        const dataKey = parseInt(currentDateColumn.format('YYYYMMDD'), 10);
        const dayOfWeekTitle = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][dayOfWeek] + ' \n ' + dayOfMonth;

        // Determine if the date is in the future
        const isFutureDate = currentDateColumn.isAfter(dayjs(), 'day');

        // Add the column object to the columns array
        columns.push({
            title: dayOfWeekTitle,
            dataKey: dataKey,
            isFuture: isFutureDate
        });

        // Move to the next date
        currentDateColumn = currentDateColumn.add(1, 'day');
    }
   
    return columns;
});
// Function to add one week to weekTimesheetPicker
const addWeekTimesheet = () => {
    const newDate = dayjs(weekTimesheetPicker.value).add(1, 'week');
    weekTimesheetPicker.value = newDate.format('YYYY-MM-DD');

    getTimesheet()
};
// Function to subtract one week from weekTimesheetPicker
const subtractWeekTimesheet = () => {
    const newDate = dayjs(weekTimesheetPicker.value).subtract(1, 'week');
    weekTimesheetPicker.value = newDate.format('YYYY-MM-DD');

    getTimesheet()
};
const avatarSrc = computed(() => {
    return '/image/' + user.value?.avatar;
});
const employeeTitle = computed(() => {
    return getPositionTitle(user.value?.position ?? 0);
});
const getPositionTitle = (position: number) => {
    switch (position) {
        case 0:
            return "Employee";
        case 1:
            return "Leader";
        case 2:
            return "Project Manager";
        case 3:
            return "Director";
        default:
            return "Unknown";
    }
};
const getWork = () => {
    axios.get('/api/home/get-work', {
        params: formStateWork.value
    }).then(response => {
        work.value = response.data      
    })
}
const timesheets = ref<TimesheetObject[]>([]);
const getTimesheet = () => {
    axios.post('/api/home/get-timesheets', formStateTimesheet.value)
    .then(response => {
        timesheets.value = response.data

        getTimesheetMobile(timesheets.value)
    })
    .catch(error => {
    });
}
const waitingTasks = ref(0)
const tasksInProgress = ref(0)
const overdueTasks = ref<OverDue>(defaultEmptyOverDue)
const workTime = ref(0)
const effortTime = ref<EffortTime>(defaultEmptyEffortTime)
const getWorkDetail = () => {
    axios.post('/api/home/get-work-detail', formStateWorkDetail.value)
    .then(response => {
        waitingTasks.value = response.data.waitingTasks
        tasksInProgress.value = response.data.tasksInProgress
        overdueTasks.value = response.data.overdueTasks[0]
        workTime.value = response.data.workTime
    })
    .catch(error => {
        waitingTasks.value = 0
        tasksInProgress.value = 0
        overdueTasks.value = defaultEmptyOverDue
        workTime.value = 0
    });
}

const getEffortTime = () => {
    axios.post('/api/home/get-effort-time', formStateEffort.value)
    .then(response => {
        effortTime.value = response.data
    })
    .catch(error => {
        effortTime.value = defaultEmptyEffortTime
    });
}
const getProcessAttendance = () => {
    axios.post('/api/home/get-process-attendance', formStateAttendance.value)
    .then(response => {
        const data = response.data;

        attendanceChart.value = [
            data.count.on_time,
            data.count.missed_timesheets_log,
            data.count.go_late
        ];
        percentAttendanceChart.value = [
            data.percent.on_time,
            data.percent.missed_timesheets_log,
            data.percent.go_late
        ];
        
    })
    .catch(error => {
        attendanceChart.value = [0,0,0]
        percentAttendanceChart.value = [0,0,0]
    });
}
const alerts = ref<Alert[]>([]);
const getAlerts = () => {
    axios.post('/api/home/get-alerts')
    .then(response => {
        alerts.value = response.data;
    })
    .catch(error => {
        alerts.value = []
    });
}
const formattedFromNow = (date: string | undefined) => {
    let dateOject = dayjs(date, 'YYYY/MM/DD HH:mm:ss');
    return dayjs(dateOject).fromNow();
}
const formatDateTime = (date: string | undefined) => {
    return dayjs(date).format('DD/MM/YYYY HH:mm:ss');
}
// Decide whether to show the top or bottom percent based on the condition
const showTopPercent = computed(() => {
    return effortTime.value.request_effort_time >= effortTime.value.last_effort_time;
});
const percentageDifference = computed(() => {
    const lastEffortTime = effortTime.value.last_effort_time;
    const requestEffortTime = effortTime.value.request_effort_time;

    if (requestEffortTime === 0 || lastEffortTime === 0) {
        return lastEffortTime === 0 && requestEffortTime === 0 ? 0 : 100;
    }

    return (
        ((requestEffortTime - lastEffortTime) / lastEffortTime) * 100
    );
});
// Calculate the percentage value and icon
const percentValue = computed(() => Math.abs(Math.round(percentageDifference.value)));
const calculatePercentageTasks = (data:any) => {
    const overdue = data.overdue
    const total = data.total
    if (overdue === 0 || total === 0) {
        return 0;
    }
    const percentage = (overdue / total) * 100;

    return Math.round(percentage);
}
const violations = ref<Array<Violation>>([])
const getViolations = () => {
    axios.post('/api/home/get-violations', formStateViolations.value)
    .then(response => {
        violations.value = response.data
    })
    .catch(error => {
        violations.value = []
    });
}
const generateTimekeepingContent = (column: Column, item: TimesheetObject) => {
    if (column.title.includes('CN')) {
        return '<div class="none-timekeep">Ngày nghỉ</div>';
    }

    if (item) {
        if (item.is_holiday !== undefined) {
            return '<div class="none-timekeep">Du lịch</div>';
        }

        if (item.long_leave !== undefined) {
            let label = 'Nghỉ một ngày'
            if ((item as { long_leave?: number }).long_leave === 2) {
                label = 'Nghỉ nhiều ngày'
            }
            return `<div class="none-timekeep">${label}</div>`;
        }

        let petitionsLength = 0;
        if (Array.isArray(item.petitions)) {
            petitionsLength = item.petitions.length;
        }
        return `
            <div class="time-to">${item.time}</div>
            <div class="description">Ra ngoài: <span>${item.go_out}</span></div>
            <div class="description">Yêu cầu: <span>${petitionsLength}</span></div>
        `;
    } else {
        return '<div class="none-timekeep">--</div>';
    }
}
const mobileTime = ref("---")
const mobileGoOut = ref()
const mobilePetition = ref()
const mobileWorkday = ref()
const isSelectDateMobile = ref()
const getTimesheetMobile = (item: any) => {
    const today = dayjs().format('YYYYMMDD')
    const start_week = dayjs(formStateTimesheet.value.start_date).format('YYYYMMDD');
    const end_week = dayjs(formStateTimesheet.value.end_date).format('YYYYMMDD');
    isSelectDateMobile.value = today
    
    if (today in item && today >= start_week && today <= end_week) {
        mobileTime.value = String(item[today].time);
        mobileGoOut.value = item[today].go_out;
        mobilePetition.value = Array.isArray(item[today].petitions) ? item[today].petitions.length : 0;
        mobileWorkday.value = item[today].workday;
    } else if ( start_week in item && (today < start_week || today > end_week)) {
        isSelectDateMobile.value = start_week
        mobileTime.value = String(item[start_week].time);
        mobileGoOut.value = item[start_week].go_out;
        mobilePetition.value = Array.isArray(item[start_week].petitions) ? item[start_week].petitions.length : 0;
        mobileWorkday.value = item[start_week].workday;
    } else {
        mobileTime.value = '---';
        mobileGoOut.value = 0;
        mobilePetition.value = 0;
        mobileWorkday.value = 0;
    }
}
const handleTimesheetMobile = (column: Column, item: TimesheetObject) => {
    getColorClassMobile(column, item);
    isSelectDateMobile.value = column.dataKey
    if (item) {
        mobileTime.value = String(item.time);
        mobileGoOut.value = item.go_out;
        mobilePetition.value = Array.isArray(item.petitions) ? item.petitions.length : 0;
        mobileWorkday.value = item.workday;
    } else {
        mobileTime.value = '---';
        mobileGoOut.value = 0;
        mobilePetition.value = 0;
        mobileWorkday.value = 0;
    }
}
const classMobile = ref('is-active');
const getTimesheetsClass = (column: Column, item: TimesheetObject) => {
    if (column.title.includes('CN')) {
        return 'is-dayoff';
    }

    if (column.isFuture) {
        return 'is-coming';
    }
    if (item) {
        //2 is employees has dayoff petitions
        if (item.is_holiday !== undefined || detectValue(item, 2) || item.long_leave !== undefined) {
            return 'is-holiday';
        }
        //1 is employees has go late or leave early petitions
        if (detectValue(item, 1) || item.is_going_out !== undefined || item.is_out_a_day !== undefined) {
            return 'is-petition-late-early';
        }

        if (item.punctuality_issue) {
            return 'is-late';
        }
    } else {
        return 'is-late'
    }

    if (parseInt(dayjs().format('YYYYMMDD'), 10) === column.dataKey) {
        return 'is-active';
    }
    
}
const getColorClassMobile = (column: Column, item: TimesheetObject) => {
    classMobile.value = '';
    if (item) {
        //2 is employees has dayoff petitions
        if (item.is_holiday !== undefined || detectValue(item, 2) || item.long_leave !== undefined) {
            classMobile.value = 'is-holiday';
        }
        //1 is employees has go late or leave early petitions
        if (detectValue(item, 1) || item.is_going_out !== undefined || item.is_out_a_day !== undefined) {
            classMobile.value = 'is-petition-late-early';
        }

        if (item.punctuality_issue) {
            classMobile.value = 'is-late';
        }
    } else {
        classMobile.value = 'is-late';
    }
    if (column.title.includes('CN')) {
        classMobile.value = 'is-dayoff';
    }

    if (column.isFuture) {
        classMobile.value = 'is-coming';
    }

    if (parseInt(dayjs().format('YYYYMMDD'), 10) === column.dataKey) {
        classMobile.value = 'is-active';
    }
}
const detectValue = (item: TimesheetObject, value: number) => {
    return Array.isArray(item.petitions) && item.petitions.includes(value)
}
const getTitleClass = (column: Column) => {
    if (parseInt(dayjs().format('YYYYMMDD'), 10) === column.dataKey) {
        return 'is-active';
    }

    return '';
}
const latestPosts = ref<Post[]>([]);

const eventUser = ref()
const getEventCalendar = () => {
    axios.post('/api/home/get-event-calendar')
    .then(response => { 
        eventUser.value = response.data;
    })
    .catch(error => {
        eventUser.value = []
    });
}

    onMounted(() => {
    var screenWidth = window.screen.width;
    if (screenWidth >= 1080) {
        hideMobile.value = true
        mode.value =  'Quản Lý' ;
    } else {
        hideMobile.value = false
        mode.value =  'QL' ;
        pieStyle.value = {height:'150px'}
    } 
    axios.get('/api/home/get-selectbox-work-total')
    .then(response => {
        departments.value = response.data.departments
        projects.value = response.data.projects
    })
    axios.get('/api/common/get_employees')
    .then(response => {
        users.value = response.data
    })
    axios.get('/api/home/get-info').then(response => {
        user.value = response.data

        getWork()

        getTimesheet()

        getWorkDetail()

        getEffortTime()

        getViolations()

        getProcessAttendance()

        getAlerts()

        getEventCalendar()

    })

    // Get forums latest posts
    axios.get('/api/forum/get-latest-posts').then(response => {
        latestPosts.value = response.data
    })
})
</script>