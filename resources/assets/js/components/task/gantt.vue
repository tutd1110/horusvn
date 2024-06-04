<template>
    <el-row :gutter="20">
        <el-col :span="3">
            <label class="sub-select">Tên công việc</label>
            <el-input v-model="submitData.name" type="input" clearable />
        </el-col>
        <el-col :span="3">
            <label class="sub-select">Dự án</label>
            <el-select v-model="submitData.project_id" filterable placeholder="" clearable collapse-tags style="width: 100%;">
                <el-option v-for="item in projectSelbox" :key="item.key" :label="item.label" :value="item.key" />
            </el-select>
        </el-col>
        <el-col :span="3">
            <label class="sub-select">Bộ phận</label>
            <el-select v-model="submitData.department_id" filterable placeholder="" clearable multiple collapse-tags style="width: 100%;">
                <el-option v-for="item in departmentSelbox" :key="item.key" :label="item.label" :value="item.key" />
            </el-select>
        </el-col>
        <el-col :span="3">
            <label class="sub-select">Người thực hiện</label>
            <el-select v-model="submitData.user_id" filterable placeholder="" clearable multiple collapse-tags style="width: 100%;">
                <el-option v-for="item in users" :key="item.id" :label="item.text" :value="item.id" />
            </el-select>
        </el-col>
        <el-col :span="2" style="padding-top: 22px;">
            <el-button @click="_fetch" type="primary">Search</el-button>
        </el-col>
        <el-col :span="2" style="padding-top: 22px;">
            <!-- <el-button @click="toggleGroupsByResource" type="primary">Show Resource view</el-button> -->
            <!-- <el-button @click="showGroups()" type="primary">Show Resource view</el-button> -->
        </el-col>
    </el-row>
    <div class="container">
        <div id="gantt_here" ref="ganttContainer" class="gantt-container"></div>
    </div>
</template>

