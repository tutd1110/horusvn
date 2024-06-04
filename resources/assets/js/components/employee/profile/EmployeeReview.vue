<template>
    <employee-review-table :visible="visible" :employee-id="employeeId"></employee-review-table>
</template>
<script>
import { ref } from 'vue';
import EmployeeReviewTable from '../review/EmployeeReviewTable.vue';

export default ({
    name: 'employee-review',
    props: {
        visible: {
            type: Boolean,
            required: true,
        },
        employeeId: {
            type: Number,
            required: true,
        }
    },
    components: {
        EmployeeReviewTable
    },
    setup(props) {
        return {

        };
    }
})
</script>