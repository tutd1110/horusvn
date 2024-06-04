<template>
    <a-modal v-model:visible="visible" style="width:1100px;" :title="title" :footer="null" :maskClosable="false" :closable="true" @cancel="handleCancel">
        <a-row style="margin-bottom: 20px">
            <a-col :span="5" style="margin-right: 10px">
                <label style="margin-top: 5px; margin-right: 10px">Type</label>
                <a-select
                    ref="select"
                    v-model:value="taskSticker"
                    style="width:100%;"
                    :options="stickers"
                    :field-names="{ label:'name', value: 'id' }"
                    show-search
                    allow-clear
                    :filterOption="filterStickerOption"
                    @change="onChangeTaskSticker"
                ></a-select>
            </a-col>
            <a-col :span="2" style="margin-right: 10px">
                <label style="margin-top: 5px; margin-right: 10px">Level</label>
                <a-select
                    ref="select"
                    v-model:value="taskPriority"
                    style="width:100%;"
                    :options="priorities"
                    :field-names="{ label:'label', value: 'id' }"
                    allow-clear
                    @change="onChangeTaskPriority"
                ></a-select>
            </a-col>
            <a-col style="margin-right: 10px">
                <label style="margin-top: 5px; margin-right: 10px">Weighted</label>
                <a-row>
                    <a-input-number v-model:value="taskWeighted" @blur="onChangeTaskWeight"/>
                </a-row>
            </a-col>
            <a-col style="margin-top: 22px;">
                <a-button type="primary" v-on:click="onAddNewButton()">NEW</a-button>
            </a-col>
        </a-row>
        <a-table class="task" :dataSource="dataSource" :columns="columns" :pagination="false" bordered>
            <template #bodyCell="{column,record}">
                <template v-if="column.key === 'project_id'">
                    <a-select
                        ref="select"
                        :bordered="false"
                        v-model:value="record.project_id"
                        style="width:100%;"
                        :options="projects"
                        :field-names="{ label:'name', value: 'id' }"
                        @change="onChangeProject(record.id, $event)"
                    ></a-select>
                </template>
                <template v-if="column.key === 'percent'">
                    <a-input-number
                        :bordered="false"
                        v-model:value="record.percent"
                        :keyboard="true"
                        style="width:100%;"
                        allow-clear
                        @blur="onChangePercent(record)"
                    />
                </template>
                <template v-if="column.key === 'weight'">
                    <a-input-number
                        :bordered="false"
                        v-model:value="record.weight"
                        :keyboard="true"
                        style="width:100%;"
                        allow-clear
                        @blur="onChangeWeight(record)"
                    />
                </template>
                <template v-if="column.key === 'action'">
                    <delete-filled style="color: red; font-size: 14px;" v-on:click="onClickDeleteButton(record.id)"/>
                </template>
            </template>
        </a-table>
    </a-modal>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import { onMounted, ref, h } from 'vue';
import dayjs from 'dayjs';
import { useI18n } from 'vue-i18n';
import { DeleteFilled } from '@ant-design/icons-vue';

