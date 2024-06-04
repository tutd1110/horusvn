<template>
    <a-modal v-model:visible="visible" style="width:1500px; font-weight: bold" :footer="null" :maskClosable="false" :title="title"
        :closable="true" @cancel="handleCancel" class="modal-task-timing">
        <a-tabs v-model:activeKey="activeKey" :tab-position="tabPosition" @change="onChangeTab()">
            <a-tab-pane key="1" tab="Task" v-if="!isModeIssueOnly">
                <a-col style="margin-bottom: 10px;">
                    <a-button
                        v-if="employeeIdLogin == taskUserId || position > 1"
                        type="primary"
                        v-on:click="onAddNewButton()"
                    >NEW</a-button>
                </a-col>
                <el-table
                    class="task checkbox-custom-size" 
                    :data="dataSource" 
                    style="width:100%"
                    ref="multipleTableRef"
                    @selection-change="handleSelectionChange"
                    border
                >
                    <el-table-column type="selection" width="50" align="center">
                    </el-table-column>
                    <el-table-column property="work_date" label="Work Date" width="150" sortable  align="center">
                        <template #header>
                            <delete-filled style="color: red; font-size: 20px;" v-on:click="multipleDelete()" v-if="showMultipleDelete"/>
                            <span class="title-col">
                                Work Date
                            </span>
                        </template>
                        <template #default="scope">
                            <a-date-picker
                                style="width: 100%"
                                :bordered="false"
                                :disabled="employeeIdLogin != taskUserId && position < 1"
                                v-model:value="scope.row.work_date"
                                :format="dateFormat"
                                @change="onChangeWorkDate(scope.row.id, $event)"
                            />
                        </template>
                    </el-table-column>
                    <!-- <el-table-column property="project_id" label="Project" width="200"  align="center">
                        <template #default="scope">
                                <el-select
                                    v-model="scope.row.project_id"
                                    :bordered="false"
                                    :disabled="employeeIdLogin != taskUserId && position < 2"
                                    multiple
                                    filterable
                                    clearable
                                    style="width:100%;"
                                    class="none-border"
                                    @change="onChangeProject(scope.row.id, $event)"
                                >
                                    <el-option
                                        v-for="item in projectSelbox"
                                        :key="item.id"
                                        :label="item.name"
                                        :value="item.id"
                                    />
                                </el-select>
                            </template>    
                    </el-table-column> -->
                    <el-table-column property="estimate_time" label="Estimate Time" width="120"  align="center">
                        <template #default="scope">
                            <a-input
                                    :bordered="false"
                                    :disabled="employeeIdLogin != taskUserId && position < 2"
                                    v-model:value="scope.row.estimate_time"
                                    @blur="onChangeEstimateTime(scope.row.id, $event)"
                                />
                        </template>
                    </el-table-column>
                    <el-table-column property="time_spent" label="Time Spent" width="120"  align="center">
                        <template #default="scope">
                            <a-input
                                    :bordered="false"
                                    :disabled="employeeIdLogin != taskUserId && position < 2"
                                    v-model:value="scope.row.time_spent"
                                    @blur="onChangeTimeSpent(scope.row.id, $event)"
                                />
                        </template>
                    </el-table-column>
                    <el-table-column property="description" label="Description"  align="center">
                        <template #default="scope">
                            <div class="custom-quill">
                                <QuillEditor
                                    theme="snow"
                                    v-model:content="scope.row.description"
                                    :readOnly="employeeIdLogin != taskUserId && position < 1"
                                    :toolbar="toolbar"
                                    contentType="html"
                                    @blur="onChangeDescription(scope.row.id, scope.row.description)"
                                />
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="Action" width="80" align="center">
                        <template #default="scope">
                            <delete-filled style="color: red; font-size: 20px;" v-on:click="onClickDeleteButton(scope.row.id, scope.row.updated_at)"/>
                        </template>
                    </el-table-column>
                </el-table>
                <el-col style="margin-top: 20px;display: flex;align-items: center;justify-content: center;">
                    <el-pagination
                        v-model="formState.current_page"
                        :page-size="formState.per_page"
                        background
                        :total="total"
                        @current-change="onChangePage"
                    />
                </el-col>
            </a-tab-pane>
            <a-tab-pane key="2" tab="Issue">
                <task-issue
                    ref="modalRef"
                    :id="selectedRecord"
                    :field="field"
                    :selected-department-id="selectedDepartmentId"
                    :refresh-key="refreshKey"
                    :visible="visible"
                >
                </task-issue>
            </a-tab-pane>
        </a-tabs>
    </a-modal>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import { ref, h, computed } from 'vue';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import { TIME_ZONE } from '../const.js'
