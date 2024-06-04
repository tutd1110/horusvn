<template>
    <creat-or-update ref="modalRef" @saved="onSaved"></creat-or-update>
    <info ref="modalInfoRef"></info>
    <description ref="modalDescriptionRef" @saved="onSaved"></description>
    <task-timing ref="modalTaskTimingRef" @saved="onSavedTaskTiming"></task-timing>
    <deadline-mod-form ref="modalDeadlineModFormRef"></deadline-mod-form>
    <div id="filter-block">
        <el-row>
            <el-col :span="12" >
                <el-button
                    type="primary"
                    v-on:click="quickAddButton()"
                >Tạo nhanh
                </el-button>
                <el-button 
                    type="primary" 
                    v-on:click="showRegisterModal()">Tạo công việc
                </el-button>
            </el-col>
            <el-col :span="12">
                <el-button
                    type="primary"
                    style="float: right;"
                    v-on:click="redirectDepartment"
                >Việc bộ phận
                </el-button>
                <el-button
                    type="primary"
                    v-on:click="showInfoModal()"
                    style="float: right; margin-right: 15px;"
                >Thông tin
                </el-button>
                
            </el-col>
        </el-row>
        <el-row :gutter="10" style="margin-bottom: 0;">
            <el-col :span="2" >
                <label>Thời gian</label>
                <el-select 
                    v-model="formState.option" 
                    class="m-2"
                    style="width:100%;"
                >
                    <el-option
                        v-for="item in options"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-col>
            <el-col :span="2">
                <label>Issue</label>
                <el-select 
                    v-model="formState.issue" 
                    class="m-2"
                    style="width:100%;"
                >
                    <el-option
                    v-for="item in issues"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                    />
                </el-select>
            </el-col>
            <el-col :span="3" v-if="filteredUsers.length > 0">
                <label>Employee</label>
                <el-select
                    v-model="formState.user_id"
                    value-key="id"
                    clearable
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in filteredUsers"
                        :key="item.id"
                        :label="item.fullname"
                        :value="item.id"
                    />
                </el-select>
            </el-col>
            <div class="flex-grow" />
            <el-col :span="3" v-if="isPeriod">
                <label name="name">Khoảng thời gian</label>
                <el-date-picker
                    v-model="datePeriod"
                    type="daterange"
                    start-placeholder="Start date"
                    end-placeholder="End date"
                    :disabled-date="disabledDate"
                    format="DD/MM/YYYY"
                    value-format="YYYY-MM-DD"
                    style="width:100%;"
                />
            </el-col>
            <el-col :span="5">
                <label>Tên công việc</label>
                <el-autocomplete
                    v-model="formState.name"
                    clearable
                    :fetch-suggestions="searchSuggestionList"
                    @select="onSelectSuggestion"
                    style="width: 100%"
                />
            </el-col>
            <el-col :span="3">
                <label>Dự án</label>
                <el-form-item >
                    <el-select
                        v-model="formState.project_id"
                        multiple
                        filterable
                        collapse-tags
                        style="width: 100%"
                    >
                        <el-option
                            v-for="item in projects"
                            :key="item.id"
                            :label="item.name"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
            </el-col>
            <el-col :span="2">
                <label>Trạng thái</label>
                <el-form-item>
                    <el-select 
                        v-model="formState.status" 
                        clearable
                        class="m-2" 
                        placeholder="Trạng thái" 
                        style="width:100%;"
                        multiple
                        collapse-tags
                        collapse-tags-tooltip
                        :max-collapse-tags="0"
                    >
                        <el-option
                            v-for="item in status"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                            :class="['task-status-' + item.value]"
                        />
                    </el-select>
                </el-form-item>
            </el-col>
            <!-- <el-col :span="2">
                <label name="fullname">Tên</label>
                <el-select
                    v-model="formState.user_id"
                    value-key="id"
                    placeholder="Employees"
                    clearable
                    filterable
                    multiple
                    collapse-tags
                    collapse-tags-tooltip
                    :max-collapse-tags="0"
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in filteredUsers"
                        :key="item.id"
                        :label="item.fullname"
                        :value="item.id"
                    />
                </el-select>
            </el-col> -->
            <el-col :span="2">
                <label>Deadline</label>
                <el-form-item>
                    <el-select 
                        v-model="formState.overdue" 
                        clearable
                        class="m-2" 
                        placeholder="Select" 
                        style="width:100%;"
                    >
                        <el-option
                            v-for="item in overdue"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
            </el-col>
            <el-col :span="2" style="padding-top: 21px">
                <el-button v-on:click="search()" type="primary" style="padding: 0 30px; float: right;">Tìm kiếm</el-button>
            </el-col>
        </el-row>
    </div>
    <!-- table from here -->
    <div class="table-task">
        <el-row :gutter="2" class='table-task-header'>
            <el-col :span="1"  class="custom-col-checkbox">
                <el-card class="box-card" shadow="hover" style="line-height: normal;">
                    <el-popconfirm
                        v-if="checkedIds.length > 0"
                        width="400"
                        confirm-button-text="OK"
                        cancel-button-text="No, Thanks"
                        :icon="InfoFilled"
                        icon-color="#626AEF"
                        title="Are you sure to delete all selected tasks?"
                        @confirm="onClickDeleteSelectedButton()"
                    >
                        <template #reference>
                            <el-icon>
                                <Delete
                                    style="color: red; cursor: pointer"
                                />
                            </el-icon>
                        </template>
                    </el-popconfirm>
                </el-card>
            </el-col>
            <template v-for="(column, idx1) in columns" :key="idx1">
                <el-col :span="column.span" v-if="column.title == 'Name'" class="custom-col-name">
                    <el-card  class="box-card" shadow="hover" >
                        <div class="timesheets-title" v-html="column.title"></div>
                        <div class="item-pin">
                            <el-switch
                                v-if="formState.option != 1"
                                v-model="isPinTask"
                                inline-prompt
                                :active-icon="Paperclip"
                                :inactive-icon="Paperclip"
                                style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949 ; font-size:15px"
                                @change="changeShowPin"
                            />
                        </div>
                    </el-card>
                </el-col>
                <el-col :span="column.span" :class="column.class" v-else-if="column.filter"  @click="onColClick(column.key)">
                    <el-card class="box-card" shadow="hover" style="cursor: pointer;">
                        <div class="timesheets-title" v-html="column.title" style=" margin-right: 10px;"></div>
                        <CaretTop v-if="iconValue === 2 && keyColumnClick == column.key" style="width: 18px; color:#909399" />
                        <CaretBottom v-else-if="iconValue === 3 && keyColumnClick == column.key" style="width: 18px; color:#909399" />
                        <DCaret v-else="iconValue === 1" style="width: 18px; color:#909399" />
                    </el-card>
                </el-col>
                <el-col :span="column.span" :class="column.class" v-else>
                    <el-card  class="box-card" shadow="hover" >
                        <div class="timesheets-title" v-html="column.title"></div>
                    </el-card>
                </el-col>
            </template>
        </el-row>
        <el-scrollbar :height="heightScrollbar" class="custom-scrollbar">
            <template v-for="(record, idx2) in dataSource" :key="idx2">
                <el-row :gutter="2" :class="'table-task-body ' + getElRowStyle(record)">
                    <el-col :span="1" class="custom-col-checkbox">
                        <el-card class="box-card" shadow="hover">
                            <el-checkbox
                                class="checkbox-custom-size"
                                :id="String(record.id)"
                                :disabled="userSession?.id!=record.user_id"
                                :true-label="record.id"
                                @change="toggleCheck(record.id)"
                            />
                        </el-card>
                    </el-col>
                    <el-col :span="1" class="">
                        <el-card class="box-card" shadow="hover">
                            <div class="table-text">
                            {{ record.id }}
                        </div>
                        </el-card>
                    </el-col>
                    <el-col :span="6">
                        <el-card class="box-card name-task" shadow="hover">
                            <el-row style="margin-bottom: 0; width: 100%; align-items: center;">
                                <el-col :span="21">
                                    <el-input
                                        v-model="record.name"
                                        autosize
                                        type="textarea"
                                        placeholder="Tên công việc"
                                        @change="onChangeName(record.id, $event)"
                                        class="none-border"
                                        :input-style="inputStyleName(record.user_id)"
                                        :readonly="isReadonly(record.user_id)"
                                    />
                                    <div class="link-task" style="padding: 0 11px;">
                                        <div class="link-task-item"  v-for="(url, index) in record.urls" :key="index">
                                            <a :href="url" target="_blank">Link</a>
                                        </div>
                                    </div>
                                </el-col>
                                <el-col :span="1" style="display: flex;">
                                    <Paperclip
                                        style="width: 18px; cursor: pointer; color:#909399"
                                        :style="getIconStyle(record.is_pinned)"
                                        v-on:click="togglePin(record.id, record.is_pinned ? 0 : 1)"
                                    />
                                </el-col>
                                <el-col :span="1" style="display: flex;">
                                    <InfoFilled
                                        style="width: 18px; cursor: pointer; color:#909399"
                                        v-on:click="showEditDescriptionModal(record.id, record.user_id)"
                                        :style="record.description && record.description !== '<p><br></p>' ? 'color: green' : ''"
                                    />
                                </el-col>
                                <el-col :span="1" style="display: flex;">
                                    <el-icon v-if="record.favorite == 1" style="font-size: 20px; cursor: pointer; color: gold">
                                        <StarFilled
                                            style="font-size: 20px;"
                                            v-on:click="toggleFavorite(record.id, 0)"
                                        />
                                    </el-icon>
                                    <el-icon v-else style="font-size: 20px; cursor: pointer; color:#909399">
                                        <Star
                                            v-on:click="toggleFavorite(record.id, 1)"
                                        />
                                    </el-icon>
                                </el-col>
                                <el-col :span="24" v-if="record.overdue_task > 0 || record.none_overdue_task > 0">
                                    <span style="color: #ff4949; font-size: 12px; padding: 0 10px;">Đã quá hạn: {{ record.overdue_task - record.none_overdue_task }} lần</span>
                                    <span style="color: #13ce66; font-size: 12px; padding: 0 10px;">Dời deadline: {{ record.none_overdue_task }} lần</span>
                                </el-col>
                            </el-row>
                        </el-card>
                    </el-col>
                    <el-col :span="2">
                        <el-card class="box-card" shadow="hover">
                            <el-select
                                v-model="record.sticker_id"
                                filterable
                                clearable
                                :disabled="userSession?.id!=record.user_id"
                                @change="onChangeSticker(record.id, $event)"
                                class="none-border"
                            >
                                <el-option
                                    v-for="item in stickers"
                                    :key="item.id"
                                    :label="item.name"
                                    :value="item.id"
                                />
                            </el-select>
                        </el-card>
                    </el-col>
                    <el-col :span="4" class="custom-col-project custom-col-project-body">
                        <el-card class="box-card" shadow="hover">
                            <el-select
                                v-model="record.project_id"
                                multiple
                                filterable
                                clearable
                                :disabled="userSession?.id!=record.user_id"
                                @change="onChangeProject(record.id, $event)"
                                class="none-border"
                                style="width:100%;"
                            >
                                <el-option
                                    v-for="item in projects"
                                    :key="item.id"
                                    :label="item.name"
                                    :value="item.id"
                                />
                            </el-select>
                        </el-card>
                    </el-col>
                    <el-col :span="3" >
                        <el-card class="box-card" shadow="hover" v-on:click="showEditTaskTimingModal(record.id, record.department_id, record.user_id)" style="cursor: pointer;">
                            <el-icon style="font-size: 19px;">
                                <Timer
                                    v-on:click="showEditTaskTimingModal(record.id, record.department_id, record.user_id)"
                                    :style="getStyleTime(record)"
                                />
                            </el-icon>
                            <div class="table-text" style="margin-left: 15px;">
                                {{ record.start_time }} - {{ record.end_time }}
                            </div>
                        </el-card>
                    </el-col>
                    <el-col :span="2" class="custom-col-deadline">
                        <el-card class="box-card" shadow="hover" v-on:click="showEditModal(record.id, record.updated_at)" style="cursor: pointer;">
                            <el-icon style="font-size: 19px;">
                                <Timer
                                    v-on:click="showEditModal(record.id, record.updated_at)"
                                    :style="getStyleDeadline(record)"
                                />
                            </el-icon>
                            <div class="table-text" style="margin-left: 15px; min-width: 70px">
                                {{ formatDeadline(record.deadline) }}
                            </div>
                        </el-card>
                    </el-col>
                    <el-col :span="1">
                        <el-card class="box-card" shadow="hover">
                            <div class="table-text">
                                {{ record.total_estimate_time }}
                            </div>
                        </el-card>
                    </el-col>
                    <el-col :span="1">
                        <el-card class="box-card" shadow="hover">
                            <div class="table-text">
                                {{ record.total_time_spent }}
                            </div>
                        </el-card>
                    </el-col>
                    <el-col :span="1" >
                        <el-card class="box-card" shadow="hover">
                            <el-input
                                v-model="record.progress"
                                type="number"
                                @change="onChangeProgress(record.id, $event)"
                                class="none-border"
                                :readonly="isReadonly(record.user_id)"
                            />
                        </el-card>
                    </el-col>
                    <el-col :span="2" class="custom-col-status">
                        <el-card class="box-card" shadow="hover">
                            <el-select 
                                v-model="record.status" 
                                class="none-border" 
                                placeholder="Select"
                                :disabled="userSession?.id!=record.user_id || record.status === 9"
                                style="width:100%;"
                                @change="onChangeStatus(record.id, $event)"
                                :class="[record.status ? 'task-status-' + record.status : 'no-select']"
                            >
                                <el-option
                                    v-for="item in status"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value"
                                    :class="['task-status-' + item.value]"
                                    :disabled="item.value === 9"
                                />
                            </el-select>
                        </el-card>
                    </el-col>
                    <el-col :span="1" v-if="userSession?.id===record.user_id">
                        <el-card class="box-card" shadow="hover">
                            <el-icon style="cursor: pointer; color: green"><EditPen v-on:click="showEditModal(record.id, record.updated_at)"/></el-icon>
                            <el-icon style="margin-left: 10px; cursor: pointer; color: blue"><CopyDocument v-on:click="showCopyModal(record.id)"/></el-icon>
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
import { onMounted, computed, ref } from 'vue';
import dayjs from 'dayjs';
import { DCaret, CaretBottom, CaretTop, Paperclip, InfoFilled, Star, StarFilled, Timer, EditPen, CopyDocument, Delete, View, Hide } from '@element-plus/icons-vue'
import CreatOrUpdate from './CreatOrUpdate.vue';
import info from '../../common/info.vue';
import description from '../../common/description.vue';
import TaskTiming from '../../common/TaskTiming.vue';
import DeadlineModForm from '../../task/DeadlineModForm.vue';
import { reloadAfterTaskTimingChanged } from '../../Helper/helpers.js';
import { rangePicker, buildSubmitData } from '../../Helper/range-picker.js';
import { callMessage } from '../../Helper/el-message.js';
import { openLoading, closeLoading } from '../../Helper/el-loading';

