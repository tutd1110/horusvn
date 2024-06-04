<template>
    <Dialog ref="modalAddRef" @saved="onSaved"></Dialog>
    <ConfigCalendar ref="modalConfigRef" @saved="onSaved"></ConfigCalendar>
    <DetailCalendar ref="modalDetailRef" @saved="onSaved"></DetailCalendar>
    <el-row style="margin-bottom: 0;"  class="panel-calendar">
        <el-col :lg="4" :md="24" style=" background-color: #F2F7FF;" >
            <div class="mini-calendar" :class="!hideMobile ? 'mini-calendar-mobile' : ''">
                <el-calendar ref="miniCalendar" style="background-color: #F2F7FF;" :range="rangeDate ? rangeDate : undefined" :class="rangeDate ? 'mini-calendar-custom' : ''">
                    <template #header="{ date }">
                        <div class="button-group" v-if="!hideMobile">
                            <el-button v-if="!rangeDate" size="small" :icon="Calendar" type="primary" @click="selectDate('today')" style="font-size: 12px; width: 85px;"><span style="font-size: 12px;">Hôm nay</span></el-button>
                            <el-button v-if="rangeDate" size="small" :icon="Calendar" type="primary" @click="rangeDate = undefined" style="font-size: 12px; width: 85px;"><span style="font-size: 12px;">Tháng</span></el-button>
                        </div>
                        <div class="select-date-month-mini">
                            <ArrowLeftBold style="width: 1em; height: 1em; margin-right: 8px; cursor: pointer;" @click="!rangeDate ? selectDate('prev-month') : subtractWeekTimesheet()"/>
                            <div class="now-date-select">
                                 <span v-if="!rangeDate">{{ date }}</span>
                                 <el-date-picker
                                    v-else
                                    size="small"
                                    v-model="weekTimesheetPicker"
                                    type="week"
                                    format="[Week] ww"
                                    class="calendar-week-select none-border"
                                    value-format="YYYY-MM-DD"
                                    @change="changeWeekTimesheet"
                                    :clearable="false"
                                /> 
                            </div>
                            <ArrowRightBold style="width: 1em; height: 1em; margin-left: 8px; cursor: pointer;" @click="!rangeDate ? selectDate('next-month') : addWeekTimesheet()"/>
                        </div>
                        <div class="button-group" v-if="!hideMobile">
                            <el-button size="small" :icon="Operation" type="primary" style="font-size: 15px;"><span style="font-size: 12px;" @click="showFilter = true">Cấu hình</span></el-button>
                        </div>
                    </template>
                    <template #date-cell="{ data }">
                        <div style="padding: 8px;" :class="[data.isSelected || data.day == formState.date ? 'is-selected-mini-calendar' : '', isSunday(data.day) ? 'is-sunday' : '']" @click="getDateWeek(data)">
                            <div class="date-item">
                                <span>{{ data.day.split('-')[2] }}</span>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; min-height: 8px; justify-content: center;">
                                <!-- <div class="date-notifi-item" style="background-color: #409EFF;">
                                    {{ data.isSelected }}
                                </div> -->
                                <template v-for="(record, key) in dataSource">
                                    <div class="date-notifi-item " :class="'date-notifi-item-color-'+record.class_color" v-if="record.date == data.day" style="padding: 3px; height: 1px; width: 1px; margin-right: 2px;">
                                    </div>
                                </template>
                                <template v-for="(record, key) in birthday">
                                    <div class="date-notifi-item " :class="'date-notifi-item-color-birthday'" v-if="record.date == getDayMonth(data.day)" style="padding: 3px; height: 1px; width: 1px;">
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div v-if="!hideMobile && rangeDate" class="detail-calendar">
                            <el-row :gutter="20" style="margin-bottom: 0;">
                                <el-col :span="6">
                                    <div class="modal-header-table">Sự kiện</div>
                                </el-col>
                                <el-col :span="10">
                                    <div class="modal-header-table">Tên chương trình</div>
                                </el-col>
                                <el-col :span="is_edit_calendar ? 5 : 8">
                                    <div class="modal-header-table">Giờ</div>
                                </el-col>
                                <el-col :span="3" v-if="is_edit_calendar">
                                    <div class="modal-header-table">Action</div>
                                </el-col>
                            </el-row>
                            <el-scrollbar height="500px">
                                <el-row 
                                    :gutter="20" 
                                    style="margin-bottom: 0; display: flex; align-items: center; border-bottom: 1px solid #E4E7ED; padding: 10px 0;"  
                                    v-for="(record) in dataSourceDetail"
                                >
                                    <el-col :span="6">
                                        <div class="modal-body-table" :class="'checkbox-color-'+record.class_color">{{ record.name_event }}</div>
                                    </el-col>
                                    <el-col :span="10">
                                        <div class="modal-body-table">{{ record.name }}</div>
                                    </el-col>
                                    <el-col :span="is_edit_calendar ? 5 : 8">
                                        <div class="modal-body-table">{{ dayjs(record.start_time, 'HH:mm').format('HH:mm')+' - '+dayjs(record.end_time, 'HH:mm').format('HH:mm') }}</div>
                                    </el-col>
                                    <el-col :span="3" v-if="is_edit_calendar">
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
                                <el-row 
                                    :gutter="20" 
                                    style="margin-bottom: 0; display: flex; align-items: center; border-bottom: 1px solid #E4E7ED; padding: 10px 0;"  
                                    v-for="(record) in birthdayDetail"
                                >
                                    <el-col :span="6">
                                        <div class="modal-body-table checkbox-color-birthday"> Sinh nhật</div>
                                    </el-col>
                                    <el-col :span="10">
                                        <div class="modal-body-table">Sinh nhật {{ record.name }}</div>
                                    </el-col>
                                    <el-col :span="is_edit_calendar ? 5 : 8">
                                        
                                    </el-col>
                                    <el-col :span="3" v-if="is_edit_calendar">
                                        <div class="modal-body-table"></div>
                                    </el-col>
                                </el-row>
                            </el-scrollbar>
                        </div>
                    </template>
                </el-calendar>
                <div v-if="!hideMobile && !rangeDate" class="detail-calendar" style="border-top: 1px solid #ccc; padding-top: 10px;">
                    <el-row :gutter="20" style="margin-bottom: 0;">
                        <el-col :span="8">
                            <div class="modal-header-table">Ngày</div>
                        </el-col>
                        <el-col :span="16">
                            <div class="modal-header-table">Tên Chương trình</div>
                        </el-col>
                    </el-row>
                    <el-scrollbar height="220px">
                        <el-row 
                            :gutter="20" 
                            style="margin-bottom: 0; display: flex; align-items: center; border-bottom: 1px solid #E4E7ED; padding: 10px 0;"  
                            v-for="(record) in dataSource"
                        >
                            
                            <el-col :span="8">
                                <div class="modal-body-table"> {{ dayjs(record.date).format('DD/MM/YYYY') }}</div>
                            </el-col>
                            <el-col :span="16">
                                <div class="modal-body-table">{{ record.name }}</div>
                            </el-col>
                        </el-row>
                        <el-row 
                            :gutter="20" 
                            style="margin-bottom: 0; display: flex; align-items: center; border-bottom: 1px solid #E4E7ED; padding: 10px 0;"  
                            v-for="(record) in birthday"
                        >
                            <el-col :span="8">
                                <div class="modal-body-table"> {{ dayjs(dayjs().year()+'-'+record.date).format('DD/MM/YYYY') }}</div>
                            </el-col>
                            <el-col :span="16">
                                <div class="modal-body-table">Sinh nhật {{ record.name }}</div>
                            </el-col>
                        </el-row>
                    </el-scrollbar>
                </div>
            </div>
            <div class="list-checkbox" v-if="hideMobile" style="padding: 0px 15px;">
                <div class="sub-title" style="font-size: 18px; font-weight: 700;">
                    Lịch
                </div>
                <div class="checkbox-item" v-for="(event) in listEvent">
                    <el-checkbox 
                        size="large" 
                        :class="'checkbox-color-'+event.class_color" 
                        :label="event.id"
                        :checked="formState.event_id?.includes(event.id)"
                        @change="handleCheckboxChange(event.id)"
                        :style="{color: event.key_color}"
                    >
                        {{ event.name }}
                    </el-checkbox>
                </div>
            </div>
        </el-col>
        <el-col :lg="20" :md="24" v-if="hideMobile">
            <el-calendar ref="calendar" class="full-calendar">
                <template #header="{ date, data }">
                    <div class="button-group">
                        <el-button :icon="Calendar" type="primary" @click="selectDate('today')">Hôm nay</el-button>
                    </div>
                    <div class="select-date-month">
                        <ArrowLeftBold style="width: 1em; height: 1em; margin-right: 8px; cursor: pointer;" @click="selectDate('prev-month')"/>
                        <div class="now-date-select">{{ date }}</div>
                        <ArrowRightBold style="width: 1em; height: 1em; margin-right: 8px; cursor: pointer;" @click="selectDate('next-month')"/>
                    </div>
                    <div class="button-group">
                        <el-button v-if="is_edit_calendar" type="primary" style="margin-right: 10px;" v-on:click="showAddCalendarModal()">Tạo lịch</el-button>
                        <el-button v-if="is_config_calendar" type="primary" v-on:click="showConfigModal()">Cấu hình</el-button>
                    </div>
                </template>
                <template #date-cell="{ data }">
                    <div 
                        :class="[data.isSelected ? 'is-selected' : '', isSunday(data.day) ? 'is-sunday' : '', getAllDate(data.day)]" 
                        @click.stop="shouldChangeMonth ? '' : showDetailCalendarModal(data.day)"
                    >
                        <div class="date-item">
                            <span>{{ data.day.split('-')[2] }}</span>
                        </div>
                        <el-scrollbar height="100px">
                            <!-- <div class="date-notifi-item" style="background-color: #409EFF;">
                                {{ data }}
                            </div> -->
                            <template v-for="(record, key) in dataSource">
                                <div class="date-notifi-item " :class="'date-notifi-item-color-'+record.class_color" v-if="record.date == data.day">
                                    <!-- {{ record.name +' - '+ (record.department_id ?? 'Toàn công ty') +' - '+ dayjs(record.start_time, 'HH:mm').format('HH:mm')}} -->
                                    {{ record.name +' - '+ (record.department_id ? record.department_id+' - ' : '') + dayjs(record.start_time, 'HH:mm').format('HH:mm')}}
                                </div>
                            </template>
                            <template v-for="(record, key) in birthday">
                                <div class="date-notifi-item date-notifi-item-color-birthday" v-if="record.date == getDayMonth(data.day)" style="display: flex; align-items: center;">
                                    <!-- <Present style="width: 15px; margin-right: 5px;"/> -->
                                    {{ record.name }}
                                </div>
                            </template>
                        </el-scrollbar>
                    </div>
                </template>
            </el-calendar>
        </el-col>
    </el-row>
    <el-drawer
        v-model="showFilter"
        direction="rtl"
        size="80%"
    >
        <template #header="">
            <span style="display: block;">Cấu hình lịch</span>
        </template>
        <el-row :gutter="10" class="panel-calendar">
            <el-col :span="22" :offset="1" v-if="is_edit_calendar" style="margin-bottom: 20px;">
                <el-button type="primary" style="margin-right: 10px;" v-on:click="showAddCalendarModal()">Tạo lịch</el-button>
                <el-button type="primary" v-on:click="showConfigModal()">Cấu hình</el-button>
            </el-col>
            <el-col :span="22" :offset="1">
                <div class="list-checkbox">
                    <div class="sub-title" style="font-size: 14px; font-weight: 700;">
                        Bộ lọc lịch
                    </div>
                    <div class="checkbox-item" v-for="(event) in listEvent">
                        <el-checkbox 
                            size="large" 
                            :class="'checkbox-color-'+event.class_color" 
                            :label="event.id"
                            :checked="formState.event_id?.includes(event.id)"
                            @change="handleCheckboxChange(event.id)"
                            :style="{color: event.key_color}"
                        >
                            {{ event.name }}
                        </el-checkbox>
                    </div>
                </div>
            </el-col>
        </el-row>
    </el-drawer>
