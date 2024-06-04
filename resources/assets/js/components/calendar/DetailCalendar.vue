<template>
    <Dialog ref="modalAddRef" @saved="onSaved"></Dialog>
    <el-dialog v-model="visible" style="width:90%;padding: 20px; font-weight: bold" draggable
        :closable="false" :title="title" :before-close="handleClose">
        <template #header="{ close, titleId, titleClass }">
            <div class="header-modal" style="display: flex;">
                <span style="font-size: 20px; margin-right: 20px;">{{ title }}</span>
                <el-button type="primary" style="margin-right: 10px;" v-on:click="showAddCalendarModal(selectDate)">Tạo lịch</el-button>
                <el-button 
                    :icon="Filter" 
                    @click="handleShowFilter(showFilter)"
                    style="margin: 0px 5px 0px 0 ;"
                />
            </div>
        </template>
        <el-row :gutter="20" style="margin-bottom: 0;">
            <el-col :span="24" style="margin-bottom: 10px;" v-if="is_edit_calendar">
                <el-row :gutter="20" style="margin: 0px 0 10px 0 ;" v-if="showFilter">
                    <el-col :span="5">
                        <el-input
                            v-model="formState.name"
                            clearable
                            style="width: 100%"
                            placeholder="Tên chương trình"
                            @change="getCalendarDetail"
                        />
                    </el-col>
                    <el-col :span="4">
                        <el-select
                            v-model="formState.event_id"
                            multiple
                            filterable
                            collapse-tags
                            placeholder="Sự kiện"
                            style="width: 100%"
                            clearable
                            @change="getCalendarDetail"
                        >
                            <el-option
                                v-for="item in listEvent"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id"
                            />
                        </el-select>
                    </el-col>
                    <el-col :span="3">
                        <el-select
                            v-model="formState.department_id"
                            multiple
                            filterable
                            collapse-tags
                            placeholder="Bộ phận"
                            style="width: 100%"
                            clearable
                            @change="getCalendarDetail"
                        >
                            <el-option
                                v-for="item in departments"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id"
                            />
                        </el-select>
                    </el-col>
                </el-row>
            </el-col>
            <el-col :span="2">
                <div class="modal-header-table">Sự kiện</div>
            </el-col>
            <el-col :span="2">
                <div class="modal-header-table">Tên chương trình</div>
            </el-col>
            <el-col :span="2">
                <div class="modal-header-table">Bộ phận</div>
            </el-col>
            <el-col :span="3">
                <div class="modal-header-table">Người phụ trách</div>
            </el-col>
            <el-col :span="3">
                <div class="modal-header-table">Người tham dự</div>
            </el-col>
            <el-col :span="2">
                <div class="modal-header-table">Giờ</div>
            </el-col>
            <el-col :span="is_edit_calendar ? 4 : 5">
                <div class="modal-header-table">Nội dung</div>
            </el-col>
            <el-col :span="3">
                <div class="modal-header-table">Người tạo sự kiện</div>
            </el-col>
            <el-col :span="2">
                <div class="modal-header-table">Hiển thị</div>
            </el-col>
            <el-col :span="1" v-if="is_edit_calendar">
                <div class="modal-header-table">Action</div>
            </el-col>
        </el-row>
        <el-scrollbar height="400px">
            <el-row 
                :gutter="20" 
                style="margin-bottom: 0; display: flex; align-items: center; border-bottom: 1px solid #E4E7ED; padding: 10px 0;"  
                v-for="(record) in birthday"
            >
                <el-col :span="2">
                    <div class="modal-body-table checkbox-color-birthday"> Sinh nhật</div>
                </el-col>
                <el-col :span="2">
                    <div class="modal-body-table">Sinh nhật {{ record.name }}</div>
                </el-col>
                <el-col :span="2">
                    <div class="modal-body-table">{{ record.department_id }}</div>
                </el-col>
                <el-col :span="3">
                    <div class="modal-body-table">{{ record.name }}</div>
                </el-col>
                <el-col :span="3">
                    <div class="modal-body-table">{{ record.name }}</div>
                </el-col>
                <el-col :span="2">
                    <div class="modal-body-table"></div>
                </el-col>
                <el-col :span="is_edit_calendar ? 4 : 5">
                    <div class="modal-body-table">{{record.description}}</div>
                </el-col>
                <el-col :span="3">
                    <div class="modal-body-table"></div>
                </el-col>
                <el-col :span="2">
                    <div class="modal-body-table"></div>
                </el-col>
                <el-col :span="1" v-if="is_edit_calendar">
                    <div class="modal-body-table"></div>
                </el-col>
            </el-row>
            <el-row 
                :gutter="20" 
                style="margin-bottom: 0; display: flex; align-items: center; border-bottom: 1px solid #E4E7ED; padding: 10px 0;"  
                v-for="(record) in dataSource"
            >
                <el-col :span="2">
                    <div class="modal-body-table" :class="'checkbox-color-'+record.class_color">{{ record.name_event }}</div>
                </el-col>
                <el-col :span="2">
                    <div class="modal-body-table">{{ record.name }}</div>
                </el-col>
                <el-col :span="2">
                    <div class="modal-body-table">{{ record.department_id ?? 'Toàn công ty' }}</div>
                </el-col>
                <el-col :span="3">
                    <div class="modal-body-table">{{ record.fullnames }}</div>
                </el-col>
                <el-col :span="3">
                    <div class="modal-body-table">{{ record.user_join }}</div>
                </el-col>
                <el-col :span="2">
                    <div class="modal-body-table">{{ dayjs(record.start_time, 'HH:mm').format('HH:mm')+' - '+dayjs(record.end_time, 'HH:mm').format('HH:mm') }}</div>
                </el-col>
                <el-col :span="is_edit_calendar ? 4 : 5">
                    <div class="modal-body-table">{{record.description}}</div>
                </el-col>
                <el-col :span="3">
                    <div class="modal-body-table">{{ record.name_created }}</div>
                </el-col>
                <el-col :span="2">
                    <div class="modal-body-table">{{record.status}}</div>
                </el-col>
                <el-col :span="1" v-if="is_edit_calendar">
                    <div class="modal-body-table">
                        <EditPen
                            style="width: 16px; cursor: pointer; color:#909399; margin-right: 5px;" 
                            @click="showUpdateCalendarModal(record.id)"
                        />
                        <el-popconfirm title="Bạn có chắc chắn xóa?" @confirm="destroyCalendar(record.id)">
                            <template #reference>
                                <Delete style="width: 16px; cursor: pointer; color:red"/>
                            </template>
                        </el-popconfirm>
                    </div>
                </el-col>
            </el-row>
        </el-scrollbar>
  </el-dialog>
