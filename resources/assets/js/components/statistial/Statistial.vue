<template>
    <div class="statis-wrapper">
        <!-- row filter -->
        <el-row :gutter="10">
            <el-col :span="3" class="custom-filter">
                <el-date-picker
                v-model="defaultDateRangePicker"
                type="daterange"
                range-separator="-"
                start-placeholder="start"
                end-placeholder="end"
                :size="'default'"
                format="DD/MM/YY"
                :clearable="false"
                />
            </el-col>
            <el-col :span="3" class="custom-filter">
                <el-select
                    v-model="formState.department_id"
                    value-key="id"
                    placeholder="Bộ phận"
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
            <el-col :span="2" class="custom-filter">
                <el-space size="small" spacer="|">
                    <el-button type="primary" v-on:click="search()" :loading="loadingSearch">Search</el-button>
                    <el-button type="success" v-on:click="handleExport('all')" :loading="loadingExport">Export All</el-button>
                </el-space>
            </el-col>
        </el-row>
        
        <!-- chart wrapper -->
        <div class="chart-wrapper" v-loading="loadingSearch">
            <el-row :gutter="20" class="">
                <el-col :lg="12" :md="12" :xs="24" class="panel-forum first-chart">
                    <el-card shadow="hover">
                        <el-row :gutter="10" style="margin-bottom: 0;">
                            <div class="chart-action">
                                <div class="chart-action__left">
                                    Thời gian đi sớm
                                </div>
                                <div class="chart-action__right">
                                    <div class="action">
                                        <div class="sort">
                                            <button @click="toggleSortType(CHART_KEY.go_early_total)" class="btn-sort">
                                                <el-icon><DCaret /></el-icon>
                                            </button>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.go_early_total)" :class="{'in-active': filterOptions.go_early_total.sort_type == 'topFiveLowest'}">Cao đến thấp</span>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.go_early_total)" :class="{'in-active': filterOptions.go_early_total.sort_type == 'topFiveHighest'}">Thấp đến cao</span>
                                        </div>
                                        <el-select
                                            v-model="filterOptions.go_early_total.sort_quantity"
                                            filterable
                                            placeholder=""
                                            style="width: 60px; display: flex; align-items: center;"
                                            @change="toggleSortQty(CHART_KEY.go_early_total)"
                                        >
                                        <el-option
                                            v-for="item in sort_value_options"
                                            :key="item.value"
                                            :label="item.label"
                                            :value="item.value"
                                        />
                                        </el-select>
                                        <div>
                                            <el-button type="primary" :loading="loadingExport" @click="handleExport('go_early_total')">Export</el-button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <el-col :span="24" class="chart">
                                <Bar
                                    id="bar-chart"
                                    :options="chartOptionsBar('(h)')"
                                    :data="chartDataBarGoEarly"
                                    :style="barChartStyle"
                                />
                            </el-col>
                        </el-row>
                    </el-card>
                </el-col>
                <el-col :lg="12" :md="12" :xs="24" class="panel-forum">
                    <el-card shadow="hover">
                        <el-row :gutter="10" style="margin-bottom: 0;">
                            <div class="chart-action">
                                <div class="chart-action__left">
                                    Thời gian về  muộn
                                </div>
                                <div class="chart-action__right">
                                    <div class="action">
                                        <div class="sort">
                                            <button @click="toggleSortType(CHART_KEY.leave_late_total)" class="btn-sort">
                                                <el-icon><DCaret /></el-icon>
                                            </button>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.leave_late_total)" :class="{'in-active': filterOptions.leave_late_total.sort_type == 'topFiveLowest'}">Cao đến thấp</span>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.leave_late_total)" :class="{'in-active': filterOptions.leave_late_total.sort_type == 'topFiveHighest'}">Thấp đến cao</span>
                                        </div>
                                        <el-select
                                            v-model="filterOptions.leave_late_total.sort_quantity"
                                            filterable
                                            placeholder=""
                                            style="width: 60px; display: flex; align-items: center;"
                                            @change="toggleSortQty(CHART_KEY.leave_late_total)"
                                        >
                                            <el-option
                                                v-for="item in sort_value_options"
                                                :key="item.value"
                                                :label="item.label"
                                                :value="item.value"
                                            />
                                        </el-select>
                                        <div>
                                            <el-button type="primary" :loading="loadingExport" @click="handleExport('leave_late_total')">Export</el-button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                <el-col :span="24" class="chart">
                                    <Bar
                                        id="bar-chart"
                                        :options="chartOptionsBar('(h)')"
                                        :data="chartDataBarLeaveLate"
                                        :style="barChartStyle"
                                    />
                                </el-col>
                        </el-row>
                    </el-card>
                </el-col>
            </el-row>

            <!-- row chart warrior -->
            <el-row :gutter="20" class="">
                <el-col :lg="15" :md="15" :xs="24" class="panel-forum first-chart">
                    <WarriorYear ref="warriorComponent" :departments="departments" />
                </el-col>
                <el-col :lg="9" :md="9" :xs="24" class="panel-forum table-bold">
                    <el-card shadow="hover">
                        <el-row :gutter="10" style="margin-bottom: 0;">
                            <div class="chart-action">
                                <div class="chart-action__left">
                                    Tổng số công đi làm
                                </div>
                                <div class="chart-action__right">                       
                                    <div class="action">
                                        <div class="sort">
                                            <button @click="toggleSortType(CHART_KEY.total_day_work)" class="btn-sort">
                                                <el-icon><DCaret /></el-icon>
                                            </button>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.total_day_work)" :class="{'in-active': filterOptions.total_day_work.sort_type == 'topFiveLowest'}">Cao đến thấp</span>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.total_day_work)" :class="{'in-active': filterOptions.total_day_work.sort_type == 'topFiveHighest'}">Thấp đến cao</span>
                                        </div>
                                        <el-select
                                            v-model="filterOptions.total_day_work.sort_quantity"
                                            filterable
                                            placeholder=""
                                            style="width: 60px; display: flex; align-items: center;"
                                            @change="toggleSortQty(CHART_KEY.total_day_work)"
                                        >
                                        <el-option
                                            v-for="item in sort_value_options"
                                            :key="item.value"
                                            :label="item.label"
                                            :value="item.value"
                                        />
                                        </el-select>
                                        <div>
                                            <el-button type="primary" :loading="loadingExport" @click="handleExport('total_day_work')">Export</el-button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <el-col :span="24" class="chart">
                                <el-table v-if="totalWorkDayData" :data="totalWorkDayData" class="table-total-work-day" height="670" style="width:100%;">
                                    <el-table-column label="Member">
                                        <template #default="scope">
                                            <div class="user-info">
                                                <el-avatar :src="getFullPathAvatar(scope.row.avatar)" />
                                                <div class="user-details">
                                                    <span class="fullname">{{ scope.row.fullname }}</span>
                                                    <span class="duration">{{ getDurationWorkDay(scope.row.date_official) }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="Tổng số công" width="180">
                                        <template #default="scope">
                                            <span>{{ formatDecimal(scope.row.total_day_work) }}</span>
                                        </template>
                                    </el-table-column>
                                </el-table>
                            </el-col>   
                        </el-row>
                    </el-card>
                </el-col>
            </el-row>

            <el-row :gutter="20" class="">
                <el-col :lg="12" :md="12" :xs="24" class="panel-forum first-chart">
                    <el-card shadow="hover">
                        <el-row :gutter="10" style="margin-bottom: 0;">
                            <div class="chart-action">
                                <div class="chart-action__left">
                                    Tổng giờ nỗ  lực
                                </div>
                                <div class="chart-action__right">
                                    <div class="action">
                                        <div class="sort">
                                            <button @click="toggleSortType(CHART_KEY.total_effort_hour)" class="btn-sort">
                                                <el-icon><DCaret /></el-icon>
                                            </button>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.total_effort_hour)" :class="{'in-active': filterOptions.total_effort_hour.sort_type == 'topFiveLowest'}">Cao đến thấp</span>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.total_effort_hour)" :class="{'in-active': filterOptions.total_effort_hour.sort_type == 'topFiveHighest'}">Thấp đến cao</span>
                                        </div>
                                        <el-select
                                            v-model="filterOptions.total_effort_hour.sort_quantity"
                                            filterable
                                            placeholder=""
                                            style="width: 60px; display: flex; align-items: center;"
                                            @change="toggleSortQty(CHART_KEY.total_effort_hour)"
                                        >
                                        <el-option
                                            v-for="item in sort_value_options"
                                            :key="item.value"
                                            :label="item.label"
                                            :value="item.value"
                                        />
                                        </el-select>
                                        <div>
                                            <el-button type="primary" :loading="loadingExport" @click="handleExport('total_effort_hour')">Export</el-button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <el-col :span="24" class="chart">
                                <Bar
                                    id="bar-chart"
                                    :options="chartOptionsBar('(h)')"
                                    :data="chartDataBarEffortHour"
                                    :style="barChartStyle"
                                />
                            </el-col>
                        </el-row>
                    </el-card>
                </el-col>
                <el-col :lg="12" :md="12" :xs="24" class="panel-forum">
                    <el-card shadow="hover">
                        <el-row :gutter="10" style="margin-bottom: 0;">
                            <div class="chart-action">
                                <div class="chart-action__left">
                                    Vi phạm kỉ luật
                                </div>
                                <div class="chart-action__right">
                                    <div class="action">
                                        <div class="sort">
                                            <button @click="toggleSortType(CHART_KEY.violations)" class="btn-sort">
                                                <el-icon><DCaret /></el-icon>
                                            </button>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.violations)" :class="{'in-active': filterOptions.violations.sort_type == 'topFiveLowest'}">Cao đến thấp</span>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.violations)" :class="{'in-active': filterOptions.violations.sort_type == 'topFiveHighest'}">Thấp đến cao</span>
                                        </div>
                                        <el-select
                                            v-model="filterOptions.violations.sort_quantity"
                                            filterable
                                            placeholder=""
                                            style="width: 60px; display: flex; align-items: center;"
                                            @change="toggleSortQty(CHART_KEY.violations)"
                                        >
                                        <el-option
                                            v-for="item in sort_value_options"
                                            :key="item.value"
                                            :label="item.label"
                                            :value="item.value"
                                        />
                                        </el-select>
                                        <div>
                                            <el-button type="primary" :loading="loadingExport" @click="handleExport('violations')">Export</el-button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <el-col :span="24" class="chart">
                                <Bar
                                    id="bar-chart"
                                    :options="chartOptionsBar('(lần)')"
                                    :data="chartDataBarViolations"
                                    :style="barChartStyle"
                                />
                            </el-col>
                        </el-row>
                    </el-card>
                </el-col>
            </el-row>

            <el-row :gutter="20" class="">
                <el-col :lg="15" :md="15" :xs="24" class="panel-forum first-chart chart-rate-late">
                    <el-card shadow="hover">
                        <el-row :gutter="10" style="margin-bottom: 0;">
                            <div class="chart-action" style="max-height: 32px;">
                                <div class="chart-action__left">
                                    Tỉ lệ đi muộn
                                </div>
                                <div class="chart-action__right">
                                    <div class="action">
                                        <div class="sort">
                                            <button @click="toggleSortType(CHART_KEY.rate_late)" class="btn-sort">
                                                <el-icon><DCaret /></el-icon>
                                            </button>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.rate_late)" :class="{'in-active': filterOptions.rate_late.sort_type == 'topFiveLowest'}">Cao đến thấp</span>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.rate_late)" :class="{'in-active': filterOptions.rate_late.sort_type == 'topFiveHighest'}">Thấp đến cao</span>
                                        </div>
                                        <el-select
                                            v-model="filterOptions.rate_late.sort_quantity"
                                            filterable
                                            placeholder=""
                                            style="width: 60px; display: flex; align-items: center;"
                                            @change="toggleSortQty(CHART_KEY.rate_late)"
                                        >
                                        <el-option
                                            v-for="item in sort_value_options"
                                            :key="item.value"
                                            :label="item.label"
                                            :value="item.value"
                                        />
                                        </el-select>
                                        <div>
                                            <el-button type="primary" :loading="loadingExport" @click="handleExport('rate_late')">Export</el-button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <el-col :span="24" class="chart">
                                <Bar
                                    id="bar-chart"
                                    :options="chartOptionsBar('(%)')"
                                    :data="chartRateLateDataBar"
                                    :style="barChartStyle"
                                />
                            </el-col>
                        </el-row>
                    </el-card>
                </el-col>
                <el-col :lg="9" :md="9" :xs="24" class="panel-forum">
                    <el-card shadow="hover">
                        <el-row :gutter="10" style="margin-bottom: 0;">
                            <div class="chart-action">
                                <div class="chart-action__left">
                                    Tỉ lệ nghỉ
                                </div>
                                <div class="chart-action__right">
                                    <div class="action">
                                        <div class="sort">
                                            <button @click="toggleSortType(CHART_KEY.rate_dayoff)" class="btn-sort">
                                                <el-icon><DCaret /></el-icon>
                                            </button>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.rate_dayoff)" :class="{'in-active': filterOptions.rate_dayoff.sort_type == 'topFiveLowest'}">Cao đến thấp</span>
                                            <span class="text-sort" @click="toggleSortType(CHART_KEY.rate_dayoff)" :class="{'in-active': filterOptions.rate_dayoff.sort_type == 'topFiveHighest'}">Thấp đến cao</span>
                                        </div>
                                        <el-select
                                            v-model="filterOptions.rate_dayoff.sort_quantity"
                                            filterable
                                            placeholder=""
                                            style="width: 60px; display: flex; align-items: center;"
                                            @change="toggleSortQty(CHART_KEY.rate_dayoff)"
                                        >
                                        <el-option
                                            v-for="item in sort_value_options"
                                            :key="item.value"
                                            :label="item.label"
                                            :value="item.value"
                                        />
                                        </el-select>
                                        <div>
                                            <el-button type="primary" :loading="loadingExport" @click="handleExport('rate_dayoff')">Export</el-button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <el-col :span="24" class="chart">
                                <el-table v-if="rateDayoffData" :data="rateDayoffData" class="table-rate-late" height="355" style="width:100%;">
                                    <el-table-column label="Member">
                                        <template #default="scope">
                                            <div class="user-info">
                                                <el-avatar :src="getFullPathAvatar(scope.row.avatar)" />
                                                <div class="user-details">
                                                    <span class="fullname">{{ scope.row.fullname }}</span>
                                                    <span class="duration">{{ getDurationWorkDay(scope.row.date_official) }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </el-table-column>
                                    <el-table-column label="Tỉ lệ nghỉ" width="180">
                                        <template #default="scope">
                                            <span>{{ formatDecimal(scope.row.rate_dayoff) }}%</span>
                                        </template>
                                    </el-table-column>
                                </el-table>
                            </el-col>
                        </el-row>
                    </el-card>
                </el-col>
            </el-row>
        </div>
    </div>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { onMounted, ref, computed, watch, reactive } from 'vue';
import { Bar } from 'vue-chartjs';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { DCaret } from '@element-plus/icons-vue'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
import { callMessage } from '../Helper/el-message';
import WarriorYear from './WarriorYear.vue';
import { downloadFile } from '../Helper/export';
import { useI18n } from 'vue-i18n';

// sort
const filterOptions = ref({
    go_early_total:{
        sort_type:'topFiveHighest',
        sort_quantity: 5,
    },
    leave_late_total:{
        sort_type: 'topFiveHighest',
        sort_quantity: 5,
    },
    total_effort_hour:{
        sort_type:'topFiveHighest',
        sort_quantity: 5,
    },
    violations:{
        sort_type:'topFiveHighest',
        sort_quantity: 5,
    },
    rate_late:{
        sort_type:'topFiveHighest',
        sort_quantity: 5,
    },
    total_day_work:{
        sort_type:'topFiveHighest',
        sort_quantity: 10,
    },
    rate_dayoff:{
        sort_type:'topFiveHighest',
        sort_quantity: 5,
    },
});

const sort_value_options = [
    {
        value: '5',
        label: '5',
    },
    {
        value: '10',
        label: '10',
    },
    {
        value: '15',
        label: '15',
    }
];

const warriorComponent = ref();

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)
dayjs.Ls.en.weekStart = 1
dayjs.extend(relativeTime)

