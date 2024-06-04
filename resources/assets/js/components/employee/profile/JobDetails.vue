<template>
    <a-row style="margin-top: 20px">
        <a-col :span="10" :offset="1">
            <label name="name">Ngày bắt đầu làm việc</label>
            <a-date-picker
                v-model:value="formState.start_date"
                :format="dateFormat"
                style="width:100%"
                @change="onChangeCreatedAt"
            />
        </a-col>
        <a-col :span="10" :offset="2">
            <label name="name">Thời gian đã làm việc từ khi bắt đầu</label>
            <a-input readonly :value="workDuration" />
        </a-col>
    </a-row>

    <a-row style="margin-top: 20px">
        <a-col :span="10" :offset="1">
            <label name="name">Ngày bắt đầu làm việc chính thức</label>
            <a-date-picker
                v-model:value="formState.official_start_date"
                :format="dateFormat"
                style="width:100%"
                @change="onChangeDateOfficial"
            />
        </a-col>
        <a-col :span="10" :offset="2">
            <label name="name">Thời gian đã làm việc tính từ khi chính thức</label>
            <a-input readonly :value="officialWorkDuration" />
        </a-col>
    </a-row>

    <a-row style="margin-top: 20px">
        <a-col :span="10" :offset="1">
            <label name="name">Ngày nghỉ việc</label>
            <a-date-picker
                v-model:value="formState.termination_date"
                :format="dateFormat"
                style="width:100%"
            />
        </a-col>
    </a-row>

    <a-row style="margin-top: 20px">
        <a-col :span="22" :offset="1">
            <label name="name">Thời gian làm việc gián đoạn và lý do</label>
            <a-textarea
                v-model:value="formState.disrupted_employment"
                auto-size
            />
        </a-col>
    </a-row>

    <a-row style="margin-top: 20px">
        <a-col :offset="20">
            <a-button style="width:100px;" type="primary" v-on:click="onClickUpdateButton">Cập nhật</a-button>
        </a-col>
    </a-row>

</template>
<script>
import { onMounted, watch, computed, ref } from 'vue';
import { notification } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs'
import { errorModal } from '../../Helper/error-modal.js';
import { calculateWorkDuration } from '../../Helper/duration-datetime.js';

export default ({
    name: 'job-details',
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
    setup(props) {
        const { t } = useI18n();
        const errorMessages = ref("");
        const formState = ref({
            start_date: null,
            official_start_date: null,
            termination_date: null,
            disrupted_employment: null
        });
        const dateFormat = "DD/MM/YYYY";

        const onChangeCreatedAt = () => {
            return calculateWorkDuration(formState.value.start_date)
        }

        const onChangeDateOfficial = () => {
            return calculateWorkDuration(formState.value.official_start_date)
        }

        const workDuration = computed(() => {
            return onChangeCreatedAt()
        })

        const officialWorkDuration = computed(() => {
            return onChangeDateOfficial()
        })

        const onClickUpdateButton = () => {
            let submitData = {
                employee_id: props.employeeId,
                start_date: formState.value.start_date ? dayjs(formState.value.start_date).format("YYYY/MM/DD") : null,
                official_start_date: formState.value.official_start_date ? dayjs(formState.value.official_start_date).format("YYYY/MM/DD") : null,
                termination_date: formState.value.termination_date ? dayjs(formState.value.termination_date).format("YYYY/MM/DD") : null,
                disrupted_employment: formState.value.disrupted_employment,
            };

            axios.patch('/api/employee/profile/job_detail/update', submitData)
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

        const _fetch = () => {
            axios.get('/api/employee/profile/get_job_details', {
                params:{
                    employee_id: props.employeeId,
                }
            })
            .then(response => {
                formState.value = response.data;

                formState.value.start_date = response.data.start_date ? dayjs(response.data.start_date, dateFormat) : null
                formState.value.official_start_date = response.data.official_start_date ? dayjs(response.data.official_start_date, dateFormat) : null
                formState.value.termination_date = response.data.termination_date ? dayjs(response.data.termination_date, dateFormat) : null
            })
            .catch((error) => {
                formState.value = {};
            });
        };

        onMounted(() => {
            watch(() => props.visible, (newVal, oldVal) => {
                if (newVal) {
                    formState.value = {}
                
                    _fetch();
                }
            },
            {
                immediate: true
            })
        });

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        return {
            formState,
            dateFormat,
            onChangeCreatedAt,
            onChangeDateOfficial,
            workDuration,
            officialWorkDuration,
            onClickUpdateButton
        }
    }
})
</script>