import { useI18n } from 'vue-i18n';
import { PlusCircleTwoTone, DeleteFilled } from '@ant-design/icons-vue';
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import TaskIssue from './TaskIssue.vue';

dayjs.extend(utc);
dayjs.extend(timezone);

export default ({
    components: {
        PlusCircleTwoTone,
        DeleteFilled,
        QuillEditor,
        TaskIssue
    },
    emits: ['saved'],
    setup(props, { emit }) {
        const countChanged = ref(0);
        const title = ref("");
        const { t } = useI18n();
        const visible = ref(false);
        const activeKey = ref('1');
        const refreshKey = ref(0);
        const isModeIssueOnly = ref(false);
        const tabPosition = ref('top');
        const modalRef = ref();
        const dataSource = ref();
        const dateFormat = 'DD/MM/YYYY';
        const strictDateFormat = "YYYY/MM/DD HH:mm:ss";
        const selectedRecord = ref("");//Store ID of selected row
        const selectedDepartmentId = ref();
        const employeeIdLogin = ref();
        const taskUserId = ref();
        const field = ref("");
        const position = ref();
        const errorMessages = ref("");
        const formState = ref({
                current_page: 1,
                per_page: 20,
            });
        const total = ref(0);
        const projectSelbox = ref([]);
        const typeSelbox = ref();
        const employeeSelbox = ref([]);
        const isLoading = ref(false);
        const columns = ref([
            {
                title: 'Work Date',
                dataIndex: 'work_date',
                key: 'work_date',
                align: 'center',
                width: 30,
                sorter: (a, b) => {
                    const dateA = dayjs(a.work_date, dateFormat);
                    const dateB = dayjs(b.work_date, dateFormat);
                    return dateA - dateB;
                },
            },
            {
                title: 'Project',
                dataIndex: 'project_id',
                key: 'project_id',
                align: 'center',
                width: 40
            },
            {
                title: 'Estimate Time',
                dataIndex: 'estimate_time',
                key: 'estimate_time',
                align: 'center',
                width: 15
            },
            {
                title: 'Time Spent',
                dataIndex: 'time_spent',
                key: 'time_spent',
                align: 'center',
                width: 15
            },
            {
                title: 'Description',
                dataIndex: 'description',
                key: 'description',
                align: 'center',
                width: 170
            },
            {
                title: 'Action',
                dataIndex: '',
                key: 'action',
                align: 'center',
                width: 13,
            }
        ]);

        const selectedRows = ref([]);
        const showMultipleDelete = ref(false);

        const computedFormState = computed(() => {
            const newFormState = {
                ...formState.value,
            };
            return newFormState;
        });

        const onChangePage = (page) => {
            formState.value.current_page = page
            _fetch()
        }

        const handleSelectionChange = (selection) => {
            selectedRows.value = selection.map(row => row.id);
            if (selectedRows.value.length > 0) {
                showMultipleDelete.value = true
            } else {
                showMultipleDelete.value = false
            }
        };

        const multipleDelete = () => {
            Modal.confirm({
                title: 'Bạn có chắc chắn xoá các dữ liệu này?',
                okText: 'Ok',
                cancelText: 'Huỷ',
                onOk() {
                    axios.post('/api/task_timings/delete_multiple', {
                        id: selectedRows.value,
                        check_updated_at: dayjs().tz(TIME_ZONE.ZONE).format(strictDateFormat)
                    })
                    .then(response => {
                        notification.success({
                            message: t('message.MSG-TITLE-W'),
                            description: response.data.success,
                        });

                        //detect if employee action
                        countChanged.value++

                        onSaved()
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
        const toolbar = [
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            [{ size: ['small', false, 'large', 'huge'] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ font: [] }],
            ['link'],
            ['clean'],
        ];

        //Task Timing Mode
        const ShowWithTaskTimingMode = (task_id, screen, department_id, task_user_id = null) => {
            countChanged.value = 0;
            title.value = "";
            visible.value = true;
            activeKey.value = "1";
            refreshKey.value++;

            if (screen === 'task_assignments') {
                field.value = 'task_assignment_id';

                activeKey.value = "2";
                isModeIssueOnly.value = true
            } else {
                field.value = 'task_id';
            }

            selectedRecord.value = task_id
            selectedDepartmentId.value = department_id
            taskUserId.value = task_user_id
            dataSource.value = []

            if (!isModeIssueOnly.value) {
                //init data
                CreateSelbox();

                _fetch()
            }
        };

        const handleCancel = () => {
            let taskIssueIds = []
            if (modalRef.value) {
                countChanged.value += modalRef.value.countChanged

                taskIssueIds = modalRef.value.taskIssueIds
            }

            if (countChanged.value > 0 && !isModeIssueOnly.value) {
                const args = {
                    task_id: selectedRecord.value,
                    task_user_id: taskUserId.value,
                    task_issue_ids: taskIssueIds
                };

                emit('saved', args);
            }
        }

        const CreateSelbox = () => {
            let paramObj = {
                department_id: selectedDepartmentId.value
            }

            if (field.value === 'task_id') {
                paramObj.task_id = selectedRecord.value
            }
            //create select boxes
            axios.get('/api/task_timings/get_selboxes', {
                params: paramObj
            })
            .then(response => {
                typeSelbox.value = response.data.type;
                employeeSelbox.value = response.data.employees;
                employeeIdLogin.value = response.data.employee_id_login;
                position.value = response.data.position;
                projectSelbox.value = response.data.projects;
            })
            .catch((error) => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                //When search target data does not exist
                typeSelbox.value = []; //typeSelbox empty
                employeeSelbox.value = [];
                projectSelbox.value = [];
                employeeIdLogin.value = "";
                position.value = "";
                errorModal();//Show error message modally
            });
        }

        const transferData = (data) => {
            var newData = [];

            data.forEach(function(item) {
                //project_id
                let project_id = [];
                if (item.project_id) {
                    let projectId = item.project_id.trim();
                    if (projectId.startsWith("{") && projectId.endsWith("}")) {
                        projectId = projectId.slice(1, -1);
                    }
                    project_id = projectId.split(",").map((id) => parseInt(id.trim()));
                }

                let value = {
                    id: item.id,
                    name: item.name,
                    work_date: item.work_date ? dayjs(item.work_date, dateFormat) : "",
                    project_id: project_id,
                    estimate_time: item.estimate_time,
                    time_spent: item.time_spent,
                    description: item.description,
                    updated_at: item.updated_at
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
            isLoading.value = true;
            //get data from task id
            axios.get('/api/task_timings/list', {
                params: {
                    id: selectedRecord.value,
                    column: field.value,
                    current_page: computedFormState.value.current_page,
                    per_page: computedFormState.value.per_page,
                }
            })
            .then(response => {
                isLoading.value = false;
                dataSource.value = transferData(response.data.data);
                if (dataSource.value.length > 0) {
                    title.value = dataSource.value[0].name
                }

                if (dataSource.value.length == 1) {
                    for (let i = columns.value.length - 1; i >= 0; i--) {
                        if (columns.value[i].key === 'action') {
                            columns.value.splice(i, 1);
                        }
                    }
                } else {
                    const index = columns.value.findIndex(obj => obj.key === 'action');
                    if (index === -1) {
                        columns.value.push({
                            title: 'Action',
                            dataIndex: '',
                            key: 'action',
                            align: 'center',
                            width:  20,
                        });
                    }
                }
                total.value = response.data.totalItems
            })
            .catch(error => {
                isLoading.value = false;
                dataSource.value = [];
            })
        };

        const onChangeTab = () => {
            if (activeKey.value == '2') {
                refreshKey.value++;
            }
        }

        const onChangeWorkDate = (id, value) => {
            let work_date = "";
            if (value !== null && value !== undefined) {
                work_date = dayjs(value).format(strictDateFormat);
            }

            update({'id': id, 'work_date': work_date, 'task_id': selectedRecord.value})
        }

        const onChangeProject = (id, value) => {
            let project_ids = value ? value : ""

            update({'id': id, 'project_ids': project_ids})
        }

        const onChangeEstimateTime = (id, event) => {
            update({'id': id, 'estimate_time': event.target.value})
        }

        const onChangeTimeSpent = (id, event) => {
            update({'id': id, 'time_spent': event.target.value})
        }

        const onChangeDescription = (id, value) => {
            update({'id': id, 'description': value})
        }

        const update = (submitData) => {
            axios.patch('/api/task_timings/update', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                //detect if employee action
                countChanged.value++;
            })
            .catch(error => {
                onReloaded()
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(false);//show error message modally
            })
        }

        const onAddNewButton = () => {
            axios.post('/api/task_timings/store', {task_id: selectedRecord.value})
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

        const onClickDeleteButton = (id, check_updated_at) => {
            Modal.confirm({
                title: 'Bạn có chắc chắn xoá dữ liệu này?',
                okText: 'Ok',
                cancelText: 'Huỷ',
                onOk() {
                    axios.delete('/api/task_timings/delete', {
                        params: {
                            id: id,
                            check_updated_at: check_updated_at ?
                                dayjs(check_updated_at).tz(TIME_ZONE.ZONE).format(strictDateFormat) : null
                        }
                    })
                    .then(response => {
                        notification.success({
                            message: t('message.MSG-TITLE-W'),
                            description: response.data.success,
                        });

                        //detect if employee action
                        countChanged.value++

                        onSaved()
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

        const filterUserOption = (input, option) => {
            if (option.fullname) {
                return option.fullname.toLowerCase().indexOf(input.toLowerCase()) >= 0 ? true : false;
            }
        }

        const onReloaded = () => {
            _fetch();
        }

        const onSaved = () => {
            _fetch();
        };

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        return {
            title,
            visible,
            activeKey,
            refreshKey,
            tabPosition,
            isModeIssueOnly,
            modalRef,
            dataSource,
            formState,
            projectSelbox,
            typeSelbox,
            employeeSelbox,
            selectedRecord,
            field,
            employeeIdLogin,
            position,
            selectedDepartmentId,
            taskUserId,
            dateFormat,
            columns,
            toolbar,
            errorMessages,
            isLoading,
            onChangeTab,
            ShowWithTaskTimingMode,
            handleCancel,
            onClickDeleteButton,
            onChangeWorkDate,
            onChangeProject,
            onChangeEstimateTime,
            onChangeTimeSpent,
            onChangeDescription,
            onAddNewButton,
            filterUserOption,
            multipleDelete,
            handleSelectionChange,
            showMultipleDelete,
            onChangePage,
            total
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

.custom-quill .ql-toolbar {
  border: none;
}

.custom-quill .ql-container {
  border: none;
}

.custom-quill{
    word-break: break-word;
}
</style>