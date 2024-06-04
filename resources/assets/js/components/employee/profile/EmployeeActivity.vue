<template>
    <a-row>
        <a-col :span="4" :offset="1" style="margin-right: 10px;">
            <label name="name">Ngày</label>
            <a-date-picker
                v-model:value="formState.start_date"
                :format="dateFormat"
                style="width:100%"
            />
        </a-col>
        <a-col :span="15" style="margin-right: 10px;">
            <label name="name">Nội dung</label>
            <a-textarea
                v-model:value="formState.content"
                auto-size
            />
        </a-col>
        <a-col>
            <a-button style="margin-top: 22px;" type="primary" v-on:click="onClickStoreButton">ADD</a-button>
        </a-col>
    </a-row>
    <a-row style="margin-top: 50px; margin-right: 130px">
        <a-col :offset="5">
            <a-timeline mode="alternate">
                <template v-for="(item, index) in dataSource">
                    <a-timeline-item>
                        <span style="color: green; display: block;">{{ item.start_date }}</span>{{ item.content }}
                        <a style="color: red" @click="onClickDelete(item.id)">Delete</a>
                    </a-timeline-item>
                </template>
            </a-timeline>
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
    name: 'employee-award',
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
            content: null,
        });
        const dataSource = ref([]);
        const dateFormat = "DD/MM/YYYY";

        const onClickStoreButton = () => {
            let submitData = {
                employee_id: props.employeeId,
                start_date: formState.value.start_date ? dayjs(formState.value.start_date).format("YYYY/MM/DD") : null,
                content: formState.value.content
            };

            axios.post('/api/employee/profile/activity/store', submitData)
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
            axios.delete('/api/employee/profile/activity/delete', {
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
            axios.get('/api/employee/profile/get_activities', {
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
            formState,
            dateFormat,
            onClickStoreButton,
            onClickDelete
        }
    }
})
</script>