<template>
    <a-row>
        <a-col :span="10" :offset="1">
            <label name="name">Họ và tên</label>
            <a-input allow-clear v-model:value="formState.fullname" />
        </a-col>
        <a-col :span="10" :offset="2">
            <label name="name">Ngày sinh</label>
            <a-date-picker
                v-model:value="formState.date_of_birth"
                :format="dateFormat"
                style="width:100%"
            />
        </a-col>
    </a-row>

    <a-row style="margin-top: 20px">
        <a-col :span="10" :offset="1">
            <label name="name">Email</label>
            <a-input allow-clear v-model:value="formState.email" />
        </a-col>
        <a-col :span="10" :offset="2">
            <label name="name">Số điện thoại</label>
            <a-input allow-clear v-model:value="formState.phone" />
        </a-col>
    </a-row>

    <a-row style="margin-top: 20px">
        <a-col :span="10" :offset="1">
            <label name="name">Giới tính</label>
            <a-select v-model:value="formState.gender" style="width: 100%">
                <a-select-option value="male">Male</a-select-option>
                <a-select-option value="female">Female</a-select-option>
                <a-select-option value="other">Other</a-select-option>
            </a-select>
        </a-col>
        <a-col :span="10" :offset="2">
            <label name="name">Căn cước</label>
            <a-input allow-clear v-model:value="formState.id_number" />
        </a-col>
    </a-row>

    <a-row style="margin-top: 20px">
        <a-col :span="10" :offset="1">
            <label name="name">Ngày cấp</label>
            <a-date-picker
                v-model:value="formState.date_of_issue"
                :format="dateFormat"
                style="width:100%"
            />
        </a-col>
        <a-col :span="10" :offset="2">
            <label name="name">Nơi cấp</label>
            <a-textarea
                v-model:value="formState.place_of_issue"
                auto-size
                allow-clear
            />
        </a-col>
    </a-row>

    <a-row style="margin-top: 20px">
        <a-col :span="10" :offset="1">
            <label name="name">Quê quán</label>
            <a-textarea
                v-model:value="formState.hometown"
                auto-size
                allow-clear
            />
        </a-col>
        <a-col :span="10" :offset="2">
            <label name="name">Thường trú</label>
            <a-textarea
                v-model:value="formState.origin_place"
                auto-size
                allow-clear
            />
        </a-col>
    </a-row>
    <a-row style="margin-top: 20px">
        
        <a-col :span="10" :offset="1">
            <label name="name">Chỗ ở hiện tại</label>
            <a-textarea
                v-model:value="formState.current_place"
                auto-size
                allow-clear
            />
        </a-col>
        <a-col :span="10" :offset="2">
            <label name="name">Ghi chú</label>
            <a-textarea
                v-model:value="formState.note"
                auto-size
                allow-clear
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
import { onMounted, watch, ref } from 'vue';
import { notification } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs'
import { errorModal } from '../../Helper/error-modal.js';

export default ({
    name: 'personal-info',
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
            fullname: null,
            date_of_birth: null,
            email: null,
            phone: null,
            gender: null,
            id_number: null,
            date_of_issue: null,
            place_of_issue: null,
            hometown: null,
            current_place: null,
            origin_place: null,
            note:null,
        });
        const dateFormat = "DD/MM/YYYY";

        const onClickUpdateButton = () => {
            let submitData = {
                employee_id: props.employeeId,
                fullname: formState.value.fullname,
                date_of_birth: formState.value.date_of_birth ? dayjs(formState.value.date_of_birth).format("YYYY/MM/DD") : null,
                email: formState.value.email,
                phone: formState.value.phone,
                gender: formState.value.gender,
                id_number: formState.value.id_number,
                date_of_issue: formState.value.date_of_issue ? dayjs(formState.value.date_of_issue).format("YYYY/MM/DD") : null,
                place_of_issue: formState.value.place_of_issue,
                hometown: formState.value.hometown,
                current_place: formState.value.current_place,
                origin_place: formState.value.origin_place,
                note:formState.value.note
            };

            axios.patch('/api/employee/profile/personal_info/update', submitData)
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
            axios.get('/api/employee/profile/get_personal_info', {
                params:{
                    employee_id: props.employeeId,
                }
            })
            .then(response => {
                formState.value = response.data;

                formState.value.date_of_birth = response.data.date_of_birth ? dayjs(response.data.date_of_birth, dateFormat) : null
                formState.value.date_of_issue = response.data.date_of_issue ? dayjs(response.data.date_of_issue, dateFormat) : null
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
            onClickUpdateButton
        }
    }
})
</script>