<template>
    <el-dialog v-model="dialogTableVisible" draggable>
        <el-tabs type="card" v-model="tabValue" @tab-change="onChangeTab">
            <el-tab-pane label="Summary">
                <el-table :data="tableData" show-summary border>
                    <el-table-column
                        v-for="key in tableKeys"
                        :key="key"
                        :prop="key"
                        :label="key"
                    >
                    </el-table-column>
                </el-table>
            </el-tab-pane>
            <el-tab-pane v-if="isDepartmentMode" label="Detail">
                <el-table :data="reportData" border show-summary style="width: 100%">
                    <el-table-column
                        prop="fullname"
                        label="FULLNAME"
                        width="180"
                        :filters="departmentFilters"
                        :filter-method="filterDepartment"
                    />
                    <el-table-column prop="new" sortable label="NEW" />
                    <el-table-column prop="open" sortable label="OPEN" />
                    <el-table-column prop="fixed" sortable label="FIXED" />
                    <el-table-column prop="cnr" sortable label="CNR" />
                    <el-table-column prop="tfu" sortable label="TFU" />
                    <el-table-column prop="confirmed" sortable label="CONFIRMED" />
                    <el-table-column prop="nab" sortable label="NAB" />
                </el-table>
            </el-tab-pane>
        </el-tabs>
    </el-dialog>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { computed, ref } from 'vue';
import type { TabPaneName } from 'element-plus'
import { callMessage } from '../../Helper/el-message.js';

interface FormState {
    id?: number,
    project_id?: number,
    assigned_department_id?: number,
    task_id?: number,
    tester_id?: number,
    status: number[],
    weighted?: number
};

const dialogTableVisible = ref(false)
const errorMessages = ref()
const tabValue = ref('0')
const tableData = ref([])
const reportData = ref([])
const tableKeys = computed(() => {
    if (tableData.value.length > 0) {
        const firstObject = tableData.value[0];

        return Object.keys(firstObject);
    } else {
        return [];
    }
});
const formState = ref<FormState>()
const endpoint = ref()
const isDepartmentMode = computed(() => {
    return endpoint.value.includes('da') || endpoint.value.includes('dsc');
});
const ShowWithReportMode = (param: FormState, url: string) => {
    dialogTableVisible.value = true

    formState.value = param
    endpoint.value = url

    tabValue.value = '0'
    _summary()
}
const _summary = () => {
    axios.post('/api/task_assignments/' + endpoint.value, formState.value)
    .then(response => {
        tableData.value = response.data
    })
    .catch(error => {
        tableData.value = []
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const _report = () => {
    let api = 'get_dsc_report';
    if (endpoint.value.includes('da')) {
        api = 'get_da_report';
    }

    axios.post('/api/task_assignments/' + api, formState.value)
    .then(response => {
        reportData.value = response.data
    })
    .catch((error) => {
        reportData.value = []
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    });
}
const onChangeTab = (targetName: TabPaneName | undefined) => {
    if (targetName == 0) {
        _summary()
    } else {
        _report()
        CreateSelbox()
    }
}
interface Item {
    fullname: string,
    department_id: number,
    new: number,
    open: number,
    fixed: number,
    cnr: number,
    tfu: number,
    confirmed: number,
    nab: number
}
interface Department {
    value: number,
    label: string
}
const departments = ref<Department[]>([])
const departmentFilters = computed(() => {
    return departments.value.map(department => {
        return {
            text: department.label, // Adjust this based on your data structure
            value: department.value,   // Adjust this based on your data structure
        };
    });
});
const filterDepartment = (value: number, row: Item) => {
    return row.department_id === value
}
const CreateSelbox = () => {
    axios.get('/api/common/departments-job')
    .then(response => {
        departments.value = response.data
    })
}

defineExpose({
    ShowWithReportMode,
});
</script>