// define filter field
interface FormState {
    department_id?: number,
    date_range?: string,
    start_date?: string,
    end_date?: string,
};
interface Department {
    id: number,
    name: string
};
interface OptionSelect {
  value: number;
  label: string;
  ticks: string;
}

const CHART_KEY = {
    total_effort_hour:"total_effort_hour",
    go_early_total:"go_early_total",
    leave_late_total:"leave_late_total",
    violations:"violations",
    rate_late:"rate_late",
    total_day_work:"total_day_work",
    rate_dayoff:"rate_dayoff",
};

const currentDate = dayjs()
const startOfMonth = currentDate.startOf('month').format('YYYY/MM/DD')
const endOfMonth = currentDate.endOf('month').format('YYYY/MM/DD')

const labelBarChartGoEarly = ref([{fullname:''}]);
const dataBarChartGoEarly = ref([0, 0, 0, 0, 0]);
const percentBarChartGoEarly = ref([0, 0, 0, 0, 0]);
const labelBarChartLeaveLate = ref([{fullname:''}]);
const dataBarChartLeaveLate = ref([0, 0, 0, 0, 0]);
const percentBarChartLeaveLate = ref([0, 0, 0, 0, 0]);
const labelBarChartEffortHour = ref([{fullname:''}]);
const dataBarChartEffortHour = ref([0, 0, 0, 0, 0]);
const percentBarChartEffortHour = ref([0, 0, 0, 0, 0]);
const labelBarChartRateLate = ref([{fullname:''}]);
const dataBarChartRateLate = ref([0, 0, 0, 0, 0]);
const percentBarChartRateLate = ref([0, 0, 0, 0, 0]);
const labelBarChartViolations = ref([{fullname:''}]);
const dataBarChartViolations = ref([0, 0, 0, 0, 0]);
const percentBarChartViolations = ref([0, 0, 0, 0, 0]);

