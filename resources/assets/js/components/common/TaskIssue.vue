<template>
    <span>{{ title }}</span>
    <a-col style="margin-bottom: 10px;" v-if="field === 'task_assignment_id'">
        <a-button
            type="primary"
            v-on:click="onAddNewButton()"
        >NEW</a-button>
    </a-col>
    <a-table class="task" :dataSource="dataSource" :loading="isLoading" :columns="columns" :scroll="{ x: 1000, y: 1000 }"
    :pagination="{position: ['bottomCenter'],pageSize:20,showSizeChanger: false}" bordered>
        <template #bodyCell="{column,record}">
            <template v-if="column.key === 'work_date'">
                <a-date-picker
                    style="width: 100%"
                    :bordered="false"
                    :disabled="employeeIdLogin != record.performer && position < 1"
                    v-model:value="record.work_date"
                    :format="dateFormat"
                    @change="onChangeWorkDate(record.id, $event)"
                />
            </template>
            <template v-if="column.key === 'user_id'">
                <a-select
                    :bordered="false"
                    ref="select"
                    v-model:value="record.performer"
                    :disabled="employeeIdLogin != record.performer && position < 1"
                    style="width:100%;"
                    :options="employeeSelbox"
                    :field-names="{ label:'fullname', value: 'id' }"
                    show-search
                    allow-clear
                    :filterOption="filterUserOption"
                    @change="onChangeTaskAssignmentUser(record.task_assignment_id, $event)"
                ></a-select>
            </template>
            <template v-if="column.key === 'sticker_id'">
                <a-select
                    :bordered="false"
                    ref="select"
                    v-model:value="record.sticker_id"
                    style="width:100%;"
                    :disabled="position < 1"
                    :options="stickerSelbox"
                    :field-names="{ label:'name', value: 'id' }"
                    show-search
                    allow-clear
                    :filterOption="filterStickerOption"
                    @change="onChangeSticker(record.id, $event)"
                ></a-select>
            </template>
            <template v-if="column.key === 'priority'">
                <a-select
                    :bordered="false"
                    ref="select"
                    v-model:value="record.priority"
                    :disabled="position < 1"
                    style="width:100%;"
                    :options="prioritySelbox"
                    :field-names="{ label:'label', value: 'id' }"
                    allow-clear
                    @change="onChangePriority(record.id, $event)"
                ></a-select>
            </template>
            <template v-if="column.key === 'weight'">
                <template v-if="position >= 1">
                    <a-input :bordered="false" v-model:value="record.weight" @blur="onChangeWeight(record.id, $event)"/>
                </template>
                <template v-else>
                    <a-input :bordered="false" readonly :value="record.weight"/>
                </template>
            </template>
            <template v-if="column.key === 'estimate_time'">
                <a-input
                    :bordered="false"
                    :disabled="employeeIdLogin != record.performer && position < 1"
                    v-model:value="record.estimate_time"
                    @blur="onChangeEstimateTime(record.id, $event)"
                />
            </template>
            <template v-if="column.key === 'time_spent'">
                <a-input
                    :bordered="false"
                    :disabled="employeeIdLogin != record.performer && position < 1"
                    v-model:value="record.time_spent"
                    @blur="onChangeTimeSpent(record.id, $event)"
                />
            </template>
            <template v-if="column.key === 'description'">
                <a-textarea
                    :bordered="false"
                    :disabled="employeeIdLogin != record.performer && position < 1"
                    v-model:value="record.description"
                    @blur="onChangeDescription(record.id, $event)"
                    auto-size
                />
            </template>
            <template v-if="column.key === 'type'">
                <a-select
                    :bordered="false"
                    v-model:value="record.type"
                    allow-clear
                    :disabled="true"
                    style="width:100%;"
                >
                    <a-select-option v-for="option in typeSelbox" :value="option.value">
                        <span :class="['task-timing-type-' + option.value]">{{ option.label }}</span>
                    </a-select-option>
                </a-select>
            </template>
            <template v-if="column.key === 'action'">
                <template v-if="dataSource.length > 1">
                    <DeleteFilled
                        v-if="field === 'task_assignment_id' && employeeIdLogin == record.performer"
                        style="color: red"
                        v-on:click="onDeleteButton(record.id)"
                    />
                </template>
            </template>
        </template>
    </a-table>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import { onMounted, ref, h, watch } from 'vue';
import dayjs from 'dayjs';
import { useI18n } from 'vue-i18n';
import { PlusCircleTwoTone, DeleteFilled } from '@ant-design/icons-vue';
import {
    onCommonChangeSticker,
    onCommonChangePriority
} from '../Helper/helpers.js';

