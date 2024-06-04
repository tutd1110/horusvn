<template>
    <a-modal v-model:visible="visible" style="width:1000px; font-weight: bold" :footer="null" :maskClosable="false"
        :closable="false">
        <a-tabs v-model:activeKey="activeKey" :tab-position="tabPosition" animated @change="onChangeTab()">
            <a-tab-pane key="1" :tab="title">
                <a-form :model="formState" autocomplete="off" style="width:800px;">
                    <a-row style="padding-bottom: 50px;">
                        <a-col style="margin-left:47.5%">
                            <a-image :src="previewImage" style="width: 200px; height:200px; border-radius: 50%;"></a-image>
                        </a-col>
                        <a-col style="margin-top:142px; margin-left:-65px;">
                            <a-upload 
                                :multiple="false"
                                :showUploadList="false"
                                :beforeUpload="beforeUpload"
                                @change="onFileChange"
                            >
                                <a-button style="width: 60px;height:60px; border-radius: 50%; background-color: transparent;">
                                    <span style="color: orange;">Edit</span>
                                </a-button>
                            </a-upload>
                        </a-col>
                    </a-row>
                    <a-row>
                        <a-col :span="8" :offset="5">
                            <label name="name">Họ và tên</label>
                            <a-form-item :span="3">
                                <a-input allow-clear v-model:value="formState.fullname" />
                            </a-form-item>
                        </a-col>
                        <a-col :span="8" :offset="3">
                            <label name="name">Số điện thoại</label>
                            <a-form-item :span="3">
                                <a-input allow-clear v-model:value="formState.phone" />
                            </a-form-item>
                        </a-col>
                    </a-row>
                    <a-row>
                        <a-col :span="8" :offset="5">
                            <label name="name">Ngày sinh</label>
                            <a-form-item :span="3">
                                <a-space direction="vertical">
                                    <a-date-picker
                                        v-model:value="formState.birthdayPicker"
                                        :format="dateFormat"
                                        style="width:175%"
                                    />
                                </a-space>
                            </a-form-item>
                        </a-col>
                        <a-col :span="8" :offset="3">
                            <label name="name">Email</label>
                            <a-form-item :span="3">
                                <a-input allow-clear v-model:value="formState.email" />
                            </a-form-item>
                        </a-col>
                    </a-row>
                    <a-row>
                        <a-col :span="8" :offset="5">
                            <label name="name">Bộ phận</label>
                            <a-form-item :span="3">
                                <a-select
                                        ref="select"
                                        v-model:value="formState.department_id"
                                        style="width:100%;"
                                        :options="departmentSelectbox"
                                        allow-clear
                                        :field-names="{ label:'label', value: 'value' }"
                                ></a-select>
                            </a-form-item>
                        </a-col>
                        <a-col :span="8" :offset="3">
                            <label name="name">Chức danh</label>
                            <a-form-item :span="3">
                                <a-select
                                        ref="select"
                                        allow-clear
                                        v-model:value="formState.position"
                                        style="width:100%;"
                                        :options="positionSelectbox"
                                        :field-names="{ label:'label', value: 'value' }"
                                ></a-select>
                            </a-form-item>
                        </a-col>
                        <a-col :span="7" :offset="5">
                            <label name="name">Ngày bắt đầu làm việc</label>
                            <a-form-item :span="5">
                                <a-space direction="vertical">
                                    <a-date-picker
                                        v-model:value="formState.dateCreatedAtPicker"
                                        :format="dateFormat"
                                        style="width:175%"
                                    />
                                </a-space>
                            </a-form-item>
                        </a-col>
                        <a-col :span="6" :offset="4">
                            <label name="name">Quyền truy cập</label>
                            <a-form-item :span="3">
                                <a-select
                                        ref="select"
                                        allow-clear
                                        v-model:value="formState.permission"
                                        style="width:133%;"
                                        :options="permissionSelectbox"
                                        :field-names="{ label:'label', value: 'value' }"
                                ></a-select>
                            </a-form-item>
                        </a-col>
                    </a-row>
                    <a-row v-if="mode==='UPDATE'">
                        <a-col :span="8" :offset="5">
                            <label name="name">Ngày lên thử việc</label>
                            <a-form-item :span="5">
                                <a-space direction="vertical">
                                    <a-date-picker
                                        v-model:value="formState.dateProbationPicker"
                                        :format="dateFormat"
                                        style="width:175%"
                                    />
                                </a-space>
                            </a-form-item>
                        </a-col>
                        <a-col :span="6" :offset="3">
                            <label name="name">Loại chấm công</label>
                            <a-form-item :span="3">
                                <a-select
                                        ref="select"
                                        allow-clear
                                        v-model:value="formState.check_type"
                                        style="width:133%;"
                                        :options="typeCheckSelectBox"
                                        :field-names="{ label:'label', value: 'value' }"
                                ></a-select>
                            </a-form-item>
                        </a-col>
                    </a-row>
                    <a-row>
                        <a-col :span="8" :offset="5">
                            <label name="name">Ngày làm việc chính thức</label>
                            <a-form-item :span="5">
                                <a-space direction="vertical">
                                    <a-date-picker
                                        v-model:value="formState.dateOfficialPicker"
                                        :format="dateFormat"
                                        style="width:175%"
                                    />
                                </a-space>
                            </a-form-item>
                        </a-col>
                        <a-col :span="8" :offset="3">
                            <label name="name">Type of User</label>
                            <a-form-item :span="3">
                                <a-select
                                    ref="select"
                                    allow-clear
                                    v-model:value="formState.user_type"
                                    style="width:100%;"
                                    :options="typeSelectUser"
                                    :field-names="{ label:'label', value: 'value' }"
                                ></a-select>
                            </a-form-item>
                        </a-col>
                        
                    </a-row>
                    <a-row >
                        <a-col :span="8" :offset="5">
                            <label name="name">Mật khẩu</label>
                            <a-form-item :span="3">
                                <a-input-password allow-clear v-model:value="formState.password" placeholder="Password" />
                            </a-form-item>
                        </a-col>
                        <a-col :span="6" :offset="3">
                            <label name="name">Trạng thái hoạt động</label>
                            <a-form-item :span="3">
                                <a-select
                                        ref="select"
                                        allow-clear
                                        v-model:value="formState.user_status"
                                        style="width:133%;"
                                        :options="statusSelectBox"
                                        :field-names="{ label:'label', value: 'value' }"
                                ></a-select>
                            </a-form-item>
                        </a-col>
                    </a-row>
                    <a-row>
                        <a-col :span="8" :offset="5">
                            <label name="name">Xác nhận mật khẩu</label>
                            <a-form-item :span="3">
                                <a-input-password allow-clear v-model:value="formState.password_confirmation" placeholder="Confirm Password" />
                            </a-form-item>
                        </a-col>
                        <a-col :span="8" :offset="3">
                            <label name="name">Giới tính</label>
                            <a-select v-model:value="formState.gender" style="width: 100%">
                                <a-select-option value="male">Male</a-select-option>
                                <a-select-option value="female">Female</a-select-option>
                                <a-select-option value="other">Other</a-select-option>
                            </a-select>
                        </a-col>
                    </a-row>
                    <!--Delete button-->
                    <a-row style="margin-top: 20px; margin-bottom: -10px;">
                        <a-col style="margin-left:168px">
                            <a-form-item>
                                <single-submit-button style="width:100px;" v-if="mode==='UPDATE'" type="primary" danger :onclick="onClickDeleteButton">Xoá</single-submit-button>
                            </a-form-item>
                        </a-col>
                    </a-row>
                    <!--Cancel button-->
                    <a-row style="float:right; margin-top:-50px;">
                        <a-col style="margin-right:10px">
                            <a-form-item>
                                <a-button style="width:100px;" @click="cancel">Huỷ</a-button>
                            </a-form-item>
                        </a-col>
                        <a-form-item>
                            <a-col>
                                <single-submit-button style="width:100px;" v-if="mode==='ADD'" type="primary" :onclick="onClickStoreButton">Thêm</single-submit-button>
                                <single-submit-button style="width:100px;" v-if="mode==='UPDATE'" type="primary" :onclick="onClickUpdateButton">Cập nhật</single-submit-button>
                            </a-col>
                        </a-form-item>
                    </a-row>
                </a-form>
            </a-tab-pane>
            <a-tab-pane key="2" tab="Đăng ký với HANET's Camera" v-if="mode==='UPDATE'">
                <a-row style="padding-bottom: 50px;">
                    <a-col :span="6" :offset="0">
                        <a-image :src="previewImage" style="width: 100px; height: 100px; border-radius: 50%;"></a-image>
                    </a-col>
                    <a-col :span="5" :offset="0">
                        <label name="name">Chọn nơi đặt camera</label>
                        <a-form-item :span="3">
                            <a-select
                                    ref="select"
                                    v-model:value="formState.place_id"
                                    style="width:100%;"
                                    :options="cameraPlacesSelectBox"
                                    allow-clear
                                    :field-names="{ label:'name', value: 'id' }"
                            ></a-select>
                        </a-form-item>
                    </a-col>
                </a-row>
                <a-row>
                    <a-col>
                        <a-form-item>
                            <a-button @click="cancel">Close</a-button>
                        </a-form-item>
                    </a-col>
                    <a-col :span="3" :offset="0" style="margin-left: 10px">
                        <single-submit-button v-if="mode==='UPDATE'" type="primary" :onclick="onClickRegisterFaceIDButton">Register FaceID</single-submit-button>
                    </a-col>
                    <a-col :span="3" :offset="5">
                        <single-submit-button v-if="mode==='UPDATE'" type="primary" :onclick="onClickUpdateFaceIDButton">Update FaceID</single-submit-button>
                    </a-col>
                </a-row>
            </a-tab-pane>
            <a-tab-pane key="3" tab="Crop Avatar" v-if="isCrop">
                <a-row style="padding-bottom: 50px;">
                    <a-col style="margin-right: 20px">
                        <cropper
                            class="cropper"
                            :src="previewImage"
                            :stencil-props="{
                                aspectRatio: 10/12
                            }"
                            ref="cropper"
                            @change="onCropImage"
                        />
                    </a-col>
                    <a-col>
                        <span>Width: {{ avatarCoordinates.width }}</span>
                        <br>
                        <span v-if="avatarCoordinates.height > 736" style="color: red">Height: {{ avatarCoordinates.height }}</span>
                        <span v-else>Height: {{ avatarCoordinates.height }}</span>
                        <br>
                        <span>X: {{ avatarCoordinates.left }}</span>
                        <br>
                        <span>Y: {{ avatarCoordinates.top }}</span>
                    </a-col>
                </a-row>
                <a-row>
                    <a-col style="margin-right: 10px">
                        <a-form-item>
                            <a-button @click="cancel">Close</a-button>
                        </a-form-item>
                    </a-col>
                    <a-col :span="3" :offset="0">
                        <a-button type="primary" v-on:click="onCropImageButton()">Crop</a-button>
                    </a-col>
                </a-row>
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
import SingleSubmitButton from '../Shared/SingleSubmitButton/SingleSubmitButton.vue';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';