const formState = ref<FormState>({});
const departments = ref<Array<Department>>([]);
const loadingSearch = ref(false);
const loadingExport = ref(false);
const errorMessages = ref("");
const defaultDateRangePicker = ref<[string, string]>([
    startOfMonth,
    endOfMonth
]);
const statistialData = ref();
const formStateData = computed(()=>{
    return {
        start_date: defaultDateRangePicker.value[0],
        end_date: defaultDateRangePicker.value[1],
        department_id: formState.value.department_id,
        sort_options: filterOptions.value
    };
});
const totalWorkDayData = ref(); // so cong
const rateDayoffData = ref(); // ti le nghi
watch(defaultDateRangePicker, ([startDate, endDate]) => {
    formStateData.value.start_date = dayjs(startDate).format('YYYY/MM/DD');
    formStateData.value.end_date = dayjs(endDate).format('YYYY/MM/DD');
});
const timeRangeExport = {
    start_date: startOfMonth,
    end_date: endOfMonth,
};

const chartDataBarGoEarly :any = computed(() =>({
    labels: labelBarChartGoEarly.value.map(item => item.fullname.split(' ').reduce((acc:any, cur:any, index: any, arr:any) => {
        if(filterOptions.value.go_early_total.sort_quantity >= 15){
            if (index % 1 === 0) {
                acc.push(arr.slice(index, index + 1).join(' '));
            }
        }else{
            if (index % 2 === 0) {
                acc.push(arr.slice(index, index + 2).join(' '));
            } 
        }
        return acc;
    }, [])),
    datasets: [{
        label: '',
        data: dataBarChartGoEarly.value,
        percent: percentBarChartGoEarly.value,
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
const chartDataBarLeaveLate :any = computed(() =>({
    labels: labelBarChartLeaveLate.value.map(item => item.fullname.split(' ').reduce((acc:any, cur:any, index: any, arr:any) => {
        if(filterOptions.value.leave_late_total.sort_quantity >= 15){
            if (index % 1 === 0) {
                acc.push(arr.slice(index, index + 1).join(' '));
            }
        }else{
            if (index % 2 === 0) {
                acc.push(arr.slice(index, index + 2).join(' '));
            } 
        }
        return acc;
    }, [])),
    datasets: [{
        label: '',
        data: dataBarChartLeaveLate.value,
        percent: percentBarChartLeaveLate.value,
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
const chartDataBarEffortHour :any = computed(() =>({
    labels: labelBarChartEffortHour.value.map(item => item.fullname.split(' ').reduce((acc:any, cur:any, index: any, arr:any) => {
        if(filterOptions.value.total_effort_hour.sort_quantity >= 15){
            if (index % 1 === 0) {
                acc.push(arr.slice(index, index + 1).join(' '));
            }
        }else{
            if (index % 2 === 0) {
                acc.push(arr.slice(index, index + 2).join(' '));
            } 
        }
        return acc;
    }, [])),
    datasets: [{
        label: '',
        data: dataBarChartEffortHour.value,
        percent: percentBarChartEffortHour.value,
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
const chartDataBarViolations:any = computed(() =>({
    labels: labelBarChartViolations.value ? labelBarChartViolations.value.map(item => item.fullname.split(' ').reduce((acc:any, cur:any, index: any, arr:any) => {
        if(filterOptions.value.violations.sort_quantity >= 15){
            if (index % 1 === 0) {
                acc.push(arr.slice(index, index + 1).join(' '));
            }
        }else{
            if (index % 2 === 0) {
                acc.push(arr.slice(index, index + 2).join(' '));
            } 
        }
        return acc;
    }, [])) : '',
    datasets: [{
        label: '',
        data: dataBarChartViolations.value,
        percent: percentBarChartViolations.value,
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
// chart rate late
const chartRateLateDataBar :any = computed(() =>({
    labels: labelBarChartRateLate.value ? labelBarChartRateLate.value.map(item => item.fullname.split(' ').reduce((acc:any, cur:any, index: any, arr:any) => {
        if(filterOptions.value.rate_late.sort_quantity >= 15){
            if (index % 1 === 0) {
                acc.push(arr.slice(index, index + 1).join(' '));
            }
        }else{
            if (index % 2 === 0) {
                acc.push(arr.slice(index, index + 2).join(' '));
            } 
        }
        return acc;
    }, [])) : '',
    datasets: [{
        label: '',
        data: dataBarChartRateLate.value,
        percent: percentBarChartRateLate.value,
        backgroundColor: [
            'red'
        ],
        borderColor: [
            'red'
        ],
        borderWidth: 1,
        barThickness: 35,
        borderRadius: 3,
        
        
    }],
}))
const chartOptionsBar :any = computed(()=>(label = '(h)') =>({
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
                        return value + ' ' + label;
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
                 
                const labelText = data.toFixed(2)
                return labelText;
            },
        },
    },
  },
}))
const barChartStyle = ref({
    height: '305px',
    width: '100%',
});
const fetchStatistial = async ()=>{
    loadingSearch.value = true;
    
    try{
        const resp = await axios.get('/api/statistial/top',{
            params: formStateData.value
        });

        statistialData.value = resp.data;
        
        totalWorkDayData.value = resp.data.total_day_work ? resp.data.total_day_work.user : [{fullname:''}];
        rateDayoffData.value = resp.data.rate_dayoff ? resp.data.rate_dayoff.user : [{fullname:''}];
        
        labelBarChartGoEarly.value = resp.data.go_early_total ? resp.data.go_early_total.user : [];
        dataBarChartGoEarly.value = resp.data.go_early_total ? resp.data.go_early_total.dataChart : [];
        percentBarChartGoEarly.value = [];

        labelBarChartLeaveLate.value = resp.data.leave_late_total ? resp.data.leave_late_total.user : [];
        dataBarChartLeaveLate.value = resp.data.leave_late_total ? resp.data.leave_late_total.dataChart : [];
        percentBarChartLeaveLate.value = [];

        labelBarChartEffortHour.value = resp.data.total_effort_hour ? resp.data.total_effort_hour.user : [];
        dataBarChartEffortHour.value = resp.data.total_effort_hour ? resp.data.total_effort_hour.dataChart : [];
        percentBarChartEffortHour.value = [];

        labelBarChartRateLate.value = resp.data.rate_late ? resp.data.rate_late.user : [];
        dataBarChartRateLate.value = resp.data.rate_late ? resp.data.rate_late.dataChart : [];
        percentBarChartRateLate.value = [];

        labelBarChartViolations.value = resp.data.violations ? resp.data.violations.user : [];
        dataBarChartViolations.value = resp.data.violations ? resp.data.violations.dataChart : [];
        percentBarChartViolations.value = [];

        loadingSearch.value = false;
    }catch(error : any){
        console.log(error);
        if(error.response){
            callMessage(error.response.data.message);
        }
    }
}
const search = ()=>{
    timeRangeExport.start_date = formStateData.value.start_date;
    timeRangeExport.end_date = formStateData.value.end_date;
    // reset filter chart item
    fetchStatistial();
}
const { t } = useI18n();
const handleExport = async (chart:string) => {
    loadingExport.value = true;
    
    const warriorData = warriorComponent.value.getDataExport();
    const dataExport : any = {
        type: chart,
        start_date: timeRangeExport.start_date,
        end_date: timeRangeExport.end_date,
        department_id:formState.value.department_id,
        warrior_data: warriorData,
    };
    
    try{
        const resp = await downloadFile('/api/statistial/export/top', dataExport, errorMessages, t);
        loadingExport.value = false;
    }catch(error: any){
        loadingExport.value = false;
    }
}

onMounted(()=>{
    axios.get('/api/common/departments')
    .then(response => {
        departments.value = response.data
    });

    fetchStatistial();
})

const formatDecimal = (num: number)=>{
    return (Math.round(num * 100) / 100).toFixed(2);
}
const getFullPathAvatar = (path : string | '') => {
    return window.location.origin + '/image/' + path;
}
const getDurationWorkDay = (dateCompare: any) => {
    const currentDate = dayjs();
    let resp = 'Thử việc';
    let years = 0;
    if (dateCompare) {
        years = currentDate.diff(dayjs(dateCompare), "year");
        const months = currentDate.diff(dayjs(dateCompare), "month") - years * 12;
        const days = currentDate.diff(
            dayjs(dateCompare).add(years, "year").add(months, "month"),
            "day"
        );
        let strYear = years ? years + " năm " : "";
        let strMonth = months ? months + " tháng " : "";
    
        let string = strYear + strMonth + days + " ngày";
    
        resp = string;
    }
    return resp;
};
const toggleSortType = (chart:string)=>{
    if(filterOptions.value[chart].sort_type == 'topFiveHighest'){
        filterOptions.value[chart].sort_type = 'topFiveLowest'
    }else{
        filterOptions.value[chart].sort_type = 'topFiveHighest'
    }
    fetchStatistial();
}
const toggleSortQty = (chart:string)=>{
    fetchStatistial();
}
</script>
<style lang="scss" >
    $medium: 992px;

    .statis-wrapper{

        .chart-action{
            width: 100%;
            display: flex;
            justify-content: space-between;
            
            .action{
                display: flex;
                 align-items: center;
                  gap: 15px;
            }
            .sort{
                display: flex;
                 align-items: center;
                  gap: 5px;
            }

            button.btn-sort{
                display: flex;
                height: 32px;
                align-items: center;
                border: 1px solid var(--el-border-color-light);
                border-radius: 4px;
                background: unset;
                cursor: pointer;
                transition: var(--el-transition-box-shadow);

                &:hover{
                    transition: var(--el-transition-box-shadow);
                    border: 1px solid var(--el-border-color-hover) !important;
                }
            }

            .text-sort{
                font-size: 12px;
                &.in-active{
                    display: none;
                }
            }

            .chart-action__left{
                font-weight: bold;
                color: var(--el-text-color-primary);
            }
            .chart-action__right{
                display: flex;
                gap: 10px;
            }
        }

        .row-chart-bar{
            height: 300px;
            .col-chart-bar{
                border-radius: 15px;
                border: 1px solid #ccc;
            }
        }
        .example-showcase .el-dropdown + .el-dropdown {
            margin-left: 15px;
          }
          .example-showcase .el-dropdown-link {
            cursor: pointer;
            color: var(--el-color-primary);
            display: flex;
            align-items: center;
        }
    
        .chart-wrapper{
            .more-filled-icon{
                rotate: 90deg;
            }
            .btn-more{
                all: unset !important;

                width: 100%;
                height: 100%;
                background-color: none;
                border: unset;
                background-color: unset;
                
            }
        }
        .first-chart{
            @media screen and (max-width: $medium) {
                margin-bottom: 20px;                
            }
        }
        .table-bold th{
            color: var(--el-text-color-primary) !important;
        }

        table tr th:nth-child(3),table tr td:nth-child(3){
            color: #4CB759 !important;
            text-align: center;
        }
        table tr th:nth-child(4),table tr td:nth-child(4){
            color: #FFA500 !important;
            text-align: center;
        }
        table tr th:nth-child(5),table tr td:nth-child(5){
            color: #800000;
            text-align: center;
        }
        table tr th:nth-child(6),table tr td:nth-child(6){
            color: var(--el-text-color-primary) !important;
            text-align: center;
        }
        .table-total-work-day{
            width: 100%; 
            margin-top: 20px;
            .user-details{
                .fullname{
                    color: #000 !important;
                }
                .duration{
                    font-size: .8rem;
                }
            }
        }
        .chart-rate-late{
            .el-card{
                height: 100%;
                .el-card__body{
                    height: 100%;
                    .el-row{
                        height: 100%;
                    }
                }
            }
        }
        .table-rate-late{
            tr th .cell{
                color: var(--el-text-color-primary) !important;
            }
        }
    }
</style>