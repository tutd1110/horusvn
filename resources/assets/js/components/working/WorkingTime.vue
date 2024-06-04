<template>
    <div id="filter-block">
        <el-row :gutter="20">
            <!-- <el-col :span="3">
                <label name="name">Khoảng thời gian</label>
                <el-date-picker
                    v-model="datePeriod"
                    type="daterange"
                    start-placeholder="Start date"
                    end-placeholder="End date"
                    format="DD/MM/YYYY"
                    value-format="YYYY/MM/DD"
                    style="width:100%;"
                />
            </el-col> -->
            <el-col :span="3" style="padding-top: 21px" v-if="timeOption != 'Option'">
            <el-date-picker
                v-model="datePeriod"
                :type="range"
                :clearable="false"
                size="default"
                style="width: 100%"
            ></el-date-picker>
            </el-col>
            <el-col :span="3" style="padding-top: 21px" v-if="timeOption === 'Option'">
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
            <el-col :span="2" style="padding-top: 21px">
                <el-radio-group v-model="timeOption" style="width: 120%;">
                    <el-radio-button label="Month"/>
                    <el-radio-button label="Option"/>
                </el-radio-group>
            </el-col>
            <el-col :span="1" style="padding-top: 21px">
                <el-button v-on:click="doDownloadTemplate" type="warning" style=" ">
                    <span class="upload-spn">File mẫu</span><el-icon class="el-icon--right"><Download /></el-icon>
                </el-button>
            </el-col>
            <el-col :span="2" style="padding-top: 21px">
                <el-upload
                    :multiple="false"
                    :show-file-list="false"
                    :before-upload="beforeUpload"
                    style=" float: right;"
                >
                    <el-button type="success" >
                        <span class="upload-spn">Chọn file</span><el-icon class="el-icon--right"><Upload /></el-icon>
                    </el-button>
                </el-upload>
            </el-col>
            
            <el-col :span="4" style="padding-top: 21px">
                <el-input style="width:100%" readonly placeholder="Only file xlsx can be import" v-model="fileName" />
            </el-col>
            
            <el-col :span="2" style="padding-top: 21px">
                <el-button v-on:click="_fetch()" type="primary" style="padding: 0 30px; ">Apply</el-button>
            </el-col>
            <el-col :span="2" style="padding-top: 21px">
                <el-button v-on:click="doExport" type="warning" style="">Export</el-button>
            </el-col>
            <div class="flex-grow" />
            <el-col :span="2" v-if="activeName == 'first'">
                <label name="name">Hiển thị Project</label>
                <el-switch v-model="showProject" style="width:100%;"/>
            </el-col>
        </el-row>
    </div>
    <el-tabs v-model="activeName" class="custom-height-tabs">
        <el-tab-pane label="Thời gian hoàn thành" name="first">
            <el-table :data="dataSource" style="width: 100%" :height="heightScrollbar" border>
                <el-table-column prop="fullname" label="Họ và tên" min-width="250" />
                <el-table-column v-for="record in column" :key="record['project_id']" :label="record['project_name']" v-if="showProject" align="right">
                    <template #default="scope">
                        {{ scope.row[record['project_id']] }}
                    </template>
                </el-table-column>
                <el-table-column prop="total_time" label="Tổng giờ hoàn thành" min-width="100" align="right"/>
                <el-table-column prop="total_time_working" label="Giờ làm việc thực tế" min-width="100" align="right"/>
            </el-table>
        </el-tab-pane>
        
        <el-tab-pane label="Chi phí lương" name="second">
            <el-table :data="dataSalary" style="width: 100%" :height="heightScrollbar" border>
                <el-table-column prop="fullname" label="Họ và tên" min-width="250" />
                <el-table-column v-for="record in columnSalary" :key="record['project_id']" :label="record['project_name']" align="right">
                    <template #default="scope">
                        {{ scope.row[record['project_id']] ? scope.row[record['project_id']].toLocaleString() : '' }}
                    </template>
                </el-table-column>
                <el-table-column prop="salary" label="Tổng lương" min-width="100"  align="right">
                    <template #default="scope">
                        {{ scope.row.salary ? scope.row.salary.toLocaleString() : '' }}
                    </template>
                </el-table-column>
            </el-table>
        </el-tab-pane>

        <el-tab-pane label="Phần trăm dự án" name="third">
            <el-row :gutter="20" class='table-task-header'>
                <el-col :span="10">
                    <el-card class="box-card" shadow="hover" style="line-height: normal;">
                        <template #header>
                            <div class="card-header">
                                <span style="font-weight: bold; font-size: 18px;">Thống kê dự án</span>
                            </div>
                        </template>
                        <el-table :data="projectData" style="width: 100%" :height="heightScrollbarTable" border>
                            <el-table-column prop="project_name" label="Tên dự án" min-width="200" />
                            <el-table-column prop="sum_time" label="Tổng thời gian" align="right">
                                <template #default="record">
                                    {{ record.row.sum_time ? record.row.sum_time.toLocaleString() : '' }}
                                </template>
                            </el-table-column>
                            <el-table-column label="Tỉ lệ %" align="right">
                                <template #default="record">
                                    {{ record.row.percent ? record.row.percent.toLocaleString() : '' }}%
                                </template>
                            </el-table-column>
                            <el-table-column label="Thuộc dự án" min-width="200">
                                <template #default="record">
                                    <el-select
                                        v-model="record.row.project_parent_time"
                                        filterable
                                        clearable
                                        class="none-border"
                                        style="width:100%;"
                                        size="small"
                                        @change="toggleProjectParent(record.row.project_id,record.row.project_parent_time)"
                                        :disabled="record.row.project_name == 'Total'"
                                        v-if="record.row.project_name != 'Total'"
                                    >
                                        <el-option
                                            v-for="item in projectSelect"
                                            :key="item.project_id"
                                            :label="item.project_name"
                                            :value="item.project_id"
                                        />
                                    </el-select>
                                </template>
                            </el-table-column>
                        </el-table>
                    </el-card>
                </el-col>
                <el-col :span="14">
                    <el-card class="box-card" shadow="hover" style="line-height: normal;">
                        <template #header>
                            <div class="card-header">
                                <span style="font-weight: bold; font-size: 18px;">Thống kê sau gộp dự án</span>
                                <el-input 
                                    v-model="generalExpenses" 
                                    style="width: 300px;float: right;" 
                                    placeholder="Nhập chi phí chung" 
                                    clearable 
                                    :formatter="(value:any) => value.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                                    :parser="(value:any) => value.replace(/\$\s?|(,*)/g, '')"
                                >
                                    <template #append>VNĐ</template>
                                </el-input>
                            </div>
                        </template>
                        <el-table :data="projectParent" style="width: 100%" :height="heightScrollbarTable" border>
                            <el-table-column prop="project_name" label="Tên dự án" min-width="200" />
                            <el-table-column label="Tổng thời gian" align="right">
                                <template #default="record">
                                    {{ record.row.sum_time ? record.row.sum_time.toLocaleString() : '' }}
                                </template>
                            </el-table-column>
                            <el-table-column label="Tỉ lệ thời gian dự án" align="right">
                                <template #default="record">
                                    {{ record.row.percent ? record.row.percent.toLocaleString() : 0 }}%
                                </template>
                            </el-table-column>
                            <el-table-column label="Chi phí lương" align="right">
                                <template #default="record">
                                    {{ record.row.project_salary ? record.row.project_salary.toLocaleString() : 0 }}
                                </template>
                            </el-table-column>
                            <el-table-column label="Chi phí chung" align="right">
                                <template #default="record">
                                    {{ record.row.general_expenses ? record.row.general_expenses.toLocaleString() : 0 }}
                                </template>
                            </el-table-column>
                            <el-table-column label="Tổng chi phí" align="right">
                                <template #default="record">
                                    {{ record.row.total_cost ? record.row.total_cost.toLocaleString() : 0 }}
                                </template>
                            </el-table-column>
                            <el-table-column label="Tỉ lệ chi phí" align="right">
                                <template #default="record">
                                    {{ record.row.percent_cost ? record.row.percent_cost.toLocaleString() : 0 }}%
                                </template>
                            </el-table-column>
                        </el-table>
                    </el-card>
                </el-col>
            </el-row>
        </el-tab-pane>
    </el-tabs>
    
