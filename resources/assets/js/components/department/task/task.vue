<template>
    <creat-or-update ref="modalRef" @saved="onSaved"></creat-or-update>
    <info ref="modalInfoRef"></info>
    <description ref="modalDescriptionRef" @saved="onSaved"></description>
    <task-timing ref="modalTaskTimingRef" @saved="onSavedTaskTiming"></task-timing>
    <task-project ref="modalTaskProjectRef" @saved="onSavedTaskProject"></task-project>
    <div id="filter-block">
        <el-row >
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
                <el-button type="danger" v-on:click="onBuzz()" v-if="session && [51, 107, 161, 63].includes(session.id)">Buzz</el-button>
            </el-col>
            <el-col :span="12">
                <el-button
                    type="primary"
                    tag="a"
                    style="float: right;"
                    href="/me/tasks"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Việc của tôi
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
            <el-col :span="1">
                <label>Loại CV</label>
                <el-form-item>
                    <el-select 
                        v-model="formState.mode" 
                        class="m-2" 
                        placeholder="Select" 
                        style="width:100%;"
                    >
                        <el-option
                            v-for="item in modes"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
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
            <el-col :span="3">
                <label name="name">Tên công việc</label>
                <el-input
                    v-model="formState.name"
                    type="input"
                    clearable
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
            <el-col :span="2" v-if="session?.is_manager">
                <label name="department">Bộ phận</label>
                <el-select
                    v-model="formState.department_id"
                    value-key="id"
                    placeholder="Department"
                    clearable
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
            </el-col>
            <el-col :span="1">
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
            <el-col :span="2">
                    <label>Trạng thái</label>
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
            </el-col>
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
            <el-col :span="2" style=" margin-bottom: 20px;" :style="{ paddingTop: isPeriod ? '0px' : '21px' }">
                <el-button v-on:click="search()" type="primary" style="">Tìm kiếm</el-button>
            </el-col>
        </el-row>
    </div>
    <!-- table from here -->
    <div class="table-task">
        <el-row :gutter="2" class='table-task-header'>
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
                    <el-col :span="1">
                        <el-card class="box-card" shadow="hover">
                            <div class="table-text">
                                {{ record.id }}
                            </div>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('name')">
                        <el-card class="box-card name-task" shadow="hover">
                            <el-row style="margin-bottom: 0; width: 100%; align-items: center;">
                                <el-col :span="record.task_parent ? 20 : 21">
                                    <el-input
                                        v-model="record.name"
                                        :readonly="!getEditable(record)"
                                        autosize
                                        type="textarea"
                                        placeholder="Tên công việc"
                                        @change="onChangeSelect(record, 'name', $event)"
                                        class="none-border"
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
                                        v-on:click="togglePin(record, record.is_pinned ? 0 : 1)"
                                    />
                                </el-col>
                                <el-col :span="1" style="display: flex; margin-left: 5px;">
                                    <InfoFilled
                                        style="width: 18px; cursor: pointer; color:#909399"
                                        v-on:click="showEditDescriptionModal(record)"
                                        :style="record.description && record.description !== '<p><br></p>' ? 'color: green' : ''"
                                    />
                                </el-col>
                                <el-col :span="1" style="display: flex; margin-left: 5px;" v-if="record.task_parent">
                                    <Connection
                                        style="width: 18px; color:blue"
                                    />
                                </el-col>
                                <el-col :span="24" v-if="record.overdue_task > 0 || record.none_overdue_task > 0">
                                    <span style="color: #ff4949; font-size: 12px; padding: 0 10px;">Đã quá hạn: {{ record.overdue_task - record.none_overdue_task }} lần</span>
                                    <span style="color: #13ce66; font-size: 12px; padding: 0 10px;">Dời deadline: {{ record.none_overdue_task }} lần</span>
                                </el-col>
                            </el-row>
                        </el-card>
                    </el-col>
                    <!-- <el-col :span="getColSpan('department_id')">
                        <el-card class="box-card" shadow="hover" style="text-align: center;">
                            <el-select
                                v-model="record.department_id"
                                filterable
                                placeholder=""
                                class="none-border"
                                :disabled="!getEditable(record)"
                                clearable
                                style="width:100%;"
                                @change="onChangeSelect(record, 'department_id', $event)"
                            >
                                <el-option
                                    v-for="item in departments"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value"
                                />
                            </el-select>
                        </el-card>
                    </el-col> -->
                    <el-col :span="getColSpan('project_id')" class="custom-col-project-department custom-col-project-department-body">
                        <el-card class="box-card" shadow="hover">
                            <el-col :span="22">
                                <el-select
                                    v-model="record.project_id"
                                    multiple
                                    filterable
                                    placeholder=""
                                    @change="onChangeSelect(record, 'project_ids', $event)"
                                    :disabled="!getEditable(record)"
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
                            </el-col>
                            <el-col :span="2" style="display: flex;">
                                <InfoFilled
                                    style="width: 16px; cursor: pointer; color:#909399"
                                    v-on:click="showEditTaskProjectsModal(record)"
                                />
                            </el-col>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('sticker_id')">
                        <el-card class="box-card" shadow="hover" style="text-align: center;">
                            <el-select
                                v-model="record.sticker_id"
                                filterable
                                placeholder=""
                                class="none-border"
                                :disabled="!getEditable(record) && !session?.add_permission"
                                clearable
                                style="width:100%;"
                                @change="onChangeSelect(record, 'sticker_id', $event)"
                                @visible-change="onVisibleChangeType(record, $event)"
                            >
                                <el-option
                                    v-for="item in stickersCache[record.department_id] || stickersCache['initial']"
                                    :key="item.id"
                                    :label="item.name"
                                    :value="item.id"
                                />
                            </el-select>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('priority')">
                        <el-card class="box-card" shadow="hover" style="text-align: center;">
                            <el-select
                                v-model="record.priority"
                                :disabled="!getEditable(record) && !session?.add_permission"
                                filterable
                                placeholder=""
                                class="none-border"
                                clearable
                                style="width:100%;"
                                @change="onChangeSelect(record, 'priority', $event)"
                            >
                                <el-option
                                    v-for="item in priorities"
                                    :key="item.id"
                                    :label="item.label"
                                    :value="item.id"
                                />
                            </el-select>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('weight')">
                        <el-card class="box-card" shadow="hover">
                            <el-input
                                v-model="record.weight"
                                :readonly="!getEditable(record) && !session?.add_permission"
                                type="number"
                                @change="onChangeSelect(record, 'weight', $event)"
                                class="none-border"
                                :disabled="!getEditable(record) && !session?.add_permission"
                            />
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('work_date')">
                        <el-card class="box-card" shadow="hover" v-on:click="showEditTaskTimingModal(record)" style="cursor: pointer;">
                            <el-icon style="font-size: 16px;">
                                <Timer
                                    v-on:click="showEditTaskTimingModal(record)"
                                    :style="getStyleTime(record)"
                                />
                            </el-icon>
                            <div class="table-text" style="margin-left: 10px;">
                                {{ record.start_time }} - {{ record.end_time }}
                            </div>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('deadline')" class="custom-col-deadline"  v-on:click="showEditModal(record)" style="cursor: pointer;">
                        <el-card class="box-card" shadow="hover">
                            <el-icon style="font-size: 16px;">
                                <Timer
                                    v-on:click="showEditModal(record)"
                                    :style="getStyleDeadline(record)"
                                />
                            </el-icon>
                            <div class="table-text" style="margin-left: 15px; min-width: 70px">
                                {{ formatDeadline(record.deadline) }}
                            </div>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('total_estimate_time')">
                        <el-card class="box-card" shadow="hover">
                            <div class="table-text">
                                {{ record.total_estimate_time }}
                            </div>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('total_time_spent')">
                        <el-card class="box-card" shadow="hover">
                            <div class="table-text">
                                {{ record.total_time_spent }}
                            </div>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('user_id')">
                        <el-card class="box-card" shadow="hover" style="text-align: center;">
                            <el-select
                                v-model="record.user_id"
                                :disabled="!getEditable(record)"
                                filterable
                                clearable
                                class="none-border"
                                style="width:100%;"
                                @change="onChangeSelect(record, 'user_id', $event)"
                                @visible-change="onVisibleChangeUser(record, $event)"
                            >
                                <el-option
                                    v-for="item in usersCache[record.department_id] || usersCache['initial']"
                                    :key="item.id"
                                    :label="item.fullname"
                                    :value="item.id"
                                />
                            </el-select>
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('progress')">
                        <el-card class="box-card" shadow="hover">
                            <el-input
                                v-model="record.progress"
                                :readonly="!getEditable(record)"
                                type="number"
                                @change="onChangeSelect(record, 'progress', $event)"
                                class="none-border"
                            />
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('quality')">
                        <el-card class="box-card" shadow="hover">
                            <el-input
                                v-model="record.quality"
                                :readonly="!getEditable(record) && !session?.add_permission"
                                type="number"
                                @change="onChangeSelect(record, 'quality', $event)"
                                class="none-border"
                            />
                        </el-card>
                    </el-col>
                    <el-col :span="getColSpan('status')" class="custom-col-status">
                        <el-card class="box-card" shadow="hover">
                            <el-select 
                                v-model="record.status"
                                :disabled="!getEditable(record) || record.status === 9" 
                                class="none-border" 
                                placeholder="Select"
                                style="width:100%;"
                                @change="onChangeSelect(record, 'status', $event)"
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
                    <el-col :span="getColSpan('action')">
                        <el-card class="box-card" shadow="hover">
                            <el-icon style="cursor: pointer; color: green"><EditPen v-on:click="showEditModal(record)"/></el-icon>
                            <el-icon style="margin-left: 10px; cursor: pointer; color: blue"><CopyDocument v-on:click="showCopyModal(record)"/></el-icon>
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
import { DCaret, CaretBottom, CaretTop, Paperclip, InfoFilled, Connection, Star, StarFilled, Timer, EditPen, CopyDocument, Delete, View, Hide } from '@element-plus/icons-vue'
import CreatOrUpdate from './CreatOrUpdate.vue';
import info from '../../common/info.vue';
import description from '../../common/description.vue';
import TaskProject from '../../common/TaskProject.vue';
import TaskTiming from '../../common/TaskTiming.vue';
import dayjs from 'dayjs';
import {
    reloadAfterTaskTimingChanged,
    reloadAfterTaskProjectChanged,
    onCommonChangeSticker,
    onCommonChangePriority
} from '../../Helper/helpers.js';
import { rangePicker, buildSubmitData } from '../../Helper/range-picker.js';
import { callMessage } from '../../Helper/el-message.js';
import { openLoading, closeLoading } from '../../Helper/el-loading';