export default ({
    components: {
        DeleteFilled,
    },
    emits: ['saved'],
    setup(props, { emit }) {
        const countChanged = ref(0);
        const title = ref("");
        const taskId = ref("");
        const taskSticker = ref();
        const taskPriority = ref();
        const taskWeighted = ref();
        const { t } = useI18n();
        const visible = ref(false);
        const dataSource = ref([]);
        const selectedDepartmentId = ref("");
        const selectedTaskId = ref("");
        const projects = ref([]);
        const stickers = ref([]);
        const priorities = ref([]);
        const errorMessages = ref("");
        const columns = ref([
            {
                title: 'Project',
                dataIndex: 'project_id',
                key: 'project_id',
                align: 'center',
                width: 300,
            },
            {
                title: 'Percent %',
                dataIndex: 'percent',
                key: 'percent',
                align: 'center',
                width: 15
            },
            {
                title: 'Weighted',
                dataIndex: 'weight',
                key: 'weight',
                align: 'center',
                width: 15
            },
            {
                title: 'Action',
                dataIndex: '',
                key: 'action',
                align: 'center',
                width:  15,
            }
        ]);

        const formClear = () => {
            countChanged.value = 0;
            taskId.value = "";
            taskSticker.value = "";
            taskPriority.value = "";
            taskWeighted.value = "";
            errorMessages.value = "";
        };

        //select box list generation
        const CreateSelbox = () => {
            //create select boxes
            axios.get('/api/task_projects/get_selbox', {
                params: {
                    department_id: selectedDepartmentId.value
                }
            })
            .then(response => {
                projects.value = response.data.projects;
                stickers.value = response.data.stickers;
                priorities.value = response.data.priorities;
            })
        };

        //Task Project Mode
        const ShowWithTaskProjectMode = (task_id, department_id) => {
            visible.value = true;

            title.value = ""
            dataSource.value = []

            selectedTaskId.value = task_id
            selectedDepartmentId.value = department_id

            formClear()
            CreateSelbox()

            initData()
        };

        const initData = () => {
            axios.get('/api/task_projects/list', {
                params:{
                    task_id: selectedTaskId.value
                }
            })
            .then(response => {
                let task = response.data.task

                title.value = task.name
                taskWeighted.value = task.weight
                taskSticker.value = task.sticker_id
                taskPriority.value = task.priority
                taskId.value = task.id

                dataSource.value = response.data.task_project;
            })
            .catch((error) => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                //When search target data does not exist
                dataSource.value = []; //dataSource empty
                title.value = ""
                taskWeighted.value = ""
                taskSticker.value = ""
                taskPriority.value = ""
                taskId.value = ""
                errorModal();//Show error message modally
            });
        }

        const onAddNewButton = () => {
            axios.post('/api/task_projects/store', {task_id: selectedTaskId.value})
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                onReloaded()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(false);//show error message modally
            })
        }

        const onClickDeleteButton = (id) => {
            Modal.confirm({
                title: 'Bạn có chắc chắn xoá dữ liệu này?',
                okText: 'Ok',
                cancelText: 'Huỷ',
                onOk() {
                    axios.delete('/api/task_projects/delete', {
                        params: {
                            id: id
                        }
                    })
                    .then(response => {
                        notification.success({
                            message: t('message.MSG-TITLE-W'),
                            description: response.data.success,
                        });

                        onReloaded()
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

        const filterStickerOption = (input, option) => {
            if (option.name) {
                return option.name.toLowerCase().indexOf(input.toLowerCase()) >= 0 ? true : false;
            }
        }

        const onChangeTaskSticker = () => {
            let submitData = {
                id: taskId.value,
                sticker_id: taskSticker.value
            }

            if (taskSticker.value && taskPriority.value) {
                const stickerIndex = stickers.value.findIndex((item) => item.id === taskSticker.value);

                const priorityIndex = priorities.value.findIndex((item) => item.id === taskPriority.value);

                const element = 'level_'+priorities.value[priorityIndex].label

                taskWeighted.value = stickers.value[stickerIndex][element]
                submitData.weight = stickers.value[stickerIndex][element]
            } else {
                submitData.weight = ""
                taskWeighted.value = ""
            }

            //save it to DB
            updateTask(submitData)
        }

        const onChangeTaskPriority = () => {
            let submitData = {
                id: taskId.value,
                priority: taskPriority.value
            }

            if (taskPriority.value) {
                if (taskSticker.value) {
                    const stickerIndex = stickers.value.findIndex((item) => item.id === taskSticker.value);

                    const priorityIndex = priorities.value.findIndex((item) => item.id === taskPriority.value);

                    const element = 'level_'+priorities.value[priorityIndex].label

                    taskWeighted.value = stickers.value[stickerIndex][element]
                    submitData.weight = stickers.value[stickerIndex][element]
                }
            } else {
                submitData.weight = ""
                taskWeighted.value = ""
            }

            //save it to DB
            updateTask(submitData)
        }

        const onChangeTaskWeight = () => {
            //save it to DB
            updateTask({id: taskId.value, weight: taskWeighted.value})
        }

        const updateTask = (submitData) => {
            axios.patch('/api/task/quick_update', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                onReloaded()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(false);//show error message modally
            })
        }

        const onChangePercent = (record) => {
            const index = dataSource.value.findIndex((item) => item.id === record.id);

            if (record.percent) {
                dataSource.value[index].weight = taskWeighted.value * record.percent / 100;
            } else {
                dataSource.value[index].weight = "";
            }

            updateTaskProject({
                id: record.id,
                percent: dataSource.value[index].percent,
                weight: dataSource.value[index].weight,
            })
        }

        const onChangeWeight = (record) => {
            const index = dataSource.value.findIndex((item) => item.id === record.id);

            if (record.weight) {
                dataSource.value[index].percent = record.weight*100/taskWeighted.value;
            } else {
                dataSource.value[index].percent = "";
            }

            updateTaskProject({
                id: record.id,
                percent: dataSource.value[index].percent,
                weight: dataSource.value[index].weight,
            })
        }

        const onChangeProject = (id, value) => {
            let project_id = value ? value : null

            updateTaskProject({id: id, project_id: project_id})
        }

        const updateTaskProject = (submitData) => {
            axios.patch('/api/task_projects/quick_update', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                onReloaded()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(false);//show error message modally
            })
        }

        const handleCancel = () => {
            let isEmpty = false;

            dataSource.value.forEach((item) => {
                if (!item.project_id) {
                    isEmpty = true;

                    errorMessages.value = "Dự án đang để trống, vui lòng chọn dự án hoặc xoá!";
                    errorModal(false);
                }
            });

            if (isEmpty) {
                visible.value = true;
            } else {
                if (countChanged.value > 0) {
                    emit('saved', selectedTaskId.value);
                }
            }
        }

        const onReloaded = () => {
            initData();

            //detect if employee action
            countChanged.value++;
        }

        const errorModal = (status = null) => {
            Modal.warning({
                title: t('message.MSG-TITLE-W'),
                content: h('ul', {}, errorMessages.value.split('<br>').map((error) => { return h('li', error) })),
                onOk() {
                    onReloaded()
                },
            });
        };

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        return {
            title,
            taskSticker,
            taskPriority,
            taskWeighted,
            visible,
            projects,
            stickers,
            priorities,
            dataSource,
            columns,
            ShowWithTaskProjectMode,
            onAddNewButton,
            onClickDeleteButton,
            filterStickerOption,
            onChangeTaskSticker,
            onChangeTaskPriority,
            onChangeTaskWeight,
            onChangePercent,
            onChangeWeight,
            onChangeProject,
            handleCancel
        };
    }
})
</script>