export default ({
    components: {
        PlusCircleTwoTone,
        DeleteFilled
    },
    name: 'task-issue',
    props: {
        id: {
            type: Number,
            required: true
        },
        field: {
            type: String,
            required: true
        },
        selectedDepartmentId: {
            type: [Number, null],
            required: true,
            default: null
        },
        refreshKey: {
            type: Number,
            required: true,
        },
        visible: {
            type: Boolean,
            required: true,
        },
    },
    emits: ['saved'],
    setup(props, { emit }) {
        const title = ref("");
        const { t } = useI18n();
        const dataSource = ref();
        const dateFormat = 'DD/MM/YYYY';
        const strictDateFormat = "YYYY/MM/DD HH:mm:ss";
        const selectedRecord = ref("");//Store ID of selected row
        const countChanged = ref(0);
        const taskIssueIds = ref([]);
        const employeeIdLogin = ref();
        const position = ref();
        const field = ref("");
        const errorMessages = ref("");
        const formState = ref({});
        const typeSelbox = ref();
        const stickerSelbox = ref([]);
        const prioritySelbox = ref([]);
        const employeeSelbox = ref([]);
        const isLoading = ref(false);
        const columns = ref([
            {
                title: 'Work Date',
                dataIndex: 'work_date',
                key: 'work_date',
                align: 'center',
                width: 40,
                sorter: (a, b) => {
                    const dateA = dayjs(a.work_date, dateFormat);
                    const dateB = dayjs(b.work_date, dateFormat);
                    return dateA - dateB;
                },
            },
            {
                title: 'Performer',
                dataIndex: 'user_id',
                key: 'user_id',
                align: 'center',
                width: 70
            },
            {
                title: 'Type',
                dataIndex: 'sticker_id',
                key: 'sticker_id',
                align: 'center',
                width: 100
            },
            {
                title: 'Level',
                dataIndex: 'priority',
                key: 'priority',
                align: 'center',
                width: 30
            },
            {
                title: 'Weight',
                dataIndex: 'weight',
                key: 'weight',
                align: 'center',
                width: 30
            },
            {
                title: 'Estimate Time',
                dataIndex: 'estimate_time',
                key: 'estimate_time',
                align: 'center',
                width: 30
            },
            {
                title: 'Time Spent',
                dataIndex: 'time_spent',
                key: 'time_spent',
                align: 'center',
                width: 30
            },
            {
                title: 'Description',
                dataIndex: 'description',
                key: 'description',
                align: 'center',
                width: 150
            },
            {
                title: 'Issue',
                dataIndex: 'type',
                key: 'type',
                align: 'center',
                width: 50
            },
            {
                title: '',
                dataIndex: 'action',
                key: 'action',
                align: 'center',
                width: 20
            }
        ]);

        const CreateSelbox = () => {
            //create select boxes
            axios.get('/api/task_timings/get_selboxes', {
                params:{
                    department_id: props.selectedDepartmentId,
                }
            })
            .then(response => {
                typeSelbox.value = response.data.type;
                employeeSelbox.value = response.data.employees;
                stickerSelbox.value = response.data.stickers;
                prioritySelbox.value = response.data.priorities;
                employeeIdLogin.value = response.data.employee_id_login;
                position.value = response.data.position;
            })
            .catch((error) => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                //When search target data does not exist
                typeSelbox.value = []; //typeSelbox empty
                employeeSelbox.value = [];
                stickerSelbox.value = [];
                prioritySelbox.value = [];
                employeeIdLogin.value = "";
                position.value = "";
                errorModal();//Show error message modally
            });
        }

        const transferData = (data) => {
            var newData = [];

            data.forEach(function(item, index) {
                let value = {
                    id: item.id,
                    name: item.name,
                    task_assignment_id: item.task_assignment_id,
                    work_date: item.work_date ? dayjs(item.work_date, dateFormat) : "",
                    sticker_id: item.sticker_id,
                    priority: item.priority,
                    weight: item.weight,
                    task_user_id: item.task_user_id,
                    performer: item.assigned_user_id,
                    estimate_time: item.estimate_time,
                    time_spent: item.time_spent,
                    description: item.description,
                    type: item.type,
                    updated_at: item.updated_at,
                    fullname: item.fullname,
                    department_id: item.department_id
                };

                newData.push(value);
            });

            return newData;
        };

        const errorModal = () => {
            Modal.warning({
                title: t('message.MSG-TITLE-W'),
                content: h('ul', {}, errorMessages.value.split('<br>').map((error) => { return h('li', error) })),
                onOk() {
                    isLoading.value = false;
                },
            });
        };

        const _fetch = () => {
            title.value = "";
            taskIssueIds.value = [];
            CreateSelbox();

            isLoading.value = true;
            //get data from task id
            axios.get('/api/task_timings/issues', {
                params: {
                    id: props.id,
                    column: props.field
                }
            })
            .then(response => {
                isLoading.value = false;
                dataSource.value = transferData(response.data);
                if (dataSource.value.length > 0) {
                    title.value = dataSource.value[0].name+' - '+dataSource.value[0].department_id+' - '+dataSource.value[0].fullname
                }

                taskIssueIds.value = response.data.map(item => item.id);
            })
            .catch(error => {
                isLoading.value = false;
                dataSource.value = [];
                taskIssueIds.value = [];
            })
        };

        const onAddNewButton = () => {
            axios.post('/api/task_assignments/clone-by-id', {id: props.id, field: props.field})
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                _fetch()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(false);//show error message modally
            })
        }

        const onChangeWorkDate = (id, value) => {
            let work_date = "";
            if (value !== null && value !== undefined) {
                work_date = dayjs(value).format(strictDateFormat);
            }

            update({'id': id, 'work_date': work_date, 'task_id': selectedRecord.value})
        }

        const onChangeSticker = (id, value) => {
            const args = [
                id, value, dataSource.value, stickerSelbox.value, prioritySelbox.value
            ]
            let submitData = onCommonChangeSticker(...args)

            //save it to DB
            update(submitData)
        }

        const onChangePriority = (id, value) => {
            const args = [
                id, value, dataSource.value, stickerSelbox.value, prioritySelbox.value
            ]
            let submitData = onCommonChangePriority(...args)

            //save it to DB
            update(submitData)
        }

        const onChangeWeight = (id, event) => {
            update({'id': id, 'weight': event.target.value})
        }

        const onChangeEstimateTime = (id, event) => {
            update({'id': id, 'estimate_time': event.target.value})
        }

        const onChangeTimeSpent = (id, event) => {
            update({'id': id, 'time_spent': event.target.value})
        }

        const onChangeDescription = (id, event) => {
            update({'id': id, 'description': event.target.value})
        }

        const onChangeTaskAssignmentUser = (id, value) => {
            let user_id = value ? value : ""

            axios.patch('/api/task_assignments/update', {
                id: id,
                assigned_user_id: user_id
            })
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                //detect if employee action
                countChanged.value++

                _fetch()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(false);//show error message modally
            })
        }

        const update = (submitData) => {
            axios.patch('/api/task_timings/update', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                //detect if employee action
                countChanged.value++
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(false);//show error message modally
            })
        }

        const filterUserOption = (input, option) => {
            if (option.fullname) {
                return option.fullname.toLowerCase().indexOf(input.toLowerCase()) >= 0 ? true : false;
            }
        }

        const filterStickerOption = (input, option) => {
            if (option.name) {
                return option.name.toLowerCase().indexOf(input.toLowerCase()) >= 0 ? true : false;
            }
        }

        const onDeleteButton = (id) => {
            Modal.confirm({
                title: 'Bạn có chắc chắn xoá dữ liệu này?',
                okText: 'Ok',
                cancelText: 'Huỷ',
                onOk() {
                    axios.delete('/api/task_timings/delete', {
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
                        errorModal(false);
                    })
                },
                onCancel() {
                },
            })
        };

        onMounted(() => {
            watch(() => props.refreshKey, (newVal, oldVal) => {
                dataSource.value = []
                
                _fetch();
            },
            {
                immediate: true
            })

            watch(() => props.visible, (newVal) => {
                if (!newVal) {
                    countChanged.value = 0;
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
            title,
            countChanged,
            taskIssueIds,
            dataSource,
            formState,
            typeSelbox,
            employeeSelbox,
            stickerSelbox,
            prioritySelbox,
            employeeIdLogin,
            position,
            dateFormat,
            columns,
            errorMessages,
            isLoading,
            onAddNewButton,
            onChangeWorkDate,
            onChangeSticker,
            onChangePriority,
            onChangeWeight,
            onChangeEstimateTime,
            onChangeTimeSpent,
            onChangeDescription,
            filterUserOption,
            filterStickerOption,
            onChangeTaskAssignmentUser,
            onDeleteButton
        };
    }
})
</script>
<style lang="scss">
.task-timing-type-0 {
    font-weight: bold;
    color: rgb(2, 48, 8)
}
.task-timing-type-1 {
    font-weight: bold;
    color: red
}
.task-timing-type-2 {
    font-weight: bold;
    color: blue
}
.task-timing-type-3 {
    font-weight: bold;
    color: green
}
.task-timing-type-4 {
    font-weight: bold;
    color: purple
}
</style>