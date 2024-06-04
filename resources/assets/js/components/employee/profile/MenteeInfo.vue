<template>
    <a-row>
        <a-col :span="4" style="margin-right: 10px;">
            <label>Mentee</label>
            <a-form-item>
                <a-select
                    allow-clear
                    v-model:value="formState.mentee_id"
                    style="width:100%;"
                    :options="employeeSelbox"
                    :field-names="{ label:'fullname', value: 'id' }"
                ></a-select>
            </a-form-item>
        </a-col>
        <a-col>
            <a-button style="margin-top: 22px;" type="primary" v-on:click="onClickStoreButton">ADD</a-button>
        </a-col>
    </a-row>
    <a-table class="task" :dataSource="dataSource" :columns="columns" :pagination="false" bordered>
        <template #bodyCell="{column,record}">
            <template v-if="column.key === 'action'">
                <a style="color: red" @click="onClickDelete(record.id)">Delete</a>
            </template>
        </template>
    </a-table>
</template>
<script>
import { onMounted, watch, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { notification } from 'ant-design-vue';
import { errorModal } from '../../Helper/error-modal.js';
import dayjs from 'dayjs';

export default ({
    name: 'mentee-info',
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
        const employeeSelbox = ref([]);
        const formState = ref({});
        const dataSource = ref();
        const columns = ref([
            {
                title: 'Mentee',
                dataIndex: 'mentee_name',
                key: 'mentee_name',
                fixed: false,
                align: 'center',
                width:  50,
            },
            {
                title: 'Action',
                dataIndex: '',
                key: 'action',
                align: 'center',
                width:  15,
            }
        ]);

        const onClickStoreButton = () => {
            let submitData = {
                employee_id: props.employeeId,
                mentee_id: formState.value.mentee_id
            };

            axios.post('/api/employee/profile/mentee/store', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                getMentees()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);//show error message modally
            })
        }

        const onClickDelete = (id) => {
            axios.delete('/api/employee/profile/mentee/delete', {
                params: {
                    id: id
                }
            })
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                getMentees()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);//show error message modally
            })
        }

        const _fetch = () => {
            //create select boxes
            axios.get('/api/common/get_employees')
            .then(response => {
                employeeSelbox.value = response.data;
            }).catch((error) => {
                employeeSelbox.value = [];
            });

            //get list mentees
            getMentees();
        };

        const getMentees = () => {
            //get list mentees
            axios.get('/api/employee/profile/get_mentees', {
                params:{
                    employee_id: props.employeeId,
                }
            })
            .then(response => {
                dataSource.value = response.data;
            }).catch((error) => {
                dataSource.value = [];
            });
        }

        onMounted(() => {
            watch(() => props.visible, (newVal, oldVal) => {
                if (newVal) {
                    dataSource.value = []
                
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
            employeeSelbox,
            dataSource,
            columns,
            onClickStoreButton,
            onClickDelete
        }
    }
})
</script>