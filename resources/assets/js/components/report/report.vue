<template>
    <div class="height-container" id="filter-block">
        <el-row :gutter="20">
            <el-col :span="3">
                <label>Projects</label>
                <el-select
                    v-model="formState.project_ids"
                    clearable
                    multiple
                    filterable
                    collapse-tags
                    collapse-tags-tooltip
                    value-key="id"
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in projects"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                    />
                </el-select>
            </el-col>
            <el-col :span="3">
                <label>Departments</label>
                <el-select
                    v-model="formState.department_ids"
                    clearable
                    multiple
                    filterable
                    collapse-tags
                    collapse-tags-tooltip
                    value-key="value"
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in departments"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-col>
            <el-col :span="4">
                <label>Period Time</label>
                <el-date-picker
                    v-model="datePeriod"
                    type="daterange"
                    start-placeholder="Start Date"
                    end-placeholder="End Date"
                    style="width: 100%"
                    format="DD/MM/YYYY"
                    value-format="YYYY/MM/DD"
                />
            </el-col>
            <el-col :span="2" style="margin-top: 22px">
                <el-space size="small" spacer="|">
                    <el-button type="primary" @click="search" :loading="searchLoading">Search</el-button>
                    <el-button type="primary" @click="doExport" :loading="loadingExport">Export</el-button>
                </el-space>
            </el-col>
        </el-row>
        <el-row>
            <el-table v-loading="isLoading" :data="dataSource" border style="width: 100%">
                <el-table-column label="Bộ phận" prop="department" />
                <el-table-column label="Tổng" prop="total" />
                <el-table-column label="Đang làm" prop="total_processing" />
                <el-table-column label="Quá hạn" prop="total_slow" />
                <el-table-column label="Đang chờ" prop="total_wait" />
                <el-table-column label="Tạm dừng" prop="total_pause" />
                <el-table-column label="Chờ feedback" prop="total_wait_fb" />
                <el-table-column label="Làm lại" prop="total_again" />
                <el-table-column label="Hoàn thành" prop="total_completed" />
                <el-table-column label="Trọng số" prop="total_weight" />
                <el-table-column label="Trọng số hoàn thành" prop="total_weight_completed" />
                <el-table-column label="Tỉ lệ hoàn thành(công việc)">
                    <template #default="scope">
                        <span style="font-weight: bold">{{ scope.row.rate_task_completed + "%" }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="Tỉ lệ hoàn thành(trọng số)">
                    <template #default="scope">
                        <span style="font-weight: bold">{{ scope.row.rate_weight_completed + "%" }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="Tỉ lệ không hoàn thành(trọng số)">
                    <template #default="scope">
                        <span style="font-weight: bold">{{ (100 - scope.row.rate_weight_completed) + "%" }}</span>
                    </template>
                </el-table-column>
            </el-table>
        </el-row>
        <el-row :gutter="20">
            <el-col :span="3">
                <label>Member</label>
                <el-select
                    v-model="formState.user_id"
                    value-key="id"
                    clearable
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in users"
                        :key="item.id"
                        :label="item.fullname"
                        :value="item.id"
                    />
                </el-select>
            </el-col>
            <el-col :span="1" style="margin-top: 22px">
                <el-button type="primary" @click="searchUser">Search</el-button>
            </el-col>
        </el-row>
    </div>
    <el-row>
        <el-table v-loading="isLoading1" :data="dataSource2" border :height="heightTable" style="width: 100%">
            <el-table-column label="Member" prop="fullname" sortable :formatter="fullnameFormatter"/>
            <el-table-column label="Tổng" prop="total" sortable/>
            <el-table-column label="Hoàn thành" prop="total_completed" sortable/>
            <el-table-column label="Tỉ lệ công việc" prop="rate_task_completed" sortable :formatter="rateTaskFormatter"/>
            <el-table-column label="Quá hạn" prop="total_slow" sortable/>
            <el-table-column label="Đang làm" prop="total_processing" sortable/>
            <el-table-column label="Tạm dừng" prop="total_pause" sortable/>
            <el-table-column label="Đang chờ" prop="total_wait" sortable/>
            <el-table-column label="Chờ feedback" prop="total_wait_fb" sortable/>
            <el-table-column label="Làm lại" prop="total_again" sortable/>
            <el-table-column label="Trọng số" prop="total_weight_employee" sortable/>
            <el-table-column label="Trọng số hoàn thành" prop="total_weight_employee_completed" sortable/>
            <el-table-column label="Quality" prop="total_weight_employee_quality" sortable/>
            <el-table-column label="Weighted +" prop="weights_added" sortable/>
            <el-table-column label="Weighted -" prop="weights_lost" sortable/>
            <el-table-column label="Tỉ lệ hoàn thành(trọng số)" prop="rate_weight_completed" sortable :formatter="rateWeightFormatter"/>
            <el-table-column label="Trọng số/dự án" prop="rate_weight_project" sortable :formatter="rateWeightProjectFormatter"/>
            <el-table-column label="Trọng số/bộ phận" prop="rate_weight_department" sortable :formatter="rateWeightDepartmentFormatter"/>
            <el-table-column label="Trọng số không hoàn thành" prop="total_weight_employee_not_completed" sortable/>
            <el-table-column label="Trọng số không hoàn thành/bộ phận" prop="rate_weight_not_completed_department" sortable :formatter="rateWeightDepartmentNotCompleteFormatter"/>
        </el-table>
    </el-row>
</template>
<script>
import { onMounted, ref, h } from 'vue';
import { useI18n } from 'vue-i18n';
import { downloadFile } from '../Helper/export.js';
import { callMessage } from '../Helper/el-message.js';
import { resizeScreen } from '../Helper/resize-screen.js';

export default ({
    setup() {
        const { t } = useI18n();
        const searchLoading = ref(false);
        const dataSource = ref();
        const dataSource2 = ref();
        const errorMessages = ref("");
        const formState = ref([]);
        const projects = ref([]);
        const users = ref([]);
        const isLoading = ref(false);
        const isLoading1 = ref(false);
        const departments = ref([]);
        const datePeriod = ref([]);
        const loadingExport = ref(false);
        const heightTable = ref()
        const search = () => {
            searchLoading.value = true;
            isLoading.value = true;
            handleDatePeriod();

            axios.get('/api/report/get_report', {
                params:{
                    department_ids: formState.value.department_ids,
                    project_ids: formState.value.project_ids,
                    start_time: formState.value.start_time,
                    end_time: formState.value.end_time
                }})
                .then(response => {
                    dataSource.value = response.data;
                    searchLoading.value = false;
                    isLoading.value = false;
                    setTimeout(() => { heightTable.value = resizeScreen() }, 0);
                })
                .catch((error) => {
                    searchLoading.value = false;
                    isLoading.value = false;
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    //When search target data does not exist
                    dataSource.value = []; //dataSource empty
                    callMessage(errorMessages.value, 'error');
                });

            searchUser()
        }
        const searchUser = () => {
            isLoading1.value = true;
            handleDatePeriod();

            axios.get('/api/report/get_user_report', {
                params:{
                    user_id: formState.value.user_id,
                    department_ids: formState.value.department_ids,
                    project_ids: formState.value.project_ids,
                    start_time: formState.value.start_time,
                    end_time: formState.value.end_time
                }})
                .then(response => {
                    dataSource2.value = response.data;

                    const departmentWeights = dataSource.value.reduce((acc, department) => {
                        acc[department.id] = department.total_weight;

                        return acc;
                    }, {});

                    const sumDepartmentWeights = dataSource.value.reduce((acc, department) => {
                        acc += department.total_weight;

                        return acc;
                    }, 0);

                    dataSource2.value.forEach((employee) => {
                        const departmentWeight = departmentWeights[employee.department_id];
                        const percentage =
                            departmentWeight === 0
                            ? 0
                            : Math.round((employee.total_weight_employee_completed / departmentWeight) * 10000) / 100;
                        employee.rate_weight_department = percentage;
                        
                        const projectPercentage = departmentWeight === 0 || sumDepartmentWeights === 0
                        ? 0
                        : Math.round((percentage * departmentWeight / sumDepartmentWeights) * 100) / 100;
                        employee.rate_weight_project = projectPercentage;
                        
                        employee.total_weight_employee_not_completed = employee.total_weight_employee - employee.total_weight_employee_completed
                        const percentage_not_complete =
                            departmentWeight === 0
                            ? 0
                            : Math.round((employee.total_weight_employee_not_completed / departmentWeight) * 10000) / 100;
                        employee.rate_weight_not_completed_department = percentage_not_complete;
                        
                    });

                    isLoading1.value = false;
                    setTimeout(() => { heightTable.value = resizeScreen() }, 0);
                })
                .catch((error) => {
                    isLoading1.value = false;
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    //When search target data does not exist
                    dataSource2.value = []; //dataSource empty
                    callMessage(errorMessages.value, 'error');
                });
        };
        const handleDatePeriod = () => {
            if (datePeriod.value) {
                formState.value.start_time = datePeriod.value[0]
                formState.value.end_time = datePeriod.value[1]
            } else {
                formState.value.start_time = null
                formState.value.end_time = null
            }
        };
        const doExport = () => {
            let submitData = {
                departments: dataSource.value,
                employees: dataSource2.value
            }

            loadingExport.value = true;
            downloadFile('/api/report/export', submitData, errorMessages, t)
            .then(() => {
                loadingExport.value = false;
            })
            .catch(() => {
                loadingExport.value = false;
            });
        }
        const _fetch = () => {
            //create select boxes
            axios.get('/api/report/get_select_boxes')
            .then(response => {
                let data = response.data;

                departments.value = data.departments;
                projects.value = data.projects;
                users.value = data.users;

                datePeriod.value = [
                    data.date_period.date_start,
                    data.date_period.date_end
                ]

                handleDatePeriod()

                search()
            })
        };
        const rateTaskFormatter = (row) => {
            return row.rate_task_completed + '%'
        }
        const fullnameFormatter = (row) => {
            return row.fullname ? row.fullname : 'N/A'
        }
        const rateWeightFormatter = (row) => {
            return row.rate_weight_completed + '%'
        }
        const rateWeightProjectFormatter = (row) => {
            return row.rate_weight_project + '%'
        }
        const rateWeightDepartmentFormatter = (row) => {
            return row.rate_weight_department + '%'
        }
        const rateWeightDepartmentNotCompleteFormatter = (row) => {
            return row.rate_weight_not_completed_department + '%'
        }
        onMounted(() => {
            _fetch();
        });

        return {
            searchLoading,
            dataSource,
            isLoading,
            isLoading1,
            dataSource2,
            rateTaskFormatter,
            fullnameFormatter,
            rateWeightFormatter,
            rateWeightProjectFormatter,
            rateWeightDepartmentFormatter,
            formState,
            datePeriod,
            errorMessages,
            projects,
            users,
            departments,
            search,
            searchUser,
            loadingExport,
            doExport,
            heightTable,
            rateWeightDepartmentNotCompleteFormatter
        };
    }
})
</script>