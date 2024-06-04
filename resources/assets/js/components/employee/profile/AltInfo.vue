<template>
    <a-row style="margin-top: 20px; margin-bottom: 30px">
        <a-col :span="6" style="margin-right: 10px;">
            <label name="fullname">Họ và tên con cái</label>
            <a-input v-model:value="childState.fullname"></a-input>
        </a-col>
        <a-col :span="3" style="margin-right: 10px;">
            <label name="gender">Giới tính</label>
            <a-select v-model:value="childState.gender" allow-clear style="width: 100%">
                <a-select-option value="male">Male</a-select-option>
                <a-select-option value="female">Female</a-select-option>
            </a-select>
        </a-col>
        <a-col :span="4" style="margin-right: 10px;">
            <label name="bỉthday">Ngày sinh</label>
            <a-date-picker
                v-model:value="childState.birthday"
                format="DD/MM/YYYY"
                style="width:100%"
            />
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
    <a-row style="margin-top: 20px">
        <a-col :span="10" style="margin-right: 10px;">
            <label name="name">Tên người liên hệ</label>
            <a-input allow-clear v-model:value="formState.contact_name" />
        </a-col>
        <a-col :span="10">
            <label name="name">Mối liên hệ</label>
            <a-input allow-clear v-model:value="formState.relationship" />
        </a-col>
    </a-row>

    <a-row style="margin-top: 20px">
        <a-col :span="10">
            <label name="name">Số điện thoại</label>
            <a-input allow-clear v-model:value="formState.contact_number" />
        </a-col>
    </a-row>

    <a-row style="margin-top: 20px">
        <a-col :offset="20">
            <a-button style="width:100px;" type="primary" v-on:click="onClickUpdateButton">Cập nhật</a-button>
        </a-col>
    </a-row>
</template>
<script>
import { onMounted, watch, ref } from 'vue';
import { notification } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs'
import { errorModal } from '../../Helper/error-modal.js';

export default ({
    name: 'alt-info',
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
        const formState = ref({});
        const childState = ref([]);
        const dataSource = ref([]);
        const columns = ref([
            {
                title: 'Fullname',
                dataIndex: 'fullname',
                key: 'fullname',
                align: 'center',
                width: 100,
            },
            {
                title: 'Gender',
                dataIndex: 'gender',
                key: 'gender',
                align: 'center',
                width: 100,
            },
            {
                title: 'Birthday',
                dataIndex: 'birthday',
                key: 'birthday',
                align: 'center',
                width: 100,
            },
            {
                title: 'Action',
                dataIndex: '',
                key: 'action',
                align: 'center',
                width:  15,
            }
        ]);

        const onClickUpdateButton = () => {
            let submitData = {
                employee_id: props.employeeId,
                contact_name: formState.value.contact_name,
                relationship: formState.value.relationship,
                contact_number: formState.value.contact_number
            };

            axios.patch('/api/employee/profile/alt_info/update', submitData)
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

        const onClickStoreButton = () => {
            let submitData = {
                employee_id: props.employeeId,
                birthday: childState.value.birthday ? dayjs(childState.value.birthday).format("YYYY/MM/DD") : null,
                fullname: childState.value.fullname,
                gender: childState.value.gender
            };

            axios.post('/api/employee/profile/employee_children/store', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                childState.value = []

                _fetch()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);//show error message modally
            })
        }

        const onClickDelete = (id) => {
            axios.delete('/api/employee/profile/employee_children/delete', {
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
                errorModal(t, errorMessages);
            })
        }

        const _fetch = () => {
            axios.get('/api/employee/profile/get_alt_info', {
                params:{
                    employee_id: props.employeeId,
                }
            })
            .then(response => {
                if (response.data.alt_info) {
                    formState.value = response.data.alt_info
                };
                dataSource.value = response.data.childrens;
            })
            .catch((error) => {
                formState.value = {};
                dataSource.value = [];
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
            dataSource,
            columns,
            formState,
            childState,
            onClickStoreButton,
            onClickUpdateButton,
            onClickDelete
        }
    }
})
</script>