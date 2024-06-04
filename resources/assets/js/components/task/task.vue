<template>
    <creat-or-update ref="modalRef" @saved="onSaved"></creat-or-update>
    <config ref="modalConfigRef" @saved="onSaved"></config>
    <preview ref="modalPreviewRef"></preview>
    <task-timing ref="modalTaskTimingRef" @saved="onSavedTaskTiming"></task-timing>
    <task-project ref="modalTaskProjectRef" @saved="onSavedTaskProject"></task-project>
    <div class="height-container" id="filter-block">
        <a-row style="margin-bottom: 5px">
            <a-col>
                <a-switch
                    checked-children="S"
                    un-checked-children="H"
                    v-model:checked="checked"
                    @change="onShowHideFilters()"
                />
            </a-col>
            <a-col v-if="is_hide_filters">
                <plus-outlined title="Tạo công việc" style="margin-left: 15px;" :disabled = "!is_authority" v-on:click="showRegisterModal()"/>
            </a-col>
        </a-row>
        <template v-if="!is_hide_filters">
            <a-row>
                <a-col style="margin-right:10px">
                    <a-button type="primary" :disabled = "!is_authority" v-on:click="showRegisterModal()">Tạo công việc</a-button>
                </a-col>
                <a-col style="margin-right:10px; margin-top: 10px">
                    <a-switch
                        v-model:checked="rowSelection.checkStrictly"
                        :style="{ backgroundColor: '#f5222d', borderColor: '#f5222d' }"
                        checked-children="Single"
                        un-checked-children="Multiple"
                        @change="onChangeSwitch()"
                    >
                    </a-switch>
                </a-col>
            </a-row>
            <a-row style="float:right; margin-top:-30px">
                <a-col style="margin-right:1px">
                    <a-upload 
                        :multiple="false"
                        :showUploadList="false"
                        :beforeUpload="beforeUpload"
                    >
                        <a-button type="primary" class="upload-btn">
                            <span class="upload-spn">Chọn file</span>
                        </a-button>
                    </a-upload>
                </a-col>
                <a-col style="margin-right:1px">
                    <a-input style="width:400px" readonly placeholder="Only file xlsx can be import" v-model:value="fileName" />
                </a-col>
                <a-col style="margin-right:10px">
                    <single-submit-button type="primary" class="upload-btn" :disabled = "!is_authority" :onclick="doImport">
                        <span class="upload-spn">Import</span>
                    </single-submit-button>
                </a-col>
                <a-col style="margin-right:10px" v-if="session?.id == 161">
                    <single-submit-button type="primary" class="upload-btn" :onclick="doImportJob">
                        <span class="upload-spn">ImportJob</span>
                    </single-submit-button>
                </a-col>
                <a-col style="margin-right:10px">
                    <a-button type="primary" :loading="loadingExport" v-on:click="doExport()">Export</a-button>
                </a-col>
                <a-col>
                    <a-button type="primary" :disabled = "!is_authority" v-on:click="showConfigModal()">Cấu hình</a-button>
                </a-col>
            </a-row>
            <a-row style="margin-top: 10px;">
                <a-col :span="4" :offset="0" style="margin-right:10px">
                    <label>Tên công việc</label>
                    <a-input allow-clear placeholder="Tên công việc" v-model:value="formState.name" />
                </a-col>
                <!-- <a-col :span="3" :offset="0" style="margin-right:10px">
                    <label>Dự án</label>
                    <a-form-item :span="5">
                        <a-select
                                ref="select"
                                allow-clear
                                v-model:value="formState.project_id"
                                style="width:100%;"
                                :options="projectSelbox"
                                mode="multiple"
                                :field-names="{ label:'name', value: 'id' }"
                                :filterOption="filterProjectOption"
                        ></a-select>
                    </a-form-item>
                </a-col> -->
                <a-col :span="4">
                    <label>Dự án <span v-if="projectsFilter.length">({{activeTabProject == 'include' ? 'Include' : 'Exclude'}})</span></label>
                    <a-form-item :span="4" class="projects-filter">
                        <el-select
                            v-model="projectsFilter"
                            multiple
                            clearable
                            collapse-tags
                            filterable
                            placeholder=""
                            :max-collapse-tags="5"
                            style="width: 97%"
                        >
                            <div class="filter-header">
                                <el-button :class="{active: activeTabProject == 'include'}" size="small" @click="changeTabProjectActive('include')" style="width: 100%;">Include</el-button>
                                <el-button :class="{active: activeTabProject == 'exclude'}" size="small" @click="changeTabProjectActive('exclude')" style="width: 100%;">Exclude</el-button>
                            </div>
                            <el-option
                            v-for="item in projects"
                            :key="item.id"
                            :label="item.name"
                            :value="item.id"
                            />
                      </el-select>
                    </a-form-item>
                </a-col>
                <a-col :span="2" :offset="0" style="margin-right:10px">
                    <label>Bộ phận</label>
                    <a-form-item :span="5">
                        <el-select
                            v-model="formState.department_id"
                            multiple
                            clearable
                            collapse-tags
                            filterable
                            placeholder=""
                            style="width: 100%"
                            @change="onChangeSelectDepartment()"
                        >
                            <el-option
                                v-for="item in departmentSelbox"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                      </el-select>
                    </a-form-item>
                </a-col>
                <a-col :span="3" :offset="0" style="margin-right:10px">
                    <label>Người thực hiện</label>
                    <a-form-item :span="5">
                        <a-select
                                ref="select"
                                allow-clear
                                v-model:value="formState.user_id"
                                style="width:100%;"
                                :options="usersSelectBoxOnFilter"
                                mode="multiple"
                                :field-names="{ label:'fullname', value: 'id' }"
                                :filterOption="filterEmployeeOption"
                        ></a-select>
                    </a-form-item>
                </a-col>
                <a-col :span="2" :offset="0" style="margin-right:10px">
                    <label>Trạng thái</label>
                    <a-form-item :span="5">
                        <a-select
                                ref="select"
                                allow-clear
                                v-model:value="formState.status"
                                style="width:100%;"
                                :options="status"
                                mode="multiple"
                                :field-names="{ label:'label', value: 'value' }"
                        ></a-select>
                    </a-form-item>
                </a-col>
                <a-col style="margin-right:10px">
                    <label name="name">Khoảng thời gian</label>
                    <a-form-item>
                        <a-space direction="vertical">
                            <a-range-picker
                                v-model:value="datePeriod"
                                :allowEmpty="[true,true]"
                                :format="dateFormat"
                                style="width:100%;"
                            />
                        </a-space>
                    </a-form-item>
                </a-col>
                <a-col :span="2" :offset="0" style="margin-right:10px">
                    <label>Mã công việc</label>
                    <a-input allow-clear placeholder="Mã công việc" v-model:value="formState.id" />
                </a-col>
                <a-col style="margin-top:22px">
                    <a-button v-on:click="search()" type="primary" style="margin-right:10px" :loading="searchLoading">Tìm kiếm</a-button>
                </a-col>
            </a-row>
        </template>
    </div>
    <!-- table from here -->
    <a-row style="margin-top:0px">
        <a-col>
            <a-table class="task" :indentSize="30" :dataSource="dataSource" :loading="isLoading" :columns="columns" rowKey="id" childrenColumnName="grandchildren"
            :defaultExpandedRowKeys="defaultExpandedRowKeys" :row-selection="rowSelection"
            :scroll="scroll" :pagination="pagination" :rowClassName="getRowClassName" bordered>
                <template #headerCell="{column}">
                    <template v-if="column.key === 'name'">
                        <delete-outlined
                            v-if="is_show_delete_selected && is_authority"
                            style="color: red; margin-right: 10px; font-size: 15px;"
                            v-on:click="onClickDeleteSelectedButton()"
                        />
                        <span style="font-size: 9px;">Name</span>
                        <el-select
                            v-if="is_show_delete_selected && is_authority" 
                            v-model="multipleStatus" 
                            clearable 
                            placeholder="Select"
                            class="none-border"
                            style="float: right;"
                            @change="onChangeMultipleStatus"
                        >
                            <el-option
                                v-for="item in status"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </template>
                </template>
                <template #bodyCell="{column,record}">
                    <template v-if="column.key === 'project_id'">
                        <a-row>
                            <a-col :span="22" class="custom-col-project-body">
                                <el-select
                                    v-model="record.project_id"
                                    multiple
                                    filterable
                                    style="width: 100%; padding: 0;"
                                    @change="onChangeProject(record.id, $event)"
                                    :disabled = "!is_authority"
                                    class="none-border"
                                    clearable
                                >
                                    <el-option
                                        v-for="item in projects"
                                        :key="item.id"
                                        :label="item.name"
                                        :value="item.id"
                                    />
                                </el-select>
                            </a-col>
                            <a-col :span="2">
                                <info-circle-outlined
                                    v-if = "is_authority"
                                    style="font-size: 14px;"
                                    v-on:click="showEditTaskProjectsModal(record.id, record.department_id)"
                                />
                            </a-col>
                        </a-row>
                    </template>
                    <template v-if="column.key === 'sticker_id'">
                        <el-select
                            v-model="record.sticker_id"
                            filterable
                            style="width: 100%"
                            class="none-border"
                            @change="onChangeSticker(record.id, $event)"
                            :filterOption="filterStickersOption"
                            @click="onClickSticker(record.id, record.department_id)"
                            :disabled = "!is_authority"
                            clearable
                        >
                            <el-option
                                v-for="item in stickers"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id"
                            />
                        </el-select>
                    </template>
                    <template v-if="column.key === 'priority'">
                        <a-select
                            ref="select"
                            :bordered="false"
                            v-model:value="record.priority"
                            style="width:100%;"
                            :options="priorities"
                            allow-clear
                            :field-names="{ label:'label', value: 'id' }"
                            @change="onChangePriority(record.id, $event)"
                            :disabled = "!is_authority"
                        ></a-select>
                    </template>
                    <template v-if="column.key === 'weight'">
                        <a-input :bordered="false" v-model:value="record.weight" @blur="onChangeWeight(record.id, $event)" :disabled = "!is_authority"/>
                    </template>
                    <template v-if="column.key === 'department_id'">
                        <a-select
                            ref="select"
                            :bordered="false"
                            v-model:value="record.department_id"
                            style="width:100%;"
                            :options="departments"
                            allow-clear
                            :field-names="{ label:'label', value: 'value' }"
                            @change="onChangeDepartment(record.id, $event)"
                            :disabled = "!is_authority"
                        ></a-select>
                    </template>
                    <template v-if="column.key === 'task_timing'">
                        <field-time-outlined
                            style="font-size: 16px;"
                            v-on:click="showEditTaskTimingModal(record.id, record.department_id)"
                            :style="record.total_time_spent > 0 || record.total_estimate_time > 0 ? 'color: green' : ''"
                        />
                    </template>
                    <template v-if="column.key === 'start_time'">
                        <span v-if="!record.task_parent" style="color: green; font-weight: bold">{{ record.start_time }}</span>
                        <span v-else>{{ record.start_time }}</span>
                    </template>
                    <template v-if="column.key === 'end_time'">
                        <span v-if="!record.task_parent" style="color: green; font-weight: bold">{{ record.end_time }}</span>
                        <span v-else>{{ record.end_time }}</span>
                    </template>
                    <template v-if="column.key === 'deadline'">
                        <span
                            style="font-size: 12px;"
                            v-on:click="showEditModal(record.id, record.updated_at)"
                        >
                            {{ formatDeadline(record.deadline) }}
                        </span>
                    </template>
                    <template v-if="column.key === 'user_id'">
                        <a-select
                            ref="select"
                            :bordered="false"
                            v-model:value="record.user_id"
                            style="width:100%;"
                            show-search
                            :options="employees"
                            allow-clear
                            :field-names="{ label:'fullname', value: 'id' }"
                            @change="onChangeUser(record.id, $event)"
                            :filterOption="filterEmployeesOption"
                            :disabled = "!is_authority"
                        ></a-select>
                    </template>
                    <template v-if="column.key === 'progress'">
                        <a-input :bordered="false" v-model:value="record.progress" @blur="onChangeProgress(record.id, $event)" :disabled = "!is_authority"/>
                    </template>
                    <template v-if="column.key === 'quality'">
                        <a-input :bordered="false" v-model:value="record.quality" @blur="onChangeQuality(record.id, $event)" :disabled = "!is_authority"/>
                    </template>
                    <template v-if="column.key === 'status'">
                        <a-select
                            :bordered="false"
                            v-model:value="record.status"
                            allow-clear
                            style="width:100%;"
                            @change="onChangeStatus(record.id, $event)"
                            :disabled = "!is_authority"
                        >
                            <a-select-option v-for="option in status" :key="option.value" :value="option.value">
                                <span :class="['task-status-' + option.value]">{{ option.label }}</span>
                            </a-select-option>
                        </a-select>
                    </template>
                    <template v-if="column.key === 'action'">
                        <edit-outlined style="color: green" v-if = "is_authority" v-on:click="showEditModal(record.id, record.updated_at)"/>
                        <copy-outlined style="margin-left: 5px; color: blue" v-if = "is_authority" v-on:click="showCopyModal(record.id)"/>
                        <plus-outlined style="margin-left: 5px;" v-if = "is_authority" v-on:click="showQuickAddParentModal(record.id, record.project_id)"/>
                    </template>
                </template>
            </a-table>
        </a-col>
    </a-row>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import { InfoCircleOutlined, EditOutlined, CopyOutlined, PlusOutlined, FieldTimeOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import { onMounted, ref, watch, onBeforeUnmount } from 'vue';
import dayjs from 'dayjs';
import minMax from 'dayjs/plugin/minMax';
import { useI18n } from 'vue-i18n';
import CreatOrUpdate from './CreatOrUpdate.vue';
import config from './config.vue';
import preview from './preview.vue';
import SingleSubmitButton from '../Shared/SingleSubmitButton/SingleSubmitButton.vue';
import TaskTiming from '../common/TaskTiming.vue';
import TaskProject from '../common/TaskProject.vue';
import {
    reloadAfterTaskTimingChanged,
    reloadAfterTaskProjectChanged,
    onCommonChangeSticker,
    onCommonChangePriority
} from '../Helper/helpers.js';
import { buildFormStateTime } from '../Helper/build-datetime.js';
import { downloadFile, errorModal } from '../Helper/export.js';
import {Close,CircleCloseFilled} from '@element-plus/icons-vue';

import { resizeScreen } from '../Helper/resize-screen.js';

dayjs.extend(minMax)

export default ({
    components: {
        CreatOrUpdate,
        config,
        preview,
        SingleSubmitButton,
        TaskTiming,
        TaskProject,
        InfoCircleOutlined,
        EditOutlined,
        CopyOutlined,
        PlusOutlined,
        FieldTimeOutlined,
        DeleteOutlined,
        Close,
        CircleCloseFilled
    },
    setup() {
        const { t } = useI18n();
        const dataSource = ref();
        const pagination = ref({
            position: ['bottomCenter'],
            pageSize:20,
            showSizeChanger: false
        });
        const scroll = ref({ x: 700, y: 450 });
        const is_show_delete_selected = ref(false);
        const listSelectedIds = ref([]);
        const modalRef = ref();
        const is_hide_filters = ref(false);
        const is_authority = ref(false);
        const session = ref();
        const checked = ref(true);
        const modalConfigRef = ref();
        const modalPreviewRef = ref();
        const modalTaskTimingRef = ref();
        const modalTaskProjectRef = ref();
        const dateFormat = 'DD/MM/YYYY';
        const errorMessages = ref("");
        const formState = ref([]);
        const fileName = ref("");
        const excel_file = ref({});
        const projects = ref([]);
        const projectSelbox = ref([]);
        const departmentSelbox = ref([]);
        const employees = ref([]);
        const usersSelectBoxOnFilter = ref([]);
        const status = ref([]);
        const departments = ref([]);
        const stickers = ref([]);
        const priorities = ref([]);
        const onAction = ref(false);//Detect user actions
        const datePeriod = ref([]);
        const isLoading = ref(false);
        const searchLoading = ref(false);
        const loadingExport = ref(false);
        const treeData = ref([]);
        const defaultExpandedRowKeys = ref ([]);
        const fieldNames = ref({children:'grandchildren', label:'name', value: 'id' });
        const multipleStatus = ref([]);
        const columns = ref([
            {
                title: 'Name',
                dataIndex: 'name',
                key: 'name',
                align: 'left',
                width: 350,
            },
            {
                title: 'Action',
                dataIndex: '',
                key: 'action',
                fixed: false,
                align: 'center',
                width:  40,
            },
            {
                title: 'ID',
                dataIndex: 'id',
                key: 'id',
                align: 'left',
                width: 30,
            },
            {
                title: 'Project',
                dataIndex: 'project_id',
                key: 'project_id',
                align: 'center',
                width: 130,
            },
            {
                title: 'Type',
                dataIndex: 'sticker_id',
                key: 'sticker_id',
                fixed: false,
                align: 'center',
                ellipsis: true,
                resizable: true,
                width: 80,
            },
            {
                title: 'Level',
                dataIndex: 'priority',
                key: 'priority',
                fixed: false,
                align: 'center',
                ellipsis: true,
                resizable: true,
                width: 50,
            },
            {
                title: 'Weight',
                dataIndex: 'weight',
                key: 'weight',
                fixed: false,
                align: 'center',
                width: 30,
            },
            {
                dataIndex: 'task_timing',
                key: 'task_timing',
                align: 'center',
                width: 15,
            },
            {
                title: 'Begin',
                dataIndex: 'start_time',
                key: 'start_time',
                fixed: false,
                align: 'center',
                width: 55,
                sorter: (a, b) => {
                    const dateA = dayjs(a.start_time, dateFormat);
                    const dateB = dayjs(b.start_time, dateFormat);
                    return dateA - dateB;
                },
            },
            {
                title: 'End',
                dataIndex: 'end_time',
                key: 'end_time',
                fixed: false,
                align: 'center',
                width: 55,
                sorter: (a, b) => {
                    const dateA = dayjs(a.end_time, dateFormat);
                    const dateB = dayjs(b.end_time, dateFormat);
                    return dateA - dateB;
                },
            },
            {
                title: 'Deadline',
                dataIndex: 'deadline',
                key: 'deadline',
                fixed: false,
                align: 'center',
                width: 67,
                sorter: (a, b) => {
                    const dateA = dayjs(a.deadline, 'YYYY-MM-DD');
                    const dateB = dayjs(b.deadline, 'YYYY-MM-DD');
                    return dateA - dateB;
                },
            },
            {
                title: 'Estimated(h)',
                dataIndex: 'total_estimate_time',
                key: 'total_estimate_time',
                fixed: false,
                align: 'center',
                width: 40,
            },
            {
                title: 'Actual(h)',
                dataIndex: 'total_time_spent',
                key: 'total_time_spent',
                fixed: false,
                align: 'center',
                width: 40,
            },
            {
                title: 'Department',
                dataIndex: 'department_id',
                key: 'department_id',
                fixed: false,
                align: 'center',
                width: 70,
            },
            {
                title: 'Performer',
                dataIndex: 'user_id',
                key: 'user_id',
                fixed: false,
                align: 'center',
                width: 100,
            },
            {
                title: 'Progress',
                dataIndex: 'progress',
                key: 'progress',
                fixed: false,
                align: 'center',
                width: 35,
            },
            {
                title: 'Quality',
                dataIndex: 'quality',
                key: 'quality',
                fixed: false,
                align: 'center',
                width: 35,
            },
            {
                title: 'Status',
                dataIndex: 'status',
                key: 'status',
                fixed: false,
                align: 'center',
                width: 70,
            }
        ]);
        const project_ids = ref([]);
        const projectsFilter = ref([]);

        const clearRowSelected = () => {
            is_show_delete_selected.value = false
            listSelectedIds.value = []
            rowSelection.value.selectedRowKeys = [];
        }

        const showRegisterModal = () => {
            modalRef.value.ShowWithAddMode();
        };

        const showEditModal = (id, updated_at) => {
            if (listSelectedIds.value.length >0 && !listSelectedIds.value.includes(id)) {
                errorMessages.value = "Công việc bạn muốn sửa không nằm trong danh sách đã chọn";

                errorModal(t, errorMessages);
            } else {
                modalRef.value.ShowWithUpdateMode(id, listSelectedIds.value, updated_at);
            }
        };

        const showCopyModal = (id) => {
            modalRef.value.ShowWithCopyMode(id);
        };

        const showQuickAddParentModal = (id, project_id) => {
            modalRef.value.ShowWithQuickAddParentMode(id, project_id);
        }

        const showEditTaskTimingModal = (id, department_id) => {
            modalTaskTimingRef.value.ShowWithTaskTimingMode(id, 'tasks', department_id);
        }

        const showEditTaskProjectsModal = (task_id, department_id) => {
            modalTaskProjectRef.value.ShowWithTaskProjectMode(task_id, department_id);
        }

        const search = () => {
            //Detect user actions
            onAction.value = true;
            
            handleDatePeriod();
            isLoading.value = true;
            searchLoading.value = true;

            let ids = null
            if (formState.value.id) {
                if (formState.value.id.indexOf(',') !== -1) {
                    ids = formState.value.id.split(",").map(function(item) {
                        return parseInt(item, 10);
                    });
                } else {
                    ids = [parseInt(formState.value.id, 10)]
                }
            }

            let project_ids_include_filter = [];
            let project_ids_exclude_filter = [];
            
            if(activeTabProject.value == 'include'){
                project_ids_include_filter = projectsFilter.value;
                project_ids_exclude_filter = [];
            }else{
                project_ids_include_filter = [];
                project_ids_exclude_filter = projectsFilter.value;
            }

            let submitData = {
                project_id: project_ids_include_filter,
                exclude_project_ids: project_ids_exclude_filter,
                start_time: formState.value.start_time,
                end_time: formState.value.end_time,
                name: formState.value.name ? encodeURIComponent(formState.value.name): "",
                department_id: formState.value.department_id,
                user_id: formState.value.user_id,
                status: formState.value.status,
                ids: ids
            }

            axios.post('/api/task/get_task_list', submitData)
                .then(response => {
                        isLoading.value = false;
                        searchLoading.value = false;

                        dataSource.value = traverseTree(response.data.tasks, response.data.tasks);

                        // stickers.value = response.data.stickers;
                        employees.value = response.data.employees;

                        clearRowSelected()

                        setTimeout(() => { scroll.value.y = resizeScreen() }, 0);
                    })
                .catch((error) => {
                    clearRowSelected()
                    
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    //When search target data does not exist
                    dataSource.value = []; //dataSource empty
                    errorModal(t, errorMessages);//Show error message modally

                    isLoading.value = false;
                    searchLoading.value = false;
                });
        }

        const traverseTree = (originTree, tree, parentIndex = null, listStartWorkDate = [], listEndWorkDate = []) => {
            try {
                let listStarts = listStartWorkDate;
                let listEnds = listEndWorkDate;

                for (let i = 0; i < tree.length; i++) {

                    //project_id
                    if (tree[i].project_id) {
                        let projectId = tree[i].project_id.trim();
                        if (projectId.startsWith("{") && projectId.endsWith("}")) {
                            projectId = projectId.slice(1, -1);
                        }
                        tree[i].project_id = projectId.split(",").map((id) => parseInt(id.trim()));
                    } else {
                        tree[i].project_id = []
                    }

                    // let parentIndex = null
                    if (!tree[i].task_parent) {
                        parentIndex = i
                        listStarts = []
                        listEnds = []
                    }

                    //start_time
                    if (tree[i].start_time) {
                        listStarts.push(dayjs(tree[i].start_time, dateFormat));
                    }

                    //end_time
                    if (tree[i].end_time) {
                        listEnds.push(dayjs(tree[i].end_time, dateFormat));
                    }

                    if (tree[i].grandchildren) {
                        traverseTree(originTree, tree[i].grandchildren, parentIndex, listStarts, listEnds);
                    } 
                    else {
                        if (parentIndex !== null) {
                            originTree[parentIndex].start_time = listStarts.length > 0 ? dayjs.min(listStarts).format(dateFormat) : null
                            originTree[parentIndex].end_time = listEnds.length > 0 ? dayjs.max(listEnds).format(dateFormat) : null
                        }
                    }
                }
                
                return originTree;
            } catch (error) {
                console.log(error)
            }
        }

        const getRowClassName = (record, index) => {
            const deadline = dayjs(record.deadline, "YYYY-MM-DD");
            const end_time = dayjs(record.end_time, "DD/MM/YYYY");

            if (end_time.isAfter(deadline) || record.status === 0) {
                return "task-is-red";
            } else if (deadline.isBefore(dayjs(), "day") && record.status == 2) {
                return "task-is-warning";
            }

            return "";
        }

        const handleDatePeriod = () => {
            buildFormStateTime(formState, datePeriod)
        };

        const _fetch = () => {
            //create select boxes
            axios.get('/api/task/get_selectboxes')
            .then(response => {
                projects.value = response.data.projects;
                usersSelectBoxOnFilter.value = [{ id: 0, fullname: '----------' }, ...response.data.users];
                status.value = response.data.status;
                departments.value = response.data.departments;
                priorities.value = response.data.priorities;
                is_authority.value = response.data.is_authority;
                session.value = response.data.session;

                projectSelbox.value = [{ id: 0, name: '----------' }, ...response.data.projects];
                departmentSelbox.value = [{ value: 0, label: '----------' }, ...response.data.departments];

                if (onAction.value) {
                    search();
                }
            })
        };

        const onChangeProject = (id, value) => {
            let project_ids = value ? value : ""

            update({'id': id, 'project_ids': project_ids})
        }

        const onChangeSticker = (id, value) => {
            const args = [
                id, value, dataSource.value, stickers.value, priorities.value
            ]
            let submitData = onCommonChangeSticker(...args)

            //save it to DB
            update(submitData)
        }

        const onChangePriority = (id, value) => {
            const args = [
                id, value, dataSource.value, stickers.value, priorities.value
            ]
            let submitData = onCommonChangePriority(...args)

            //save it to DB
            update(submitData)
        }

        const onChangeWeight = (id, event) => {
            update({'id': id, 'weight': event.target.value})
        }

        const onChangeDepartment = (id, value) => {
            let department_id = value ? value : ""

            update({'id': id, 'department_id': department_id})
        }

        const onChangeUser = (id, value) => {
            let user_id = value ? value : ""

            update({'id': id, 'user_id': user_id})
        }

        const formatDeadline = (deadline) => {
            return deadline ? dayjs(deadline).format('DD/MM/YYYY') : null;
        }

        const onChangeProgress = (id, event) => {
            update({'id': id, 'progress': event.target.value})
        }
        const onChangeQuality = (id, event) => {
            update({'id': id, 'quality': event.target.value})
        }

        const onChangeStatus = (id, value) => {
            update({'id': id, 'status': value})
        }
        const onChangeMultipleStatus = () => {
            updateMultiple({'id': rowSelection.value.selectedRowKeys, 'multiple_status': multipleStatus.value})
        }

        const update = (submitData) => {
            axios.patch('/api/task/quick_update', submitData)
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
        const updateMultiple = (submitData) => {
            axios.patch('/api/task/update_multiple', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });
                search();
                multipleStatus.value = '';
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);//show error message modally
            })
        }

        const showConfigModal = () => {
            modalConfigRef.value.ShowWithConfigMode();
        }

        const doExport = () => {
            //format datetime
            handleDatePeriod();

            let ids = []
            if (formState.value.id) {
                if (formState.value.id.indexOf(',') !== -1) {
                    ids = formState.value.id.split(",").map(function(item) {
                        return parseInt(item, 10);
                    });
                } else {
                    ids = [parseInt(formState.value.id, 10)]
                }
            }

            let submitData = {
                project_id: formState.value.project_id,
                start_time: formState.value.start_time,
                end_time: formState.value.end_time,
                department_id: formState.value.department_id,
                user_id: formState.value.user_id,
                status: formState.value.status
            }

            if (ids.length > 0) {
                submitData.ids = ids
            }

            if (formState.value.name) {
                submitData.name = encodeURIComponent(formState.value.name)
            }

            loadingExport.value = true;
            downloadFile('/api/task/export', submitData, errorMessages, t)
            .then(() => {
                loadingExport.value = false;
            })
            .catch(() => {
                loadingExport.value = false;
            });
        }

        const beforeUpload = (file) => {
            excel_file.value = file
            fileName.value = file.name
            //Prevent upload
            return false;
        };

        const doImport = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                let formData = new FormData();
                formData.append('excel_file', excel_file.value);
                
                axios.post('/api/task/import/check_excel_data', formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(response => {
                    let errors = response.data

                    if (errors.length > 0) {
                        Modal.confirm({
                            title: t('message.MSG-I-009'),
                            okText: 'Ok',
                            cancelText: 'Cancel',
                            onOk() {
                                modalPreviewRef.value.ShowWithPreviewMode(errors);

                                reject();
                                fileName.value = ""
                                excel_file.value = {}
                            },
                            onCancel() {
                                reject();
                                fileName.value = ""
                                excel_file.value = {}
                            },
                        })
                    } else {
                        axios.post('/api/task/import', formData,
                        {
                            headers: {
                                'Content-Type': 'multipart/form-data'
                            }
                        })
                        .then(response => {
                            resolve();
                            notification.success({
                                message: t('message.MSG-TITLE-W'),
                                description: response.data.success,
                            });

                            fileName.value = ""
                            excel_file.value = {} 
                        })
                        .catch(error => {
                            reject();
                            errorMessages.value = error.response.data.errors;
                            errorModal(t, errorMessages);
                        })
                    }
                })
                .catch(error => {
                    reject();
                    errorMessages.value = error.response.data.errors;
                    errorModal(t, errorMessages);
                })
            })
        }
        const doImportJob = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                let formData = new FormData();
                formData.append('excel_file', excel_file.value);
                
                axios.post('/api/task/import/check_excel_data', formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(response => {
                    let errors = response.data

                    if (errors.length > 0) {
                        Modal.confirm({
                            title: t('message.MSG-I-009'),
                            okText: 'Ok',
                            cancelText: 'Cancel',
                            onOk() {
                                modalPreviewRef.value.ShowWithPreviewMode(errors);

                                reject();
                                fileName.value = ""
                                excel_file.value = {}
                            },
                            onCancel() {
                                reject();
                                fileName.value = ""
                                excel_file.value = {}
                            },
                        })
                    } else {
                        axios.post('/api/task/import/import-job', formData,
                        {
                            headers: {
                                'Content-Type': 'multipart/form-data'
                            }
                        })
                        .then(response => {
                            resolve();
                            notification.success({
                                message: t('message.MSG-TITLE-W'),
                                description: response.data.success,
                            });

                            fileName.value = ""
                            excel_file.value = {} 
                        })
                        .catch(error => {
                            reject();
                            errorMessages.value = error.response.data.errors;
                            errorModal(t, errorMessages);
                        })
                    }
                })
                .catch(error => {
                    reject();
                    errorMessages.value = error.response.data.errors;
                    errorModal(t, errorMessages);
                })
            })
        }

        const onShowHideFilters = () => {
            if (checked.value) {
                is_hide_filters.value = false;
            } else {
                is_hide_filters.value = true;
            }
            setTimeout(() => { scroll.value.y = resizeScreen() }, 0);
        };

        const onChangeSelectDepartment = () => {
            var departmentId = null;
            if (Number.isInteger(formState.value.department_id) && formState.value.department_id > 0) {
                departmentId = formState.value.department_id
            }

            axios.post('/api/task/get_select_boxes_by_department_id', {department_id: departmentId})
            .then(response => {
                    usersSelectBoxOnFilter.value = [{ id: 0, fullname: '----------' }, ...response.data];
                })
            .catch((error) => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                //When search target data does not exist
                usersSelectBoxOnFilter.value = []; //users select box on filter empty
            });
        }

        const filterProjectOption = (input, option) => {
            if (option.name) {
                return option.name.toLowerCase().indexOf(input.toLowerCase()) >= 0 ? true : false;
            }
        }

        const filterEmployeeOption = (input, option) => {
            if (option.fullname) {
                return option.fullname.toLowerCase().indexOf(input.toLowerCase()) >= 0 ? true : false;
            }
        }

        const filterStickersOption = (input, option) => {
            if (option.name) {
                return option.name.toLowerCase().indexOf(input.toLowerCase()) >= 0 ? true : false;
            }
        }

        const filterEmployeesOption = (input, option) => {
            if (option.fullname) {
                return option.fullname.toLowerCase().indexOf(input.toLowerCase()) >= 0 ? true : false;
            }
        }
        const onClickSticker = (id, department_id) => {

            let dataDepartment = {
                department_id: department_id
            }

            axios.post('/api/task/get_sticker', dataDepartment)
                .then(response => {
                        stickers.value = response.data;
                    })
                .catch((error) => {
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    errorModal(t, errorMessages);//Show error message modally
                });
        }

        const onChangeSwitch = () => {
            rowSelection.value.selectedRowKeys = [];
        }

        const rowSelection = ref({
            checkStrictly: true,

            columnWidth: 20,

            onChange: (selectedRowKeys, selectedRows) => {
                listSelectedIds.value = selectedRowKeys

                rowSelection.value.selectedRowKeys = selectedRowKeys;

                if (selectedRowKeys.length > 0) {
                    is_show_delete_selected.value = true
                } else {
                    is_show_delete_selected.value = false
                }
            },
        });

        const onClickDeleteSelectedButton = () => {
            if (listSelectedIds.value.length > 0) {

                let title = 'đơn lẻ';
                let mode = 1
                if (!rowSelection.value.checkStrictly) {
                    title = 'cha và con';
                    mode = 2
                }

                Modal.confirm({
                    title: 'Bạn đang chọn xoá '+ title +'. Bạn có chắc chắn xoá các thông tin công việc đã chọn này?',
                    okText: 'Ok',
                    cancelText: 'Huỷ',
                    onOk() {
                        axios.post('/api/task/delete_multiple',
                            {
                                ids: listSelectedIds.value,
                                mode: mode
                            }
                        )
                        .then(response => {
                            clearRowSelected()

                            onSaved()
                        })
                        .catch(error => {
                            errorMessages.value = error.response.data.errors;
                            errorModal(t, errorMessages);

                            onSaved()
                        })
                    },
                })
            }
        }

        const onSaved = () => {
            _fetch();
        };

        const onSavedTaskTiming = (args) => {
            reloadAfterTaskTimingChanged(dataSource.value, args, formState.value)
        }

        //reload whenever the user action on taskporject model
        const onSavedTaskProject = (selectedTaskId = null) => {
            reloadAfterTaskProjectChanged(dataSource.value, selectedTaskId)
        }

        const activeTabProject = ref('include');

        const changeTabProjectActive = (type='include')=>{
            activeTabProject.value = type;    
        }

        onMounted(() => {
            onClickSticker(null,null)
            _fetch();
            var screenWidth = window.screen.width; // Screen width in pixels
            var screenHeight = window.screen.height; // Screen height in pixels
            console.log(screenWidth, screenHeight)

            if (screenWidth === 2560 && screenHeight === 1440) {
                scroll.value.y = 750
            } else if (screenWidth === 1080 && screenHeight === 1920) {
                scroll.value.y = 1750
            } else if (screenWidth === 1920 && screenHeight === 1080) {
                scroll.value.y = 650
            }
        });

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        return {
            dataSource,
            scroll,
            pagination,
            formState,
            is_hide_filters,
            checked,
            fileName,
            dateFormat,
            columns,
            errorMessages,
            projects,
            projectSelbox,
            search,
            getRowClassName,
            onSaved,
            onSavedTaskTiming,
            onSavedTaskProject,
            treeData,
            fieldNames,
            datePeriod,
            showRegisterModal,
            showEditModal,
            showCopyModal,
            showQuickAddParentModal,
            showEditTaskTimingModal,
            modalRef,
            modalConfigRef,
            modalPreviewRef,
            modalTaskTimingRef,
            modalTaskProjectRef,
            employees,
            usersSelectBoxOnFilter,
            status,
            departments,
            departmentSelbox,
            stickers,
            priorities,
            isLoading,
            loadingExport,
            searchLoading,
            defaultExpandedRowKeys,
            onChangeProject,
            onChangeSticker,
            onChangePriority,
            onChangeWeight,
            onChangeDepartment,
            onChangeUser,
            onChangeProgress,
            onChangeQuality,
            onChangeStatus,
            formatDeadline,
            showConfigModal,
            doExport,
            beforeUpload,
            doImport,
            onShowHideFilters,
            onChangeSelectDepartment,
            filterProjectOption,
            filterEmployeeOption,
            filterStickersOption,
            filterEmployeesOption,
            rowSelection,
            is_show_delete_selected,
            onClickDeleteSelectedButton,
            showEditTaskProjectsModal,
            onChangeSwitch,
            multipleStatus,
            onChangeMultipleStatus,
            onClickSticker,
            is_authority,
            doImportJob,
            session,
            project_ids,
            activeTabProject,
            changeTabProjectActive,
            projectsFilter,
        };
    }
})
</script>
<style lang="scss">
.task th {
    font-weight: bold !important;
    font-size:9px;
}
.task span {
    font-size: 12px;
}
.task input{
    font-size:12px;
    min-height:22px;
}
.task .ant-input {
    font-size: 12px;
    min-height:30px;
}
.task td.ant-table-cell {
    font-size:12px;
}

.filter-header{
    display: flex;
    justify-content: space-between;

    .active{
        background: #1890ff;
        color: #fff;
    }
}
</style>