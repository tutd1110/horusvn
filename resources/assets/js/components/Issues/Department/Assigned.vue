<template>
    <note ref="modalNoteRef" @saved="onSaved"></note>
    <task-timing ref="modalTaskTimingRef" @saved="onSaved"></task-timing>
    <Report ref="modalReportRef"></Report>
    <div id="filter-block">
        <el-row :gutter="10" style="margin-bottom: 15px;">
            <el-col :span="2" style="padding-top: 21px">
                <el-badge :value="totalDSelfCreated" class="item" :hidden="totalDSelfCreated === 0">
                    <!-- <el-button style="width: 100%" type="success" v-on:click="onRedirect()">Goto Department SCreated</el-button> -->
                    <el-button style="width: 100%" type="success" v-on:click="onRedirect()">Bug Toàn Công Ty</el-button>
                </el-badge>
            </el-col>
            <div class="flex-grow" />
            <el-col :span="5">
                <label class="sub-select">Dự án mặc định</label>
                <el-select
                    v-model="projectGetTask"
                    clearable
                    multiple
                    collapse-tags
                    collapse-tags-tooltip
                    :max-collapse-tags="2"
                    filterable
                    style="width:100%;"
                >
                    <el-option
                        v-for="item in projects"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-col>
        </el-row>
        <el-row :gutter="10" style="margin-bottom: 0;">
            <el-col :span="1" >
                <label class="sub-select">ID Issue</label>
                <el-input
                    v-model="formState.id"
                    type="number"
                    class="id-issue"
                    style="width: 100%"
                />
            </el-col>
            <el-col :span="3">
                <label class="sub-select">Dự án</label>
                <el-form-item >
                    <el-select
                        v-model="formState.project_id"
                        filterable
                        clearable
                        collapse-tags
                        style="width: 100%"
                    >
                        <el-option
                            v-for="item in projects"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
            </el-col>
            <el-col :span="2">
                <label class="sub-select">Bộ phận</label>
                <el-select
                    v-model="formState.assigned_department_id"
                    value-key="value"
                    placeholder="Department"
                    clearable
                    multiple
                    collapse-tags
                    collapse-tags-tooltip
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in departments"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-col>
            <el-col :span="2">
                <label class="sub-select">Type</label>
                <el-select
                    v-model="formState.type"
                    filterable
                    placeholder=""
                    clearable
                    multiple
                    collapse-tags
                    style="width:100%;"
                >
                    <el-option
                        v-for="item in types"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-col>   
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Fixer</label>
                <el-select
                    v-model="formState.assigned_user_id"
                    value-key="id"
                    placeholder="Employees"
                    clearable
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in filteredUsers"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-col>
            <el-col :span="3">
                <label class="sub-select">Chức năng</label>
                <el-select-v2
                    v-model="formState.task_id"
                    filterable
                    clearable
                    :options="tasks"
                    style="width:100%;"
                />
            </el-col>
            <el-col :span="3">
                <label class="sub-select">Nội dung</label>
                <el-input
                    v-model="formState.description"
                    type="input"
                    clearable
                />
            </el-col>
            <el-col :span="4">
                <label class="sub-select">Trạng thái</label>
                <el-select
                    v-model="formState.status"
                    value-key="value"
                    placeholder="Status"
                    clearable
                    multiple
                    collapse-tags
                    collapse-tags-tooltip
                    :max-collapse-tags="2"
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in status"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-col>
            <el-col :span="2">
                <label class="sub-select">Weight</label>
                <el-select
                    v-model="formState.weighted"
                    placeholder="Weight"
                    clearable
                    style="width: 100%"
                >
                    <el-option value="0" label="Empty"></el-option>
                </el-select>
            </el-col>
            <el-col :span="1" style="padding-top: 21px">
                <el-space size="small" spacer="|">
                    <el-button type="primary" v-on:click="search()">Search</el-button>
                    <el-button type="primary" v-on:click="showReportModal()">Report</el-button>
                </el-space>
            </el-col>
        </el-row>
    </div>
    <!-- table from here -->
    <div class="table-task">
        <el-row :gutter="2" class='table-task-header'>
            <template v-for="(column, idx1) in columns" :key="idx1">
                <el-col :span="column.span" v-if="column.sorter"  @click="onColClick(column.key)">
                    <el-card class="box-card" shadow="hover" style="cursor: pointer;">
                        <div class="timesheets-title" v-html="column.title" style=" margin-right: 10px;"></div>
                        <CaretTop v-if="iconValue === 2 && keyColumnClick == column.key" style="width: 18px; color:#909399" />
                        <CaretBottom v-else-if="iconValue === 3 && keyColumnClick == column.key" style="width: 18px; color:#909399" />
                        <DCaret v-else="iconValue === 1" style="width: 18px; color:#909399" />
                    </el-card>
                </el-col>
                <el-col :span="column.span" v-else>
                    <el-card  class="box-card" shadow="hover" >
                        <div class="timesheets-title" v-html="column.title"></div>
                    </el-card>
                </el-col>
            </template>
        </el-row>
        <el-scrollbar :height="heightScrollbar" class="custom-scrollbar">
            <template v-for="(record, idx2) in dataSource" :key="idx2">
                <el-row :gutter="2" class="table-task-body">
                    <el-col :span="getColSpan('id')">
                        <el-card class="box-card name-task" shadow="hover" style="text-align: center;">
                            <span style="margin-right: 16px;">{{ record.id }}</span>
                            <DocumentCopy
                                style="width: 18px; cursor: pointer; color:#909399"
                                v-on:click="onCopy(record)"
                            />
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('start_date')">
                        <el-card class="box-card" shadow="hover" style="text-align: center;">
                            {{ record.start_date }}
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('tester')">
                        <el-card class="box-card" shadow="hover" style="text-align: center;">
                            {{ record.tester }}
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('project_id')">
                        <el-card class="box-card" shadow="hover" style="text-align: center;">
                            <el-select
                                v-model="record.project_id"
                                filterable
                                clearable
                                :disabled="!getEditable(record, 'project_id')"
                                placeholder=""
                                class="none-border"
                                style="width:100%;"
                                @change="onChangeSelect(record, 'project_id', $event)"
                            >
                                <el-option
                                    v-for="item in projects"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value"
                                />
                            </el-select>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('task_id')">
                        <el-card class="box-card" shadow="hover" style="text-align: center;">
                            <el-col :span="22">
                                <el-select-v2
                                    v-model="record.task_id"
                                    filterable
                                    clearable
                                    :disabled="!getEditable(record, 'task_id')"
                                    :options="getTasksForItem(record) || []"
                                    class="none-border"
                                    style="width:100%;"
                                    @change="onChangeSelect(record, 'task_id', $event)"
                                    @visible-change="onVisibleChangeTask(record, $event)"
                                >
                                    <template #default="{ item }">
                                        <el-tooltip
                                            class="box-item"
                                            effect="dark"
                                            :content="item.label"
                                            placement="left"
                                        >
                                            <span>{{ item.label }}</span>
                                        </el-tooltip>
                                    </template>
                                </el-select-v2>
                            </el-col>
                            <el-col :span="2" style="display: flex;">
                                <InfoFilled
                                    style="width: 20px; cursor: pointer; color:#909399"
                                    v-on:click="showEditTaskTimingModal(record)"
                                />
                            </el-col>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('task_input_id')">
                        <el-card class="box-card" shadow="hover" style="text-align: center;">
                            <el-input
                                v-model="record.task_id_input"
                                type="number"
                                class="none-border"
                                clearable
                                :readonly="!getEditable(record, 'task_input_id')"
                                @change="updateTaskId(record)"
                            />    
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('level')">
                        <el-card class="box-card" shadow="hover" style="text-align: center;">
                            <el-select
                                v-model="record.level"
                                filterable
                                placeholder=""
                                clearable
                                :disabled="!getEditable(record, 'level')"
                                class="none-border"
                                style="width:100%;"
                                @change="onChangeSelect(record, 'level', $event)"
                                :class="[record.level >= 0 ? 'task-level-' + record.level : 'no-select']"
                            
                            >
                                <el-option
                                    v-for="item in levels"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value"
                                    :class="['task-level-' + item.value]"
                                />
                            </el-select>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('description')">
                        <el-card class="box-card" shadow="hover">
                            <el-col :span="22">
                                <el-input
                                    v-model="record.description"
                                    autosize
                                    :readonly="!getEditable(record, 'description')"
                                    type="textarea"
                                    class="none-border"
                                    @change="onChangeSelect(record, 'description', $event)"
                                />
                                <div class="link-task" style="padding: 0 11px;">
                                    <div class="link-task-item"  v-for="(url, index) in record.urls" :key="index">
                                        <a :href="url" target="_blank">Link</a>
                                    </div>
                                </div>
                            </el-col>
                            <el-col :span="2" style="display: flex;">
                                <el-badge :value="record.comment_count" class="item" :hidden="record.comment_count === 0">
                                    <ChatDotRound
                                        style="width: 20px; cursor: pointer; color:#909399"
                                        v-on:click="showEditNoteModal(record.id)"
                                    />
                                </el-badge>
                            </el-col>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('assigned_user_id')">
                        <el-card class="box-card" shadow="hover" style="text-align: center;">
                            <el-select
                                v-model="record.assigned_user_id"
                                filterable
                                clearable
                                :disabled="!getEditable(record, 'assigned_user_id')"
                                class="none-border"
                                style="width:100%;"
                                @change="onChangeSelect(record, 'assigned_user_id', $event)"
                            >
                                <el-option
                                    v-for="item in fixers"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value"
                                />
                            </el-select>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('status')">
                        <el-card class="box-card" shadow="hover">
                            <el-select 
                                v-model="record.status" 
                                class="none-border" 
                                style="width:100%;"
                                :disabled="!getEditable(record, 'status')"
                                :class="[record.status >= 0 ? 'bug-status-' + record.status : 'no-select']"
                                @change="onChangeSelect(record, 'status', $event)"
                            >
                                <el-option
                                    v-for="item in status"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value"
                                    :class="['bug-status-' + item.value]"
                                />
                            </el-select>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('tag_test')">
                        <el-card class="box-card" shadow="hover">
                            <el-select 
                                v-model="record.tag_test" 
                                class="none-border" 
                                style="width:100%;"
                                :disabled="!getEditable(record, 'tag_test')"
                                @change="onChangeSelect(record, 'tag_test', $event)"
                            >
                                <el-option
                                    v-for="item in tag_tests"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value"
                                />
                            </el-select>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('type')">
                        <el-card class="box-card" shadow="hover">
                            <el-select 
                                v-model="record.type" 
                                class="none-border" 
                                style="width:100%;"
                                :disabled="!getEditable(record, 'type')"
                                :class="[record.type >= 0 ? 'task-assignment-type-' + record.type : 'no-select']"
                                @change="onChangeSelect(record, 'type', $event)"
                            >
                                <el-option
                                    v-for="item in types"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value"
                                    :class="['task-assignment-type-' + item.value]"
                                />
                            </el-select>
                        </el-card>
                    </el-col>
                </el-row>
            </template>
        </el-scrollbar>
    </div>
    <el-col style="margin-top: 20px;display: flex;align-items: center;justify-content: center;">
        <el-pagination
            v-model="formState.current_page"
            :page-size="formState.per_page"
            background
            :total="total"
            @current-change="onChangePage"
        />
    </el-col>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { onMounted, computed, reactive, ref } from 'vue';
