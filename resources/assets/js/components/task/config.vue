<template>
    <a-modal v-model:visible="visible" style="width:1700px; font-weight: bold" :footer="null" :maskClosable="false" :closable="true" :title="title" @cancel="handleCancel">
        <a-tabs v-model:activeKey="activeKey" :tab-position="tabPosition" @change="onChangeTab()">
            <a-tab-pane key="1" tab="Quản lý loại công việc">
                <a-row>
                    <a-col :span="3" :offset="0" style="margin-right:10px;">
                        <label>Bộ phận</label>
                        <a-form-item :span="5">
                            <a-select
                                    ref="select"
                                    allow-clear
                                    v-model:value="selectedDepartmentId"
                                    style="width:100%;"
                                    :options="departments"
                                    :field-names="{ label:'label', value: 'value' }"
                                    @change="onChangeSelectDepartment($event)"
                            ></a-select>
                        </a-form-item>
                    </a-col>
                    <a-col style="margin-right:1px; margin-top: 22px;">
                        <a-upload 
                            :multiple="false"
                            :showUploadList="false"
                            :beforeUpload="beforeUpload"
                        >
                            <a-button type="primary" class="upload-btn">
                                <span class="upload-spn">Chọn file</span>
                            </a-button>
                        </a-upload>
                    </a-col>
                    <a-col style="margin-right:1px; margin-top: 22px;">
                        <a-input style="width:400px" readonly placeholder="Only file xlsx can be import" v-model:value="fileName" />
                    </a-col>
                    <a-col style="margin-right:10px; margin-top: 22px;">
                        <a-button type="primary" :loading="loadingImport" v-on:click="doImport()">Import</a-button>
                    </a-col>
                    <a-col :span="1" style="margin-top: 22px; margin-right:10px;">
                        <a-button type="primary" :loading="loadingExport" v-on:click="doExport()">Export</a-button>
                    </a-col>
                    <a-col v-if="!is_add_sticker" :span="1" :offset="loadingExport ? 1 : 0" style="margin-top: 22px;">
                        <a-button type="primary" v-on:click="onChangeAddSticker">ADD</a-button>
                    </a-col>
                </a-row>
                <a-row style="margin-bottom:30px;" v-if="is_add_sticker">
                    <a-col :span="2" :offset="0">
                        <label>Tên nhãn dán</label>
                        <a-input allow-clear placeholder="Tiêu đề" v-model:value="formState.name" />
                    </a-col>
                    <a-col :span="2" :offset="0">
                        <label>Bộ phận</label>
                        <a-select
                            ref="select"
                            v-model:value="formState.department_id"
                            style="width:100%;"
                            :options="departments"
                            allow-clear
                            :field-names="{ label:'label', value: 'value' }"
                        ></a-select>
                    </a-col>
                    <a-col :span="2" :offset="0">
                        <label>Level 1</label>
                        <a-input allow-clear placeholder="Trọng số" v-model:value="formState.level_1" />
                    </a-col>
                    <a-col :span="2" :offset="0">
                        <label>Level 2</label>
                        <a-input allow-clear placeholder="Trọng số" v-model:value="formState.level_2" />
                    </a-col>
                    <a-col :span="2" :offset="0">
                        <label>Level 3</label>
                        <a-input allow-clear placeholder="Trọng số" v-model:value="formState.level_3" />
                    </a-col>
                    <a-col :span="2" :offset="0">
                        <label>Level 4</label>
                        <a-input allow-clear placeholder="Trọng số" v-model:value="formState.level_4" />
                    </a-col>
                    <a-col :span="2" :offset="0">
                        <label>Level 5</label>
                        <a-input allow-clear placeholder="Trọng số" v-model:value="formState.level_5" />
                    </a-col>
                    <a-col :span="2" :offset="0">
                        <label>Level 6</label>
                        <a-input allow-clear placeholder="Trọng số" v-model:value="formState.level_6" />
                    </a-col>
                    <a-col :span="2" :offset="0">
                        <label>Level 7</label>
                        <a-input allow-clear placeholder="Trọng số" v-model:value="formState.level_7" />
                    </a-col>
                    <a-col :span="2" :offset="0">
                        <label>Level 8</label>
                        <a-input allow-clear placeholder="Trọng số" v-model:value="formState.level_8" />
                    </a-col>
                    <a-col :span="2" :offset="0">
                        <label>Level 9</label>
                        <a-input allow-clear placeholder="Trọng số" v-model:value="formState.level_9" />
                    </a-col>
                    <a-col :span="2" :offset="0">
                        <label>Level 10</label>
                        <a-input allow-clear placeholder="Trọng số" v-model:value="formState.level_10" />
                    </a-col>
                    <a-col :span="1" :offset="22" style="margin-top: 22px;">
                        <a-button @click="cancelSticker">Huỷ</a-button>
                    </a-col>
                    <a-col :span="1" :offset="0" style="margin-top: 12px;">
                        <a-button type="primary" block :onclick="onClickStoreStickerButton">Thêm</a-button>
                    </a-col>
                </a-row>
                <a-row>
                    <a-table :dataSource="stickers" :columns="sticker_columns" style = "white-space:pre-wrap" :pagination="false" bordered>
                        <template #bodyCell="{column, text, record}">
                            <template v-if="arrayColumnsEdit.includes(column.key)">
                                <div>
                                    <a-input
                                        v-if="editableData[record.id]"
                                        v-model:value="editableData[record.id][column.key]"
                                        style="margin: -5px 0"
                                    />
                                    <template v-else>
                                        {{ text }}
                                    </template>
                                </div>
                            </template>
                            <template v-else-if="column.key === 'action'">
                                <div class="editable-row-operations">
                                    <span v-if="editableData[record.id]">
                                        <a-typography-link @click="save(record.id)">Save</a-typography-link>
                                        <a-popconfirm title="Sure to cancel?" @confirm="cancel(record.id)">
                                            <a>Cancel</a>
                                        </a-popconfirm>
                                    </span>
                                    <span v-else>
                                        <a @click="edit(record.id)">Edit</a>
                                        <delete-outlined style="color: red; margin-left: 5px" v-on:click="onClickDeleteStickerButton(record.id)"/>
                                    </span>
                                </div>
                            </template>
                            <template v-if="column.key === 'ordinal_number'">
                                <a-input :bordered="false" v-model:value="record.ordinal_number" @blur="onChangeOrdinalNumber(record.id, $event)"/>
                            </template>
                            <template v-if="column.key === 'name'" data-index="name">
                                <a-input :bordered="false" v-model:value="record.name" @blur="onChangeStickerName(record.id, $event)"/>
                            </template>
                            <template v-if="column.key === 'department_id'" data-index="department_id">
                                <a-select
                                    ref="select"
                                    :bordered="false"
                                    v-model:value="record.department_id"
                                    style="width:100%;"
                                    :options="departments"
                                    allow-clear
                                    :field-names="{ label:'label', value: 'value' }"
                                    @change="onChangeDepartment(record.id, $event)"
                                ></a-select>
                            </template>
                        </template>
                    </a-table>
                </a-row>
            </a-tab-pane>
            <a-tab-pane key="2" tab="Quản lý cấp độ công việc">
                <a-row style="margin-top:20px; margin-bottom:30px;">
                    <template v-if="is_add_priority">
                        <a-col :span="7" :offset="0">
                            <label>Tiêu đề</label>
                            <a-input allow-clear placeholder="Tiêu đề" v-model:value="formState.label" />
                        </a-col>
                        <a-col :span="1" :offset="15" style="margin-top: 22px;">
                            <a-button @click="cancelPriority">Huỷ</a-button>
                        </a-col>
                        <a-col :span="1" :offset="0" style="margin-top: 12px;">
                            <a-button type="primary" block :onclick="onClickStorePriorityButton">Thêm</a-button>
                        </a-col>
                    </template>
                    <template v-else>
                        <a-col :span="3" :offset="21" style="margin-top: 12px;">
                            <a-button type="primary" block v-on:click="onChangeAddPriority">Thêm mới</a-button>
                        </a-col>
                    </template>
                </a-row>
                <a-row>
                    <a-table :dataSource="priorities" :columns="priority_columns" style = "white-space:pre-wrap"
                    :pagination="false">
                        <template #bodyCell="{column,record}">
                                <template v-if="column.key === 'label'" data-index="label">
                                    <a-input v-model:value="record.label" @blur="onChangeLabel(record.id, $event)"/>
                                </template>
                                <template v-if="column.key === 'action'" data-index="dataIndex">
                                    <a-button danger v-on:click="onClickDeletePriorityButton(record.id)">Xoá</a-button>
                                </template>
                            </template>
                    </a-table>
                </a-row>
            </a-tab-pane>
        </a-tabs>
    </a-modal>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import { DeleteOutlined } from '@ant-design/icons-vue';
