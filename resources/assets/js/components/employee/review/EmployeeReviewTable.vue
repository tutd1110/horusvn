<template>
    <review-editor ref="modalReviewEditor"></review-editor>
    <a-table class="task" :dataSource="dataSource" :columns="columns" :pagination="false" bordered>
        <template #bodyCell="{column,record}">
            <template v-if="column.key === 'progress'">
                <a-tag v-if="record.progress">
                    {{ record.progress }}
                </a-tag>
            </template>
            <template v-if="column.key === 'start_date'">
                <el-date-picker
                    v-model="record.start_date"
                    type="date"
                    format="DD/MM/YYYY"
                    value-format="YYYY/MM/DD"
                    class="none-border"
                    style="width: 100%;"
                    @change="onChangeStartDate(record.id, $event)"
                />
            </template>
            <template v-if="column.key === 'next_date'">
                <el-date-picker
                    v-model="record.next_date"
                    type="date"
                    format="DD/MM/YYYY"
                    value-format="YYYY/MM/DD"
                    class="none-border"
                    style="width: 100%;"
                    @change="onChangeNextDate(record.id, $event)"
                />
            </template>
            <template v-if="column.key === 'action'">
                <edit-outlined style="margin-right: 5px; color: blue" v-on:click="onEditReviewModal(record.id)"/>
                <download-outlined style="margin-right: 5px; color: green" v-on:click="doExport(record.id)"/>
                <delete-outlined
                    style="color: red"
                    v-on:click="onClickDeleteButton(record.id)"
                />
            </template>
        </template>
    </a-table>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { onMounted, watch, ref } from 'vue';
import { EditOutlined, DeleteOutlined, DownloadOutlined } from '@ant-design/icons-vue';
import ReviewEditor from '../ReviewEditor.vue';
import { errorModal } from '../../Helper/error-modal.js';

export default ({
    components: {
        EditOutlined,
        DownloadOutlined,
        DeleteOutlined,
        ReviewEditor
    },
    name: 'employee-review-table',
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
    emits: ['saved'],
    setup(props, { emit }) {
        const { t } = useI18n();
        const dataSource = ref([]);
        const errorMessages = ref("");
        const modalReviewEditor = ref();
        const columns = ref([
            {
                title: 'Họ và tên',
                dataIndex: 'fullname',
                key: 'fullname',
                align: 'center',
                width: 150
            },
            {
                title: 'Trạng thái đánh giá',
                dataIndex: 'progress',
                key: 'progress',
                align: 'center',
                width: 100
            },
            {
                title: 'Ngày đánh giá',
                dataIndex: 'start_date',
                key: 'start_date',
                align: 'center',
                width: 100
            },
            {
                title: 'Loại đánh giá',
                dataIndex: 'period',
                key: 'period',
                align: 'center',
                width: 100
            },
            {
                title: 'Ngày đánh giá tiếp theo',
                dataIndex: 'next_date',
                key: 'next_date',
                align: 'center',
                width: 100
            },
            {
                title: 'Approve',
                dataIndex: 'status',
                key: 'status',
                align: 'center',
                width: 100
            },
            {
                title: 'Action',
                dataIndex: '',
                key: 'action',
                align: 'center',
                width:  15,
            }
        ]);

        const onChangeStartDate = (id, value) => {
            update({'id': id, 'start_date': value})
        }

        const onChangeNextDate = (id, value) => {
            update({'id': id, 'next_date': value})
        }

        const update = (submitData) => {
            axios.patch('/api/review/update', submitData)
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

        const onEditReviewModal = (review_id) => {
            modalReviewEditor.value.showEditReviewModal(review_id);
        }

        const onClickDeleteButton = (review_id) => {
            Modal.confirm({
                title: 'Bạn có chắc chắn xoá thông tin đánh giá này?',
                okText: 'Ok',
                cancelText: 'Huỷ',
                onOk() {
                    axios.delete('/api/employee/review/delete', {
                        params: {
                            review_id: review_id
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
                },
            })
        }

        const doExport = (review_id) => {
            const url = `/preview_review/?id=${review_id}`;
            
            window.open(url, '_blank');
        }

        const _fetch = () => {
            //get reviews by employee id
            axios.get('/api/employee/review/get_reviews_by_employee_id', {
                params: {
                    employee_id: props.employeeId
                }
            })
            .then(response => {
                dataSource.value = response.data;
            })
            .catch(error => {
                dataSource.value = [];
            })
        };

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
            modalReviewEditor,
            dataSource,
            columns,
            onChangeStartDate,
            onChangeNextDate,
            onEditReviewModal,
            onClickDeleteButton,
            doExport
        };
    }
})
</script>