import { resizeScreen } from '../../Helper/resize-screen.js';

interface FormState {
    option: number,
    issue?: number,
    user_id?: number,
    department_id?: number,
    name?: string,
    project_id?: number,
    status?: number,
    weighted?: number,
    start_time?: string,
    end_time?: string,
    current_page: number,
    per_page: number,
    column?: string | undefined,
    order?: string | undefined,
    is_pin_show: number,
    mode?: string,
    overdue?: number,
};
interface Item {
    id: number,
    name: string,
    project_id: string,
    sticker_id: number,
    task_parent: number,
    priority: number,
    start_time: string,
    end_time: string,
    total_estimate_time: number,
    total_time_spent: number,
    weight: number,
    department_id: number,
    description: string,
    user_id: number,
    progress: number,
    status: number,
    deadline: string,
    is_pinned: boolean,
    updated_at: string,
    overdue_task: number;
    none_overdue_task: number;
    quality: number;
}
interface Option {
    value: number,
    label: string,
};
interface OptionDB {
    id: number,
    name: string,
};
interface User {
    id: number,
    fullname: string,
    department_id: number
};
interface Session {
    id: number,
    department_id: number,
    is_authority: boolean,
    is_manager: boolean,
    add_permission: boolean
};
interface TransformedItem extends Omit<Item, 'project_id'> {
    project_id: number[];
    urls: string[];
}

