<template>
    <div id="filter-block">
        <el-row :gutter="20">
            <el-col :span="4">
                <label>Dự án</label>
                <el-select
                    v-model="formState.project_id"
                    multiple
                    filterable
                    collapse-tags
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
                <label>Bộ phận</label>
                <el-select
                    v-model="formState.department_id"
                    multiple
                    filterable
                    collapse-tags
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
            <el-col :span="3">
                <label>Nhân viên</label>
                <el-select
                    v-model="formState.user_id"
                    multiple
                    filterable
                    collapse-tags
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
            <el-col :span="3" v-if="timeOption != 'Option' && showWithTime">
                <label>Thời gian</label>
                <el-date-picker
                    v-model="datePeriod"
                    :type="range"
                    :clearable="false"
                    size="default"
                    style="width: 100%"
                ></el-date-picker>
            </el-col>
            <el-col :span="3" v-if="timeOption === 'Option' && showWithTime">
                <label>Thời gian</label>
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
            <el-col :span="2" style="padding-top: 21px" v-if="showWithTime">
                <el-radio-group v-model="timeOption" style="width: 120%;">
                    <el-radio-button label="Month"/>
                    <el-radio-button label="Option"/>
                </el-radio-group>
            </el-col>
            <el-col :span="2" style="padding-top: 21px">
                <el-button v-on:click="_fetch()" type="primary" style="padding: 0 30px; ">Tìm kiếm</el-button>
            </el-col>
            <el-col :span="2" style="padding-top: 21px">
                <!-- <el-button v-on:click="doExport" type="warning" style="">Export</el-button> -->
            </el-col>
            <div class="flex-grow" />
            <el-col :span="2" v-if="activeName == 'first'">
                <label name="name">Hiển thị Theo thời gian</label>
                <el-switch v-model="showWithTime" style="width:100%;"/>
            </el-col>
        </el-row>
    </div>
    <el-tabs v-model="activeName" class="custom-height-tabs">
        <el-tab-pane label="Thời gian hoàn thành" name="first">
            <el-table :data="dataSource" style="width: 100%" :height="heightScrollbar" border>
                <el-table-column type="index" width="50" label="STT"/>
                <el-table-column prop="fullname" label="Họ và tên" min-width="250" />
                <el-table-column v-for="record in column" :key="record['project_id']" :label="record['project_name'] + ' ('+ formatDate(record['min_work_date']) +' - '+ formatDate(record['max_work_date'])+')'" align="right">
                    <!-- <el-table-column label="Công làm việc" min-width="80" >
                        <template #default="scope">
                            {{ scope.row.projects[record['project_id']].X }}
                        </template>
                    </el-table-column>
                    <el-table-column label="Giờ nỗ lực" min-width="80" >
                        <template #default="scope">
                            {{ scope.row.projects[record['project_id']].T }}
                        </template>
                    </el-table-column> -->
                    <el-table-column label="Số công nghỉ" min-width="50">
                        <template #default="scope">
                            <template v-if="scope.row.projects && scope.row.projects[record['project_id']]">
                                {{ scope.row.projects[record['project_id']].totalLeave }}
                            </template>
                        </template>
                    </el-table-column>
                    <el-table-column label="Tỉ lệ nghỉ/công thực tế" min-width="50">
                        <template #default="scope">
                            <template v-if="scope.row.projects && scope.row.projects[record['project_id']]">
                                {{ scope.row.projects[record['project_id']].percentLeave }}%
                            </template>
                        </template>
                    </el-table-column>
                    <el-table-column label="Công thực tế" min-width="50">
                        <template #default="scope">
                            <template v-if="scope.row.projects && scope.row.projects[record['project_id']]">
                                {{ scope.row.projects[record['project_id']].totalWork }}
                            </template>
                        </template>
                    </el-table-column>
                    <el-table-column label="Công làm việc thực tế dự án (T)" min-width="80">
                        <template #default="scope">
                            <template v-if="scope.row.projects && scope.row.projects[record['project_id']]">
                                {{ scope.row.projects[record['project_id']].T }}
                            </template>
                        </template>
                    </el-table-column>
                    <el-table-column label="Tổng giờ nỗ lực dự án (X)" min-width="80">
                        <template #default="scope">
                            <template v-if="scope.row.projects && scope.row.projects[record['project_id']]">
                                {{ scope.row.projects[record['project_id']].X }}
                            </template>
                        </template>
                    </el-table-column>
                    <el-table-column label="Cấp độ (W)" min-width="100" >
                        <template #default="scope">
                            <template v-if="scope.row.projects && scope.row.projects[record['project_id']]">
                                {{ scope.row.projects[record['project_id']].W }}
                            </template>
                        </template>
                    </el-table-column>
                </el-table-column>
            </el-table>
        </el-tab-pane>
    </el-tabs>
</template>

<script lang="ts" setup>
import { ref, onMounted, computed } from 'vue'
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
    project_id?: string,
    department_id?: string,
    user_id?: string,
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

    if (Array.isArray(newFormState.user_id) && newFormState.user_id.length === 0) {
        delete newFormState.user_id;
    }
    if (Array.isArray(newFormState.department_id) && newFormState.department_id.length === 0) {
        delete newFormState.department_id;
    }
    if (Array.isArray(newFormState.project_id) && newFormState.project_id.length === 0) {
        delete newFormState.project_id;
    }
    
    if (showWithTime.value == true) {
        if (timeOption.value === 'Option') {
            newFormState.start_date = dateRange.value[0];
            newFormState.end_date = dateRange.value[1];
        } else {
            newFormState.start_date = dayjs(datePeriod.value).startOf('month').format('YYYY/MM/DD');
            newFormState.end_date = dayjs(datePeriod.value).endOf('month').format('YYYY/MM/DD');
        }
    }
    return newFormState;
});

const dataSource = ref()
const formState = ref<FormState>({})
const column = ref()
const heightScrollbar = ref()
const heightScrollbarTable = ref()
const errorMessages = ref('')
const activeName = ref('first')

const projects = ref()
const users = ref()
const departments = ref()
const session = ref()

const showWithTime = ref(false)

const _fetch = () => {
    axios.post('/api/warrior/get_warrior_project', computedFormState.value)
    // axios.post('/api/warrior/get_warrior_project', formState.value)
    .then(response => {
        column.value = response.data.projects
        dataSource.value = response.data.dataWarriorTotal
        setTimeout(() => { heightScrollbar.value = resizeScreen(), heightScrollbarTable.value = heightScrollbar.value-120 }, 0);
        
    })
    .catch(error => {
        dataSource.value = []
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
};
const formatDate = (date:any) => {
    return dayjs(date).format('DD/MM/YYYY')
};

const excel_file = ref()
const { t } = useI18n();
// const doExport = () => {
//     let formData = new FormData();
//     excel_file.value ? formData.append('excel_file', excel_file.value) : '';
//     formData.append('start_date', String(computedFormState.value.start_date));
//     formData.append('end_date', String(computedFormState.value.end_date));
//     downloadFile('/api/working_time/export', formData, errorMessages,t)
//     .then((response) => {
//          callMessage(response.data.success, 'success');
//     }).catch(error => {
//         errorMessages.value = error.response.data.errors;
//         callMessage(errorMessages.value, 'error');
//     })
// }

onMounted (() => {
    axios.get('/api/department/task/get_selectboxes')
    .then(response => {
        projects.value = response.data.projects;
        users.value = response.data.users;
        departments.value = response.data.departments;
        session.value = response.data.session;

        _fetch()
    })
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