<script lang="ts" setup>
    import { ref, onMounted } from "vue";
    import { gantt } from "dhtmlx-gantt";
    import "dhtmlx-gantt/codebase/dhtmlxgantt.css";
    import "dhtmlx-gantt/codebase/dhtmlxgantt.js";
    import "dhtmlx-gantt/codebase/dhtmlxgantt.d.ts";
    import dayjs from "dayjs";
    import axios from 'axios';

    import { callMessage } from '../Helper/el-message.js';
    import { onCommonChangeSticker, onCommonChangePriority } from '../Helper/helpers.js';

    
    interface FormState {
        name?: string,
        date?: string,
        time?: string[],
        department_id?: number,
        project_id?: number,
        user_id?: number[],
        description?: string,
        sub_event?: number,
        event_id?: number,
        start_time?: string,
        end_time?: string,
        status?: number,
        user_join?: number,
    };

    interface User {
        id?: number,
        text?: string,
        parent?: number,
    }

    interface ResourceItem {
        text?: string;
    }


    const ganttContainer = ref();
    const dataSource = ref();
    const projectSelbox = ref();
    const departmentSelbox = ref();
    const statusSelbox = ref();
    const prioritiesSelbox = ref();
    const stickersSelbox = ref();
    const users = ref<User[]>([]);
    const errorMessages = ref('')

    const submitData = ref<FormState>({});

    const _fetch = () => {
        axios
            .post("/api/task/get_task_list_gantt", submitData.value)
            .then((response) => {
                dataSource.value = response.data;

                gantt.clearAll();
                gantt.config.date_format = "%Y-%m-%d";
                gantt.init(ganttContainer.value);
                gantt.config.grid_resize = true;

                if (response.data && Array.isArray(response.data.data) && response.data.data.length > 0) {
                    gantt.parse(dataSource.value);
                }
          })
            .catch((error) => {
                console.log("empty data");
                dataSource.value = [];
                errorMessages.value = error.response.data.errors;
                callMessage(errorMessages.value, 'error');
                gantt.clearAll();
            });
    }

    const showGroups =  () => {

        gantt.serverList("user_id", [
            ...users.value.map((user: User) => ({ key: user.id, label: user.text }))
        ]);
        gantt.groupBy({
            relation_property: "user_id",
            groups: gantt.serverList("user_id"),
            group_id: "key",
            group_text: "label"
        });

    }

    onMounted(() => {
        gantt.config.grid_resize = true;
        gantt.plugins({
            grouping: true,
        });
        axios.get('/api/task/get_selectboxes_gantt')
            .then(response => {
                projectSelbox.value = response.data.projects;
                departmentSelbox.value = response.data.departments;
                statusSelbox.value = response.data.status;
                prioritiesSelbox.value = response.data.priorities;
                stickersSelbox.value = response.data.stickers;
                users.value = response.data.users;

                setUpGantt();
                gantt.$resourceStore = gantt.createDatastore({
                    name: gantt.config.resource_store,
                    type: "treeDatastore",
                    initItem: function (item:any) {
                        item.parent = item.parent || gantt.config.root_id;
                        item[gantt.config.resource_property] = item.id;
                        item.open = true;
                        return item;
                    },
                });
            })

        _fetch();
        gantt.ext.zoom.init(zoomConfig);
        gantt.ext.zoom.setLevel('day');
        gantt.config.bar_height = 18;
        gantt.templates.scale_cell_class = function (date) {
            if (date.getDay() == 0 || date.getDay() == 6) {
                return "weekend";
            }
            return "";
        };
        gantt.templates.timeline_cell_class = function (item, date) {
            if (date.getDay() == 0 || date.getDay() == 6) {
                return "weekend"
            }
            return "";
        };
   
    });

    const store = (dataUpdate:any) => {
        dataUpdate.type = 'child'
        dataUpdate.name = dataUpdate.text
        dataUpdate.task_parent = dataUpdate.parent; 
        dataUpdate.project_ids = [submitData.value.project_id]
        dataUpdate.weight = null
        dataUpdate.clone_id = ''
        dataUpdate.start_time = dayjs(dataUpdate.start_date).format('YYYY/MM/DD HH:mm:ss')
        dataUpdate.end_time = dayjs(dataUpdate.end_date).format('YYYY/MM/DD HH:mm:ss')
        axios.post('/api/task/store_gantt', dataUpdate)
        .then(response => {
            _fetch();
            callMessage(response.data.success, 'success');
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error');
        })
    }

    const update = (dataUpdate:any) => {
        dataUpdate.type = 'child'
        dataUpdate.name = dataUpdate.text 
        dataUpdate.project_ids = [submitData.value.project_id]
        dataUpdate.check_updated_at = dayjs().format('YYYY/MM/DD HH:mm:ss');
        dataUpdate.task_parent = dataUpdate.parent;
        dataUpdate.weight = null
        dataUpdate.id_list = []
        dataUpdate.start_time = dayjs(dataUpdate.start_date).format('YYYY/MM/DD HH:mm:ss')
        dataUpdate.end_time = dayjs(dataUpdate.end_date).format('YYYY/MM/DD HH:mm:ss')
        axios.patch('/api/task/update_gantt', dataUpdate)
        .then(response => {
            _fetch();
            callMessage(response.data.success, 'success');
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error');
        })
    }
    const changeParent = (dataUpdate:any) => {
        dataUpdate.check_updated_at = dayjs().format('YYYY/MM/DD HH:mm:ss');
        // dataUpdate.task_parent = dataUpdate.target;
        // dataUpdate.id = dataUpdate.source;
        dataUpdate.id = dataUpdate.target;
        dataUpdate.task_parent = dataUpdate.source;
        axios.post('/api/task/change_parent_gantt', dataUpdate)
        .then(response => {
            _fetch();
            callMessage(response.data.success, 'success');
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error');
        })
    }

    const destroy = (dataUpdate:any) => {
        dataUpdate.ids = [dataUpdate.id]
        dataUpdate.check_updated_at = dayjs().format('YYYY/MM/DD HH:mm:ss')
        dataUpdate.mode = 1
        axios.post('/api/task/delete_gantt', dataUpdate)                  
        .then(response => {
            _fetch();
            callMessage(response.data.success, 'success');
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error');
        })
    }

    const onChangeSticker = (task:any) => {
        if (task.priority) {
            const args = [
                task.id, task.sticker_id, [task], stickersSelbox.value, prioritiesSelbox.value
            ]
            onCommonChangeSticker(...args)
        }
        
    }

    const onChangePriority = (task:any) => {
        console.log(task);
        
        if (task.sticker_id) {
            const args = [
                task.id, task.priority, [task], stickersSelbox.value, prioritiesSelbox.value
            ]
            onCommonChangePriority(...args)
        }
    }
    
    
    const setUpGantt = () => {
        gantt.locale.labels.section_name = "Tên công việc";
        gantt.locale.labels.section_description = "Thông tin công việc";
        gantt.locale.labels.section_department = "Bộ phận";
        gantt.locale.labels.section_status = "Trạng thái";
        gantt.locale.labels.section_priorities = "Cấp độ";
        gantt.locale.labels.section_weight = "Trọng số";
        gantt.locale.labels.section_stickers = "Loại công việc";
        gantt.locale.labels.section_user = "Người thực hiện";
        gantt.locale.labels.section_deadline = "Deadline";
        gantt.locale.labels.section_time = "Thời gian bắt đầu và kết thúc";

        const defaultOption = [{ key: '', id: '', label: '------' }];
        gantt.config.lightbox.sections = [
            { name: "name", height: 70, map_to: "text", type: "textarea", focus: true },
            { name: "description", height: 70, map_to: "description", type: "textarea" },
            // { name: "project", height: 22, map_to: "project_id", type: "select", options: [...defaultOption, ...projectSelbox.value] },
            { name: "department", height: 30, map_to: "department_id", type: "select", options: [...defaultOption, ...departmentSelbox.value] },
            { name: "status", height: 30, map_to: "status", type: "select", options: statusSelbox.value },
            //{ name: "priorities", height: 30, map_to: "priority", type: "select", options: [...defaultOption, ...prioritiesSelbox.value] },
            { 
                name: "priorities", 
                height: 30, 
                map_to: "priority", 
                type: "select", 
                options: [...defaultOption, ...prioritiesSelbox.value],
                onchange: function() {
                   // Lấy toàn bộ dữ liệu form từ lightbox
                   var formData = gantt.getLightboxValues();
                   onChangePriority(formData);
                }
            },
            { name: "weight", height: 30, map_to: "weight", type: "textarea" },
            //{ name: "stickers", height: 30, map_to: "sticker_id", type: "select", options: [...defaultOption, ...stickersSelbox.value] },
            { 
                name: "stickers", 
                height: 30, 
                map_to: "sticker_id", 
                type: "select", 
                options: [...defaultOption, ...stickersSelbox.value],
                onchange: function() {
                   var formData = gantt.getLightboxValues();
                   onChangeSticker(formData);
                }
            },
            { name: "user", height: 30, map_to: "user_id", type: "select", options: [...defaultOption, ...users.value.map((user: User) => ({ key: user.id, label: user.text }))] },
            { name: "time", height: 50, map_to: "auto", type: "duration" },
            // { name: "time", height: 50, map_to: "auto", type: "time" },
            // { name: "deadline", height: 50, map_to: "auto", type: "duration" },
        ];

        const dateEditor = {type: "date", map_to: "deadline"};
        const selectEditor = {type: "text", map_to: "text"};
        gantt.config.columns = [
            { 
                name: "text",  
                label: "Task Name", 
                tree: true, 
                width: 250, 
                resize: true,
            },
            {
                name: "owner",
                align: "left",
                width: 150,
                label: "Owner",
                template: function (task) {
                    gantt.$resourceStore.parse(users.value);
                    if (task.type == gantt.config.types.project) {
                        return "";
                    }
                    const owner = task.owner;
                    
                    if (!owner || !owner.resource_id) {
                        return "<strong>Unassigned</strong>";
                    }

                    const store = gantt.getDatastore("resource");
                    const resourceItem = store.getItem(owner.resource_id) as ResourceItem;

                    if (!resourceItem) {
                        return "<strong>Unassigned</strong>";
                    }
                    return "<strong>" + resourceItem.text + "</strong>";
                },
                resize: true,
            },
            {
                name: "department_name",
                align: "center",
                width: 120,
                label: "Department",
                resize: true,
            },
            { name: "start_date", label: "Start", align: "center", width: 80, resize: true },
            { name: "end_date", label: "End", align: "center", width: 80, resize: true },
            { 
                name: "deadline", 
                label: "Deadline", 
                align: "center", 
                width: 80, 
                resize: true,
                template: function (task) {
                    return "<strong>" + task.deadline + "</strong>";
                },
                editor: dateEditor
            },
            {
                name: "duration",
                label: "Duration",
                resize: true,
                align: "center",
                width: 50,
            },
            {
                name: "status_name",
                align: "center",
                width: 120,
                label: "Status",
                resize: true,
                template: function (task) {
                    return "<span style='font-size:12px' class='" + (task.status ? "task-status-" + task.status : "no-select") + "'>" + task.status_name + "</span>";
                },
            },
            {
                name: "process",
                align: "center",
                width: 50,
                label: "Process",
                resize: true,
                template: function (task:any) {
                    return  Math.round(task.progress * 100) + "%";
                },
            },
            { name: "add", width: 40 },
        ];   

        gantt.templates.task_time = function(start,end,task){
            return dayjs(start).format('DD/MM/YYYY')+" - "+dayjs(end).format('DD/MM/YYYY');
        };
    }
    
        const zoomConfig = {
            levels: [
                {
                    name: "hour",
                    scale_height: 50,
                    min_column_width: 15,
                    scales: [
                        { unit: "day", format: "%d" },
                        { unit: "hour", format: "%H" },
                    ],
                },

                {
                    name: "day",
                    scale_height: 50,
                    min_column_width: 20,
                    scales: [
                        { unit: "month", format: "%m - %Y" },
                        { unit: "day", format: "%d" },
                    ],
                },
                {
                    name: "week",
                    scale_height: 50,
                    min_column_width: 50,
                    scales: [
                        {
                            unit: "week",
                            step: 1,
                            format: function (date:any) {
                                var dateToStr = gantt.date.date_to_str("%d %M");
                                var endDate = gantt.date.add(date, -6, "day");
                                var weekNum = gantt.date.date_to_str("%W")(date);
                                return "#" + weekNum + ", " + dateToStr(date) + " - " + dateToStr(endDate);
                            },
                        },
                        { unit: "day", step: 1, format: "%j %D" },
                    ],
                },
                {
                    name: "month",
                    scale_height: 50,
                    min_column_width: 120,
                    scales: [
                        { unit: "month", format: "%F, %Y" },
                        { unit: "week", format: "Week #%W" },
                        { unit: "day", step: 1, format: "%j %D" },
                    ],
                },
                {
                    name: "quarter",
                    height: 50,
                    min_column_width: 90,
                    scales: [
                        {
                            unit: "quarter",
                            step: 1,
                            format: function (date:any) {
                                var dateToStr = gantt.date.date_to_str("%M");
                                var endDate = gantt.date.add(gantt.date.add(date, 3, "month"), -1, "day");
                                return dateToStr(date) + " - " + dateToStr(endDate);
                            },
                        },
                        { unit: "month", step: 1, format: "%M" },
                    ],
                },
                {
                    name: "year",
                    scale_height: 50,
                    min_column_width: 30,
                    scales: [{ unit: "year", step: 1, format: "%Y" }],
                },
            ],
            useKey: "ctrlKey",
            trigger: "wheel",
            element: function () {
                return gantt.$root.querySelector(".gantt_task");
            },
        };
        // gantt.templates.progress_text = function (start, end, task:any) {
    	// 	return "<span style='float:left; color:#fff; padding-left:5px'>" + Math.round(task.progress * 100) + "% </span>";
    	// };

        gantt.config.work_time = true;
        gantt.setWorkTime({hours:["8:00-12:00"]});
        gantt.setWorkTime({hours:["13:30-17:30"]});
        gantt.setWorkTime({day : 6, hours : ["8:00-12:00"]});
        // gantt.config.correct_work_time = true;
        // gantt.templates.timeline_cell_class = function (task, date) {
        //     if (!gantt.isWorkTime({ date: date, task: task, unit: gantt.getScale().unit })) return "weekend";
        // };
        gantt.config.dynamic_resource_calendars = true;

        gantt.addCalendar({
            id: "custom",
        });

        // gantt.attachEvent("onTaskClick", function(id, task) {
        //     console.log(task);
        // });
        gantt.attachEvent("onAfterTaskAdd", function(id, task) {
            console.log(task);
            store(task)
        });
        gantt.attachEvent("onAfterTaskUpdate", function(id, task) {
            console.log(task);
            update(task)
        });
        gantt.attachEvent("onAfterTaskDelete", function(id, task) {
            console.log(task);
            destroy(task);
        });

        gantt.attachEvent("onAfterLinkAdd", function(id, link) {
            console.log(link);
            changeParent(link)
        });
        gantt.attachEvent("onAfterLinkDelete", function(id, link) {
            console.log(link);
            link.source = null
            changeParent(link)
        });
        gantt.attachEvent("onAfterLinkUpdate", function(id, link) {
            console.log("onAfterLinkUpdate");
            // changeParent(link)
        });

        /*
    		var autoFormatter = gantt.ext.formatters.durationFormatter({
    			enter: "day",
    			store: "hour",
    			format: "auto"
    		});
    	*/
        // const toggleGroupsByResource = () => {
        //     gantt.$groupMode = !gantt.$groupMode;
        //     if (gantt.$groupMode) {
        //         const resources = gantt.$resourceStore.getItems();

        //         // Tạo danh sách các group dựa trên người thực hiện
        //         const groups = resources.map(function (resource: any) {
        //             const group = gantt.copy(resource);
        //             group.group_id = group.id;
        //             group.id = gantt.uid();

        //             return group;
        //         });
        //         console.log(groups);
                
        //         gantt.groupBy({
        //             groups: groups,
        //             relation_property: "id", // Sử dụng ID của người thực hiện để gộp
        //             group_id: "group_id",
        //             group_text: "text", // Hiển thị tên người thực hiện trong danh sách nhóm
        //             delimiter: ", ",
        //             default_group_label: "Not Assigned"
        //         });
        //     } else {
        //         gantt.groupBy(false);
        //     }
        // };
        
</script>

<style>
    html,
    body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
    .container {
        height: 750px;
        width: 100%;
    }
    .gantt-container {
        overflow: hidden;
        position: relative;
        height: 100%;
    }
    .gantt_cal_light {
        padding: 15px;
        height: auto !important;

    }
    .gantt_task_progress >* {
        font-size: 10px;
        color: #fff !important;
    }
    .gantt_tree_content{
        font-size: 12px;
    }

    .weekend {
        background: #f4f7f4;
    }

    .gantt_selected .weekend {
        background: #f7eb91;
    }

    .gantt_task .gantt_task_scale .gantt_scale_cell {
        font-size: 12px;
    }
</style>
