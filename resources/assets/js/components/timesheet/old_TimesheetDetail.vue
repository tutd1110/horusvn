<template>
    <a-modal v-model:visible="visible" style="width:1100px; font-weight: bold" :footer="null" :maskClosable="false"
        :closable="true" :title="title">
        <a-tabs v-model:activeKey="activeKey" :tab-position="tabPosition" animated @change="onChangeTab()">
            <a-tab-pane key="1" tab="Chi tiết chấm công">
                <template v-if="!is_add_petition">
                    <a-table :dataSource="petitions" :columns="petition_columns" :pagination="false" v-if="petitions.length > 0"></a-table>
                    <a-col style="margin-top:5px;" v-if="check_in || check_in">
                        <span>Time: {{ check_in }} - {{ check_out }}</span>
                    </a-col>
                    <a-col :span="5">
                        <a-button type="primary" block v-on:click="onClickAddPettion">Thay đổi giờ chấm công</a-button>
                    </a-col>
                </template>
                <template v-else>
                    <a-row>
                        <a-col :span="6" :offset="0">
                            <label name="name">Thời gian</label>
                            <a-form-item :span="4">
                                <a-space direction="vertical">
                                    <a-time-range-picker
                                        v-model:value="timePicker"
                                        :format="timeFormat"
                                        :allowEmpty="[true,true]"
                                    />
                                </a-space>
                            </a-form-item>
                        </a-col>
                    </a-row>
                    <a-row>
                        <a-col :span="7" :offset="0">
                            <label>Lý do</label>
                            <a-textarea show-count allow-clear placeholder="Lý do" v-model:value="formState.reason" />
                        </a-col>
                        <a-col :span="2" :offset="4" style="margin-top: 22px;">
                            <a-button @click="cancel">Huỷ</a-button>
                        </a-col>
                        <a-col :span="3" :offset="0" style="margin-top: 12px;">
                            <a-button type="primary" block :onclick="onClickStorePetition">Tạo đơn</a-button>
                        </a-col>
                    </a-row>
                </template>
                <!-- table from here -->
                <a-row style="margin-top:5px;">
                    <a-col :span="24" :offset="0">
                        <a-table :indentSize="30" :dataSource="dataSource" :loading="isLoading" :columns="columns"
                            :pagination="{position: ['bottomCenter'],pageSize:5,showSizeChanger: false}">
                            <template #bodyCell="{column,record}">
                                <template v-if="column.key === 'image'" data-index="dataIndex">
                                    <a-image :src="record.detected_image_url" style="width: 150px; height: 150px; border-radius: 50%;"></a-image>
                                </template>
                            </template>
                        </a-table>
                    </a-col>
                </a-row>
            </a-tab-pane>
            <a-tab-pane key="2" tab="Chi tiết ra ngoài">
                <!-- table from here -->
                <a-row>
                    <span>Số lần ra ngoài: {{ total.total_out }}</span>
                </a-row>
                <a-row :span="2" :offset="5" style="margin-top: 15px">
                    <span>Tổng thời gian ra ngoài: {{ total.total_time }} phút</span>
                </a-row>
                <a-row style="margin-top:30px;">
                    <a-col :span="24" :offset="0">
                        <a-table :indentSize="30" :dataSource="employees" :loading="isLoading" :columns="employee_outs_columns"
                            :pagination="{position: ['bottomCenter'],pageSize:5,showSizeChanger: false}">
                            <template #bodyCell="{column,record}">
                                <template v-if="column.key === 'action'">
                                    <edit-outlined v-on:click="showEditModal(record.id, record.start_time, record.end_time)"/>
                                </template>
                            </template>
                        </a-table>
                    </a-col>
                </a-row>
                <a-modal v-model:visible="is_edit_log_goout" :maskClosable="false" :closable="false" @ok="handleOk">
                    <a-col :span="10" :offset="0">
                        <label name="name">Thời gian</label>
                        <a-form-item>
                            <a-space direction="vertical">
                                <a-time-range-picker
                                    v-model:value="timePicker"
                                    :format="timeFormat"
                                    :allowEmpty="[true,true]"
                                />
                            </a-space>
                        </a-form-item>
                    </a-col>
                    <a-col :span="24" :offset="0">
                        <label>Lý do</label>
                        <a-textarea show-count allow-clear placeholder="Lý do" v-model:value="formState.reason" />
                    </a-col>
                </a-modal>
            </a-tab-pane>
        </a-tabs>
    </a-modal>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import axios from 'axios';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import { EditOutlined } from '@ant-design/icons-vue';
import { TIME_ZONE } from '../const.js'
import { ref, h } from 'vue';
import { useI18n } from 'vue-i18n';

dayjs.extend(utc);
dayjs.extend(timezone);

