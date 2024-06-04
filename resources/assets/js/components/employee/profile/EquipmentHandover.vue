<template>
    <a-row style="margin-bottom: 30px">
        <a-col :span="4" style="margin-right: 10px;">
            <label name="name">Name</label>
            <a-input v-model:value="formState.name"></a-input>
        </a-col>
        <a-col :span="19" style="margin-right: 10px;">
            <label name="name">Detail</label>
            <a-textarea
                v-model:value="formState.detail"
                auto-size
            />
        </a-col>
    </a-row>

    <a-row style="margin-bottom: 30px">
        <a-col :span="4" style="margin-right: 10px;">
            <label name="name">Ngày</label>
            <a-date-picker
                v-model:value="formState.handover_date"
                :format="dateFormat"
                style="width:100%"
            />
        </a-col>
        <a-col :span="3" style="margin-right: 10px;">
            <label name="name">Status</label>
            <a-select v-model:value="formState.status" style="width: 100%">
                <a-select-option value="0">Using</a-select-option>
                <a-select-option value="1">Recall</a-select-option>
            </a-select>
        </a-col>
        <a-col>
            <a-button style="margin-top: 22px;" type="primary" v-on:click="onClickStoreButton">ADD</a-button>
        </a-col>
    </a-row>

    <a-table class="task" :dataSource="dataSource" :columns="columns" style = "white-space:pre-wrap; margin-bottom: 10px"
        :pagination="false" bordered>
        <template #bodyCell="{column,record}">
            <template v-if="column.key === 'action'">
                <a style="color: red" @click="onClickDelete(record.id)">Delete</a>
            </template>
        </template>
    </a-table>
</template>
<script>
import { onMounted, watch, ref } from 'vue';
import { notification } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs'
import { errorModal } from '../../Helper/error-modal.js';

export default ({
    name: 'equipment-handover',
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
            handover_date: null,
            name: null,
            detail: null,
            status: null,
        });
        const dataSource = ref([]);
        const dateFormat = "DD/MM/YYYY";
        const columns = ref([
            {
                title: 'Date',
                dataIndex: 'handover_date',
                key: 'handover_date',
                align: 'center',
                width: 100,
            },
            {
                title: 'Name',
                dataIndex: 'name',
                key: 'name',
                align: 'center',
                width: 100,
            },
            {
                title: 'Detail',
                dataIndex: 'detail',
                key: 'detail',
                align: 'center',
                width: 300
            },
            {
                title: 'Status',
                dataIndex: 'status',
                key: 'status',
                align: 'center',
                width: 15,
                customRender: (record) => {
                    return record.text === 0 ? 'Using' : 'Recall';
                }
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
                handover_date: formState.value.handover_date ? dayjs(formState.value.handover_date).format("YYYY/MM/DD") : null,
                name: formState.value.name,
                detail: formState.value.detail,
                status: formState.value.status
            };

            axios.post('/api/employee/profile/equipment_handover/store', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                _fetch()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);//show error message modally
            })
        }

        const onClickDelete = (id) => {
            axios.delete('/api/employee/profile/equipment_handover/delete', {
                params: {
                    id: id
                }
            })
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                _fetch()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);//show error message modally
            })
        }

        const _fetch = () => {
            axios.get('/api/employee/profile/get_equipment_handovers', {
                params:{
                    employee_id: props.employeeId,
                }
            })
            .then(response => {
                dataSource.value = response.data;
            })
            .catch((error) => {
                dataSource.value = [];
            });
        };

        onMounted(() => {
            watch(() => props.visible, (newVal, oldVal) => {
                if (newVal) {
                    dataSource.value = []
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
            dataSource,
            columns,
            formState,
            dateFormat,
            onClickStoreButton,
            onClickDelete
        }
    }
})
</script>