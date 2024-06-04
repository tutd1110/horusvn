<template>
    <el-row class="dashboard-container dashboard-container-admin">   
        <el-col :span="5" v-if="hideMobile">
            <div class="panel-infomation">
                <el-row class="panel-footer">
                    <el-col :span="24" class="title-head">
                        Thông báo chấm công
                    </el-col>
                    <el-scrollbar height="420px">
                        <el-col :span="24" class="event-item event-item-image" style="display: flex; align-items: center;" v-for="(timeKeepItem, key) in timeKeepingNotification">
                            <div class="event-image" style="flex: 1; display: flex;">
                                <el-avatar shape="square" :size="40" fit="cover" :src="'image/' + timeKeepItem.avatar" />
                            </div>
                            <div class="event-content" style="flex: 5;">
                                <div class="sub-title">{{timeKeepItem.fullname}}</div>
                                <div class="description">{{timeKeepItem.description}}</div>
                            </div>
                            <div class="time-event">{{timeKeepItem.time}}</div>
                        </el-col>
                    </el-scrollbar>
                </el-row>
                <el-row class="panel-footer">
                        <el-col :span="24" class="title-head">
                            Sự Kiện {{ currentDate.format('DD/MM/YYYY') }}
                        </el-col>
                        <el-scrollbar height="380px" style="width:100%; height: auto;">
                            <template v-for="(item) in eventManager">
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
                            <template v-for="(item) in birthdayManager">
                                <el-col :span="24" class="event-item">
                                    <div class="sub-title">{{ item.name }}{{item.department_id ? ' - '+item.department_id : ''}}</div>
                                    <div class="description"><span style="font-weight: 700; font-size: 12px; font-style:italic">Sinh nhật ngày hôm nay</span></div>
                                </el-col>
                            </template>
                        </el-scrollbar>
                        <el-col :span="24" class="title-footer">
                            <a href="/calendar">Xem thêm</a>
                        </el-col>
                    </el-row>
            </div>
        </el-col>
        <el-col :span="19" v-if="hideMobile">
            <el-row class="panel-dashboard">
                <el-col :span="24" class="title-dashboard">
                    Dashboard
                </el-col>
                <el-col :span="24" class="main-dashboard">
                    <el-row :gutter="20">
                        <el-col :span="18" class="panel-left">
                            <el-card shadow="hover" class="panel-total-work panel-total-work-manager">
                                <el-row :gutter="10" style="margin-bottom: 0;">
                                    <el-col :span="4" class="title-timekeep">Tổng công việc</el-col>
                                    <div class="flex-grow" />
                                    <el-col :span="4" v-if="user && user.position !== 1">
                                        <el-select 
                                            v-model="formStateWorkTotal.department_id" 
                                            class="m-2" 
                                            placeholder="Bộ phận"
                                            clearable
                                            filterable
                                        >
                                            <el-option
                                                v-for="item in departments"
                                                :key="item.id"
                                                :label="item.name"
                                                :value="item.id"
                                                />
                                        </el-select>
                                    </el-col>
                                    <el-col :span="4">
                                        <el-select 
                                            v-model="formStateWorkTotal.user_id"
                                            class="m-2" 
                                            placeholder="Cá nhân"
                                            clearable
                                            filterable
                                        >
                                            <el-option
                                                v-for="item in filteredUsers"
                                                    :key="item.id"
                                                    :label="item.fullname"
                                                    :value="item.id"
                                                />
                                        </el-select>
                                    </el-col>
                                    <el-col :span="4">
                                        <el-select 
                                            v-model="formStateWorkTotal.project_id" 
                                            class="m-2" 
                                            placeholder="Dự án"
                                            clearable
                                            filterable
                                        >
                                            <el-option
                                                v-for="item in projects"
                                                :key="item.id"
                                                :label="item.name"
                                                :value="item.id"
                                                />
                                        </el-select>
                                    </el-col>
                                    <el-col :span="5">
                                        <div class="date-work">
                                            <el-date-picker
                                                v-model="waitingWorkTotalDateRangePicker"
                                                type="daterange"
                                                range-separator="-"
                                                start-placeholder="Start"
                                                end-placeholder="End"
                                                format="DD/MM/YY"
                                                value-format="YYYY/MM/DD"
                                                style="width: 100%;"
                                                :clearable="false"
                                            />
                                        </div>
                                    </el-col>
                                    <el-col :span="2" style="margin-left: 15px;">
                                        <el-button type="primary" @click="getWorkTotal()" style="">Search</el-button>
                                    </el-col>
                                    
                                    <el-col :span="24" style="margin-top: 15px; ">
                                        <el-row :gutter="10" class="panel-work-detail" style="margin-bottom: 0px;">
                                            <el-col class="work-item">
                                                <el-card shadow="hover" class="work-item-card">
                                                    <div class="sub-title">Đang chờ</div>
                                                    <div class="description" style="color: #909399;">{{ waitingTasksTotal }}</div>
                                                    
                                                </el-card>
                                            </el-col>
                                            <el-col class="work-item">
                                                <el-card shadow="hover" class="work-item-card">
                                                    <div class="sub-title">Đang tiến hành</div>
                                                    <div class="description" style="color: #409EFF;">{{ tasksInProgressTotal }}</div>
                                                    
                                                </el-card>
                                            </el-col>
                                            <el-col class="work-item">
                                                <el-card shadow="hover" class="work-item-card">
                                                    <div class="sub-title">Tạm dừng</div>
                                                    <div class="description" style="color: #FFA500;">{{ tasksInPauseTotal }}</div>
                                                    
                                                </el-card>
                                            </el-col>
                                            <el-col class="work-item">
                                                <el-card shadow="hover" class="work-item-card">
                                                    <div class="sub-title">Chờ feedback</div>
                                                    <div class="description" style="color: #47AF0E;">{{ feedbackWaitingTasksTotal }}</div>
                                                </el-card>
                                            </el-col>
                                            <el-col class="work-item">
                                                <el-card shadow="hover" class="work-item-card">
                                                    <div class="sub-title">Hoàn thành</div>
                                                    <div class="description" style="color: #47AF0E;">{{ tasksInCompleteTotal }}</div>
                                                </el-card>
                                            </el-col>
                                            <el-col class="work-item">
                                                <el-card shadow="hover" class="work-item-card">
                                                    <div class="sub-title">ĐTH chưa nhập deadline</div>
                                                    <div class="description" style="color: #FF3838;">{{ tasksInProgressNoneDeadlineTotal }}</div>
                                                </el-card>
                                            </el-col>
                                            <el-col class="work-item">
                                                <el-card shadow="hover" class="work-item-card">
                                                    <div class="sub-title">Đang sửa Bug</div>
                                                    <div class="description" style="color: #e6a23c;">{{ fixingBug }}</div>
                                                </el-card>
                                            </el-col>
                                            <el-col class="work-item">
                                                <el-tooltip
                                                    effect="customized"
                                                    class="box-item"
                                                    placement="bottom-start"
                                                >
                                                    <template #content >
                                                        <el-scrollbar max-height="130px" style="min-width: 130px;">
                                                            <div class="tootip-item" v-for="(tooltip) in overdueTasksTotalList">
                                                                <span>{{ getDepartmentName(tooltip.department_id) }} - <strong style="color: #FF3838;">{{ tooltip.overdue }}</strong>/{{ tooltip.total }} ({{ (tooltip.overdue / tooltip.total * 100).toFixed(2) }}%)</span>
                                                            </div>                                                
                                                        </el-scrollbar>
                                                    </template>
                                                    <el-card shadow="hover" class="work-item-card">
                                                        <div class="sub-title sub-title-custom">Chậm deadline</div>
                                                        <div class="description-custom">
                                                            <div class="description" style="color: #FF3838;">{{ overdueTasksTotal.overdue }}<span style="font-size: 20px; color: #909399; font-weight: 400;">/{{ overdueTasksTotal.total }}</span></div>
                                                            <div class="progress">
                                                                <el-progress type="circle" :width="50" :percentage="calculatePercentageTasks(overdueTasksTotal)" :stroke-width="8"  color='#FF3838'/>
                                                            </div>
                                                        </div>
                                                    </el-card>
                                                </el-tooltip> 
                                            </el-col>
                                        </el-row>
                                    </el-col>
                                </el-row>
                            </el-card>
                            <el-card shadow="hover" class="panel-total-work">
                                <el-row :gutter="20" class="panel-work-detail" style="margin-bottom: 0;">
                                    <el-col :span="24">
                                        <el-row :gutter="10" style="margin-bottom: 0;">
                                            <el-col :span="16" class="title-timekeep">Chấm công</el-col>
                                            <div class="flex-grow" />
                                            <el-col :span="4" style="margin-right: ;" v-if="user && user.position !== 1">
                                                <el-select 
                                                v-model="timeKeepingDepartmentId" 
                                                class="m-2" 
                                                placeholder="Bộ phận"
                                                @change="getTimekeepingTotal"
                                                clearable
                                                filterable
                                                >
                                                    <el-option
                                                    v-for="item in departments"
                                                        :key="item.id"
                                                        :label="item.name"
                                                        :value="item.id"
                                                        />
                                                </el-select>
                                            </el-col>
                                            <el-col :span="4">
                                                <el-date-picker
                                                    v-model="dayTimekeepPicker"
                                                    type="date"
                                                    placeholder="Pick a day"
                                                    style="width: 100%;"
                                                    format="DD/MM/YYYY"
                                                    @change="getTimekeepingTotal"
                                                    :clearable="false"
                                                />
                                            </el-col>
                                        </el-row>
                                    </el-col>
                                    <el-col v-for="(card, index) in elCardWorkManager" :key="index" :span="4" style="margin-top: 10px;">
                                        <!-- v-if -->
                                        <el-tooltip
                                            effect="customized"
                                            class="box-item"
                                            placement="bottom-start"
                                            v-if="card.tooltip && card.tooltip.length > 0"
                                            style=" "
                                        >
                                            <template #content >
                                                <el-scrollbar max-height="130px" style="min-width: 130px;">
                                                    <div class="tootip-item" v-for="(tooltip) in card.tooltip">
                                                        <span>{{ tooltip }}</span>
                                                    </div>                                                   
                                                </el-scrollbar>
                                            </template>
                                            <el-card shadow="hover" class="card-item" :style="{ backgroundColor: card.color }">
                                                <div class="card-top-item" :style="{ color: card.text_color }">{{ card.value }}</div>
                                                <div class="card-bottom-item">{{ card.label }}</div>
                                            </el-card>
                                        </el-tooltip> 
                                        <!-- v-else  -->
                                        <el-card v-else shadow="hover" class="card-item" :style="{ backgroundColor: card.color }">
                                            <div class="card-top-item" :style="{ color: card.text_color }">{{ card.value }}</div>
                                            <div class="card-bottom-item">{{ card.label }}</div>
                                        </el-card>  
                                    </el-col>
                                </el-row>
                            </el-card>
                            
                            <el-row :gutter="20" class="panel-footer-main-dashboard">
                                <el-col :span="14" class="panel-forum">
                                    <el-card shadow="hover">
                                        <el-row :gutter="10" style="margin-bottom: 0;">
                                            <el-col :span="5" class="title">
                                                Top
                                            </el-col>
                                            <div class="flex-grow" />
                                            <el-col :span="7" class="title">
                                                <el-select 
                                                v-model="dataSelectBar" 
                                                class="m-2" 
                                                placeholder="Chọn biểu đồ"
                                                clearable
                                                filterable
                                                @change="getChartBar"
                                                >
                                                    <el-option
                                                        v-for="item in optionSelectBar"
                                                        :key="item.value"
                                                        :label="item.label"
                                                        :value="item.value"
                                                        />
                                                </el-select>
                                            </el-col>
                                            <el-col :span="6" class="title">
                                                <el-date-picker
                                                    class="date-chart"
                                                    v-model="monthChartBar"
                                                    type="month"
                                                    placeholder="Month"
                                                    format= "MM/YYYY"
                                                    style="width: 100%;"
                                                    @change="getChartBar"
                                                    :clearable="false"
                                                />
                                            </el-col>
                                            <el-col :span="5" class="title" style="margin-left: 15px;">
                                                <el-button type="primary" @click="handleViewDetailStatis" style="width: 100%;">Xem chi tiết</el-button>
                                            </el-col>
                                            <el-col :span="24" class="chart">
                                                <Bar
                                                    id="bar-chart"
                                                    :options="chartOptionsBar"
                                                    :data="chartDataBar"
                                                    :style="barStyle"
                                                />
                                            </el-col>
                                        </el-row>
                                    </el-card>
                                </el-col>
                                <el-col :span="10" class="panel-rules">
                                    <el-card shadow="hover">
                                        <el-row :gutter="10" class="panel-head">
                                            <el-col :span="8" class="title">Nội quy</el-col>
                                            <div class="flex-grow" />
                                            <el-col :span="8" class="title" v-if="user && user.position !== 1">
                                                <el-select 
                                                v-model="violationsTotalDepartmentId" 
                                                class="m-2" 
                                                placeholder="Bộ phận"
                                                @change="getViolationsTotal"
                                                clearable
                                                filterable
                                                >
                                                    <el-option
                                                    v-for="item in departments"
                                                    :key="item.id"
                                                    :label="item.name"
                                                    :value="item.id"
                                                        />
                                                </el-select>
                                            </el-col>
                                            <el-col :span="8" class="date-year-select">
                                                <el-date-picker
                                                    v-model="yearViolationsTotalPicker"
                                                    type="year"
                                                    placeholder="Chọn Năm"
                                                    style="width: 100%; max-width: none;"
                                                    @change="getViolationsTotal"
                                                    :clearable="false"
                                                />
                                            </el-col>
                                        </el-row>
                                        <el-scrollbar height="300px">
                                            <template v-for="(violation, idx2) in violationsTotal" :key="idx2">
                                                <div class="rules-item">
                                                    <div class="sub-title"><strong>{{violation.fullname}}</strong> : Vi phạm lần {{ violation.count }} ({{ dayjs(violation.time).format('DD/MM/YYYY')}})</div>
                                                    <div class="description">{{ violation.type }} ({{ violation.description }})</div>
                                                </div>
                                            </template>
                                        </el-scrollbar>
                                    </el-card>
                                </el-col>
                            </el-row>
                        </el-col>
                        <el-col :span="6" class="panel-right">
                            <el-card shadow="hover" class="panel-progress">
                                <div class="title">Thời gian</div>
                                <el-space class="panel-chart">
                                    <div class="chart">
                                        <Pie
                                            id="pie-chart-admin"
                                            :options="chartOptionsAdmin"
                                            :data="chartDataAdmin"
                                            :style="pieStyleAdmin"
                                        />
                                    </div>
                                </el-space>
                                <el-row :gutter="10" style="margin-bottom: 0;">
                                    <el-col :span="7">
                                        <el-select 
                                            v-model="deadlineProjectId" 
                                            class="m-2" 
                                            placeholder="Dự án"
                                            clearable
                                            filterable
                                            @change="getProcessDeadline"
                                        >
                                            <el-option
                                                v-for="item in projects"
                                                    :key="item.id"
                                                    :label="item.name"
                                                    :value="item.id"
                                                />
                                        </el-select>
                                    </el-col>
                                    <el-col :span="7" v-if="user &&user.position !== 1">
                                        <el-select 
                                            v-model="deadlineDepartmentId" 
                                            class="m-2" 
                                            placeholder="Bộ Phận"
                                            clearable
                                            filterable
                                            @change="getProcessDeadline"
                                        >
                                            <el-option
                                            v-for="item in departments"
                                                :key="item.id"
                                                :label="item.name"
                                                :value="item.id"
                                                />
                                        </el-select>
                                    </el-col>
                                    <el-col :span="10">
                                        <el-date-picker
                                            v-model="waitingPieChartDateRangePicker"
                                            type="daterange"
                                            range-separator="-"
                                            start-placeholder="Start"
                                            end-placeholder="End"
                                            format="DD/MM"
                                            value-format="YYYY/MM/DD"
                                            @change="getProcessDeadline"
                                            style="width: 100%;"
                                            :clearable="false"
                                        />
                                    </el-col>
                                </el-row>
                            </el-card>
                            <el-card shadow="hover" class="panel-notification" style="height: 520px;">
                                <div class="title">Thông báo</div>
                                <el-scrollbar height="450px">
                                    <div class="notifi-item" v-for="(alert, inx3) in alertsManager" :key="inx3">
                                        <div class="notifi-image">
                                            <el-avatar shape="square" :size="40" fit="cover" :src="'image/' + alert.avatar" />
                                        </div>
                                        <div class="notifi-content">
                                            <a class="sub-title" v-html="alert.description"></a>
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
        <el-col :span="24" v-if="!hideMobile">
            <el-row class="panel-dashboard">
                <el-col :span="24" class="main-dashboard">
                    <el-col :span="24" class="panel-left">
                        <div class="panel-total-work">
                            <el-row :gutter="10" class="panel-work-detail" style="margin-bottom: 0;">
                                <el-col :span="24">
                                    <el-row :gutter="6" style="margin-bottom: 0;">
                                        <el-col :span="10" class="title-timekeep">Chấm công</el-col>
                                        <div class="flex-grow" />
                                        <el-col :span="7" style="margin-right: ;" v-if="user && user.position !== 1">
                                            <el-select 
                                                v-model="timeKeepingDepartmentId" 
                                                class="m-2" 
                                                placeholder="Bộ phận"
                                                @change="getTimekeepingTotal"
                                                clearable
                                                filterable
                                            >
                                                <el-option
                                                v-for="item in departments"
                                                    :key="item.id"
                                                    :label="item.name"
                                                    :value="item.id"
                                                    />
                                            </el-select>
                                        </el-col>
                                        <el-col :span="7">
                                            <el-date-picker
                                                v-model="dayTimekeepPicker"
                                                type="date"
                                                placeholder="Pick a day"
                                                style="width: 100%;"
                                                format="DD/MM/YYYY"
                                                @change="getTimekeepingTotal"
                                                :clearable="false"
                                            />
                                        </el-col>
                                    </el-row>
                                </el-col>
                                <el-col v-for="(card, index) in elCardWorkManager" :key="index" :span="8" style="margin-top: 10px;">
                                    <!-- v-if -->
                                    <el-tooltip
                                        effect="customized"
                                        class="box-item"
                                        placement="bottom-start"
                                        v-if="card.tooltip && card.tooltip.length > 0"
                                        style=" "
                                    >
                                        <template #content >
                                            <el-scrollbar max-height="130px" style="min-width: 130px;">
                                                <div class="tootip-item" v-for="(tooltip) in card.tooltip">
                                                    <span>{{ tooltip }}</span>
                                                </div>                                                   
                                            </el-scrollbar>
                                        </template>
                                        <el-card shadow="hover" class="card-item" :style="{ backgroundColor: card.color }">
                                            <div class="card-top-item" :style="{ color: card.text_color }">{{ card.value }}</div>
                                            <div class="card-bottom-item">{{ card.label }}</div>
                                        </el-card>
                                    </el-tooltip> 
                                    <!-- v-else  -->
                                    <el-card v-else shadow="hover" class="card-item" :style="{ backgroundColor: card.color }">
                                        <div class="card-top-item" :style="{ color: card.text_color }">{{ card.value }}</div>
                                        <div class="card-bottom-item">{{ card.label }}</div>
                                    </el-card>  
                                </el-col>
                            </el-row>
                        </div>
                    </el-col>
                </el-col>
            </el-row>
            <div class="panel-infomation">
                <el-row class="panel-footer">
                    <el-col :span="24" class="title-head">
                        Thông báo chấm công
                    </el-col>
                    <el-scrollbar height="420px">
                        <el-col :span="24" class="event-item event-item-image" style="display: flex; align-items: center;" v-for="(timeKeepItem, key) in timeKeepingNotification">
                            <div class="event-image" style="flex: 1; display: flex;">
                                <el-avatar shape="square" :size="40" fit="cover" :src="'image/' + timeKeepItem.avatar" />
                            </div>
                            <div class="event-content" style="flex: 5;">
                                <div class="sub-title">{{timeKeepItem.fullname}}</div>
                                <div class="description">{{timeKeepItem.description}}</div>
                            </div>
                            <div class="time-event">{{timeKeepItem.time}}</div>
                        </el-col>
                    </el-scrollbar>
                </el-row>
            </div>
        </el-col>
    </el-row>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { onMounted, computed, ref, watch } from 'vue';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Pie, Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement, CategoryScale, BarElement, LinearScale} from 'chart.js'