</template>

<script lang="ts" setup>
import { ref, onMounted, computed } from 'vue'
import {Upload, Download} from '@element-plus/icons-vue'
import Dialog from './Dialog.vue';
import axios from 'axios';
import { callMessage } from '../Helper/el-message.js';

import dayjs from 'dayjs';
import { resizeScreen } from '../Helper/resize-screen.js';
import { downloadFile } from '../Helper/export.js';
import { useI18n } from 'vue-i18n';

interface FormState {
    start_date?: string,
    end_date?: string,
    general_expenses?: number,
};
const currentDate = dayjs()
const startMonth = currentDate.startOf('month').format('YYYY/MM/DD')
const endMonth = currentDate.endOf('month').format('YYYY/MM/DD')
const timeOption = ref('Month')
const datePeriod = ref(startMonth)
const dateRange = ref([startMonth, endMonth])
const range = computed(() => {
    datePeriod.value = startMonth
    return 'month';
})
const computedFormState = computed<FormState>(() => {
    const newFormState: FormState = {
        ...formState.value,
    };

    if (timeOption.value === 'Option') {
        newFormState.start_date = dateRange.value[0];
        newFormState.end_date = dateRange.value[1];
    } else {
        newFormState.start_date = dayjs(datePeriod.value).startOf('month').format('YYYY/MM/DD');
        newFormState.end_date = dayjs(datePeriod.value).endOf('month').format('YYYY/MM/DD');
    }
    
    generalExpenses.value && generalExpenses.value != null && generalExpenses.value != '' ? newFormState.general_expenses = parseFloat(generalExpenses.value.toString().replace(/,/g, '')) : ''
    return newFormState;
});