import axios from 'axios';
import { ref, h, reactive } from 'vue';
import { cloneDeep } from 'lodash-es';
import dayjs from 'dayjs';
import { useI18n } from 'vue-i18n';
import { downloadFile, errorModal } from '../Helper/export.js';

export default ({
    components: {
        DeleteOutlined
    },
    emits: ['saved'],
    setup(props, { emit }) {
        const { t } = useI18n();
        const activeKey = ref('1');
        const tabPosition = ref('top');
        const errorMessages = ref();
        const title = ref("");
        const visible = ref(false);
        const formState = ref([]);
        const is_add_priority = ref(false);
        const is_add_sticker = ref(false);
        const stickers = ref([]);
        const departments = ref([]);
        const selectedDepartmentId = ref();
        const loadingImport = ref(false);
        const loadingExport = ref(false);
        const fileName = ref("");
        const excel_file = ref({});
        const sticker_columns = ref([
            {
                title: 'STT',
                dataIndex: 'ordinal_number',
                key: 'ordinal_number',
                align: 'center',
                width: 30,
            },
            {
                title: 'Tên',
                dataIndex: 'name',
                key: 'name',
                fixed: false,
                align: 'center',
                width: 450,
            },
            {
                title: 'Bộ phận',
                dataIndex: 'department_id',
                key: 'department_id',
                fixed: false,
                align: 'center',
                width: 250,
            },
            {
                title: '1',
                dataIndex: 'level_1',
                key: 'level_1',
                fixed: false,
                align: 'center',
                width: 80,
            },
            {
                title: '2',
                dataIndex: 'level_2',
                key: 'level_2',
                fixed: false,
                align: 'center',
                width: 80,
            },
            {
                title: '3',
                dataIndex: 'level_3',
                key: 'level_3',
                align: 'center',
                width: 80,
            },
            {
                title: '4',
                dataIndex: 'level_4',
                key: 'level_4',
                align: 'center',
                width: 80,
            },
            {
                title: '5',
                dataIndex: 'level_5',
                key: 'level_5',
                align: 'center',
                width: 80,
            },
            {
                title: '6',
                dataIndex: 'level_6',
                key: 'level_6',
                align: 'center',
                width: 80,
            },
            {
                title: '7',
                dataIndex: 'level_7',
                key: 'level_7',
                align: 'center',
                width: 80,
            },
            {
                title: '8',
                dataIndex: 'level_8',
                key: 'level_8',
                align: 'center',
                width: 80,
            },
            {
                title: '9',
                dataIndex: 'level_9',
                key: 'level_9',
                align: 'center',
                width: 80,
            },
            {
                title: '10',
                dataIndex: 'level_10',
                key: 'level_10',
                align: 'center',
                width: 80,
            },
            {
                title: 'Action',
                dataIndex: '',
                key: 'action',
                align: 'center',
                width: 100,
            },
        ]);
        const arrayColumnsEdit = ref([
            'level_1',
            'level_2',
            'level_3',
            'level_4',
            'level_5',
            'level_6',
            'level_7',
            'level_8',
            'level_9',
            'level_10',
        ]);
        const priorities = ref([]);
        const priority_columns = ref([
            {
                title: 'Tên mức độ ưu tiên',
                dataIndex: 'label',
                key: 'label',
                fixed: false,
                align: 'center',
                width: 1000,
            },
            {
                title: 'Thao tác',
                dataIndex: '',
                key: 'action',
                align: 'center',
                width: 500,
            },
        ]);

        const formClear = () => {
            is_add_sticker.value = false;
            selectedDepartmentId.value = "";
            formState.value.department_id = "";
            formState.value.name = "";
            formState.value.level_1 = "";
            formState.value.level_2 = "";
            formState.value.level_3 = "";
            formState.value.level_4 = "";
            formState.value.level_5 = "";
            formState.value.level_6 = "";
            formState.value.level_7 = "";
            formState.value.level_8 = "";
            formState.value.level_9 = "";
            formState.value.level_10 = "";
            formState.value.label = "";
            activeKey.value = '1';
            excel_file.value = {};
            fileName.value = "";
        };

        //new mode
        const ShowWithConfigMode = () => {
            title.value = "Cấu hình"
            visible.value = true;
            formClear()

            getStickers();
        };

        const onChangeTab = () => {
            if (activeKey.value == '2') {
                stickers.value = []
                getPriorities();
            } else if (activeKey.value == '1') {
                priorities.value = []
                getStickers();
            }
        }

        const getPriorities = () => {
            //get priorities
            axios
            .get('/api/priority/get_priorities')
            .then(response => {
                priorities.value = response.data
            })
            .catch(error => {
                priorities.value = [];
                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);
            })
        }

        const getStickers = () => {
            //get stickers
            axios.get('/api/sticker/get_stickers', {
                params: {
                    department_id: selectedDepartmentId.value ? selectedDepartmentId.value : null
                }
            })
            .then(response => {
                stickers.value = response.data
            })
            .catch(error => {
                stickers.value = [];
                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);
            })

            //get departments
            axios
            .get('/api/sticker/get_departments')
            .then(response => {
                departments.value = response.data
            })
            .catch(error => {
                departments.value = [];
                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);
            })
        }

        const onChangeAddPriority = () => {
            is_add_priority.value = true
        }

        const onChangeAddSticker = () => {
            is_add_sticker.value = true
        }

        const onClickStorePriorityButton = () => {
            axios.post('/api/priority/store', {
                label: formState.value.label
            })
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });
                getPriorities()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);
            })
        }

        const onClickStoreStickerButton = () => {
            axios.post('/api/sticker/store', {
                name: formState.value.name,
                department_id: formState.value.department_id,
                level_1: formState.value.level_1,
                level_2: formState.value.level_2,
                level_3: formState.value.level_3,
                level_4: formState.value.level_4,
                level_5: formState.value.level_5,
                level_6: formState.value.level_6,
                level_7: formState.value.level_7,
                level_8: formState.value.level_8,
                level_9: formState.value.level_9,
                level_10: formState.value.level_10
            })
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });
                formState.value = []
                getStickers()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);
            })
        }

        const onClickDeletePriorityButton = (id) => {
            Modal.confirm({
                title: 'Bạn có chắc chắn xoá cấp độ công việc này?',
                okText: 'Ok',
                cancelText: 'Huỷ',
                onOk() {
                    axios.delete('/api/priority/delete', {
                        params: {
                            id: id,
                        }
                    })
                    .then(response => {
                        notification.success({
                            message: t('message.MSG-TITLE-W'),
                            description: response.data.success,
                        });
                        getPriorities()
                    })
                    .catch(error => {
                        errorMessages.value = error.response.data.errors;
                        errorModal(t, errorMessages);
                    })
                }
            })
        }

        const onClickDeleteStickerButton = (id) => {
            Modal.confirm({
                title: 'Bạn có chắc chắn xoá loại công việc này?',
                okText: 'Ok',
                cancelText: 'Huỷ',
                onOk() {
                    axios.delete('/api/sticker/delete', {
                        params: {
                            id: id,
                        }
                    })
                    .then(response => {
                        notification.success({
                            message: t('message.MSG-TITLE-W'),
                            description: response.data.success,
                        });
                        getStickers()
                    })
                    .catch(error => {
                        errorMessages.value = error.response.data.errors;
                        errorModal(t, errorMessages);
                    })
                }
            })
        }

        const onChangeLabel = (id, event) => {
            _quickUpdatePriority(id, 'label', event.target.value)
        }

        const _quickUpdatePriority = (id, column, value) => {
            let submitData = {
                id: id,
                [column]: value ? value : ""
            }

            axios.patch('/api/priority/update', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });
                getPriorities()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);
            })
        }

        const onChangeOrdinalNumber = (id, event) => {
            _quickUpdateSticker(id, 'ordinal_number', event.target.value)
        }

        const onChangeStickerName = (id, event) => {
            _quickUpdateSticker(id, 'name', event.target.value)
        }

        const onChangeDepartment = (id, value) => {
            _quickUpdateSticker(id, 'department_id', value)
        }

        const editableData = reactive({});
        const edit = key => {
            editableData[key] = cloneDeep(stickers.value.filter(item => key === item.id)[0]);
        };
        const save = key => {
            Object.assign(stickers.value.filter(item => key === item.id)[0], editableData[key]);

            //call api to update sticker
            axios.patch('/api/sticker/update', editableData[key])
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });
                
                delete editableData[key];
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);
            })
        };
        const cancel = key => {
            delete editableData[key];

            getStickers()
        };

        const _quickUpdateSticker = (id, column, value) => {
            let submitData = {
                id: id,
                [column]: value ? value : ""
            }

            axios.patch('/api/sticker/quick_update', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });
                getStickers()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);
            })
        }

        const onChangeSelectDepartment = (department_id) => {
            selectedDepartmentId.value = department_id

            getStickers()
        }

        const cancelPriority = () => {
            // visible.value = false;
            is_add_priority.value = false
        };

        const cancelSticker = () => {
            is_add_sticker.value = false
            formState.value = []
        }

        const handleCancel = () => {
            stickers.value = []
            priorities.value = []
            visible.value = false
        };

        const doExport = () => {
            let submitData = {
                department_id: selectedDepartmentId.value
            }

            loadingExport.value = true;
            downloadFile('/api/sticker/export', submitData, errorMessages, t)
            .then(() => {
                loadingExport.value = false;
            })
            .catch(() => {
                loadingExport.value = false;
            });
        }

        const doImport = () => {
            loadingImport.value = true;

            let formData = new FormData();
            formData.append('excel_file', excel_file.value);

            axios.post('/api/sticker/import', formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                loadingImport.value = false;

                fileName.value = ""
                excel_file.value = {}

                getStickers();
            })
            .catch(error => {
                loadingImport.value = false;

                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);
            })
        }

        const beforeUpload = (file) => {
            excel_file.value = file
            fileName.value = file.name
            //Prevent upload
            return false;
        };

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        return {
            tabPosition,
            activeKey,
            errorMessages,
            title,
            visible,
            stickers,
            departments,
            selectedDepartmentId,
            formState,
            cancelPriority,
            cancelSticker,
            priorities,
            loadingExport,
            loadingImport,
            priority_columns,
            sticker_columns,
            arrayColumnsEdit,
            editableData,
            edit,
            save,
            cancel,
            is_add_priority,
            is_add_sticker,
            ShowWithConfigMode,
            onChangeTab,
            onChangeAddPriority,
            onChangeAddSticker,
            onClickStorePriorityButton,
            onChangeLabel,
            onClickDeletePriorityButton,
            onClickDeleteStickerButton,
            onChangeOrdinalNumber,
            onChangeStickerName,
            onChangeDepartment,
            onClickStoreStickerButton,
            onChangeSelectDepartment,
            doExport,
            doImport,
            beforeUpload,
            fileName,
            handleCancel
        };
    }
})
</script>
<style lang="scss">
    .editable-row-operations a {
        margin-right: 8px;
    }
</style>