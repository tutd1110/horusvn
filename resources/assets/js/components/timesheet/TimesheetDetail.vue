<template>
    <DialogPetition ref="dialogRefPetition" @saved="_search"></DialogPetition>
    <el-dialog v-model="visible" style="width:70%;" :title="title"  align-center center>
        <el-tabs v-model="activeKey"  @tab-click="onChangeTab()">
            <el-tab-pane name="1" label="Chi tiết chấm công">
                <el-button v-on:click="showRegisterModalPetition()" type="primary">Tạo yêu cầu</el-button>
                <template v-if="!is_add_petition">
                    <el-table :data="petitions" style="width: 100%" height="180" v-if="petitions.length > 0" class="timesheet-table-custom">
                        <el-table-column align="center" prop="type_name" label="Loại yêu cầu" width="230"/>
                        <el-table-column align="center" prop="info" label="Thời gian" width="280" />
                        <el-table-column align="center" prop="reason" label="Lí do" width="400"/>
                        <el-table-column align="center" prop="status" label="Trạng thái"  width="180"/>
                        <el-table-column align="center" prop="rejected_reason" label="Phản hồi"/>
                        <!-- <el-table-column align="center" label="Action">
                            <template #default="scope">
                                <Edit style="width: 16px; cursor: pointer; color:#909399; margin-right: 5px;"  v-on:click="showEditModalPetition(scope.row)"/>
                                <Delete style="width: 16px; cursor: pointer; color:red;"  v-on:click=""/>
                            </template>
                        </el-table-column> -->
                    </el-table>
                    <div style="margin-top:5px;" v-if="check_in || check_in">
                        <span>Time: {{ check_in ?? '' }} - {{ check_out ?? '' }}</span>
                        <span v-if="final_checkout == true" style="font-weight: 700; color: red;"> (Đã bấm check out lúc: {{ check_out }})</span>
                    </div>
                    <div style="margin-top:10px;">
                        <el-button type="primary" @click="onClickAddPettion">Thay đổi giờ chấm công</el-button>
                    </div>
                </template>
                <template v-else>
                    <el-row :gutter="20">
                        <!-- <el-col :span="6" :offset="0">
                            <label name="name">Thời gian</label>
                            <el-time-picker
                                v-model="timePicker"
                                is-range
                                range-separator="-"
                                start-placeholder="Start time"
                                end-placeholder="End time"
                                style="width:100%"
                            />
                        </el-col> -->
                        <el-col :span="3" style="margin-bottom: 20px;">
                            <label name="name">Thời gian bắt đầu</label>
                            <el-time-picker
                                v-model="check_in_change"
                                placeholder="Start time"
                                style="width:100%"
                                value-format="HH:mm:ss"
                                :disabled-hours="disabledHoursCheckIn"
                                :disabled-minutes="disabledMinutesCheckIn"
                                :disabled-seconds="disabledSecondsCheckIn"
                            />
                        </el-col>
                        <el-col :span="3" style="margin-bottom: 20px;">
                            <label name="name">Thời gian kết thúc</label>
                            <el-time-picker
                                v-model="check_out_change"
                                placeholder="End time"
                                style="width:100%"
                                value-format="HH:mm:ss"
                                :disabled-hours="disabledHoursCheckOut"
                                :disabled-minutes="disabledMinutesCheckOut"
                                :disabled-seconds="disabledSecondsCheckOut"
                            />
                        </el-col>
                        <el-col :span="13" :offset="0">
                            <label>Lý do</label>
                            <el-input
                                v-model="formState.reason"
                                placeholder="Lí do"
                                autosize
                                show-word-limit
                                type="textarea"
                            />
                        </el-col>
                        <el-col :span="5" style="margin-top: 22px;">
                            <el-button style="margin-left: 30px; width:100px" type="primary" :onclick="onClickStorePetition">Tạo đơn</el-button>
                            <el-button style=" width:100px" @click="cancel">Huỷ</el-button>
                        </el-col>
                    </el-row>
                </template>
                <!-- table from here -->
                <el-row style="margin-top:5px;">
                    <el-col :span="24" :offset="0">
                        <el-table :data="dataSource" style="width: 100%" height="380" class="timesheet-table-custom">
                            <el-table-column align="center" prop="time" label="Thời gian"/>
                            <el-table-column align="center" prop="fullname" label="Ngươi thực hiện"  />
                            <el-table-column align="center" prop="device_name" label="Tên camera" />
                            <el-table-column align="center" prop="person_title" label="Chức danh" />
                            <el-table-column align="center" prop="image" label="Hình ảnh">
                                <template #default="scope">
                                    <el-image
                                        style="width: 150px; height: 150px; border-radius: 50%;"
                                        :src="scope.row.detected_image_url"
                                        :zoom-rate="1.2"
                                        :preview-src-list="scope.row.detected_image_array"
                                        fit="cover"
                                        preview-teleported
                                    />
                                </template>
                            </el-table-column>
                        </el-table>
                    </el-col>
                </el-row>
                <el-col style="margin-top: 20px;display: flex;align-items: center;justify-content: center;">
                    <el-pagination
                        v-model="formState.current_page"
                        :page-size="formState.per_page"
                        background
                        :total="totalPage"
                        @current-change="onChangePage"
                    />
                </el-col>
            </el-tab-pane>
            <el-tab-pane name="2" label="Chi tiết ra ngoài">
                <!-- table from here -->
                <el-row>
                    <span>Số lần ra ngoài: {{ total.total_out }}</span>
                </el-row>
                <el-row :span="2" :offset="5" style="margin-top: 15px">
                    <span>Tổng thời gian ra ngoài: {{ total.total_time }} phút</span>
                </el-row>
                <el-row style="margin-top:30px;">
                    <el-col :span="24" :offset="0">
                        <el-table :data="employees" style="width: 100%" height="350" class="timesheet-table-custom">
                            <el-table-column align="center" prop="fullname" label="Nhân viên"  />
                            <el-table-column align="center" prop="start_time" label="Thời gian bắt đầu ra ngoài"/>
                            <el-table-column align="center" prop="end_time" label="Thời gian kết thúc" />
                            <el-table-column align="center" prop="date" label="Ngày" />
                            <el-table-column align="center" prop="action" label="Action">
                                <template #default="scope">
                                    <edit-outlined v-on:click="showEditModal(scope.row.id, scope.row.start_time, scope.row.end_time)"/>
                                </template>
                            </el-table-column>
                        </el-table>
                    </el-col>
                </el-row>
                <el-dialog v-model="is_edit_log_goout" style="width:500px;" :maskClosable="false" :closable="false">
                    <el-row :gutter="20" style="margin-bottom: 0;">
                        <!-- <el-col :span="24" style="margin-bottom: 20px;">
                            <label name="name">Thời gian</label>
                            <el-time-picker
                                v-model="timePicker"
                                is-range
                                range-separator="-"
                                start-placeholder="Start time"
                                end-placeholder="End time"
                                style="width:100%"
                            />
                        </el-col> -->
                        <el-col :span="12" style="margin-bottom: 20px;">
                            <label name="name">Thời gian bắt đầu</label>
                            <el-time-picker
                                v-model="start_time_change"
                                placeholder="Start time"
                                style="width:100%"
                                value-format="HH:mm:ss"
                            />
                        </el-col>
                        <el-col :span="12" style="margin-bottom: 20px;">
                            <label name="name">Thời gian kết thúc</label>
                            <el-time-picker
                                v-model="end_time_change"
                                placeholder="End time"
                                style="width:100%"
                                value-format="HH:mm:ss"
                            />
                        </el-col>
                        <el-col :span="24" style="margin-bottom: 20px;">
                            <label>Lý do</label>
                            <el-input
                                v-model="formState.reason"
                                placeholder="Lí do"
                                autosize
                                show-word-limit
                                type="textarea"
                            />
                        </el-col>
                        <el-col :span="24" style="margin-bottom: 20px;">
                            <el-button style="margin-left: 30px; width:120px; float: right;" type="primary" :onclick="handleOk">Chỉnh sửa</el-button>
                            <el-button style=" width:120px; float: right;" @click="is_edit_log_goout = false">Huỷ</el-button>
                        </el-col>
                    </el-row>
                </el-dialog>
            </el-tab-pane>
        </el-tabs>
    </el-dialog>