const dataSource = ref()
const formState = ref<FormState>({})
const column = ref()
const projectData = ref()
const projectParent = ref()
const heightScrollbar = ref()
const heightScrollbarTable = ref()
const errorMessages = ref('')
const showProject = ref(true)
const activeName = ref('third')
const generalExpenses = ref()
const projectSelect = ref()

const _fetch = () => {
    
    axios.get('/api/working_time/get_working_time', {
        params: computedFormState.value
    })
    .then(response => {
        column.value = response.data.project_columns
        dataSource.value = response.data.users
        projectData.value = response.data.projects
        projectParent.value = response.data.project_parents
        projectSelect.value = response.data.project_select
        setTimeout(() => { heightScrollbar.value = resizeScreen(), heightScrollbarTable.value = heightScrollbar.value-120 }, 0);
        
        if (excel_file.value) {
            doImport()
        }
        
    })
    .catch(error => {
        dataSource.value = []
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
};
const doDownloadTemplate = () => {
    downloadFile('/api/working_time/download_salary')
    .then(response => {

    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
};
const toggleProjectParent = (project_id: number, project_parent_id: any) => {
    if (project_parent_id == '') {
        project_parent_id = null
    }
    update(project_id,project_parent_id)
}
const update = (project_id:number, project_parent_id:any) => {
    let submitData = {
        id: project_id,
        project_parent_time: project_parent_id
    }

    axios.patch('/api/project/quick_update', submitData)
    .then(response => {
        callMessage(response.data.success, 'success');

        _fetch();
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;//put message content in ref
        callMessage(errorMessages.value, 'error');
        _fetch();
    })
}
const excel_file = ref()
const fileName = ref()
const beforeUpload = (file:any) => {
    excel_file.value = file
    fileName.value = file.name
    return false;
};
const dataSalary = ref(); 
const columnSalary = ref(); 
const doImport = () => {
    let formData = new FormData();
    formData.append('excel_file', excel_file.value);
    formData.append('start_date', String(computedFormState.value.start_date));
    formData.append('end_date', String(computedFormState.value.end_date));
    computedFormState.value.general_expenses ? formData.append('general_expenses', String(computedFormState.value.general_expenses)) : '';
    
    
    axios.post('/api/working_time/import_salary', formData,
    {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
    .then(response => {
        dataSalary.value = response.data.users
        columnSalary.value = response.data.project_columns
        projectParent.value = response.data.project_parents
        projectSelect.value = response.data.project_select
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
    })
}
const { t } = useI18n();
const doExport = () => {
    let formData = new FormData();
    excel_file.value ? formData.append('excel_file', excel_file.value) : '';
    formData.append('start_date', String(computedFormState.value.start_date));
    formData.append('end_date', String(computedFormState.value.end_date));
    computedFormState.value.general_expenses ? formData.append('general_expenses', String(computedFormState.value.general_expenses)) : '';
    downloadFile('/api/working_time/export', formData, errorMessages,t)
    .then((response) => {
         callMessage(response.data.success, 'success');
    }).catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
onMounted (() => {
    _fetch()
})

</script>

<style>
.custom-height-tabs > .el-tabs__content {
  padding: 0px;
  color: #111;
  font-size: 32px;
}
.custom-height-tabs .table-text {
    padding: 4px 2px;
    width:100%
}
.custom-height-tabs .el-table .cell {
    font-size: 12px;
}
.table-task .custom-scrollbar .el-card__body {
    padding: 0 !important;
}
.table-text-string{
    text-align: left;
}
.table-text-number{
    text-align: right;
}

.custom-height-tabs th {
    text-align: center !important;
}

</style>
