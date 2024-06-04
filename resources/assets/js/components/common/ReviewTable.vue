<template>
    <a-table :dataSource="content" :columns="columns" style = "white-space:pre-wrap; margin-bottom: 10px"
        :pagination="false" bordered>
        <template #summary>
            <a-table-summary-row>
                <a-table-summary-cell>Total</a-table-summary-cell>
                <a-table-summary-cell />
                <template v-for="employee in groupEmployee">
                    <a-table-summary-cell style="text-align: center;" v-if="employee.position === 0">
                        <a-typography-text type="danger">{{ totals.totalEmployeePoint }}</a-typography-text>
                    </a-table-summary-cell>
                    <a-table-summary-cell style="text-align: center;" v-else-if="employee.position === 0.5">
                        <a-typography-text type="danger">{{ totals.totalMentorPoint }}</a-typography-text>
                    </a-table-summary-cell>
                    <a-table-summary-cell style="text-align: center;" v-else-if="employee.position === 1">
                        <a-typography-text type="danger">{{ totals.totalLeaderPoint }}</a-typography-text>
                    </a-table-summary-cell>
                    <a-table-summary-cell style="text-align: center;" v-else-if="employee.position >= 2 && (employeePositionLogin >= 2 || screen === 'review_editor')">
                        <a-typography-text type="danger">{{ totals.totalPmPoint }}</a-typography-text>
                    </a-table-summary-cell>
                </template>
            </a-table-summary-row>
        </template>
        <template #bodyCell="{column,record}">
            <template v-if="column.key !== 'content' && column.key !== 'stt'">
                <template v-if="screen==='reviewer' && ((employeePositionLogin === 1 && column.key === 'employee_point')
                        || (employeePositionLogin === 2 && (column.key === 'employee_point'))
                        || employeePositionLogin === 3)">
                    <span>{{ record[column.key] }}</span>
                </template>
                <template v-else>
                    <a-input-number
                        id="inputNumberPoint"
                        v-model:value="record[column.key]"
                        :bordered="false"
                        style="width: 100%"
                        :min="1"
                        :max="5"
                        @blur="onChangePoint(record.id, column.key, $event)"
                    />
                </template>
            </template>
        </template>
    </a-table>
</template>
<script>
import { ref } from 'vue';
import { notification } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { errorModal } from '../Helper/error-modal.js';

export default ({
    name: 'review-table',
    props: {
        screen: {
            type: String,
            required: true,
        },
        content: {
            type: Array,
            required: true,
        },
        columns: {
            type: Array,
            required: true,
        },
        groupEmployee: {
            type: Array,
            required: true,
        },
        employeePositionLogin: {
            type: Number,
            required: true
        },
        totals: {
            type: Object,
            required: true
        },
    },
    setup(props) {
        const { t } = useI18n();
        const errorMessages = ref("");

        const onChangePoint = (id, column, event) => {
            updateEmployeeReviewPoint({'id': id, [column]: event.target.value})
        }

        const updateEmployeeReviewPoint = (submitData) => {
            axios.patch('/api/employee/review/update', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);//show error message modally
            })
        }

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        return {
            onChangePoint
        }
    }
})
</script>
<style lang="scss">
    .ant-table-thead th {
        font-weight: bold !important;
    }

    .ant-table-summary {
        z-index: auto !important;
    }

    #inputNumberPoint {
        text-align: center;
    }
</style>