import { DCaret, CaretBottom, CaretTop, DocumentCopy, ChatDotRound, InfoFilled } from '@element-plus/icons-vue';
import dayjs from 'dayjs';
import TaskTiming from '../../common/TaskTiming.vue';
import note from '../../common/note.vue';
import Report from '../../common/Issues/Report.vue';
import { callMessage } from '../../Helper/el-message.js';
import { openLoading, closeLoading } from '../../Helper/el-loading';
import { useI18n } from 'vue-i18n';
import { ElMessage, ElMessageBox } from 'element-plus';

import { resizeScreen } from '../../Helper/resize-screen.js';

interface FormState {
    id?: number,
    project_id?: number,
    assigned_department_id?: number[],
    task_id?: number,
    description?: any,
    assigned_user_id?: number,
    status: number[],
    weighted?: number,
    current_page: number,
    per_page: number,
    column?: string | undefined,
    order?: string | undefined,
    type?: number | undefined,
};
interface Option {
    value: number,
    department_id?: number,
    label: string
}
interface Item {
    id: number,
    start_date: string,
    tester: string,
    project_id: number,
    task_id: number,
    task_id_input: number,
    level: number,
    note: string,
    description: string,
    assigned_user_id: number,
    comment_count: number,
    status: number,
    tag_test: number,
    type: number,
    urls: string[],
    project_get_task?: number[]
}
interface Session {
    id: number,
    department_id: number,
    editable: boolean
}