const errorMessages = ref('')
const datePeriod = ref<string[]>([])
const formState = ref<FormState>({
    option: 2,
    current_page: 1,
    per_page: 30,
    is_pin_show: 1,
    mode: 'task'
});
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
const modes = ref([
    { label: "Task", value: 'task' },
    { label: "Bug", value: 'bug' },           
]);
const overdue = ref([
    // { label: "Chưa quá hạn", value: 0 },
    { label: "Đã quá hạn", value: 1 },          
    { label: "Chưa nhập deadline", value: 2 },          
]);
const issues = ref<Option[]>([]);
const status = ref<Option[]>([]);
const heightScrollbar = ref();
const departments = ref<Option[]>([]);
const total = ref(0);
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
const projects = ref<OptionDB[]>([]);
const users = ref<Array<User>>([]);
const filteredUsers = computed(() => {
    const selectedDepartmentId = formState.value.department_id;
    if (selectedDepartmentId) {
        const cacheKey = selectedDepartmentId;
        const filtered = users.value.filter(user => user.department_id === selectedDepartmentId);

        if (!stickersCache.value[cacheKey]) {
            stickersCache.value[cacheKey] = stickers.value.filter(sticker => sticker.department_id === selectedDepartmentId);
        }
        if (!usersCache.value[cacheKey]) {
            usersCache.value[cacheKey] = filtered;
        }

        return filtered;
    } else {
        return users.value.filter(user => user.department_id === session.value?.department_id);
    }
});
const columns = ref([
    { title: "Id", key: "id", span: 1, class: "" },
    { title: "Name", key: "name", span: 4, class: "" },
    // { title: "Department", key: "department_id", span: 2, class: "" },
    { title: "Project", key: "project_id", span: 2, class: "custom-col-project-department" },
    { title: "Type", key: "sticker_id", span: 2, class: "" },
    { title: "Level", key: "property", span: 1, class: "" },
    { title: "Weight", key: "weight", span: 1, class: "" },
    { title: "Date", key: "work_date", span: 2, filter: true, class: "" },
    { title: "Deadline", key: "deadline", span: 2, filter: true, class: "custom-col-deadline" },
    { title: "Estd", key: "total_estimate_time", span: 1, class: "" },
    { title: "Actual", key: "total_time_spent", span: 1, class: "" },
    { title: "User", key: "user_id", span: 2, class: "" },
    { title: "Progress", key: "progress", span: 1, class: "" },
    { title: "Quality", key: "quality", span: 1, class: "" },
    { title: "Status", key: "status", span: 1, filter: true, class: "custom-col-status" },
    { title: "Action", key: "action", span: 1, class: "" },
])
const getColSpan = (key: string) => {
    const foundColumn = columns.value.find(column => column.key === key);
    return foundColumn ? foundColumn.span : 1; // Default to 1 if not found
}
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
const dataSource = ref()
interface SubmitData {
    id: number;
    [key: string]: number | string;
}
interface ColumnHandlers {
    [key: string]: (
        id: number,
        value: number | string,
        dataSource: Item[],
        stickerSelbox: any,
        prioritySelbox: any
    ) => any; // Adjust the return type as needed
}
const columnHandlers: ColumnHandlers = {
    sticker_id: onCommonChangeSticker,
    priority: onCommonChangePriority,
};
const onChangeSelect = (item: Item, column: string, value: number | string) => {
    let submitData = {
        id: item.id,
        [column]: value ? value : ""
    }

    const handler = columnHandlers[column];
    if (handler) {
        const result = handler(
            item.id, value, dataSource.value, stickers.value, priorities.value
        );
        if (result) {
            submitData = { ...submitData, ...result };
        }
    }

    update(submitData)
}
const update = (submitData: SubmitData) => {
    axios.patch('/api/department/task/quick_update', submitData)
    .then(response => {
        callMessage(response.data.success, 'success');
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const getIconStyle = (isPinned: number) => {
    return {
        cursor: 'pointer',
        color: isPinned ? 'green' : 'black',
    };
}
const togglePin = (task_item: Item, is_pinned: number) => {
    const permission = getEditable(task_item)
    if (!permission) {
        return
    }

    const index = dataSource.value.findIndex((item: Item) => task_item.id === item.id);
    if (index !== -1) {
        dataSource.value[index].is_pinned = is_pinned;
    }

    let submitData = {
        id: task_item.id,
        is_pinned:  String(is_pinned)
    }
    
    update(submitData)
}
const getElRowStyle = (record: Item) => {
    const deadline = dayjs(record.deadline, "YYYY-MM-DD");
    const end_time = dayjs(record.end_time, "DD/MM/YYYY");

    if (end_time.isAfter(deadline) || record.status === 0 || (record.overdue_task - record.none_overdue_task) > 0) {
        return "is-red";
    } else if (deadline.isBefore(dayjs(), "day") && record.status == 2) {
        return "is-warning";
    } else if (record.deadline == null || record.total_estimate_time == 0) {
        return "is-warning-low";
    }

    return "";
}
const quickAddButton = () => {
    let submitData = {
        department_id: computedFormState.value.department_id ? computedFormState.value.department_id : "",
        project_ids: computedFormState.value.project_id,
        // user_id: computedFormState.value.user_id,
        name: computedFormState.value.name,
        // status: computedFormState.value.status,
    };

    buildSubmitData(submitData, computedFormState, datePeriod)

    axios.post('/api/department/task/store', submitData)
    .then(response => {
        search()
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const modalRef = ref()
const modalInfoRef = ref()
const modalDescriptionRef = ref()
const modalTaskProjectRef = ref()
const showRegisterModal = () => {
    modalRef.value.ShowWithAddMode();
}
const showInfoModal = () => {
    modalInfoRef.value.ShowWithInfoMode(computedFormState.value, 'department');
}
const showEditDescriptionModal = (item: Item) => {
    if (session.value?.is_authority) {
        modalDescriptionRef.value.ShowWithDescriptionMode(item.id);
    }
}
const modalTaskTimingRef = ref();
const showEditTaskTimingModal = (item: Item) => {
    if (session.value?.is_authority || session.value?.id === item.user_id) {
        modalTaskTimingRef.value.ShowWithTaskTimingMode(item.id, 'tasks', item.department_id, item.user_id);
    }
}
const showEditModal = (item: Item) => {
    if (session.value?.is_authority || session.value?.id == item.user_id || session.value?.add_permission) {
        modalRef.value.ShowWithUpdateMode(item.id, item.updated_at);
    }
}
const showCopyModal = (item: Item) => {
    const permission = getEditable(item)
    if (!permission) {
        return
    }
    modalRef.value.ShowWithCopyMode(item.id);
}
const showEditTaskProjectsModal = (item: Item) => {
    const permission = getEditable(item)
    if (!permission) {
        return
    }
    modalTaskProjectRef.value.ShowWithTaskProjectMode(item.id, item.department_id);
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
const getStyleTime = (record: Item) => {
    return {
        cursor: 'pointer',
        color: record.total_time_spent > 0 || record.total_estimate_time > 0 ? 'green' : '',
    };
}
const onChangePage = (page: number) => {
    formState.value.current_page = page

    search()
}
const isPinTask = ref(true)
const changeShowPin = () => {
    formState.value.is_pin_show = (isPinTask.value == true)? 1 : 0
    localStorage.setItem('is_pin_show_' + session.value?.id, formState.value.is_pin_show.toString());
    
    _fetch();
}
const getAllCookie = () => {
    const isPinShowFromCookie = localStorage.getItem('is_pin_show_' + session.value?.id);
    
    if (isPinShowFromCookie !== null) {
        formState.value.is_pin_show = parseInt(isPinShowFromCookie);
        isPinTask.value = (isPinShowFromCookie == '1') ? true : false
    }
}
const onAction = ref(false)
const search = () => {
    onAction.value = true;

    _fetch()
}
const _fetch = () => {
    let objParam: FormState = {
        option: formState.value.option,
        current_page: computedFormState.value.current_page,
        per_page: computedFormState.value.per_page,
        is_pin_show: computedFormState.value.is_pin_show,
        mode: computedFormState.value.mode,
    };
    
    if (onAction.value) {
        objParam = {
            option: computedFormState.value.option,
            project_id: computedFormState.value.project_id,
            start_time: computedFormState.value.start_time,
            end_time: computedFormState.value.end_time,
            department_id: computedFormState.value.department_id,
            user_id: computedFormState.value.user_id,
            name: computedFormState.value.name ? encodeURIComponent(computedFormState.value.name): "",
            status: computedFormState.value.status,
            issue: computedFormState.value.issue,
            weighted: computedFormState.value.weighted,
            current_page: computedFormState.value.current_page,
            per_page: computedFormState.value.per_page,
            is_pin_show: computedFormState.value.is_pin_show,
            mode: computedFormState.value.mode,
            overdue: computedFormState.value.overdue,
            column: computedFormState.value.column,
            order: computedFormState.value.order,
        };
    }
    openLoading('custom-scrollbar'); // Open the loading indicator before loading data
    axios.get('/api/department/task/get_task_list', {
        params:objParam
    })
    .then(response => {
        formState.value.current_page = response.data.currentPage
        dataSource.value = transferData(response.data.items);
        total.value = response.data.totalItems
        closeLoading(); // Close the loading indicator
        
        setTimeout(() => { heightScrollbar.value = resizeScreen() }, 0);
    })
    .catch((error) => {
        closeLoading(); // Close the loading indicator
        //When search target data does not exist
        dataSource.value = []; //dataSource empty
        errorMessages.value = error.response.data.errors;//put message content in ref
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
            task_parent: item.task_parent,
            priority: item.priority,
            weight: item.weight,
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
            is_pinned: item.is_pinned,
            urls: urls,
            updated_at: item.updated_at,
            overdue_task: item.overdue_task,
            none_overdue_task: item.none_overdue_task,
            quality: item.quality,
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
interface Sticker {
    id: number,
    name: string,
    department_id: number,
    level_1: number,
    level_2: number,
    level_3: number,
    level_4: number,
    level_5: number,
    level_6: number,
    level_7: number,
    level_8: number,
    level_9: number,
    level_10: number
}
const session = ref<Session>()
const stickers = ref<Array<Sticker>>([]);
const stickersCache = computed(() => {
    const cache: Record<string, Sticker[]> = {};
    
    cache.initial = stickers.value.filter(sticker => sticker.department_id === session.value?.department_id);
    
    return cache;
});
const usersCache = computed(() => {
    const cache: Record<string, User[]> = {};
    
    cache.initial = users.value.filter(user => user.department_id === session.value?.department_id);
    
    return cache;
});
const onVisibleChangeType = (item: Item, is_appear: boolean) => {
    if (is_appear && item.department_id) {
        const cacheKey = item.department_id;

        if (!stickersCache.value[cacheKey]) {
            stickersCache.value[cacheKey] = stickers.value.filter(sticker => sticker.department_id === item.department_id);
        }
    }
}
const onVisibleChangeUser = (item: Item, is_appear: boolean) => {
    if (is_appear && item.department_id) {
        const cacheKey = item.department_id.toString();

        if (!usersCache.value[cacheKey]) {
            usersCache.value[cacheKey] = users.value.filter(user => user.department_id === item.department_id);
        }
    }
}
const getEditable = (item: Item) => {
    return session.value?.id === item.user_id || session.value?.is_authority || session.value?.is_manager
}
const onBuzz = () => {
    axios.post('/api/employee/buzz', {userIds: [formState.value.user_id]})
    .then(response => {
        callMessage(response.data.success, 'success');
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const priorities = ref()
//select box list generation
const CreateSelbox = () => {
    //create select boxes
    axios.get('/api/department/task/get_selectboxes')
    .then(response => {
        projects.value = response.data.projects;
        users.value = response.data.users;
        status.value = response.data.status;
        issues.value = response.data.task_timing_type;
        departments.value = response.data.departments;
        stickers.value = response.data.stickers;
        priorities.value = response.data.priorities;
        session.value = response.data.session;

        getAllCookie()

        _fetch()
    })
}
const onSaved = () => {
    _fetch();
}
interface Arg {
    task_id: number,
    task_user_id: number,
    task_issue_ids: number[]
}
const onSavedTaskTiming = (arg: Arg) => {
    reloadAfterTaskTimingChanged(dataSource.value, arg, computedFormState.value)
}
//reload whenever the user action on taskporject model
const onSavedTaskProject = (selectedTaskId = null) => {
    reloadAfterTaskProjectChanged(dataSource.value, selectedTaskId)
}
onMounted(() => {
    CreateSelbox()
})
</script>