dayjs.extend(utc);
dayjs.extend(timezone);

export default ({
    components: {
        SingleSubmitButton,
        Cropper
    },
    emits: ['saved'],
    setup(props, { emit }) {
        const cropper = ref(null);
        const { t } = useI18n();
        const errorMessages = ref();
        const activeKey = ref('1');
        const tabPosition = ref('top');
        const title = ref("");
        const visible = ref(false);
        const previewImage = ref("");
        const formState = ref({
            id: "",
            fullname: "",
            phone: "",
            birthday: "",
            email: "",
            department_id: "",
            position: "",
            permission: "",
            password: "",
            confirm_password: "",
            birthdayPicker: "",
            created_at: "",
            date_official: "",
            dateOfficialPicker: "",
            dateCreatedAtPicker: "",
            user_type: "",
            gender: "",
            date_probation: "",
            dateProbationPicker: "",
        });//form value
        const mode = ref("");//New mode or edit mode or change
        const selectedRecord = ref("");//Store ID of selected row
        const strictDateFormat = "YYYY/MM/DD HH:mm:ss"
        const exclusionControl = ref();//exclusion control
        const departmentSelectbox = ref([]);
        const positionSelectbox = ref([]);
        const permissionSelectbox = ref([]);
        const statusSelectBox = ref([]);
        const typeCheckSelectBox = ref([]);
        const cameraPlacesSelectBox = ref([]);
        const dateFormat = "DD/MM/YYYY";
        const avatar = ref({});
        const isCrop = ref(false);
        const avatarCoordinates = ref({});
        const cancel = () => {
            visible.value = false;
            activeKey.value = '1'
        };
        const typeSelectUser = ref([]);
        const formClear = () => {
            formState.value.id = "";
            formState.value.fullname = "";
            formState.value.phone = "";
            formState.value.birthday = "";
            formState.value.email = "";
            formState.value.department_id = "";
            formState.value.position = "";
            formState.value.permission = "";
            previewImage.value = "";
            formState.value.password = "";
            formState.value.confirm_password = "";
            formState.value.birthdayPicker = "";
            formState.value.dateOfficialPicker = "";
            formState.value.dateCreatedAtPicker = "";
            formState.value.place_id = "";
            formState.value.created_at = "";
            isCrop.value = false;
            avatar.value = {};
            formState.value.user_type = "";
        };
        //select box list generation
        const CreateSelbox = () => {
            //departments
            axios.get('/api/employee/get_selectBoxes')
            .then(response => {
                departmentSelectbox.value = transferObject(response.data.departments);
                positionSelectbox.value = transferArray(response.data.positions);
                permissionSelectbox.value = transferArray(response.data.permissions);
                previewImage.value = response.data.avatar_sample_path;
                typeSelectUser.value = transferObject(response.data.user_type);
            })
        };
        const transferArray = (data) => {
            var newData = [];
            data.forEach(function(item, index) {
                let value = {
                    "label": item,
                    "value": index,
                };
                newData.push(value);
            });
            return newData;
        };
        const transferObject = (data) => {
            var newData = [];
            for (const [key, value] of Object.entries(data)) {
                let element = {
                    "label": value,
                    "value": parseInt(key),
                };
                newData.push(element);
            }
            return newData
        }
        const ShowWithAddMode = () => {
            mode.value = "ADD";
            title.value = "Thêm Nhân Viên"
            visible.value = true;
            //form initialization
            formClear();
            CreateSelbox();
        };
        const ShowWithUpdateMode = (id, updated_at) => {
            mode.value = "UPDATE";
            title.value = "Chỉnh sửa thông tin nhân viên"
            visible.value = true;
            formClear();
            CreateSelbox();
            selectedRecord.value = id;
            exclusionControl.value = dayjs(updated_at).tz(TIME_ZONE.ZONE).format(strictDateFormat);

            axios
                .get('/api/employee/get_employee_by_id', {
                    params: {
                        id: selectedRecord.value
                    }
                })
                .then(response => {
                    let employees = response.data.employee;
                    formState.value = employees;
                    
                    formState.value.birthdayPicker = dayjs(employees.birthdayDMY, dateFormat);
                    if (employees.date_official != null) {
                        formState.value.dateOfficialPicker = dayjs(employees.dateOfficialDMY, dateFormat);
                    }
                    if (employees.date_probation != null) {
                        formState.value.dateProbationPicker = dayjs(employees.dateProbationDMY, dateFormat);
                    }
                    formState.value.dateCreatedAtPicker = dayjs(employees.createdAtDMY, dateFormat);
                    formState.value.department_id = parseInt(employees.department_id);
                    formState.value.position = parseInt(employees.position);
                    formState.value.permission = parseInt(employees.permission);
                    previewImage.value = employees.avatar_path;

                    statusSelectBox.value = response.data.user_status;
                    typeCheckSelectBox.value = response.data.type_check;

                    formState.value.user_type = parseInt(employees.type);
                })
                .catch(error => {
                    errorMessages.value = error.response.data.errors;
                    errorModal(true);
                })
        };
        const onClickDeleteButton = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                Modal.confirm({
                    title: 'Bạn có chắc chắn xoá thông tin nhân viên này?',
                    okText: 'Ok',
                    cancelText: 'Huỷ',
                    onOk() {
                        axios.delete('/api/employee/delete', {
                            params: {
                                id: selectedRecord.value,
                                check_updated_at: exclusionControl.value
                            }
                        })
                        .then(response => {
                            resolve();
                            _update();
                        })
                        .catch(error => {
                            reject();
                            errorMessages.value = error.response.data.errors;
                            errorModal(false);
                        })
                    },
                    onCancel() {
                        reject();
                    },
                })
            })
        };
        const onClickUpdateButton = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                //format birthday
                _handleBirthday();

                let formData = new FormData();
                formData.append('_method', 'PATCH');

                if (avatar.value instanceof File) {
                    formData.append('avatar', avatar.value);
                }
                formData.append('id', formState.value.id);
                formData.append('fullname', formState.value.fullname);
                formData.append('gender', formState.value.gender);
                formData.append('phone', formState.value.phone);
                formData.append('birthday', formState.value.birthday);
                formData.append('email', formState.value.email);
                formData.append('department_id', formState.value.department_id);
                formData.append('position', formState.value.position);
                formData.append('permission', formState.value.permission);
                formData.append('password', formState.value.password ? formState.value.password : "");
                formData.append('password_confirmation', formState.value.password_confirmation ? formState.value.password_confirmation : "");
                formData.append('user_status', formState.value.user_status);
                formData.append('check_type', formState.value.check_type);
                formData.append('date_official', formState.value.date_official);
                formData.append('date_probation', formState.value.date_probation);
                formData.append('created_at', formState.value.created_at);
                formData.append('check_updated_at', exclusionControl.value);//exclusion control
                formData.append('avatar_width', avatarCoordinates.value.width ? avatarCoordinates.value.width : "");
                formData.append('avatar_height', avatarCoordinates.value.height ? avatarCoordinates.value.height : "");
                formData.append('avatar_left', avatarCoordinates.value.left ? avatarCoordinates.value.left : "");
                formData.append('avatar_top', avatarCoordinates.value.top ? avatarCoordinates.value.left : "");
                formData.append('type', formState.value.user_type);
                axios.post('/api/employee/update', formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(response => {
                    resolve();
                    _update();
                })
                .catch(error => {
                    reject();
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    errorModal(false);//Show error message modally
                })
            })
        };
        const _handleBirthday = () => {
            if (formState.value.birthdayPicker !== null && formState.value.birthdayPicker !== undefined) {
                formState.value.birthday = dayjs(formState.value.birthdayPicker).format(strictDateFormat);
            } else {
                formState.value.birthday = "";
            }
            if (formState.value.dateOfficialPicker !== null && formState.value.dateOfficialPicker !== undefined) {
                formState.value.date_official = dayjs(formState.value.dateOfficialPicker).format(strictDateFormat);
            } else {
                formState.value.date_official = "";
            }
            if (formState.value.dateProbationPicker !== null && formState.value.dateProbationPicker !== undefined) {
                formState.value.date_probation = dayjs(formState.value.dateProbationPicker).format(strictDateFormat);
            } else {
                formState.value.date_probation = "";
            }
            if (formState.value.dateCreatedAtPicker !== null && formState.value.dateCreatedAtPicker !== undefined) {
                formState.value.created_at = dayjs(formState.value.dateCreatedAtPicker).format(strictDateFormat);
            } else {
                formState.value.created_at = "";
            }
        };
        const _update = () => {
            visible.value = false;
            emit('saved');
        };
        const onClickStoreButton = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                //format birthday
                _handleBirthday();

                let formData = new FormData();
                formData.append('avatar', avatar.value);

                formData.append('fullname', formState.value.fullname);
                formData.append('phone', formState.value.phone);
                formData.append('birthday', formState.value.birthday);
                formData.append('email', formState.value.email);
                formData.append('department_id', formState.value.department_id);
                formData.append('position', formState.value.position);
                formData.append('permission', formState.value.permission);
                formData.append('password', formState.value.password);
                formData.append('created_at', formState.value.created_at);
                formData.append('avatar_width', avatarCoordinates.value.width ? avatarCoordinates.value.width : "");
                formData.append('avatar_height', avatarCoordinates.value.height ? avatarCoordinates.value.height : "");
                formData.append('avatar_left', avatarCoordinates.value.left ? avatarCoordinates.value.left : "");
                formData.append('avatar_top', avatarCoordinates.value.top ? avatarCoordinates.value.left : "");
                formData.append('type', formState.value.user_type);
                formData.append('gender', formState.value.gender);
                formData.append('date_probation', formState.value.date_probation);
                
                axios.post('/api/employee/store', formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(response => {
                    resolve();
                    _update();
                })
                .catch(error => {
                    reject();
                    errorMessages.value = error.response.data.errors;
                    errorModal(false);
                })
            })
        };
        const errorModal = (isEdit) => {
            Modal.warning({
                title: t('message.MSG-TITLE-W'),
                content: h('ul', {}, errorMessages.value.split('<br>').map((error) => { return h('li', error) })),
                onOk() {
                    //isEdit is boolean, show us about mode edit/delete
                    //when init edit/delete if there is no data match, we'll show message modal dialog
                    //onOk we'll close edit/delete mode modal and re-init list data
                    if (isEdit) {
                        _update();
                    }
                }
            });
        };
        const beforeUpload = (file) => {
            previewImage.value = URL.createObjectURL(file);

            if (file instanceof File) {
                isCrop.value = true
            } else {
                isCrop.value = false
            }
            activeKey.value = '3'
            //Prevent upload
            return false;
        };
        const onFileChange = (e) => {
            if (e.file instanceof File) {
                avatar.value = e.file;
            }
        };

        const onChangeTab = () => {
            if (activeKey.value == 2) {
                getHanetCameraPlaces();
            }
        }

        const getHanetCameraPlaces = () => {
            axios.get('/api/partner/get_places')
            .then(response => {
                cameraPlacesSelectBox.value = response.data;
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                cameraPlacesSelectBox.value = [];
                errorModal(true);
            })
        }

        const onClickRegisterFaceIDButton = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                let submitData = {
                    employee_id: selectedRecord.value,
                    place_id: formState.value.place_id,
                    place_name: 'VoV-Mễ Trì',
                };

                cameraPlacesSelectBox.value.forEach(function(item, index) {
                    if (item.id == formState.value.place_id) {
                        submitData.place_name = item.name
                    }
                });

                axios.post('/api/partner/register_employee_face_id', submitData)
                .then(response => {
                    resolve();
                    notification.success({
                        message: t('message.MSG-TITLE-W'),
                        description: response.data.success,
                    });
                    _update();
                })
                .catch(error => {
                    reject();
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    errorModal(false);//show error message modally
                })
            })
        }

        const onClickUpdateFaceIDButton = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                let submitData = {
                    employee_id: selectedRecord.value,
                    place_id: formState.value.place_id,
                    place_name: 'VoV-Mễ Trì',
                };

                cameraPlacesSelectBox.value.forEach(function(item, index) {
                    if (item.id == formState.value.place_id) {
                        submitData.place_name = item.name
                    }
                });

                axios.post('/api/partner/update_employee_face_id', submitData)
                .then(response => {
                    resolve();
                    notification.success({
                        message: t('message.MSG-TITLE-W'),
                        description: response.data.success,
                    });
                    _update();
                })
                .catch(error => {
                    reject();
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    errorModal(false);//show error message modally
                })
            })
        }

        const onCropImage = ({ coordinates, canvas }) => {
            avatarCoordinates.value = coordinates
        }

        const onCropImageButton = () => {
            activeKey.value = '1'
        }

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        return {
            cancel,
            t,
            isCrop,
            activeKey,
            tabPosition,
            errorModal,
            errorMessages,
            previewImage,
            formState,
            avatar,
            departmentSelectbox,
            positionSelectbox,
            permissionSelectbox,
            statusSelectBox,
            typeCheckSelectBox,
            cameraPlacesSelectBox,
            dateFormat,
            title,
            visible,
            mode,
            ShowWithAddMode,
            ShowWithUpdateMode,
            onClickStoreButton,
            onClickUpdateButton,
            onClickDeleteButton,
            onClickRegisterFaceIDButton,
            onClickUpdateFaceIDButton,
            beforeUpload,
            onFileChange,
            onChangeTab,
            onCropImage,
            onCropImageButton,
            cropper,
            avatarCoordinates,
            typeSelectUser
        };
    }
})
</script>
<style lang="scss">
.cropper {
	height: 400px;
	width: 400px;
	background: #DDD;
}
</style>