dayjs.Ls.en.weekStart = 1
dayjs.extend(relativeTime)
ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale, BarElement, LinearScale)
 
interface User {
    id: number,
    fullname: string,
    avatar: string,
    position: number,
    department_id: number,
    date_official_DMY: string,
    total_date_official: string
};
interface WorkAdmin {
    check_in_success: number,
    check_in_none: number,
    late_total: number,
    petition_sick: number,
    request_petition: number,
    request_deadline: number,
    name_check_in_none: any,
    name_late_total: any,
    name_petition_sick: any,
};
interface TimesheetObject {
    [key: string]: Timesheet
}
interface Timesheet {
    time: string;
    go_out: number;
    petitions: number[];
    is_holiday?: boolean;
    long_leave?: number;
    punctuality_issue: boolean,
    is_going_out?: boolean,
    is_out_a_day?: boolean
}
interface Violation {
    type: string,
    description: string,
    fullname: string,
    count: string,
    time: string,
}
interface Alert {
    name?: string,
    datetime?: string,
    description?: string,
    type: number,
    status: number,
    rejected_reason?: string,
    label?: string
    avatar?: string
}
interface TimeKeeping {
    name?: string,
    fullname?: string,
    time?: string,
    description?: string,
    type?: number,
    avatar?: string
}
interface OverDue {
    total: number,
    overdue: number,
    department_id?: any,
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
    name: string,
    label: string,
    value: string
};
const user = ref<User>();
const currentDate = dayjs()
const startOfMonth = currentDate.startOf('month').format('YYYY/MM/DD')
const endOfMonth = currentDate.endOf('month').format('YYYY/MM/DD')
const yearViolationsTotalPicker = ref(currentDate.startOf('year').format('YYYY/MM/DD'))
const weekTimesheetPicker = ref(currentDate.startOf('week').format('YYYY/MM/DD'));
const dayTimekeepPicker = ref(currentDate.format('YYYY/MM/DD'))
const monthChartBar = ref(startOfMonth)