</template>
<script lang="ts" setup>
    import { Modal, notification } from 'ant-design-vue';
    import axios from 'axios';
    import dayjs from 'dayjs';
    import utc from 'dayjs/plugin/utc'
    import timezone from 'dayjs/plugin/timezone'
    import { EditOutlined } from '@ant-design/icons-vue';
    import { TIME_ZONE } from '../const.js'
    import { ref, h } from 'vue';
    import { useI18n } from 'vue-i18n';
    import log from '../log/log.vue';
    import DialogPetition from '../petition/Dialog.vue';
    import { Delete, Edit } from '@element-plus/icons-vue'

    const dialogRefPetition = ref()
    const showRegisterModalPetition = () => {
        dialogRefPetition.value.ShowWithAddModeTimeSheet(employeeId.value,dayjs(selectedDate.value).format("YYYY/MM/DD"));
    };
    const showEditModalPetition = (item: any) => {
        const check_updated_at = dayjs().format('YYYY/MM/DD HH:mm:ss')
        dialogRefPetition.value.ShowWithUpdateMode(item.id, check_updated_at);
    }

    dayjs.extend(utc);
    dayjs.extend(timezone);

    interface FormState {
        reason?: string,
        start_time?: string | null,
        end_time?: string | null,
        current_page: number,
        per_page: number,
    }
    const { t } = useI18n();
    const errorMessages = ref();
    const activeKey = ref('1');
    const isLoading = ref(false);
    const title = ref("");
    const dataSource = ref([]);
    const check_in = ref("");
    const check_out = ref("");
    const check_in_change = ref("");
    const check_out_change = ref("");
    const start_time_change = ref("");
    const end_time_change = ref("");
    const final_checkout = ref();
    const is_add_petition = ref(false);
    const employees = ref([]);
    const petitions = ref([]);
    const visible = ref(false);
    const is_edit_log_goout = ref(false);
    const formState = ref<FormState>({
        current_page: 1,
        per_page: 5,
    })
    const totalPage = ref(0);
    const employeeId = ref();
    const selectedDate = ref("");
    const timePicker = ref<any[]>([]);
    const prevOutStartTime = ref("");
    const prevOutEndTime = ref("");
    const goOutId = ref("");
    const dateFormat = 'YYYY-MM-DD';
    const timeFormat = "HH:mm:ss";
    const total = ref({
        total_out: 0,
        total_time: 0,
    });

    const formClear = () => {
        dataSource.value = [];
        employeeId.value = "";
        selectedDate.value = "";
        activeKey.value = '1'
        is_add_petition.value = false
        petitions.value = [];
        formState.value.reason = ""
        prevOutStartTime.value = ""
        prevOutEndTime.value = ""
        goOutId.value = ""
    };

    //update mode
    const ShowWithDetailMode = (id:number, fullname:string, date:string) => {
        title.value = fullname+" - Chi tiết chấm công ngày "+dayjs(date).format("DD/MM/YYYY")
        visible.value = true;
        formClear();

        employeeId.value = id
        selectedDate.value = date
        _search()
    };

    const _search = () => {
        isLoading.value = true;
        //get timesheet detail by selected date
        axios
        .get('/api/timesheet/detail/get_timesheet_detail', {
            params: {
                date: selectedDate.value,
                user_id: employeeId.value,
                current_page: formState.value.current_page,
                per_page: formState.value.per_page,
            }
        })
        .then(response => {
            // isLoading.value = false;
            dataSource.value = response.data.timesheets
            
            let log = response.data.log
            check_in.value = log.check_in
            check_out.value = log.check_out
            final_checkout.value = log.final_checkout

            if (log.start_time != null && log.end_time != null) {
                check_in.value = log.start_time
                check_out.value = log.end_time
            }

            petitions.value = response.data.petitions

            formState.value.current_page = response.data.currentPage
            totalPage.value = response.data.totalItems
        })
        .catch(error => {
            // isLoading.value = false;
            dataSource.value = []
            petitions.value = []
        })
    }
    const onChangePage = (page: number) => {
        formState.value.current_page = page

        _search()
    }

    const getLogEmployeeOuts = () => {
        isLoading.value = true;
        //get timesheet detail by selected date
        axios
        .get('/api/employee/get_log_employee_outs', {
            params: {
                date: selectedDate.value,
                user_id: employeeId.value
            }
        })
        .then(response => {
            isLoading.value = false;
            employees.value = transferData(response.data)
        })
        .catch(error => {
            isLoading.value = false;
            employees.value = []
        })
    }

    const transferData = (data:any) => {
        var newData:any = [];

        total.value.total_out = 0;
        total.value.total_time = 0;

        data.forEach(function(item:any, index:any) {
            total.value.total_out++;

            let strStartTime = dayjs(item.date).tz(TIME_ZONE.ZONE).format(dateFormat) +" "+ item.start_time
            let strEndTime = dayjs(item.date).tz(TIME_ZONE.ZONE).format(dateFormat) +" "+ item.end_time

            total.value.total_time += dayjs(strEndTime).diff(dayjs(strStartTime), 'minutes', true);

            let value = {
                id: item.id,
                fullname: item.fullname,
                start_time: item.start_time,
                end_time: item.end_time,
                date: dayjs(item.date).format(dateFormat)
            };
            newData.push(value);
        });

        return newData;
    }

    const onChangeTab = () => { 
        if (activeKey.value == '2') {
            _search()
        } else if (activeKey.value == '1') {
            getLogEmployeeOuts()
        }
    }

    const onClickAddPettion = () => {
        is_add_petition.value = true;

        check_in_change.value = check_in.value ? dayjs(check_in.value, timeFormat).format(timeFormat) : '';
        check_out_change.value = check_out.value ? dayjs(check_out.value, timeFormat).format(timeFormat) : '';
    }

    const disabledHoursCheckIn = () => {
        if (check_out_change.value) {
            return makeRange(check_out_change.value ? dayjs(check_out_change.value, timeFormat).hour()+1 : 0 , 23)
        }
    }
    const disabledMinutesCheckIn = (hour: number) => {
        if (hour === dayjs(check_out_change.value, timeFormat).hour()) {
            return makeRange(dayjs(check_out_change.value, timeFormat).minute()+1 , 59)
        }
    }
    const disabledSecondsCheckIn = (hour: number, minute: number) => {
        if (hour === dayjs(check_out_change.value, timeFormat).hour() && minute === dayjs(check_out_change.value, timeFormat).minute()) {
            return makeRange(dayjs(check_out_change.value, timeFormat).second() , 59)
        }
    }

    const disabledHoursCheckOut = () => {
        return makeRange(0, check_in_change.value ? dayjs(check_in_change.value, timeFormat).hour()-1 : 0)
    }
    const disabledMinutesCheckOut = (hour: number) => {
        if (hour === dayjs(check_in_change.value, timeFormat).hour()) {
            return makeRange(0, dayjs(check_in_change.value, timeFormat).minute()-1)
        }
    }
    const disabledSecondsCheckOut = (hour: number, minute: number) => {
        if (hour === dayjs(check_in_change.value, timeFormat).hour() && minute === dayjs(check_in_change.value, timeFormat).minute()) {
            return makeRange(0, dayjs(check_in_change.value, timeFormat).second())
        }
    }
    const makeRange = (start: number, end: number) => {
        const result: number[] = []
        for (let i = start; i <= end; i++) {
            result.push(i)
        }
        return result
    }

    const onClickStorePetition = () => {
        timePicker.value = [
            check_in_change.value ? check_in_change.value : '',
            check_out_change.value ? check_out_change.value : '',
        ]
        handleTimePicker()

        let submitData = {
            type: 4,
            type_off: " ",
            type_paid: " ",
            start_time: check_in.value,
            end_time: check_out.value,
            start_time_change: formState.value.start_time ? formState.value.start_time : "",
            end_time_change: formState.value.end_time ? formState.value.end_time : "",
            user_id: employeeId.value,
            reason: formState.value.reason,
            start_date: selectedDate.value
        };

        axios.post('/api/petition/store', submitData)
        .then(response => {
            notification.success({
                message: t('message.MSG-TITLE-W'),
                description: response.data.success,
            });
            is_add_petition.value = false
            _search()
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;//put message content in ref
            errorModal();//show error message modally
        })
    }

    const handleTimePicker = () => {
        
        if (timePicker.value !== null && timePicker.value !== undefined) {

            if (timePicker.value[0] !== null && timePicker.value[0] !== undefined) {
                formState.value.start_time = timePicker.value[0];
            }
            if (timePicker.value[1] !== null && timePicker.value[1] !== undefined) {
                formState.value.end_time = timePicker.value[1];
            }
        } else {
            formState.value.start_time = null;
            formState.value.end_time = null;
        }
    }

    const showEditModal = (id:string, start_time:string, end_time:string) => {
        is_edit_log_goout.value = true

        goOutId.value = id
        prevOutStartTime.value = start_time
        prevOutEndTime.value = end_time

        start_time_change.value =  start_time ? dayjs(start_time, timeFormat).format(timeFormat) : ''
        end_time_change.value  = end_time ? dayjs(end_time, timeFormat).format(timeFormat) : ''
    }

    const handleOk = () => {
        timePicker.value = [
            start_time_change.value ? start_time_change.value : '',
            end_time_change.value ? end_time_change.value : ''
        ]
        handleTimePicker()

        let submitData = {
            type: 8,
            type_off: " ",
            type_paid: " ",
            user_go_out_id: goOutId.value,
            start_time: prevOutStartTime.value,
            end_time: prevOutEndTime.value,
            start_time_change: formState.value.start_time ? formState.value.start_time : "",
            end_time_change: formState.value.end_time ? formState.value.end_time : "",
            user_id: employeeId.value,
            reason: formState.value.reason,
            start_date: selectedDate.value
        };

        axios.post('/api/petition/store', submitData)
        .then(response => {
            notification.success({
                message: t('message.MSG-TITLE-W'),
                description: response.data.success,
            });

            is_edit_log_goout.value = false
            _search()
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;//put message content in ref
            errorModal();//show error message modally
        })
    }

    const cancel = () => {
        formState.value.reason = "";
        is_add_petition.value = false;
        timePicker.value = [];
    };

    const errorModal = () => {
        Modal.warning({
            title: t('message.MSG-TITLE-W'),
            content: h('ul', {}, errorMessages.value.split('<br>').map((error:any) => { return h('li', error) })),
        });
    };

    notification.config({
        placement: 'bottomLeft',
        duration: 3,
        rtl: true,
    });

    defineExpose({
        ShowWithDetailMode
    });
</script>