</template>

<script lang="ts" setup>
import { ref, onMounted, computed } from 'vue'
import type { CalendarDateType } from 'element-plus'
import { ArrowLeftBold, ArrowRightBold, Calendar, Present, Operation, EditPen, Delete} from '@element-plus/icons-vue'
import Dialog from './Dialog.vue';
import ConfigCalendar from './ConfigCalendar.vue';
import DetailCalendar from './DetailCalendar.vue';
import axios from 'axios';
import { callMessage } from '../Helper/el-message.js';

import dayjs from 'dayjs';

interface FormState {
    event_id?: number[],
    start_date?: string,
    end_date?: string,
    date?: string,
};
const formState = ref<FormState>({
    event_id: [],
});

const modalAddRef = ref()
const modalConfigRef = ref()
const modalDetailRef = ref()

const dataSource = ref()
const birthday = ref()
const dataSourceDetail = ref()
const birthdayDetail = ref()
const is_edit_calendar = ref()
const is_config_calendar = ref()
const errorMessages = ref('')
const calendar = ref()
const miniCalendar = ref()
const rangeDate = ref()
const shouldChangeMonth = ref(false);

const listEvent = ref()
const handleCheckboxChange = (eventId:number) => {
    if (formState.value.event_id?.includes(eventId)) {
        formState.value.event_id = formState.value.event_id.filter(id => id !== eventId);
    } else {
        formState.value.event_id?.push(eventId);
    }
    
    _fetch();
};
const weekTimesheetPicker = ref()
const getDateWeek = (data: any) => {    
    if (hideMobile.value == false) {
        rangeDate.value = [new Date(data.day), new Date(data.day)]
        formState.value.date = data.day
        weekTimesheetPicker.value = dayjs(data.day).startOf('week').format('YYYY/MM/DD')
        
        getEventDetail()
    }
};
// Function to add one week to weekTimesheetPicker
const changeWeekTimesheet = () => {
    formState.value.start_date = weekTimesheetPicker.value
    formState.value.end_date = dayjs(weekTimesheetPicker.value).endOf('week').format('YYYY/MM/DD')
    rangeDate.value = [new Date(weekTimesheetPicker.value), new Date(weekTimesheetPicker.value)]

    dataSourceDetail.value = []
    birthdayDetail.value = []
    // getEventList()
};
const addWeekTimesheet = () => {
    const newDate = dayjs(weekTimesheetPicker.value).add(1, 'week');
    weekTimesheetPicker.value = newDate.format('YYYY-MM-DD');

    changeWeekTimesheet()
};
// Function to subtract one week from weekTimesheetPicker
const subtractWeekTimesheet = () => {
    const newDate = dayjs(weekTimesheetPicker.value).subtract(1, 'week');
    weekTimesheetPicker.value = newDate.format('YYYY-MM-DD');

    changeWeekTimesheet()
};
const selectDate = (val: CalendarDateType) => {
    if (!miniCalendar.value) return
    miniCalendar.value.selectDate(val);
    if (!calendar.value) return
    calendar.value.selectDate(val);

    getEventList()
    // _fetch();
}