export default ({
    components: {
        EditOutlined
    },
    setup(props, { emit }) {
        const { t } = useI18n();
        const errorMessages = ref();
        const activeKey = ref('1');
        const tabPosition = ref('top');
        const isLoading = ref(false);
        const title = ref("");
        const dataSource = ref([]);
        const check_in = ref("");
        const check_out = ref("");
        const is_add_petition = ref(false);
        const employees = ref([]);
        const petitions = ref([]);
        const visible = ref(false);
        const is_edit_log_goout = ref(false);
        const formState = ref([]);//form value
        const employeeId = ref();
        const selectedDate = ref("");
        const timePicker = ref([]);
        const prevOutStartTime = ref("");
        const prevOutEndTime = ref("");
        const goOutId = ref("");
        const dateFormat = 'YYYY-MM-DD';
        const timeFormat = "HH:mm:ss";
        const strictDateFormat = "YYYY/MM/DD HH:mm:ss";
        const total = ref({
            total_out: 0,
            total_time: 0,
        });
        const columns = ref([
            {
                title: 'Thời gian',
                dataIndex: 'time',
                key: 'time',
                align: 'left',
                width: 50,
            },
            {
                title: 'Người thực hiện',
                dataIndex: 'fullname',
                key: 'fullname',
                fixed: false,
                align: 'center',
                ellipsis: true,
                resizable: true,
                width: 70,
            },
            {
                title: 'Tên Camera',
                dataIndex: 'device_name',
                key: 'device_name',
                fixed: false,
                align: 'center',
                ellipsis: true,
                resizable: true,
                width: 50,
            },
            {
                title: 'Chức danh',
                dataIndex: 'person_title',
                key: 'person_title',
                fixed: false,
                align: 'center',
                width: 70,
            },
            {
                title: 'Hình ảnh',
                dataIndex: '',
                key: 'image',
                fixed: false,
                align: 'center',
                width: 70,
            }
        ]);

        const petition_columns = ref([
            {
                title: 'Loại yêu cầu',
                dataIndex: 'type_name',
                key: 'type_name',
                fixed: false,
                align: 'center',
                resizable: true,
                width: 50,
            },
            {
                title: 'Thời gian',
                dataIndex: 'info',
                key: 'info',
                fixed: false,
                align: 'center',
                resizable: true,
                width: 50,
            },
            {
                title: 'Lý do',
                dataIndex: 'reason',
                key: 'reason',
                fixed: false,
                align: 'center',
                width: 90,
            },
        ]);

        const employee_outs_columns = ref([
            {
                title: 'Nhân viên',
                dataIndex: 'fullname',
                key: 'fullname',
                fixed: false,
                align: 'center',
                ellipsis: true,
                resizable: true,
                width: 70,
            },
            {
                title: 'Thời gian bắt đầu ra ngoài',
                dataIndex: 'start_time',
                key: 'start_time',
                fixed: false,
                align: 'center',
                ellipsis: true,
                resizable: true,
                width: 50,
            },
            {
                title: 'Thời gian kết thúc',
                dataIndex: 'end_time',
                key: 'end_time',
                fixed: false,
                align: 'center',
                width: 50,
            },
            {
                title: 'Ngày',
                dataIndex: 'date',
                key: 'date',
                fixed: false,
                align: 'center',
                width: 50,
            },
            {
                title: 'Action',
                dataIndex: '',
                key: 'action',
                align: 'center',
                width: 18,
            },
        ]);

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
        const ShowWithDetailMode = (id, fullname, date) => {
            title.value = "Chi tiết chấm công ngày: "+fullname+" - "+date
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
                    user_id: employeeId.value
                }
            })
            .then(response => {
                isLoading.value = false;
                dataSource.value = response.data.timesheets

                let log = response.data.log
                check_in.value = log.check_in
                check_out.value = log.check_out

                if (log.start_time != null && log.end_time != null) {
                    check_in.value = log.start_time
                    check_out.value = log.end_time
                }

                petitions.value = response.data.petitions
            })
            .catch(error => {
                isLoading.value = false;
                dataSource.value = []
                petitions.value = []
            })
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

        const transferData = (data) => {
            var newData = [];

            total.value.total_out = 0;
            total.value.total_time = 0;

            data.forEach(function(item, index) {
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
            if (activeKey.value == '1') {
                _search()
            } else if (activeKey.value == '2') {
                getLogEmployeeOuts()
            }
        }

        const onClickAddPettion = () => {
            is_add_petition.value = true;
            timePicker.value = [
                check_in.value ? dayjs(check_in.value, timeFormat) : null,
                check_out.value ? dayjs(check_out.value, timeFormat) : null
            ]
        }

        const onClickStorePetition = () => {
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
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(false);//show error message modally
            })
        }

        const handleTimePicker = () => {
            if (timePicker.value !== null && timePicker.value !== undefined) {
                var startTime = 0;
                var processedStartTime = '';
                    //is the start time entered?
                if (timePicker.value[startTime] !== null && timePicker.value[startTime] !== undefined) {
                    processedStartTime = timePicker.value[startTime];
                    formState.value.start_time = dayjs(processedStartTime).format(timeFormat);
                }
                
                var endTime = 1;
                var processedEndTime = '';
                    //is the end time entered?
                if (timePicker.value[endTime] !== null && timePicker.value[endTime] !== undefined) {
                    processedEndTime = timePicker.value[endTime];
                    formState.value.end_time = dayjs(processedEndTime).format(timeFormat);
                }
            } else {
                formState.value.start_time = null;
                formState.value.end_time = null;
            }
        }

        const showEditModal = (id, start_time, end_time) => {
            is_edit_log_goout.value = true

            goOutId.value = id
            prevOutStartTime.value = start_time
            prevOutEndTime.value = end_time

            timePicker.value = [
                start_time ? dayjs(start_time, timeFormat) : null,
                end_time ? dayjs(end_time, timeFormat) : null
            ]
        }

        const handleOk = () => {
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
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(false);//show error message modally
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
                content: h('ul', {}, errorMessages.value.split('<br>').map((error) => { return h('li', error) })),
            });
        };

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        return {
            activeKey,
            tabPosition,
            isLoading,
            formState,
            columns,
            petition_columns,
            timeFormat,
            is_add_petition,
            employee_outs_columns,
            total,
            cancel,
            title,
            visible,
            is_edit_log_goout,
            timePicker,
            dataSource,
            petitions,
            check_in,
            check_out,
            employees,
            ShowWithDetailMode,
            onChangeTab,
            onClickAddPettion,
            onClickStorePetition,
            showEditModal,
            handleOk
        };
    }
})
</script>