const { t } = useI18n();
const total = ref(0);
const errorMessages = ref('');
const formState = ref<FormState>({
    status: [0,1,4],
    current_page: 1,
    per_page: 20
})
const projectGetTask = ref([])
const heightScrollbar = ref()
const totalDSelfCreated = ref(0)
const projects = ref<Option[]>([])
const departments = ref<Option[]>([])
const tasks = ref<Option[]>([])
const status = ref<Option[]>([])
const levels = ref<Option[]>([])
const types = ref<Option[]>([])
const tag_tests = ref<Option[]>([])
const users = ref<Option[]>([])
const filteredUsers = computed(() => {
    const selectedDepartmentIds = formState.value.assigned_department_id;
    if (selectedDepartmentIds && selectedDepartmentIds.length > 0) {
        return users.value.filter((user: Option) => user.department_id?.toString() && selectedDepartmentIds.includes(user.department_id));
    } else {
        return users.value;
    }
});
// Create a reactive state to store task options based on cache keys
const tasksCache = reactive<Record<string, Option[]>>({
    initial: tasks.value, // Assuming tasks.value contains initial tasks data
});
const session = ref<Session>()
const fixers = computed(() => {
    return users.value.filter(user => user.department_id === session.value?.department_id)
});
//select box list generation
const CreateSelbox = () => {
    //create select boxes
    axios.get('/api/task_assignments/da-selbox')
    .then(response => {
        //create selboxes dropdown
        const emptyOption = {label:"---------", value:0};
        projects.value = response.data.projects;
        projects.value.unshift(emptyOption);

        departments.value = response.data.departments;
        departments.value.unshift(emptyOption);

        tasks.value = response.data.tasks;
        tasks.value.unshift(emptyOption);

        // Initialize the tasksCache with the initial tasks data
        tasksCache['initial'] = response.data.tasks; // Assuming tasks.value contains initial tasks data

        // Get total personal assigned issues
        totalDSelfCreated.value = response.data.count_d_self_created;

        status.value = response.data.status;
        levels.value = response.data.levels;
        types.value = response.data.types;
        tag_tests.value = response.data.tag_tests;
        users.value = response.data.users;
        users.value.unshift(emptyOption);

        session.value = response.data.session;
    })
}
const dataSource = ref<Item[]>([])
const onChangePage = (page: number) => {
    formState.value.current_page = page

    search()
}
const columns = ref([
    { title: "ID", key: "id", span: 1 },
    { title: "Start", key: "start_date", span: 2, sorter: true},
    { title: "Tester", key: "tester", span: 2 },
    { title: "Project", key: "project_id", span: 2 },
    { title: "Task", key: "task_id", span: 4 },
    { title: "Code", key: "task_id_input", span: 1 },
    { title: "Level", key: "level", span: 1 },
    { title: "Description", key: "description", span: 5 },
    { title: "Fixer", key: "assigned_user_id", span: 2 },
    { title: "Status", key: "status", span: 1 },
    { title: "Tag Test", key: "tag_test", span: 2 },
    { title: "Type", key: "type", span: 1 },
])
const getColSpan = (key: string) => {
    const foundColumn = columns.value.find(column => column.key === key);
    return foundColumn ? foundColumn.span : 1; // Default to 1 if not found
}
const iconValue = ref(1);
const keyColumnClick = ref();
const onColClick = (keyColumn: string) => {
    keyColumnClick.value = keyColumn
    // iconValue.value = iconValue.value ? (iconValue.value === 1 ? 2 : (iconValue.value === 2 ? 3 : 1)) : 1;
    iconValue.value = iconValue.value ? (iconValue.value === 1 ? 3 : (iconValue.value === 3 ? 2 : 1)) : 1;
    sortData();
}
const sortData = () => {
    if (iconValue.value === 2) {
        formState.value.column = keyColumnClick.value
        formState.value.order = 'asc'
    } else if (iconValue.value === 3) {
        formState.value.column = keyColumnClick.value
        formState.value.order = 'desc'
    } else {
        formState.value.column = undefined
        formState.value.order = undefined
    }

    search()
}
const onCopy = (item: Item) => {
    let selectedValue = "";

    if (item.id && String(item.id).trim() !== "") {
        selectedValue += String(item.id).trim() + " - ";
    }

    if (item.description && String(item.description).trim() !== "") {
        selectedValue += String(item.description).trim() + " - ";
    }

    const foundElement = types.value.find(element => element.value === item.type);
    if (foundElement) {
        selectedValue += foundElement.label;
    }

    const textarea = document.createElement('textarea');
    textarea.value = selectedValue;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);

    // Success
    callMessage(t('message.MSG-COPY-SUCCESS'), 'success');
}
const getTasksForItem = (item: Item): Option[] => {
    const cacheKey = `${item.project_get_task ? item.project_get_task.join('_') : ''}_${session.value?.department_id}`;

    return tasksCache[cacheKey] || tasksCache['initial'];
}
const onVisibleChangeTask = (item: Item, is_appear: boolean) => {
    if (is_appear && (item.project_id || projectGetTask.value.length > 0) && session.value?.department_id) {
        item.project_get_task = (item.project_id != null && item.project_id.toString() != '') ? [item.project_id] : projectGetTask.value;
        getTasks(item);
    }
}
const onChangeSelect = (item: Item, column: string, value: number | string) => {
    warningTaskId(item, column, value)
}
const warningTaskId = (item: Item, column: string, value: number | string) => {
    if (column === 'task_id') {
        if (!value) {
            ElMessageBox.confirm(
                'Xóa task đồng nghĩa với xóa tất cả issues của nó, bạn chắc chắn chứ?',
                'Warning',
                {
                    confirmButtonText: 'OK',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            )
            .then(() => {
                item.task_id_input = item.task_id
                update(item.id, column, value)
            })
            .catch(() => {
                item.task_id = item.task_id_input;
                ElMessage({
                    type: 'info',
                    message: 'Bạn đã hủy thao tác này',
                })
            })
        } else {
            item.task_id_input = parseInt(value.toString())
            update(item.id, column, value)
        }
    } else {
        update(item.id, column, value)
    }
}
const updateTaskId = (item: Item) => {
    const selectedOption = getTasksForItem(item).find(option => option.value == item.task_id_input);

    if (selectedOption) {
        item.task_id = selectedOption.value;

        update(item.id, 'task_id', item.task_id)
    }
}
const getTasks = (item: Item) => {
    const cacheKey = `${item.project_get_task ? item.project_get_task.join('_') : ''}_${session.value?.department_id}`;

    if (!tasksCache[cacheKey]) {
        const formGetTask = {
            project_id: item.project_get_task,
            assigned_department_id: session.value?.department_id,
        }
        axios.post('/api/task_assignments/get_tasks',formGetTask)
        .then(response => {
            tasksCache[cacheKey] = response.data;
        })
        .catch((error) => {
            tasks.value = []; //dataSource empty
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error');
        });
    }
}
const update = (id: number, column: string, value: string | number | number[]) => {
    let submitData = {
        id: id,
        [column]: value ? value : ""
    }

    axios.patch('/api/task_assignments/update', submitData)
    .then(response => {
        callMessage(response.data.success, 'success');
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const modalNoteRef = ref();
const modalTaskTimingRef = ref();
const modalReportRef = ref();
const showEditTaskTimingModal = (item: Item) => {
    modalTaskTimingRef.value.ShowWithTaskTimingMode(item.id, 'task_assignments', session.value?.department_id);
}
const showEditNoteModal = (id: number) => {
    modalNoteRef.value.ShowWithNoteMode(id);
}
const showReportModal = () => {
    modalReportRef.value.ShowWithReportMode(formState.value, 'da-total');
}
const search = () => {
    openLoading('custom-scrollbar'); // Open the loading indicator before loading data

    if ('assigned_department_id' in formState.value) {
        delete formState.value.assigned_department_id;
    }

    if (formState.value.description) {
        formState.value.description = formState.value.description.split(',');
    }
    
    axios.post('/api/task_assignments/da-issues', formState.value)
    .then(response => {
        dataSource.value = transferData(response.data.items)
        total.value = response.data.totalItems
        setTimeout(() => { heightScrollbar.value = resizeScreen() }, 0);
        closeLoading(); // Close the loading indicator after 1 second
    })
    .catch((error) => {
        closeLoading(); // Close the loading indicator
        errorMessages.value = error.response.data.errors;//put message content in ref
        //When search target data does not exist
        dataSource.value = []; //dataSource empty
        callMessage(errorMessages.value, 'error');
    });
}
const transferData = (data: Item[]) => {
    return data.map(item => {
        const urls = item.note !== '<p><br></p>' ? splitTextAndUrls(item.note) : [];
        
        const transformedItem: Item = {
            id: item.id,
            start_date: item.start_date ? dayjs(item.start_date).format('DD/MM/YYYY') : '',
            tester: item.tester,
            project_id: item.project_id,
            task_id: item.task_id,
            task_id_input: item.task_id_input,
            level: item.level,
            note: item.note,
            status: item.status,
            comment_count: item.comment_count,
            description: item.description,
            assigned_user_id: item.assigned_user_id,
            tag_test: item.tag_test,
            type: item.type,
            urls: urls,
        };

        return transformedItem;
    });
}
const getEditable = (item: Item, columnKey: string) => {
    if (columnKey === 'assigned_user_id' && session.value?.department_id === 2) {
        return true;
    }

    return session.value?.id === item.assigned_user_id || session.value?.editable
}
const splitTextAndUrls = (str: string) => {
    if (!str) {
        return []
    }

    const regex = /<a\s+(?:[^>]*?\s+)?href=(["'])(.*?)\1/g;
    const matches = Array.from(str.matchAll(regex));
    const urls = matches.map(match => match[2]);

    return urls
}
const onSaved = () => {
    search();
}
const onRedirect = () => {
    const currentOrigin = window.location.origin;
    const newPath = '/issues/department-self-created';
    const newUrl = `${currentOrigin}${newPath}`;
    
    window.open(newUrl, '_blank');
}
onMounted(() => {
    //create select boxes
    CreateSelbox()

    search()
})
</script>