const isSunday = (date: string) => {
    const dayOfWeek = new Date(date).getDay();
    return dayOfWeek === 0;
};
const showAddCalendarModal = () => { 
    modalAddRef.value.AddCalendarModalMode();
};
const showConfigModal = () => {
    modalConfigRef.value.ConfigModalMode();
};
const showDetailCalendarModal = (date: string) => {
    modalDetailRef.value.DetailCalendarMode(date, formState.value.event_id);
};
const onSaved = () => {
    // getEventList()
    _fetch();
};
const allDates:any = [];
const getAllDate = (date: any) => {

    allDates.push(date);
    formState.value.start_date = allDates[0];
    formState.value.end_date = allDates[allDates.length - 1];
}

const _fetch = () => {
    axios.post('/api/calendar_event/get_calendar', formState.value)
    .then(response => {
        allDates.splice(0, allDates.length);
        dataSource.value = response.data.data
        birthday.value = response.data.birthday
        is_edit_calendar.value = response.data.is_edit_calendar
        is_config_calendar.value = response.data.is_config_calendar
        
    })
    .catch(error => {
        dataSource.value = []
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
};

const getEventList = () => {

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
}
const getEventDetail = () => {
    axios.post('/api/calendar_event/get_calendar_detail', formState.value)
    .then(response => {
        dataSourceDetail.value = response.data.data
        birthdayDetail.value = response.data.birthday
        is_edit_calendar.value = response.data.is_edit_calendar
        is_config_calendar.value = response.data.is_config_calendar
    })
    .catch((error) => {
        errorMessages.value = error.response.data.errors;//put message content in ref
        //When search target data does not exist
        dataSource.value = []; //dataSource empty
        callMessage(errorMessages.value, 'error');
    });
}
const showUpdateCalendarModal = (id:number) => { 
    modalAddRef.value.UpdateCalendarModalMode(id);
};
const destroyCalendar = (id:number) => { 
    axios.post('/api/calendar_event/destroy_calendar', {id:id})
        .then(response => {
            callMessage(response.data.success, 'success');
            getEventDetail()
            _fetch();
        })
        .catch((error) => {
            errorMessages.value = error.response.data.errors;//put message content in ref
            //When search target data does not exist
            dataSource.value = []; //dataSource empty
            callMessage(errorMessages.value, 'error');
        });
};
const getDayMonth = (date:any) => {
    const parts = date.split('-');
    if (parts.length === 3) {
        return parts[1] + '-' + parts[2];
    }
    return '';
}

const hideMobile = ref(true)
const showFilter = ref(false)
onMounted (() => {
    var screenWidth = window.screen.width;
    if (screenWidth >= 1080) {
        hideMobile.value = true
    } else {
        hideMobile.value = false
    } 
    getEventList()
})

</script>

