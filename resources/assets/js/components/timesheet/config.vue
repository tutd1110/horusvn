<template>
    <a-modal v-model:visible="visible" style="width:1500px; font-weight: bold" :footer="null" :maskClosable="false" :closable="true" :title="title">
        <a-tabs v-model:activeKey="activeKey" :tab-position="tabPosition" animated @change="onChangeTab()">
            <a-tab-pane key="1" tab="Kết nối Camera AI">
                <a-tabs v-model:activeKey="cameraKey" :tab-position="cameraTabPosition" animated @change="onChangeCameraTab()">
                    <a-tab-pane key="1" tab="Quản lý FaceId">
                        <a-row style="margin-top:20px; margin-bottom:30px;">
                            <a-col :span="3" :offset="0" style="margin-top: 12px;">
                                <single-submit-button type="primary" block :onclick="onChangeSyncEmployee">Đồng bộ người dùng</single-submit-button>
                            </a-col>
                        </a-row>
                    </a-tab-pane>
                    <a-tab-pane key="2" tab="Quản lý Camera">
                        <a-row style="margin-top:20px; margin-bottom:30px;">
                            <a-col :span="3" :offset="21" style="margin-top: 12px;">
                                <single-submit-button type="primary" block :onclick="onChangeSyncDevice">Đồng bộ thiết bị</single-submit-button>
                            </a-col>
                        </a-row>
                        <a-row>
                            <a-table :dataSource="devices" :columns="device_columns" style = "white-space:pre-wrap"
                            :pagination="{position: ['bottomCenter'],pageSize:5,showSizeChanger: false}">
                                <template #bodyCell="{column,record}"></template>
                            </a-table>
                        </a-row>
                    </a-tab-pane>
                    <a-tab-pane key="3" tab="Kết nối Camera AI">
                        <a-row>
                            <a-col :span="10">
                                <label>Client ID</label>
                                <a-input disabled :value="config.client_id"/>
                            </a-col>
                        </a-row>
                        <a-row>
                            <a-col :span="10">
                                <label>Client Secret</label>
                                <a-input disabled :value="config.client_secret"/>
                            </a-col>
                        </a-row>
                        <a-row v-if="config.id">
                            <a-col :span="2" style="margin-top: 15px">
                                <a-button danger v-on:click="onClickDisconnect()">Ngắt kết nối</a-button>
                            </a-col>
                        </a-row>
                    </a-tab-pane>
                </a-tabs>
            </a-tab-pane>
            <a-tab-pane key="2" tab="Đồng bộ giờ công từ Hanet">
                <a-row style="margin-top:20px; margin-bottom:30px;">
                    <a-col style="width:300px">
                        <label>Thời gian</label>
                        <a-space direction="vertical">
                            <a-range-picker 
                                v-model:value="datePeriod" 
                                :allowEmpty="[true,true]" 
                                :format="dateFormat" 
                                style="width:300px;"
                            />
                        </a-space>
                    </a-col>
                    <div style="margin:28px 0px 0px 30px;" class="form-check">
                        <input class="form-check-input" v-model="option" value="1" type="radio" name="flexRadioDefault"
                            id="flexRadioDefault1">
                        <label class="form-check-label" for="flexRadioDefault1"> Cả công ty</label> &emsp;&emsp;&emsp;
                        <input class="form-check-input" type="radio" v-model="option" value="2" name="flexRadioDefault"
                            id="flexRadioDefault2">
                        <label class="form-check-label" for="flexRadioDefault2"> Chọn nhân viên</label>
                    </div>
                </a-row>
                <a-row>
                    <a-col style="width:300px; margin-right:30px;">
                        <label name="name">Thiết bị</label>
                        <a-form-item :span="1">
                            <a-select
                                    ref="select"
                                    v-model:value="formState.device_code"
                                    allow-clear
                                    style="width:100%;"
                                    :options="deviceSelectbox"
                                    :field-names="{ label:'name', value: 'code' }"
                            ></a-select>
                        </a-form-item>
                    </a-col>
                    <a-col v-if="option == 2">
                        <label>Nhân viên</label>
                        <a-form-item>
                            <el-select
                                v-model="formState.user_codes"
                                multiple
                                filterable
                                collapse-tags
                                style="width: 100%"
                            >
                                <el-option
                                    v-for="item in userSelectbox"
                                    :key="item.user_code"
                                    :label="item.fullname"
                                    :value="item.user_code"
                                />
                            </el-select>
                        </a-form-item>
                    </a-col>
                </a-row>
                <a-row>
                    
                </a-row>
                <a-row>
                    <a-col :span="3" :offset="0" style="margin-top: 12px;">
                        <single-submit-button type="primary" block :onclick="onClickSyncButton">Đồng bộ</single-submit-button>
                    </a-col>
                </a-row>
            </a-tab-pane>
            <a-tab-pane key="3" tab="Lịch nghỉ trong năm">
                <a-row style="margin-top:20px; margin-bottom:30px;">
                    <template v-if="is_add_holiday">
                        <a-col style="width:500px; margin-right:20px">
                            <label>Tiêu đề</label>
                            <a-input allow-clear placeholder="Tiêu đề" v-model:value="formState.name" />
                        </a-col>
                        <a-col style="width:500px;">
                            <label>Thời gian nghỉ</label>
                            <a-space direction="vertical">
                                <a-range-picker 
                                v-model:value="datePeriod" 
                                :allowEmpty="[true,true]" 
                                :format="dateFormat" 
                                style="width: 500px;"
                                />
                            </a-space>
                        </a-col>
                        <a-col style="margin: 22px 20px 0px 200px;">
                            <a-button style="width:100px" @click="cancel">Huỷ</a-button>
                        </a-col>
                        <a-col style="margin-top: 12px;">
                            <a-button style="width:100px" type="primary" block :onclick="onClickStoreButton">Thêm</a-button>
                        </a-col>
                    </template>
                    <template v-else>
                        <a-col :span="3" :offset="21" style="margin-top: 12px;">
                            <a-button type="primary" block v-on:click="onChangeAddHoliday">Thêm ngày nghỉ</a-button>
                        </a-col>
                    </template>
                </a-row>
                <a-row>
                    <a-table :dataSource="holidays" :columns="holiday_columns" :loading="isLoading" style = "white-space:pre-wrap"
                    :pagination="{position: ['bottomCenter'],pageSize:5,showSizeChanger: false}">
                        <template #bodyCell="{column,record}">
                                <template v-if="column.key === 'name'" data-index="name">
                                    <a-input v-model:value="record.name" @blur="onChangeName(record.id, $event)"/>
                                </template>
                                <template v-if="column.key === 'start_date'" data-index="start_date">
                                    <a-date-picker
                                        v-model:value="record.start_date"
                                        :format="dateFormat"
                                        @change="onChangeStartDate(record.id, $event)"
                                    />
                                </template>
                                <template v-if="column.key === 'end_date'" data-index="end_date">
                                    <a-date-picker
                                        v-model:value="record.end_date"
                                        :format="dateFormat"
                                        @change="onChangeEndDate(record.id, $event)"
                                    />
                                </template>
                                <template v-if="column.key === 'action'" data-index="dataIndex">
                                    <a-button danger v-on:click="onClickDeleteButton(record.id)">Xoá</a-button>
                                </template>
                            </template>
                    </a-table>
                </a-row>
            </a-tab-pane>
            <a-tab-pane key="4" tab="Lịch làm bù">
                <el-table :data="tableData" style="width: 100%" table-layout="auto">
                    <el-table-column label="Date" width="200">
                        <template #default="scope">
                            <el-date-picker
                                v-model="scope.row.offset_date"
                                type="date"
                                format="DD-MM-YYYY"
                                value-format="YYYY-MM-DD"
                                class="none-border"
                                style="width: 100%;"
                                :clearable="false"
                                @change="onChangeSelect(scope.row, 'offset_date', $event)"
                            />
                        </template>
                    </el-table-column>
                    <el-table-column label="Start" width="20">
                        <template #default="scope">
                            <el-time-picker
                                v-model="scope.row.offset_start_time"
                                arrow-control
                                class="none-border"
                                :clearable="false"
                                @change="onChangeSelect(scope.row, 'offset_start_time', $event)"
                            />
                        </template>
                    </el-table-column>
                    <el-table-column label="End" width="20">
                        <template #default="scope">
                            <el-time-picker
                                v-model="scope.row.offset_end_time"
                                arrow-control
                                class="none-border"
                                :clearable="false"
                                @change="onChangeSelect(scope.row, 'offset_end_time', $event)"
                            />
                        </template>
                    </el-table-column>
                    <el-table-column label="Workday" width="150">
                        <template #default="scope">
                            <el-input
                                v-model="scope.row.workday"
                                type="number"
                                @change="onChangeSelect(scope.row, 'workday', $event)"
                                class="none-border"
                            />
                        </template>
                    </el-table-column>
                    <el-table-column label="Holiday" width="450">
                        <template #default="scope">
                            <el-select
                                v-model="scope.row.holiday_id"
                                class="none-border"
                                style="width:100%;"
                                clearable
                                filterable
                                @change="onChangeSelect(scope.row, 'holiday_id', $event)"
                            >
                                <el-option
                                    v-for="item in holidays"
                                    :key="item.id"
                                    :label="item.name"
                                    :value="item.id"
                                />
                            </el-select>
                        </template>
                    </el-table-column>
                    <el-table-column label="Reason" width="520">
                        <template #default="scope">
                            <el-input
                                v-model="scope.row.reason"
                                autosize
                                type="textarea"
                                class="none-border"
                                @change="onChangeSelect(scope.row, 'reason', $event)"
                            />
                        </template>
                    </el-table-column>
                    <el-table-column fixed="right" label="Operations" width="110">
                        <template #default="scope">
                            <el-button
                                link
                                type="primary"
                                size="small"
                                @click.prevent="deleteRow(scope.row.id)"
                            >
                            Remove
                            </el-button>
                        </template>
                    </el-table-column>
                </el-table>
                <el-button class="mt-4" style="width: 100%" @click="onAddItem">Add Item</el-button>
            </a-tab-pane>
        </a-tabs>
    </a-modal>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import axios from 'axios';
