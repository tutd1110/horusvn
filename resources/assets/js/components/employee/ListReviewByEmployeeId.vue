<template>
    <a-modal v-model:visible="visible" style="width:1100px; font-weight: bold" :footer="null" :maskClosable="false"
        :closable="true" :title="title">
        <employee-review-table :visible="visible" :employee-id="employeeId"></employee-review-table>
    </a-modal>
</template>
<script>
import { ref } from 'vue';
import EmployeeReviewTable from './review/EmployeeReviewTable.vue';

export default ({
    components: {
        EmployeeReviewTable
    },
    emits: ['saved'],
    setup(props, { emit }) {
        const title = ref("");
        const visible = ref(false);
        const employeeId = ref();

        //List Review By Employee Id Modal
        const showListReviewByEmployeeIdModal = (employee_id) => {
            title.value = "Employee's Reviews"
            visible.value = true;

            employeeId.value = employee_id
        };

        return {
            visible,
            title,
            employeeId,
            showListReviewByEmployeeIdModal,
        };
    }
})
</script>