import { resizeScreen } from '../../Helper/resize-screen.js';

interface FormState {
    option: number,
    issue?: number,
    user_id?: number,
    name?: string,
    project_id?: number,
    status?: number,
    id_suggest?: number,
    start_time?: string,
    end_time?: string,
    current_page: number,
    per_page: number,
    column?: string | undefined,
    order?: string | undefined,
    is_pin_show: number,
    overdue?: number,
};
interface User {
    id: number,
    fullname?: string,
    position?: number,
    department_id: number
};
interface Option {
    value: number,
    label: string,
};
interface OptionDB {
    id: number,
    name: string,
};
interface Arg {
    task_id: number,
    task_user_id: number,
    task_issue_ids: number
};
interface SuggestionItem {
    value: string
    id_suggest: number
}
interface Item {
    id: number;
    name: string;
    sticker_id: number;
    project_id: string;
    department_id: number;
    start_time: string;
    end_time: string;
    total_estimate_time: number;
    total_time_spent: number;
    user_id: number;
    progress: number;
    description: string;
    status: number;
    deadline: string | null;
    favorite: number;
    is_pinned: number;
    overdue_task: number;
    none_overdue_task: number;
}
interface TransformedItem extends Omit<Item, 'project_id'> {
    project_id: number[];
    urls: string[];
}

