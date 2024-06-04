<template>
    <a-drawer width="1200" placement="right" :closable="false" :visible="visible" @close="onClose">
        <a-row style="margin-bottom: 20px">
            <a-col :offset="10">
                <a-card hoverable style="width: 200px;">
                    <template #cover>
                    <img alt="example" :src="formState.avatar" />
                    </template>
                    <a-card-meta :title="formState.fullname">
                    <template #description>
                        <span style="display: block;">{{ formState.department_name }}</span>
                        <span>{{ formState.position_name }}</span>
                    </template>
                    </a-card-meta>
                </a-card>
            </a-col>
        </a-row>
        <el-scrollbar height="1250px">
            <a-tabs v-model:activeKey="activeKey" :tab-position="tabPosition" animated style="height: 100%">
                <!-- Personal Info tab -->
                <a-tab-pane key="1" tab="Personal Info"><personal-info :visible="visible" :employee-id="employeeId"></personal-info></a-tab-pane>
                <!-- Alt Info tab -->
                <a-tab-pane key="2" tab="Alt Info"><alt-info :visible="activeKey === '2' ? true : false" :employee-id="employeeId"></alt-info></a-tab-pane>
                <!-- Job Details tab -->
                <a-tab-pane key="3" tab="Job Details"><job-details :visible="activeKey === '3' ? true : false" :employee-id="employeeId"></job-details></a-tab-pane>
                <!-- Mentees tab -->
                <a-tab-pane key="4" tab="Mentees"><mentee-info :visible="activeKey === '4' ? true : false" :employee-id="employeeId"></mentee-info></a-tab-pane>
                <!-- Projects tab -->
                <a-tab-pane key="5" tab="Projects"><project-info :visible="activeKey === '5' ? true : false" :employee-id="employeeId"></project-info></a-tab-pane>
                <!-- Awards tab -->
                <a-tab-pane key="6" tab="Awards"><employee-award :visible="activeKey === '6' ? true : false" :employee-id="employeeId"></employee-award></a-tab-pane>
                <!-- Activities tab -->
                <a-tab-pane key="7" tab="Activities"><employee-activity :visible="activeKey === '7' ? true : false" :employee-id="employeeId"></employee-activity></a-tab-pane>
                <!-- Reviews tab -->
                <a-tab-pane key="8" tab="Reviews"><employee-review :visible="activeKey === '8' ? true : false" :employee-id="employeeId"></employee-review></a-tab-pane>
                <!-- Equipment Handover -->
                <a-tab-pane key="9" tab="Equipment Handover"><equipment-handover :visible="activeKey === '9' ? true : false" :employee-id="employeeId"></equipment-handover></a-tab-pane>
                <!-- Violations -->
                <a-tab-pane key="10" tab="Violations"><employee-violation :visible="activeKey === '10' ? true : false" :employee-id="employeeId"></employee-violation></a-tab-pane>
            </a-tabs>
        </el-scrollbar>
    </a-drawer>
</template>
<script>
import { ref } from 'vue';
import JobDetails from './JobDetails.vue';
import MenteeInfo from './MenteeInfo.vue';
import PersonalInfo from './PersonalInfo.vue';
import AltInfo from './AltInfo.vue';
import ProjectInfo from './ProjectInfo.vue';
import EmployeeReview from './EmployeeReview.vue';
import EmployeeAward from './EmployeeAward.vue';
import EmployeeActivity from './EmployeeActivity.vue';
import EquipmentHandover from './EquipmentHandover.vue';
import EmployeeViolation from './EmployeeViolation.vue';

export default ({
    components: {
        JobDetails,
        MenteeInfo,
        PersonalInfo,
        AltInfo,
        ProjectInfo,
        EmployeeReview,
        EmployeeAward,
        EmployeeActivity,
        EquipmentHandover,
        EmployeeViolation
    },
    setup() {
        const visible = ref(false);
        const tabPosition = ref('left');
        const activeKey = ref('1');
        const formState = ref({});
        const employeeId = ref("");
        const employee = ref({});

        const ShowWithProfileDrawerMode = (id) => {
            visible.value = true;
            activeKey.value = '1'
            employeeId.value = id

            _fetch()
        };

        const _fetch = () => {
            axios.get('/api/employee/profile/get_main_info', {
                params:{
                    employee_id: employeeId.value,
                }
            })
            .then(response => {
                formState.value = response.data;
            })
            .catch((error) => {
                formState.value = {};
            });
        }

        const onClose = () => {
            visible.value = false;
        };

        return {
            visible,
            tabPosition,
            activeKey,
            formState,
            employeeId,
            ShowWithProfileDrawerMode,
            onClose,
        };
    },
})
</script>