</template>

<script lang="ts" setup>
    import { ref } from 'vue';
    import {EditPen, Delete, Filter } from '@element-plus/icons-vue'
    import axios from 'axios';
    import { callMessage } from '../Helper/el-message.js';
    import Dialog from './Dialog.vue';

    import dayjs from 'dayjs';

    interface FormState {
        date?: string,
        event_id?: any,
        name?: string,
        department_id?: number,
    };
    const title = ref()
    const selectDate = ref()
    const formState = ref<FormState>({});
    const visible = ref(false)
    const errorMessages = ref()
    const dataSource = ref()
    const birthday = ref()
    const is_edit_calendar = ref()
    const listEvent = ref()
    const departments = ref()

    const DetailCalendarMode = (date:string, event_id:any) => { 
        title.value = 'Chi tiết ngày '+dayjs(date).format('DD/MM/YYYY')
        visible.value = true;
        formState.value.date = date
        formState.value.event_id = event_id
        selectDate.value = date
        getSelectboxes()
        _fetch();
    };
    const showFilter = ref(false)
    const handleShowFilter = (status:boolean) => {
        showFilter.value = !status
    }
    const getCalendarDetail = () => {
        _fetch()
    }

    const handleClose = (done: () => void) => {
        formState.value = {}
        done()
    }
    const _fetch = () => {
        axios.post('/api/calendar_event/get_calendar_detail', formState.value)
            .then(response => {
                dataSource.value = response.data.data
                birthday.value = response.data.birthday
                is_edit_calendar.value = response.data.is_edit_calendar
                emit('saved');
            })
            .catch((error) => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                //When search target data does not exist
                dataSource.value = []; //dataSource empty
                callMessage(errorMessages.value, 'error');
            });
    }
    const modalAddRef = ref()
    const showUpdateCalendarModal = (id:number) => { 
        modalAddRef.value.UpdateCalendarModalMode(id);
    };
    const showAddCalendarModal = (date:string) => { 
        modalAddRef.value.AddCalendarModalDetailMode(date);
    };
    const destroyCalendar = (id:number) => { 
        axios.post('/api/calendar_event/destroy_calendar', {id:id})
            .then(response => {
                callMessage(response.data.success, 'success');
                _fetch()
            })
            .catch((error) => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                //When search target data does not exist
                dataSource.value = []; //dataSource empty
                callMessage(errorMessages.value, 'error');
            });
    };
    const emit = defineEmits(['saved'])
    const onSaved = () => {
        _fetch();
    };
    const getSelectboxes = () => {
        axios.get('/api/calendar_event/get_event_list')
        .then(response => {
            listEvent.value = response.data;
            listEvent.value.push({ id: 0, name: 'Sinh nhật', class_color: 'birthday'})
            if (formState.value.event_id?.length == 0 ){
                formState.value.event_id = response.data.map((event:any) => event.id);
            }
                    
            _fetch();
        })
        .catch(error => {
            listEvent.value = []
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error');
        })
        axios.get('/api/common/departments')
        .then(response => {
            departments.value = response.data
        })
    }
    defineExpose({
        DetailCalendarMode,
    });
</script>