const options = ref([
    { label: "Tất cả", value: 1 },
    { label: "Việc quan trọng", value: 6 },
    { label: "Việc ghim", value: 7 },
    { label: "Việc hôm nay", value: 2 },
    { label: "Việc hôm qua",value: 3 },
    { label: "Việc tuần này",value: 4 },
    { label: "Việc tuần trước",value: 5 },
    { label: "Việc tháng này",value: 8 },            
    { label: "Việc tháng trước",value: 9 }              
]);
const overdue = ref([
    // { label: "Chưa quá hạn", value: 0 },
    { label: "Đã quá hạn", value: 1 },          
]);
const userSession = ref<User>();
const isPinTask = ref(true)
const total = ref(0);
const checkedIds = ref<number[]>([]);
const errorMessages = ref('');
const modalRef = ref();
const modalDescriptionRef = ref();
const modalInfoRef = ref();
const users = ref<User[]>([]);
const issues = ref<Option[]>([]);
const stickers = ref<OptionDB[]>([]);
const projects = ref<OptionDB[]>([]);
const formState = ref<FormState>({
    option: 2,
    current_page: 1,
    per_page: 30,
    is_pin_show: 1
});
const heightScrollbar = ref();
// Computed property for computed formState
const computedFormState = computed<FormState>(() => {
    const newFormState: FormState = {
        ...formState.value,
    };

    if (datePeriod.value && datePeriod.value.length === 2) {
        newFormState.start_time = datePeriod.value[0];
        newFormState.end_time = datePeriod.value[1];
    } else {
        newFormState.start_time = undefined;
        newFormState.end_time = undefined;
    }

    return newFormState;
});
const filteredUsers = computed(() => {
    return (
        (userSession.value?.position === 1 && userSession.value?.department_id !== undefined)
        ? users.value.filter(user => user.department_id === userSession.value?.department_id)
        : (userSession.value?.id === 107 || userSession.value?.id === 51)
        ? users.value
        : []
    );
});
const datePeriod = ref<string[]>([]);
const status = ref<Option[]>([]);
const isPeriod = computed(() => {
    const option = formState.value?.option;
  
    if (![1, 4, 5].includes(option)) {
        formState.value.start_time = undefined;
        formState.value.end_time = undefined;
        datePeriod.value = [];
    }

    return [1, 4, 5].includes(option);
});
const disabledDate = (current: Date) => {
    return rangePicker(dayjs(current), computedFormState.value.option);
};
const suggestionList = ref<SuggestionItem[]>([])
const searchSuggestionList = (queryString: string, cb: any) => {
    const results = queryString
        ? suggestionList.value.filter(createFilter(queryString))
        : suggestionList.value
    // call callback function to return suggestions
    cb(results)
}
const createFilter = (queryString: string) => {
    return (suggestion: SuggestionItem) => {
        return (
            suggestion.value.toLowerCase().indexOf(queryString.toLowerCase()) === 0
        )
    }
}
const onSelectSuggestion = (item: SuggestionItem) => {
    formState.value.id_suggest = item.id_suggest
}
const quickAddButton = () => {
    let submitData = {
        project_ids: computedFormState.value.project_id,
        name: computedFormState.value.name,
        // status: computedFormState.value.status,
    };

    buildSubmitData(submitData, computedFormState, datePeriod)

    axios.post('/api/me/task/store', submitData)
    .then(response => {
        search()
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const showRegisterModal = () => {
    modalRef.value.ShowWithAddMode();
}
const showInfoModal = () => {
    modalInfoRef.value.ShowWithInfoMode(computedFormState.value, 'me');
}
const showEditModal = (id: number, updated_at: string) => {
    modalRef.value.ShowWithUpdateMode(id, updated_at);
}
const showCopyModal = (id: number) => {
    modalRef.value.ShowWithCopyMode(id);
}
const showEditDescriptionModal = (id: number, user_id: number) => {
    let is_readonly = false
    if (userSession?.value?.id != user_id) {
        is_readonly = true
    }
    modalDescriptionRef.value.ShowWithDescriptionMode(id, is_readonly);
}
const columns = ref([
    { title: "Id", key: "id", span: 1, class: "" },
    { title: "Name", key: "name", span: 6, class: "" },
    { title: "Type", key: "sticker_id", span: 2, class: "" },
    { title: "Project", key: "project_id", span: 4, class: "custom-col-project" },
    { title: "Date", key: "work_date", span: 3, filter: true, class: "" },
    { title: "Deadline", key: "deadline", span: 2, filter: true, class: "custom-col-deadline" },
    { title: "Estd", key: "total_estimate_time", span: 1, class: "" },
    { title: "Actual", key: "total_time_spent", span: 1, class: "" },
    { title: "Progress", key: "progress", span: 1, class: "" },
    { title: "Status", key: "status", span: 2, filter: true, class: "custom-col-status" },
    { title: "Action", key: "action", span: 1, class: "" },
])
const iconValue = ref(1);
const keyColumnClick = ref();
const onColClick = (keyColumn: string) => {
    keyColumnClick.value = keyColumn
    iconValue.value = iconValue.value ? (iconValue.value === 1 ? 2 : (iconValue.value === 2 ? 3 : 1)) : 1;
    sortData();
};
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
};
const onAction = ref(false)
const dataSource = ref()
const onChangePage = (page: number) => {
    formState.value.current_page = page

    search()
}
const search = () => {
    //Detect user actions
    onAction.value = true;

    //Reset the checkedIds
    checkedIds.value = []

    let id_suggest = null
    if (computedFormState.value.id_suggest && computedFormState.value.name) {
        const option = suggestionList.value.findIndex(
            (item) => item.id_suggest === computedFormState.value.id_suggest && item.value === computedFormState.value.name
        );

        if (option !== -1) {
            id_suggest = suggestionList.value[option].id_suggest;
        }
    }
    openLoading('custom-scrollbar'); // Open the loading indicator before loading data
    axios.get('/api/me/task/get_task_list', {
        params:{
            option: computedFormState.value.option,
            project_id: computedFormState.value.project_id,
            start_time: computedFormState.value.start_time,
            end_time: computedFormState.value.end_time,
            name: computedFormState.value.name ? encodeURIComponent(computedFormState.value.name): undefined,
            status: computedFormState.value.status,
            issue: computedFormState.value.issue,
            user_id: computedFormState.value.user_id,
            id_suggest: id_suggest ? id_suggest : undefined,
            current_page: computedFormState.value.current_page,
            per_page: computedFormState.value.per_page,
            column: computedFormState.value.column,
            order: computedFormState.value.order,
            is_pin_show: computedFormState.value.is_pin_show,
            overdue: computedFormState.value.overdue,
        }
    })
    .then(response => {
        formState.value.current_page = response.data.currentPage
        dataSource.value = transferData(response.data.items);
        total.value = response.data.totalItems

        closeLoading(); // Close the loading indicator
    })
    .catch((error) => {
        closeLoading(); // Close the loading indicator
        errorMessages.value = error.response.data.errors;//put message content in ref
        //When search target data does not exist
        dataSource.value = []; //dataSource empty
        callMessage(errorMessages.value, 'error');
    });
}
const transferData = (data: Item[]): TransformedItem[] => {
    return data.map(item => {
        const urls = item.description !== '<p><br></p>' ? splitTextAndUrls(item.description) : [];
        const project_id = parseProjectIds(item.project_id);
        
        const transformedItem: TransformedItem = {
            id: item.id,
            name: item.name,
            sticker_id: item.sticker_id,
            project_id,
            department_id: item.department_id,
            start_time: item.start_time,
            end_time: item.end_time,
            total_estimate_time: roundFloat(item.total_estimate_time),
            total_time_spent: roundFloat(item.total_time_spent),
            user_id: item.user_id,
            progress: item.progress,
            description: item.description,
            status: item.status,
            deadline: item.deadline,
            favorite: item.favorite,
            is_pinned: item.is_pinned,
            urls: urls,
            overdue_task: item.overdue_task,
            none_overdue_task: item.none_overdue_task,
        };

        return transformedItem;
    });
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
const parseProjectIds = (project_id: string): number[] => {
    if (!project_id) {
        return [];
    }

    const projectIdCleaned = project_id.trim().replace(/(^\{|\}$)/g, '');
    return projectIdCleaned.split(',').map(id => parseInt(id.trim(), 10));
}
const roundFloat = (num: number) => {
    let power = 10;
    let decimals = 1;

    return Math.round(num * Math.pow(power, decimals)) / Math.pow(power, decimals);
}
const inputStyleName = (user_id: number) => {
    return userSession?.value?.id !== user_id ? 'color: red' : '';
}
const getStyleTime = (record: Item) => {
    return {
        cursor: 'pointer',
        color: record.total_time_spent > 0 || record.total_estimate_time > 0 ? 'green' : '',
    };
}
const getStyleDeadline = (record: Item) => {
    return {
        cursor: 'pointer',
        color: record.deadline ? 'green' : '',
    };
}
const formatDeadline = (deadline: string | null) => {
    return deadline ? dayjs(deadline).format('DD/MM/YYYY') : null;
};
const isReadonly = (user_id: number) => {
    return userSession?.value?.id !== user_id;
}
const getIconStyle = (isPinned: number) => {
    return {
        cursor: 'pointer',
        color: isPinned ? 'green' : 'black',
    };
}
const getElRowStyle = (record: Item) => {
    const deadline = dayjs(record.deadline, "YYYY-MM-DD");
    const end_time = dayjs(record.end_time, "DD/MM/YYYY");

    if (end_time.isAfter(deadline) || record.status === 0 || (record.overdue_task - record.none_overdue_task) > 0) {
        return "is-red";
    } else if (deadline.isBefore(dayjs(), "day") && record.status == 2) {
        return "is-warning";
    }

    return "";
}
const togglePin = (task_id: number, is_pinned: number) => {
    const index = dataSource.value.findIndex((item: Item) => item.id === task_id);
    if (index !== -1) {
        dataSource.value[index].is_pinned = is_pinned;
    }

    update(task_id, 'is_pinned', String(is_pinned))
}
const toggleFavorite = (id: number, value: number) => {
    const index = dataSource.value.findIndex((item: Item) => item.id === id);
    if (index !== -1) {
        dataSource.value[index].favorite = value;
    }

    update(id, 'favorite', value)
}
const toggleCheck = (id: number) => {
    if (checkedIds.value.includes(id)) {
        checkedIds.value = checkedIds.value.filter((checkedId) => checkedId !== id);
    } else {
        checkedIds.value.push(id);
    }
}
const onChangeName = (id: number, value: string | number) => {
    update(id, 'name', value)
}
const onChangeSticker = (id: number, value: number) => {
    update(id, 'sticker_id', value)
}
const onChangeProject = (id: number, value: number[]) => {
    let project_ids = value ? value : []

    update(id, 'project_ids', project_ids)
}
const onChangeProgress = (id: number, value: number) => {
    update(id, 'progress', value)
}
const onChangeStatus = (id: number, value: number) => {
    update(id, 'status', value)
}
const modalDeadlineModFormRef = ref();
const update = (id: number, column: string, value: string | number | number[]) => {
    let submitData = {
        id: id,
        [column]: value ? value : ""
    }

    axios.patch('/api/me/task/quick_update', submitData)
    .then(response => {
        if (column == 'deadline' && response.data.warning) {
            modalDeadlineModFormRef.value.ShowWithAddMode(id, value);
        }

        // Success
        callMessage(response.data.success, 'success');
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;//put message content in ref
        callMessage(errorMessages.value, 'error');
    })
}
const onClickDeleteSelectedButton = () => {
    if (checkedIds.value.length > 0) {
        axios.post('/api/me/task/delete_multiple', {ids: checkedIds.value})
        .then(response => {
            checkedIds.value = []

            _fetch()
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error');

            _fetch()
        })
    }
}
const modalTaskTimingRef = ref();
const showEditTaskTimingModal = (id: number, department_id: number, task_user_id: number) => {
    modalTaskTimingRef.value.ShowWithTaskTimingMode(id, 'tasks', department_id, task_user_id);
}
const onSaved = () => {
    _fetch();
}
const onSavedTaskTiming = (args: Arg) => {
    reloadAfterTaskTimingChanged(dataSource.value, args, formState.value)
}
const redirectDepartment = () => {
    const newTab = window.open('/department/tasks', '_blank');

    newTab?.focus();
}

const changeShowPin = () => {
    formState.value.is_pin_show = (isPinTask.value == true)? 1 : 0
    localStorage.setItem('is_pin_show_' + userSession.value?.id, formState.value.is_pin_show.toString());

    _fetch();
}
const getAllCookie = () => {
    const isPinShowFromCookie = localStorage.getItem('is_pin_show_' + userSession.value?.id);
    
    if (isPinShowFromCookie !== null) {
        formState.value.is_pin_show = parseInt(isPinShowFromCookie);
        isPinTask.value = (isPinShowFromCookie == '1') ? true : false
    }
}

const _fetch = () => {
    let objParam: FormState = {
        option: formState.value.option,
        current_page: computedFormState.value.current_page,
        per_page: computedFormState.value.per_page,
        is_pin_show: computedFormState.value.is_pin_show,
    };
    
    if (onAction.value) {
        objParam = {
            option: formState.value.option,
            project_id: formState.value.project_id,
            start_time: computedFormState.value.start_time,
            end_time: computedFormState.value.end_time,
            name: formState.value.name ? encodeURIComponent(formState.value.name): "",
            status: formState.value.status,
            issue: formState.value.issue,
            current_page: computedFormState.value.current_page,
            per_page: computedFormState.value.per_page,
            is_pin_show: computedFormState.value.is_pin_show,
        };
    }
    openLoading('custom-scrollbar'); // Open the loading indicator before loading data
    axios.get('/api/me/task/get_task_list', {
        params:objParam
    })
    .then(response => {
        formState.value.current_page = response.data.currentPage
        dataSource.value = transferData(response.data.items);
        total.value = response.data.totalItems
        closeLoading(); // Close the loading indicator
        console.log(111);
        
        setTimeout(() => { heightScrollbar.value = resizeScreen() }, 0);
    })
    .catch((error) => {
        closeLoading(); // Close the loading indicator
        //When search target data does not exist
        dataSource.value = []; //dataSource empty
        errorMessages.value = error.response.data.errors;//put message content in ref
        callMessage(errorMessages.value, 'error');
    });
};
onMounted(() => {
    axios.get('/api/common/get_employees')
    .then(response => {
        users.value = response.data
    })
    axios.get('/api/me/task/get_selectboxes')
    .then(response => {
        projects.value = response.data.projects;
        status.value = response.data.status;
        stickers.value = response.data.stickers;
        issues.value = response.data.task_timing_type;
        userSession.value = response.data.user_session;
        suggestionList.value = response.data.suggestion_list;

        getAllCookie()

        _fetch()
    })
});
</script>