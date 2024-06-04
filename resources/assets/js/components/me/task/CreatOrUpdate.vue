<template>
    <a-modal v-model:visible="visible" style="width:1500px; font-weight: bold" :footer="null" :maskClosable="false"
        :closable="false" :title="title">
        <a-form :model="formState" autocomplete="off" >
            <a-row>
                <a-col :span="20" :offset="2">
                    <label name="name">Tên công việc</label>
                    <a-form-item :span="3">
                        <a-input allow-clear v-model:value="formState.name" />
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row>
                <a-col :span="5" :offset="2">
                    <label name="name">Loại công việc</label>
                    <!-- <a-form-item :span="1">
                        <a-select
                                ref="select"
                                v-model:value="formState.sticker_id"
                                allow-clear
                                style="width:100%;"
                                :options="stickerSelectbox"
                                :field-names="{ label:'name', value: 'id' }"
                        ></a-select>
                    </a-form-item> -->
                    <el-select
                            v-model="formState.sticker_id"
                            filterable
                            clearable
                            style="width:100%;"
                        >
                            <el-option
                                v-for="item in stickerSelectbox"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id"
                            />
                        </el-select>
                </a-col>
                <a-col :span="14" :offset="1">
                    <label name="name">Dự án</label>
                    <a-form-item :span="1">
                        <!-- <a-select
                                ref="select"
                                allow-clear
                                mode="multiple"
                                v-model:value="formState.project_id"
                                style="width:100%;"
                                :options="projectSelectbox"
                                :field-names="{ label:'name', value: 'id' }"
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
            <a-row>
                <a-col :span="2" :offset="2">
                    <label name="name">ID Công việc cha</label>
                    <a-form-item :span="3">
                        <a-input allow-clear v-model:value="searchId" @blur="onChangeSearchId($event)"/>
                    </a-form-item>
                </a-col>
                <a-col :span="17" :offset="1">
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
                <a-col :span="6" :offset="1">
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
            <!--Delete button-->
            <a-row style="margin-top: 20px; margin-bottom: -10px;">
                <a-col style="margin-left:151px">
                    <a-form-item>
                        <single-submit-button style="width:100px;" v-if="mode==='UPDATE'" type="primary" danger :onclick="onClickDeleteButton">Xoá</single-submit-button>
                    </a-form-item>
                </a-col>
            </a-row>
            <!--Cancel/Update button-->
            <a-row style="float:right; margin-top:-50px;">
                <a-col style="margin-right:10px">
                    <a-form-item>
                        <a-button style="width:100px;" @click="cancel">Huỷ</a-button>
                    </a-form-item>
                </a-col>
                <a-form-item>
                    <a-col>
                        <single-submit-button style="width:100px;" v-if="mode==='ADD' || mode==='COPY'" type="primary" :onclick="onClickStoreButton">Thêm</single-submit-button>
                        <single-submit-button style="width:100px;" v-if="mode==='UPDATE'" type="primary" :onclick="onClickUpdateButton">Cập nhật</single-submit-button>
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

dayjs.extend(utc);
dayjs.extend(timezone);

export default ({
    components: {
        SingleSubmitButton,
        QuillEditor,
        TaskDeadline
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
        const dateFormat = "DD/MM/YYYY";
        const projectSelectbox = ref([]);
        const statusSelectbox = ref([]);
        const stickerSelectbox = ref([]);
        const datePeriod = ref([]);

        const treeData = ref([]);
        const searchId = ref();
        const fieldNames = ref({children:'grandchildren', label:'name', value: 'id' });

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
            formState.value.time = "";
            formState.value.sticker_id = "";
            formState.value.start_time = "";
            formState.value.project_id = [];
            formState.value.description = ref("<p><br></p>", "");
            datePeriod.value = [];
            formState.value.status = "";
            selectedRecord.value = "";
            treeData.value = [];
            searchId.value = "";
        };

        //select box list generation
        const CreateSelbox = () => {
            //users
            axios.get('/api/me/task/get_selectboxes_for_create_update')
            .then(response => {
                let data = response.data
                projectSelectbox.value = data.projects;
                statusSelectbox.value = data.status;
                stickerSelectbox.value = data.stickers;
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
                .get('/api/me/task/get_task_by_id/', {
                    params: {
                        id: selectedRecord.value
                    }
                })
                .then(response => {
                    selectedRecord.value = "";
                    exclusionControl.value = dayjs(response.data.updated_at).tz(TIME_ZONE.ZONE).format(strictDateFormat);

                    formState.value = response.data;

                    formState.value.project_id = transferProjectIds(response.data.project_id)
                    if (formState.value.project_id) {
                        getTasksTreeData(formState.value.project_id)
                    }
                    searchId.value = formState.value.task_parent

                    datePeriod.value = [
                        dayjs(response.data.start_time, dateFormat),
                        dayjs(response.data.end_time, dateFormat)
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
            axios.get('/api/me/task/get_task_by_id/', {
                params: {
                    id: selectedRecord.value
                }
            }).then(response => {
                exclusionControl.value = dayjs(response.data.updated_at).tz(TIME_ZONE.ZONE).format(strictDateFormat);

                formState.value = response.data;

                formState.value.project_id = transferProjectIds(response.data.project_id)
                if (formState.value.project_id) {
                    getTasksTreeData(formState.value.project_id)
                }
                searchId.value = formState.value.task_parent

                datePeriod.value = [
                    dayjs(response.data.start_time, dateFormat),
                    dayjs(response.data.end_time, dateFormat)
                ]
            }).catch(error => {
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
        const onChangeProject = () => {
            if (formState.value.project_id) {
                getTasksTreeData(formState.value.project_id)
            }
        };

        const onClickDeleteButton = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                Modal.confirm({
                    title: 'Bạn có chắc chắn xoá thông tin công việc này?',
                    okText: 'Ok',
                    cancelText: 'Huỷ',
                    onOk() {
                        axios.delete('/api/me/task/delete', {
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
                    sticker_id: formState.value.sticker_id,
                    description: formState.value.description,
                    project_ids: formState.value.project_id,
                    status: formState.value.status,
                    check_updated_at: exclusionControl.value,//exclusion control
                    task_parent: formState.value.task_parent,
                };

                axios.patch('/api/me/task/update', submitData)
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
                //format start time
                handleDatePeriod();

                let submitData = {
                    name: formState.value.name,
                    description: formState.value.description,
                    project_ids: formState.value.project_id,
                    start_time: formState.value.start_time,
                    end_time: formState.value.end_time,
                    sticker_id: formState.value.sticker_id,
                    status: formState.value.status,
                    task_parent: formState.value.task_parent
                };

                axios.post('/api/me/task/store', submitData)
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
            selectedRecord,
            formState,
            projectSelectbox,
            statusSelectbox,
            stickerSelectbox,
            datePeriod,
            dateFormat,
            title,
            visible,
            mode,
            ShowWithAddMode,
            ShowWithCopyMode,
            ShowWithUpdateMode,
            onClickStoreButton,
            onClickUpdateButton,
            onClickDeleteButton,
            toolbar,
            onChangeSearchId,
            getTasksTreeData,
            treeData,
            searchId,
            fieldNames,
            onChangeProject
        };
    }
})
</script>