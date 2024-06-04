<template>
    <a-modal v-model:visible="visible" style="width:1500px; font-weight: bold" :footer="null" :maskClosable="false"
        :closable="false" :title="title">
        <a-form :model="formState" autocomplete="off">
            <a-row>
                <a-col :span="4" :offset="2">
                    <label name="name">Loại công việc</label>
                    <a-form-item :span="1">
                        <a-select
                                ref="select"
                                v-model:value="formState.type"
                                style="width:100%;"
                                :options="typeTaskSelectbox"
                                allow-clear
                                :field-names="{ label:'label', value: 'value' }"
                        ></a-select>
                    </a-form-item>
                </a-col>
                <a-col :span="15" :offset="1">
                    <label name="name">Tên công việc</label>
                    <a-form-item :span="3">
                        <a-input allow-clear v-model:value="formState.name" />
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row v-if="formState.type == 'child'">
                <a-col :span="8" :offset="2">
                    <label>Thời gian bắt đầu và kết thúc</label>
                    <a-form-item>
                        <a-space direction="vertical">
                            <a-range-picker
                                :disabled="mode==='UPDATE'"
                                v-model:value="datePeriod" 
                                :allowEmpty="[true,true]" 
                                :format="dateFormat"
                                />
                        </a-space>
                    </a-form-item>
                </a-col>
                <a-col :span="6" :offset="1" v-if="is_manager">
                    <label name="name">Bộ phận</label>
                    <a-form-item :span="1">
                        <a-select
                                ref="select"
                                v-model:value="formState.department_id"
                                style="width:100%;"
                                :options="departmentSelectbox"
                                :field-names="{ label:'label', value: 'value' }"
                                @change="onChangeSelectDepartment()"
                        ></a-select>
                    </a-form-item>
                </a-col>
                <a-col :span="4" :offset="is_manager ? 1 : 4">
                    <label name="name">Trạng thái công việc</label>
                    <a-form-item :span="1">
                        <el-select 
                            v-model="formState.status" 
                            placeholder="Select"
                            :disabled="formState.status === 9"
                            style="width:100%;"
                            :class="[formState.status ? 'task-status-' + formState.status : 'no-select']"
                        >
                            <el-option
                                v-for="item in statusSelectbox"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                                :class="['task-status-' + item.value]"
                                :disabled="item.value === 9"
                            />
                        </el-select>
                    </a-form-item>
                </a-col>
            </a-row>
            <el-row v-if="mode==='UPDATE'">
                <el-col :span="20" :offset="2">
                    <task-deadline :visible="visible" :id="selectedRecord" :status="statusSelectbox"></task-deadline>
                </el-col>
            </el-row>
            <a-row>
                <a-col :span="20" :offset="2" style="padding-bottom: 108px;">
                    <label name="name">Thông tin công việc</label>
                    <QuillEditor
                        theme="snow"
                        v-model:content="formState.description"
                        :toolbar="toolbar"
                        contentType="html"
                    />
                </a-col>
            </a-row>
            <a-row v-if="formState.type == 'child'">
                <a-col :span="5" :offset="2">
                    <label name="name">Loại công việc</label>
                    <a-form-item :span="1">
                        <a-select
                                ref="select"
                                v-model:value="formState.sticker_id"
                                style="width:100%;"
                                :options="stickerSelectbox"
                                allow-clear
                                :field-names="{ label:'name', value: 'id' }"
                                @change="onChangeSticker"
                                :disabled="!session.add_permission && !session.is_authority"
                        ></a-select>
                    </a-form-item>
                </a-col>
                <a-col :span="3" :offset="3">
                    <label name="name">Cấp độ</label>
                    <a-form-item :span="1">
                        <a-select
                                ref="select"
                                v-model:value="formState.priority"
                                style="width:100%;"
                                :options="prioritySelectbox"
                                allow-clear
                                :field-names="{ label:'label', value: 'id' }"
                                @change="onChangePriority"
                                :disabled="!session.add_permission && !session.is_authority"
                        ></a-select>
                    </a-form-item>
                </a-col>
                <a-col :span="6" :offset="3">
                    <label name="name">Trọng số</label>
                    <a-form-item :span="3">
                        <a-input readonly v-model:value="formState.weight" :disabled="!session.add_permission && !session.is_authority"/>
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row>
                <a-col :span="20" :offset="2">
                    <label name="name">Dự án</label>
                    <a-form-item :span="1">
                        <!-- <a-select
                                ref="select"
                                v-model:value="formState.project_id"
                                style="width:100%;"
                                mode="multiple"
                                :options="projectSelectbox"
                                allow-clear
                                :field-names="{ label:'name', value: 'id' }"
                                @change="onChangeProject()"
                        ></a-select> -->
                        <el-select
                            v-model="formState.project_id"
                            multiple
                            filterable
                            clearable
                            style="width:100%;"
                            @change="onChangeProject()"
                        >
                            <el-option
                                v-for="item in projectSelectbox"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id"
                            />
                        </el-select>
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row>
                <a-col :span="20" :offset="2">
                    <label name="name">Công việc cha</label>
                    <a-form-item :span="1">
                        <a-tree-select
                            v-model:value="formState.task_parent"
                            show-search
                            style="width: 100%"
                            :dropdown-style="{ maxHeight: '400px', overflow: 'auto' }"
                            placeholder="Please select"
                            allow-clear
                            treeNodeFilterProp="name"
                            :fieldNames="fieldNames"
                            :tree-data="treeData"
                        >
                        </a-tree-select>
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row>
                <a-col :span="20" :offset="2">
                    <label name="name">ID Công việc cha</label>
                    <a-form-item :span="3">
                        <a-input allow-clear v-model:value="searchId" @blur="onChangeSearchId($event)"/>
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row v-if="formState.type == 'child'">
                <el-col :span="8" :offset="2">
                    <label class="sub-select">Người thực hiện</label>
                    <el-select
                        v-model="formState.user_id"
                        value-key="id"
                        placeholder="Employees"
                        clearable
                        filterable
                        style="width: 100%"
                        :disabled="!session.add_permission && !session.is_authority && mode==='UPDATE'"
                    >
                        <el-option
                            v-for="item in userSelectbox"
                            :key="item.id"
                            :label="item.fullname"
                            :value="item.id"
                        />
                    </el-select>
                </el-col>
            </a-row>
            <!--Delete button-->
            <a-row>
                <a-col :span="8" :offset="13">
                    <a-form-item>
                        <single-submit-button v-if="mode==='UPDATE'" type="primary" danger :onclick="onClickDeleteButton">Xoá</single-submit-button>
                    </a-form-item>
                </a-col>
            </a-row>
            <!--Cancel button-->
            <a-row>
                <a-col :span="8" :offset="8">
                    <a-form-item>
                        <a-button @click="cancel">Huỷ</a-button>
                    </a-form-item>
                </a-col>
                <a-form-item>
                    <a-col :span="8" :offset="24" style="align:center">
                        <single-submit-button v-if="mode==='ADD' || mode==='COPY'" type="primary" :onclick="onClickStoreButton">Thêm</single-submit-button>
                        <single-submit-button v-if="mode==='UPDATE'" type="primary" :onclick="onClickUpdateButton">Cập nhật</single-submit-button>
                    </a-col>
                </a-form-item>
            </a-row>
        </a-form>
    </a-modal>