import { ref, h } from 'vue';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import { TIME_ZONE } from '../const.js'
import { useI18n } from 'vue-i18n';
import { callMessage } from '../Helper/el-message.js';
import SingleSubmitButton from '../Shared/SingleSubmitButton/SingleSubmitButton.vue';

dayjs.extend(utc);
dayjs.extend(timezone);

export default ({
    components: {
        SingleSubmitButton,
    },
    emits: ['saved'],
    setup(props, { emit }) {
        const tabPosition = ref('top');
        const config = ref({});
        const cameraTabPosition = ref('top');
        const option = ref(1);
        const activeKey = ref('1');
        const cameraKey = ref('1');
        const holidays = ref([]);
        const devices = ref([]);
        const isLoading = ref(false);
        const { t } = useI18n();
        const is_add_holiday = ref(false);
        const errorMessages = ref();
        const title = ref("");
        const visible = ref(false);
        const datePeriod = ref([]);
        const formState = ref([]);//form value
        const deviceSelectbox = ref([]);
        const userSelectbox = ref([]);
        const mode = ref("");//New mode or edit mode or change
        const dateFormat = "YYYY/MM/DD";
        const holiday_columns = ref([
            {
                title: 'Tiêu đề',
                dataIndex: 'name',
                key: 'name',
                fixed: false,
                align: 'center',
                width: 400,
            },
            {
                title: 'Ngày bắt đầu',
                dataIndex: 'start_date',
                key: 'start_date',
                fixed: false,
                align: 'center',
                width: 300,
            },
            {
                title: 'Ngày kết thúc',
                dataIndex: 'end_date',
                key: 'end_date',
                fixed: false,
                align: 'center',
                width: 300,
            },
            {
                title: 'Số ngày',
                dataIndex: 'days',
                key: 'days',
                fixed: false,
                align: 'center',
                width: 300,
            },
            {
                title: 'Thao tác',
                dataIndex: '',
                key: 'action',
                align: 'center',
                width: 200,
            },
        ]);

        const device_columns = ref([
            {
                title: 'Tên Camera',
                dataIndex: 'name',
                key: 'name',
                fixed: false,
                align: 'center',
                width: 500,
            },
            {
                title: 'Loại ghi nhận',
                dataIndex: 'code',
                key: 'code',
                fixed: false,
                align: 'center',
                width: 500,
            },
            {
                title: 'Thao tác',
                dataIndex: 'type_text',
                key: 'type_text',
                fixed: false,
                align: 'center',
                width: 500,
            }
        ]);

        const cancel = () => {
            // visible.value = false;
            is_add_holiday.value = false
        }

        const tableData = ref([])
        const deleteRow = (id) => {
            axios.delete('/api/holiday_offsets/delete', {
                params: {
                    id: id
                }
            })
            .then(response => {
                getHolidayOffsets();
            })
            .catch(error => {
                getHolidayOffsets();
                errorMessages.value = error.response.data.errors;
                callMessage(errorMessages.value, 'error');
            })
        }

        const onAddItem = () => {
            axios.post('/api/holiday_offsets/store').then(response => {
                getHolidayOffsets()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                callMessage(errorMessages.value, 'error');
            })
        }

        //select box list generation
        const CreateSelbox = () => {
            //users
            axios.get('/api/device/get_devices')
            .then(response => {
                deviceSelectbox.value = response.data.devices;
                userSelectbox.value = response.data.users;
            })
        };

        //new mode
        const ShowWithConfigMode = () => {
            mode.value = "Config";
            title.value = "Cấu hình chấm công"
            visible.value = true;
        };

        const onChangeAddHoliday = () => {
            is_add_holiday.value = true
        }

        const onChangeTab = () => {
            if (activeKey.value == '3') {
                getHolidays()
            } else if (activeKey.value == '2') {
                CreateSelbox()
            } else if (activeKey.value == '4') {
                getHolidays()
                getHolidayOffsets()
            }
        }

        const onChangeCameraTab = () => {
            if (cameraKey.value == '3') {
                getConfig();
            } else if (cameraKey.value == '2') {
                getDevicesInfo();
            }
        }

        const getDevicesInfo = () => {
            //get holidays
            axios
            .get('/api/device/get_devices_info')
            .then(response => {
                devices.value = response.data
            })
            .catch(error => {
                devices.value = {};
                errorMessages.value = error.response.data.errors;
                errorModal();
            })
        }

        const getConfig = () => {
            //get holidays
            axios
            .get('/api/partner/get_config')
            .then(response => {
                config.value = response.data
            })
            .catch(error => {
                config.value = {};
                errorMessages.value = error.response.data.errors;
                errorModal();
            })
        }

        const getHolidays = () => {
            //get holidays
            axios
            .get('/api/holiday/get_holidays')
            .then(response => {
                holidays.value = transferData(response.data)
            })
            .catch(error => {
                holidays.value = [];
                errorMessages.value = error.response.data.errors;
                errorModal();
            })
        }

        const getHolidayOffsets = () => {
            //get holidays
            axios
            .get('/api/holiday_offsets/list')
            .then(response => {
                tableData.value = response.data
            })
            .catch(error => {
                tableData.value = [];
                errorMessages.value = error.response.data.errors;
                errorModal();
            })
        }
        const onChangeSelect = (item, column, value) => {
            let submitData = {
                id: item.id,
                [column]: value ? value : ""
            }
            if (column === 'offset_start_time' || column === 'offset_end_time') {
                submitData[column] = dayjs(value).tz(TIME_ZONE.ZONE).format('YYYY/MM/DD H:mm:ss')
            }

            axios.patch('/api/holiday_offsets/update', submitData)
            .then(response => {
                callMessage(response.data.success, 'success');
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                callMessage(errorMessages.value, 'error');
            })
        }

        const transferData = (data) => {
            var newData = [];

            data.forEach(function(item, index) {
                let startDate = dayjs(item.start_date).tz(TIME_ZONE.ZONE).format(dateFormat)
                let endDate = dayjs(item.end_date)
                let total_days = endDate.diff(startDate, 'day') + 1

                let value = {
                    id: item.id,
                    name: item.name,
                    start_date: dayjs(item.start_date).tz(TIME_ZONE.ZONE),
                    end_date: endDate,
                    days: total_days
                };
                newData.push(value);
            });

            return newData;
        };

        const onClickStoreButton = () => {
            //format start time
            _handleDatePeriod();

            axios.post('/api/holiday/store', {
                name: formState.value.name,
                start_date: formState.value.start_date,
                end_date: formState.value.end_date
            })
            .then(response => {
                getHolidays()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal();//show error message modally
            })
        }

        const onClickDeleteButton = (id) => {
            Modal.confirm({
                title: 'Bạn có chắc chắn xoá ngày nghỉ này?',
                okText: 'Ok',
                cancelText: 'Huỷ',
                onOk() {
                    axios.delete('/api/holiday/delete', {
                        params: {
                            id: id,
                        }
                    })
                    .then(response => {
                        getHolidays()
                    })
                    .catch(error => {
                        errorMessages.value = error.response.data.errors;
                        errorModal();
                    })
                }
            })
        };

        const _handleDatePeriod = () => {
            if (datePeriod.value !== null && datePeriod.value !== undefined) {
                var startDay = 0;
                var processedStartDay = '';

                if (datePeriod.value[startDay] !== null && datePeriod.value[startDay] !== undefined) {
                    processedStartDay = datePeriod.value[startDay];
                    formState.value.start_date = dayjs(processedStartDay).format(dateFormat);
                    }
                
                var endDay = 1;
                var processedEndDay = '';

                if (datePeriod.value[endDay] !== null && datePeriod.value[endDay] !== undefined) {
                    processedEndDay = datePeriod.value[endDay];
                    formState.value.end_date = dayjs(processedEndDay).format(dateFormat);
                }
            } else {
                formState.value.start_date = null;
                formState.value.end_date = null;
            }
        }

        const onChangeName = (id, event) => {
            _quickUpdate(id, 'name', event.target.value)
        }

        const onChangeStartDate = (id, value) => {
            let start_date = "";
            if (value !== null && value !== undefined) {
                start_date = dayjs(value).format(dateFormat);
            }
            _quickUpdate(id, 'start_date', start_date)
        }

        const onChangeEndDate = (id, value) => {
            let end_date = "";
            if (value !== null && value !== undefined) {
                end_date = dayjs(value).format(dateFormat);
            }
            _quickUpdate(id, 'end_date', end_date)
        }

        const _quickUpdate = (id, column, value) => {
            let submitData = {
                id: id,
                [column]: value ? value : ""
            }

            axios.patch('/api/holiday/update', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });
                getHolidays()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal();//show error message modally
            })
        }

        const onClickSyncButton = () => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                //format start date and end date
                _handleDatePeriod();

                //get user_code list that has selected on select box
                var user_codes = [];
                if (option.value == 1) {
                    user_codes = getListUser(userSelectbox.value);
                } else if (option.value == 2) {
                    user_codes = getListUser(formState.value.user_codes);
                }

                let submitData = {
                    start_date: formState.value.start_date,
                    end_date: formState.value.end_date,
                    device_code: formState.value.device_code,
                    users: user_codes
                };

                axios.post('/api/timesheet/detail/sync', submitData)
                .then(response => {
                    resolve();
                    notification.success({
                        message: t('message.MSG-TITLE-W'),
                        description: response.data.success,
                    });
                })
                .catch(error => {
                    reject();
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    errorModal();//show error message modally
                })
            })
        }

        const getListUser = (users) => {
            var newData = [];

            users.forEach(function(element, index) {
                var item = {
                    user_code: element.hasOwnProperty('user_code') ? element.user_code : element
                };

                newData.push(item);
            });

            return newData;
        }

        const onClickDisconnect = () => {
            Modal.confirm({
                title: 'Bạn có chắc chắn ngắt kết nối?',
                okText: 'Ok',
                cancelText: 'Huỷ',
                onOk() {
                    axios.delete('/api/partner/delete', {
                        params: {
                            id: config.value.id,
                        }
                    })
                    .then(response => {
                        notification.success({
                            message: t('message.MSG-TITLE-W'),
                            description: response.data.success,
                        });
                    })
                    .catch(error => {
                        errorMessages.value = error.response.data.errors;
                        errorModal();
                    })
                }
            })
        }

        const onChangeSyncDevice = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                axios.get('/api/device/sync_devices')
                .then(response => {
                    resolve();
                    notification.success({
                        message: t('message.MSG-TITLE-W'),
                        description: response.data.success,
                    });
                    getDevicesInfo()
                })
                .catch(error => {
                    reject();
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    errorModal();//show error message modally
                })
            })
        }

        const onChangeSyncEmployee = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                axios.get('/api/partner/sync_employee')
                .then(response => {
                    notification.success({
                        message: t('message.MSG-TITLE-W'),
                        description: response.data.success,
                    });
                    resolve();
                })
                .catch(error => {
                    reject();
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    errorModal();//show error message modally
                })
            })
        }

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        const errorModal = () => {
            Modal.warning({
                title: t('message.MSG-TITLE-W'),
                content: h('ul', {}, errorMessages.value.split('<br>').map((error) => { return h('li', error) })),
            });
        };

        return {
            devices,
            device_columns,
            cameraTabPosition,
            tabPosition,
            activeKey,
            cameraKey,
            ShowWithConfigMode,
            cancel,
            t,
            option,
            is_add_holiday,
            datePeriod,
            deviceSelectbox,
            userSelectbox,
            isLoading,
            errorModal,
            errorMessages,
            formState,
            dateFormat,
            title,
            visible,
            mode,
            config,
            holidays,
            holiday_columns,
            onChangeAddHoliday,
            onChangeTab,
            onChangeCameraTab,
            onClickStoreButton,
            onClickDeleteButton,
            onChangeName,
            onChangeStartDate,
            onChangeEndDate,
            onClickSyncButton,
            onClickDisconnect,
            onChangeSyncDevice,
            onChangeSyncEmployee,
            tableData,
            onChangeSelect,
            deleteRow,
            onAddItem
        };
    }
})
</script>

