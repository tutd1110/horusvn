<template>
    <Dialog ref="modalAddRef" @saved="onSaved"></Dialog>
    <ConfigCalendar ref="modalConfigRef" @saved="onSaved"></ConfigCalendar>
    <DetailCalendar ref="modalDetailRef" @saved="onSaved"></DetailCalendar>
    <el-row style="margin-bottom: 0;"  class="panel-calendar">
        <el-col :span="4" style="padding: 10px 15px; background-color: #F2F7FF;" >
            <div class="mini-calendar">
                <el-calendar ref="miniCalendar" style="background-color: #F2F7FF;" >
                    <template #header="{ date }">
                        <div class="select-date-month-mini">
                            <ArrowLeftBold style="width: 1em; height: 1em; margin-right: 8px; cursor: pointer;" @click="selectDate('prev-month')"/>
                            <div class="now-date-select">{{ date }}</div>
                            <ArrowRightBold style="width: 1em; height: 1em; margin-right: 8px; cursor: pointer;" @click="selectDate('next-month')"/>
                        </div>
                    </template>
                    <template #date-cell="{ data }">
                        <div :class="[data.isSelected ? 'is-selected' : '', isSunday(data.day) ? 'is-sunday' : '']" >
                            <div class="date-item">
                                <span>{{ data.day.split('-')[2] }}</span>
                            </div>
                        </div>
                    </template>
                </el-calendar>
            </div>
            <div class="list-checkbox">
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
        <el-col :span="20">
            <el-calendar ref="calendar" class="full-calendar">
                <template #header="{ date }">
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
                        <el-button v-if="is_edit_calendar" type="primary" v-on:click="showConfigModal()">Cấu hình</el-button>
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
                                <div class="date-notifi-item " :class="'date-notifi-item-color-birthday'" v-if="record.date == getDayMonth(data.day)" style="display: flex; align-items: center;">
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
</template>

<script lang="ts" setup>
import { ref, onMounted, computed } from 'vue'
import type { CalendarDateType } from 'element-plus'
import { ArrowLeftBold, ArrowRightBold, Calendar, Present } from '@element-plus/icons-vue'
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
};
const formState = ref<FormState>({
    event_id: [],
});

const modalAddRef = ref()
const modalConfigRef = ref()
const modalDetailRef = ref()

const dataSource = ref()
const birthday = ref()
const is_edit_calendar = ref()
const errorMessages = ref('')
const calendar = ref()
const miniCalendar = ref()
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
const selectDate = (val: CalendarDateType) => {
    if (!calendar.value) return
    calendar.value.selectDate(val)
    if (!miniCalendar.value) return
    miniCalendar.value.selectDate(val)
    
    getEventList()
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
    modalDetailRef.value.DetailCalendarMode(date);
};
const onSaved = () => {
    getEventList()
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
        formState.value.event_id = response.data.map((event:any) => event.id);
        
        _fetch();
    })
    .catch(error => {
        listEvent.value = []
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}

const getDayMonth = (date:any) => {
        const parts = date.split('-');
        if (parts.length === 3) {
            return parts[1] + '-' + parts[2];
        }
        return '';
    }

onMounted (() => {
    getEventList()
})

</script>