</template>
<script>
import { Modal } from 'ant-design-vue';
import axios from 'axios';
import { ref, h } from 'vue';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import { TIME_ZONE } from '../../const.js'
import { useI18n } from 'vue-i18n';
import SingleSubmitButton from '../../Shared/SingleSubmitButton/SingleSubmitButton.vue';
import TaskDeadline from '../../common/TaskDeadline.vue';
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { buildFormStateTime } from '../../Helper/build-datetime.js';
import { onCommonChangeSticker, onCommonChangePriority } from '../../Helper/helpers.js';

dayjs.extend(utc);
dayjs.extend(timezone);

export default ({
    components: {
        SingleSubmitButton,
        TaskDeadline,
        QuillEditor
    },
    emits: ['saved'],
    setup(props, { emit }) {
        const { t } = useI18n();
        const errorMessages = ref();
        const title = ref("");
        const visible = ref(false);
        const formState = ref([]);//form value
        const mode = ref("");//New mode or edit mode or change
        const selectedRecord = ref("");//Store ID of selected row
        const strictDateFormat = "YYYY/MM/DD HH:mm:ss"
        const exclusionControl = ref();//exclusion control
        const datePeriod = ref([]);
        const dateFormat = "DD/MM/YYYY";
        const typeTaskSelectbox = ref([]);
        const projectSelectbox = ref([]);
        const prioritySelectbox = ref([]);
        const departmentSelectbox = ref([]);
        const is_manager = ref(false);
        const statusSelectbox = ref([]);
        const userSelectbox = ref([]);
        const stickerSelectbox = ref([]);
        const treeData = ref([]);
        const searchId = ref();
        const fieldNames = ref({children:'grandchildren', label:'name', value: 'id' });
        const session = ref()

        const toolbar = [
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            [{ size: ['small', false, 'large', 'huge'] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ font: [] }],
            ['link'],
            ['clean'],
        ];

        const cancel = () => {
            visible.value = false;
        };

        const formClear = () => {
            formState.value.id = "";
            formState.value.name = ref("");
            formState.value.type = "parent";
            formState.value.time = "";
            formState.value.real_time = "";
            formState.value.weight = "";
            formState.value.priority = "";
            formState.value.sticker_id = "";
            formState.value.department_id = "";
            datePeriod.value = [];
            searchId.value = "";
            formState.value.start_time = "";
            formState.value.end_time = "";
            formState.value.project_id = [];
            formState.value.user_id = "";
            formState.value.task_parent = "";
            formState.value.description = ref("<p><br></p>", "");
            formState.value.status = "";
            treeData.value = [];
            selectedRecord.value = "";
        };

        //select box list generation
        const CreateSelbox = () => {
            //users
            axios.get('/api/department/task/get_selectboxes_for_create_update')
            .then(response => {
                let data = response.data
                typeTaskSelectbox.value = data.type_of_task;
                projectSelectbox.value = data.projects;
                prioritySelectbox.value = data.priorities;
                departmentSelectbox.value = data.departments;
                statusSelectbox.value = data.status;
                userSelectbox.value = data.users;
                stickerSelectbox.value = data.stickers;
                is_manager.value = data.is_manager
            })

            axios.get('/api/department/task/get_selectboxes')
            .then(response => {
                session.value = response.data.session;
            })
        };

        //new mode
        const ShowWithAddMode = () => {
            mode.value = "ADD";
            title.value = "Thêm công việc"
            visible.value = true;
            //form initialization
            formClear();
            CreateSelbox();
        };

        //copy mode
        const ShowWithCopyMode = (id) => {
            mode.value = "COPY";
            title.value = "Sao chép công việc"
            visible.value = true;
            //form initialization
            formClear();
            CreateSelbox();
            //id record that has been selected
            selectedRecord.value = id;

            //get data from id
            axios
                .get('/api/department/task/get_task_by_id/', {
                    params: {
                        id: selectedRecord.value
                    }
                })
                .then(response => {
                    selectedRecord.value = "";

                    let projectIds = transferProjectIds(response.data.project_id)
                    if (projectIds) {
                        getTasksTreeData(projectIds)
                    }

                    exclusionControl.value = dayjs(response.data.updated_at).tz(TIME_ZONE.ZONE).format(strictDateFormat);

                    formState.value = response.data;

                    formState.value.project_id = projectIds

                    searchId.value = formState.value.task_parent

                    datePeriod.value = [
                        response.data.start_time ? dayjs(response.data.start_time, dateFormat) : null,
                        response.data.end_time ? dayjs(response.data.end_time, dateFormat) : null
                    ]
                })
                .catch(error => {
                    errorMessages.value = error.response.data.errors;
                    errorModal(true);
                })
        };

        //update mode
        const ShowWithUpdateMode = (id, updated_at) => {
            mode.value = "UPDATE";
            title.value = "Chỉnh sửa thông tin công việc"
            visible.value = true;
            formClear();
            CreateSelbox();
            //id record that has been selected
            selectedRecord.value = id;

            //get data from id
            axios
                .get('/api/department/task/get_task_by_id/', {
                    params: {
                        id: selectedRecord.value
                    }
                })
                .then(response => {
                    let projectIds = transferProjectIds(response.data.project_id)
                    if (projectIds) {
                        getTasksTreeData(projectIds)
                    }

                    exclusionControl.value = dayjs(response.data.updated_at).tz(TIME_ZONE.ZONE).format(strictDateFormat);

                    formState.value = response.data;
                    
                    formState.value.project_id = projectIds
                    searchId.value = formState.value.task_parent

                    datePeriod.value = [
                        response.data.start_time ? dayjs(response.data.start_time, dateFormat) : null,
                        response.data.end_time ? dayjs(response.data.end_time, dateFormat) : null
                    ]
                })
                .catch(error => {
                    errorMessages.value = error.response.data.errors;
                    errorModal(true);
                })
        };

        const transferProjectIds = (project_id) => {
            //project_id
            let projectArray = [];
            if (project_id) {
                let projectId = project_id.trim();
                if (projectId.startsWith("{") && projectId.endsWith("}")) {
                    projectId = projectId.slice(1, -1);
                }
                projectArray = projectId.split(",").map((id) => parseInt(id.trim()));
            }

            return projectArray
        }

        const onClickDeleteButton = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                Modal.confirm({
                    title: 'Bạn có chắc chắn xoá thông tin công việc này?',
                    okText: 'Ok',
                    cancelText: 'Huỷ',
                    onOk() {
                        axios.delete('/api/department/task/delete', {
                            params: {
                                id: selectedRecord.value,
                                check_updated_at: exclusionControl.value
                            }
                        })
                        .then(response => {
                            resolve();
                            _update();
                        })
                        .catch(error => {
                            reject();
                            errorMessages.value = error.response.data.errors;
                            errorModal(false);
                        })
                    },
                    onCancel() {
                        reject();
                    },
                })
            })
        };

        const onClickUpdateButton = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                let submitData = {
                    id: formState.value.id,
                    name: formState.value.name,
                    description: formState.value.description,
                    project_ids: formState.value.project_id,
                    task_parent: formState.value.task_parent ? formState.value.task_parent : null,
                    type: formState.value.type,
                    check_updated_at: exclusionControl.value,//exclusion control
                };
                if (formState.value.type == 'child') {
                    submitData.weight = formState.value.weight;
                    submitData.priority = formState.value.priority;
                    submitData.sticker_id = formState.value.sticker_id;
                    submitData.department_id = formState.value.department_id;
                    submitData.status = formState.value.status;
                    submitData.user_id = formState.value.user_id;
                }
                axios.patch('/api/department/task/update', submitData)
                .then(response => {
                    resolve();
                    _update();
                })
                .catch(error => {
                    reject();
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    errorModal(false);//show error message modally
                })
            })
        };

        const handleDatePeriod = () => {
            buildFormStateTime(formState, datePeriod)
        };

        const _update = () => {
            visible.value = false;
            emit('saved');
        };

        const onClickStoreButton = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                let submitData = {
                    name: formState.value.name,
                    description: formState.value.description,
                    project_ids: formState.value.project_id,
                    task_parent: formState.value.task_parent,
                    type: formState.value.type
                };
                if (formState.value.type == 'child') {
                    //format start time
                    handleDatePeriod();

                    submitData.start_time = formState.value.start_time ? dayjs(formState.value.start_time).format(strictDateFormat) : null;
                    submitData.end_time = formState.value.end_time ? dayjs(formState.value.end_time).format(strictDateFormat) : null;
                    submitData.weight = formState.value.weight;
                    submitData.priority = formState.value.priority;
                    submitData.sticker_id = formState.value.sticker_id;
                    submitData.department_id = formState.value.department_id;
                    submitData.status = formState.value.status;
                    submitData.user_id = formState.value.user_id;
                }
                axios.post('/api/department/task/store', submitData)
                .then(response => {
                    resolve();
                    _update();
                })
                .catch(error => {
                    reject();
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    errorModal(false);//show error message modally
                })
            })
        };

        const onChangeProject = () => {
            if (formState.value.project_id) {
                getTasksTreeData(formState.value.project_id)
            }
        };

        const onChangeSelectDepartment = () => {
            formState.value.user_id = ""
            formState.value.sticker_id = ""
            axios.get('/api/department/task/get_select_boxes_by_department_id', {
            params:{
                department_id: formState.value.department_id
            }})
            .then(response => {
                    userSelectbox.value = response.data.employees;
                    stickerSelectbox.value = response.data.stickers;
                })
            .catch((error) => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                //When search target data does not exist
                userSelectbox.value = []; //users empty
                stickerSelectbox.value = []; //stickers empty
            });
        }

        const onChangeSticker = () => {
            if (formState.value.priority) {
                const args = [
                    formState.value.id, formState.value.sticker_id, [formState.value], stickerSelectbox.value, prioritySelectbox.value
                ]
                onCommonChangeSticker(...args)
            }
        }

        const onChangePriority = () => {
            if (formState.value.sticker_id) {
                const args = [
                    formState.value.id, formState.value.priority, [formState.value], stickerSelectbox.value, prioritySelectbox.value
                ]
                onCommonChangePriority(...args)
            }
        }

        const getTasksTreeData = (projectIds) => {
            //list tasks by project_id
            axios.post('/api/department/task/get_tasks_with_tree_data', {
                project_id: projectIds,
                id: selectedRecord.value
            })
            .then(response => {
                    treeData.value = response.data;
                })
        };

        const onChangeSearchId = (event) => {
            let id = parseInt(searchId.value)
            let result = traverseTree(treeData.value, id)

            if (Number.isInteger(id) && result == true) {
                formState.value.task_parent = id
            } else {
                formState.value.task_parent = ""
            }
        }

        const traverseTree = (tree, id) => {
            if (tree.length == 0) {
                return false;
            }
            let found = false;

            for (let i = 0; i < tree.length && !found; i++) {
                if (tree[i].id === id) {
                    found = true;
                } else if (tree[i].grandchildren) {
                    found = traverseTree(tree[i].grandchildren, id);
                }
            }

            return found;
        }

        const errorModal = (isEdit) => {
            Modal.warning({
                title: t('message.MSG-TITLE-W'),
                content: h('ul', {}, errorMessages.value.split('<br>').map((error) => { return h('li', error) })),
                onOk() {
                    //isEdit is boolean, show us about mode edit/delete
                    //when init edit/delete if there is no data match, we'll show message modal dialog
                    //onOk we'll close edit/delete mode modal and re-init list data
                    if (isEdit) {
                        _update();
                    }
                }
            });
        };

        return {
            cancel,
            t,
            errorModal,
            errorMessages,
            formState,
            selectedRecord,
            typeTaskSelectbox,
            projectSelectbox,
            prioritySelectbox,
            is_manager,
            departmentSelectbox,
            statusSelectbox,
            userSelectbox,
            stickerSelectbox,
            datePeriod,
            dateFormat,
            title,
            visible,
            mode,
            treeData,
            searchId,
            fieldNames,
            ShowWithAddMode,
            ShowWithCopyMode,
            ShowWithUpdateMode,
            onClickStoreButton,
            onClickUpdateButton,
            onClickDeleteButton,
            toolbar,
            onChangeProject,
            onChangeSelectDepartment,
            onChangeSearchId,
            onChangeSticker,
            onChangePriority,
            session
        };
    }
})
</script>