const deadlineChart = ref([0, 0]);
const percentDeadlineChart = ref([0, 0]);
const dataBarChart = ref([0, 0, 0, 0, 0]);
const lateBarChart = ref([0, 0, 0, 0, 0]);
const labelBarChart = ref<string[]>([]);

const dataSelectBar = ref(1)
interface OptionSelect {
  value: number;
  label: string;
  ticks: string;
}

const optionSelectBar: OptionSelect[] = [
    {
        value: 1,
        label: 'Tổng giờ nỗ lực',
        ticks: '(h)'
    },
    {
        value: 2,
        label: 'Thời gian đi sớm',
        ticks: '(h)'
    },
    {
        value: 3,
        label: 'Thời gian về muộn',
        ticks: '(h)'
    },
    {
        value: 4,
        label: 'Vi phạm kỷ luật',
        ticks: ''
    },
    {
        value: 5,
        label: 'Tỉ lệ đi muộn',
        ticks: '(%)'
    },
];
// setup chart bar
const chartDataBar = computed(() =>({
    labels: labelBarChart.value.map(label => label.split(' ').reduce((acc:any, cur:any, index: any, arr:any) => {
        if (index % 2 === 0) {
            acc.push(arr.slice(index, index + 2).join(' '));
        }
        return acc;
    }, [])),
    datasets: [{
        label: '',
        data: dataBarChart.value,
        percent: lateBarChart.value,
        backgroundColor: [
            '#409EFF'
        ],
        borderColor: [
            '#409EFF'
        ],
        borderWidth: 1,
        barThickness: 35,
        borderRadius: 3,
        
        
    }],
}))
const chartOptionsBar = computed(() =>({
    scales: {
        x: {
            grid: {
                drawOnChartArea: false,
            },
            ticks: {
                autoSkip: false,
                maxRotation: 0,
                minRotation: 0,
            },
        },
        y: {
            beginAtZero: true,
            grid: {
                drawOnChartArea: true,
            },
            ticks: {
                callback: (value:any, index:number) => {
                    if (index === 0) {
                        const selectedOption = optionSelectBar.find(option => option.value === formStateChartBar.value.option);
                        const ticks = selectedOption ? selectedOption.ticks : '';
                        return value + ' ' + ticks;
                    }
                    return value;
                },
            }, 
        },
  },
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
        callbacks: {
            label: (context:any) => {
                const data = context.dataset.data[context.dataIndex];
                const percent = context.dataset.percent[context.dataIndex];
                 
                const labelText = (formStateChartBar.value.option == 5) ? data.toFixed(2) + '% (' + percent + ' lần)' : data.toFixed(2)
                return labelText;
            },
        },
    },
  },
}))
const barStyle = ref({
    height: '305px'
})
// setup chart pie
const chartDataAdmin = computed(() =>({
    labels: ["Đúng deadline", "Chậm deadline"],
    datasets: [ {
        backgroundColor: [
            '#47AF0E',
            '#FF3838',
        ],
        data: deadlineChart.value,
        percent: percentDeadlineChart.value,
        borderWidth: 1,
    } ]
}));
const chartOptionsAdmin = ref({
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
const pieStyleAdmin = ref({
    height: '165px'
})

const timeKeepingDepartmentId = ref(null);
const formTimeKeepingTotal = computed(() => {
  return {
    start_date: dayjs(dayTimekeepPicker.value).format('YYYY/MM/DD'),
    end_date: dayjs(dayTimekeepPicker.value).format('YYYY/MM/DD'),
    department_id: timeKeepingDepartmentId.value,
  };
});
const timeKeepingTotal = ref<WorkAdmin>();

const getTimekeepingTotal = () => {
    axios.post('/api/home/get-time-keeping-total', formTimeKeepingTotal.value)
    .then(response => {
        timeKeepingTotal.value = response.data
    })
    .catch(error => {
        
    });
}

const elCardWorkManager = computed(() => [
    { color: '#C0F6C8', text_color: '#47AF0E', label: 'Checkin', value: timeKeepingTotal.value?.check_in_success || 0 },
    { color: '#FFD6D6', text_color: '#FF6060', label: 'Chưa checkin', value: timeKeepingTotal.value?.check_in_none || 0, tooltip: timeKeepingTotal.value?.name_check_in_none || []},
    { color: '#FFF4C4', text_color: '#DAB422', label: 'Đi muộn', value: timeKeepingTotal.value?.late_total || 0, tooltip: timeKeepingTotal.value?.name_late_total || [] },
    { color: '#D7B5EC', text_color: '#7A0BC7', label: 'Nghỉ phép', value: timeKeepingTotal.value?.petition_sick || 0, tooltip: timeKeepingTotal.value?.name_petition_sick || []},
    { color: '#E7C2C2', text_color: '#800000', label: 'Yêu cầu', value: timeKeepingTotal.value?.request_petition || 0 },
    { color: '#FFE2AD', text_color: '#FFA500', label: 'Yêu cầu Deadline', value: timeKeepingTotal.value?.request_deadline || 0  },
]);

const users = ref<Array<User>>([]);
const departments = ref<Array<Department>>([]);
const projects = ref<Array<Department>>([]);
const filteredUsers = computed(() => {
    const selectedDepartmentId = (user.value != undefined && user.value.position == 1 ) ? user.value.department_id : formStateWorkTotal.value.department_id;
    if (selectedDepartmentId != undefined) {
        return users.value.filter(user => user.department_id === selectedDepartmentId);
    } else {
        return users.value;
    }
});

const waitingWorkTotalDateRangePicker = ref<[string, string]>([
    startOfMonth,
    endOfMonth
]);
const waitingPieChartDateRangePicker = ref<[string, string]>([
    startOfMonth,
    endOfMonth
]);
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
watch(waitingWorkTotalDateRangePicker, ([startDate, endDate]) => {
    formStateWorkTotal.value.start_date = startDate;
    formStateWorkTotal.value.end_date = endDate;
});

const violationsTotalDepartmentId = ref(null)
const formStateViolationsTotal = computed(() => {
    return {
        start_date: dayjs(yearViolationsTotalPicker.value).startOf('year').format('YYYY/MM/DD'),
        end_date: dayjs(yearViolationsTotalPicker.value).endOf('year').format('YYYY/MM/DD'),
        department_id: violationsTotalDepartmentId.value
    };
});
const deadlineDepartmentId = ref(null);
const deadlineProjectId = ref(null);
const formStateDeadline = computed(() => {
    return {
        start_date: waitingPieChartDateRangePicker.value[0],
        end_date: waitingPieChartDateRangePicker.value[1],
        department_id: deadlineDepartmentId.value,
        project_id: deadlineProjectId.value,
    };
});

const formStateChartBar = computed(() => {
    return {
        start_date: dayjs(monthChartBar.value).startOf('month').format('YYYY/MM/DD'),
        end_date: dayjs(monthChartBar.value).endOf('month').format('YYYY/MM/DD'),
        option: dataSelectBar.value,
    };
});

const waitingTasks = ref(0)
const tasksInProgress = ref(0)
const overdueTasks = ref<OverDue>(defaultEmptyOverDue)
const workTime = ref(0)

const waitingTasksTotal = ref(0)
const tasksInProgressTotal = ref(0)
const tasksInPauseTotal = ref(0)
const feedbackWaitingTasksTotal = ref(0)
const tasksInCompleteTotal = ref(0)
const tasksInProgressNoneDeadlineTotal = ref(0)
const overdueTasksTotal = ref<OverDue>(defaultEmptyOverDue)
const overdueTasksTotalList = ref<OverDue>(defaultEmptyOverDue)
const fixingBug = ref(0)

const getWorkTotal = () => {
    axios.post('/api/home/get-work-total', formStateWorkTotal.value)
    .then(response => {
        waitingTasksTotal.value = response.data.waitingTasks
        tasksInProgressTotal.value = response.data.tasksInProgress
        tasksInPauseTotal.value = response.data.tasksInPause
        feedbackWaitingTasksTotal.value = response.data.feedbackWaitingTasks
        tasksInCompleteTotal.value = response.data.tasksInComplete
        tasksInProgressNoneDeadlineTotal.value = response.data.tasksInProgressNoneDeadline
        overdueTasksTotal.value = response.data.overdueTasks[0]
        overdueTasksTotalList.value = response.data.overdueTaskList
        fixingBug.value = response.data.fixingBug
    })
    .catch(error => {
        waitingTasks.value = 0
        tasksInProgress.value = 0
        overdueTasks.value = defaultEmptyOverDue
        workTime.value = 0       
    });
}
const getProcessDeadline = () => {
    axios.post('/api/home/get-process-deadline', formStateDeadline.value)
    .then(response => {
        const data = response.data;
        deadlineChart.value = [
            data.count.complete,
            data.count.deadline
        ];

        percentDeadlineChart.value = [
            data.percent.complete,
            data.percent.deadline
        ];             
    })
    .catch(error => {
        deadlineChart.value = [0,0]
        percentDeadlineChart.value = [0,0]
    });
}
const getChartBar = () => {
    axios.post('/api/home/get-chart-bar', formStateChartBar.value)
    .then(response => {
        const data = response.data;
        labelBarChart.value = data.fullname
        dataBarChart.value = data.dataChart
        lateBarChart.value = (formStateChartBar.value.option == 5) ? data.late : [];
    })
    .catch(error => {
        labelBarChart.value = []
        dataBarChart.value = [0,0,0,0,0]
    });
}

const alertsManager = ref<Alert[]>([]);
const getAlerts = () => {
    axios.post('/api/home/get-alerts-manager')
    .then(response => { 
        alertsManager.value = response.data;
    })
    .catch(error => {
        alertsManager.value = []
    });
}

const eventManager = ref()
const birthdayManager = ref()
const getEventCalendarManager = () => {
    axios.post('/api/home/get-event-calendar-manager')
    .then(response => { 
        eventManager.value = response.data.data;
        birthdayManager.value = response.data.birthday;
    })
    .catch(error => {
        eventManager.value = []
        birthdayManager.value = []
    });
}

const timeKeepingNotification = ref<TimeKeeping[]>([]);
const getTimeKeeping = () => {
    axios.get('/api/home/get-notification-timekeeping')
    .then(response => {
        timeKeepingNotification.value = response.data;
    })
    .catch(error => {
        timeKeepingNotification.value = []
    });
}

const formattedFromNow = (date: string | undefined) => {
    let dateOject = dayjs(date, 'YYYY/MM/DD HH:mm:ss');
    return dayjs(dateOject).fromNow();
}

const calculatePercentageTasks = (data:any) => {
    const overdue = data.overdue
    const total = data.total
    if (overdue === 0 || total === 0) {
        return 0;
    }
    const percentage = (overdue / total) * 100;

    return Math.round(percentage);
}
const getDepartmentName = (data:any) => {
    const department = departments.value.find(item => item.id === data);
    return department ? department.name : '';
}

const violationsTotal = ref<Array<Violation>>([])
const getViolationsTotal = () => {
    axios.post('/api/home/get-violation-total', formStateViolationsTotal.value)
    .then(response => {
        violationsTotal.value = response.data
    })
    .catch(error => {
        violationsTotal.value = []
    });
}
const handleViewDetailStatis = ()=> {
    window.location.href = '/statistial';  
}
const hideMobile = ref(true)
onMounted(() => {
    var screenWidth = window.screen.width;
    if (screenWidth >= 1080) {
        hideMobile.value = true
    } else {
        hideMobile.value = false
    } 
    axios.get('/api/home/get-selectbox-work-total')
    .then(response => {
        departments.value = response.data.departments
        projects.value = response.data.projects
        users.value = response.data.users    

    })
    axios.get('/api/home/get-info').then(response => {
        user.value = response.data

        getWorkTotal()

        getProcessDeadline()
        
        getAlerts()
        
        getTimeKeeping()
        
        getTimekeepingTotal()

        getViolationsTotal()

        getChartBar()

        getEventCalendarManager()
    })
})
</script>