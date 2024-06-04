<template>
    <el-row>
        <!-- Table here -->
        <el-table :data="dataSource" table-layout="auto" resizable="true" border>
            <el-table-column type="expand">
                <template #default="props">
                    <el-row :gutter="20">
                        <el-col :span="18" :offset="1">{{props.row.description}}</el-col>
                        <el-col :span="5">
                            <div class="slideshow">
                                <template v-for="(image, idx) in props.row.images" :key="idx+1">
                                    <el-image
                                        style="width: 70px; height: 70px"
                                        :src="image"
                                        :zoom-rate="1.2"
                                        :initial-index="4"
                                        :preview-src-list="props.row.images"
                                        fit="cover"
                                    />
                                </template>
                            </div>
                        </el-col>
                    </el-row>
                </template>
            </el-table-column>
            <el-table-column label="Name" prop="fullname" />
            <el-table-column label="Time" prop="time" />
            <el-table-column label="Type" prop="type" />
            <el-table-column align="right">
                <template #header>
                    <el-button size="small" type="success" @click="handleCreate">Create</el-button>
                </template>
                <template #default="scope">
                    <el-button size="small" @click="handleEdit(scope.row)">
                        Edit
                    </el-button>
                    <el-button size="small" type="danger" @click="handleDelete(scope.row)">
                        Delete
                    </el-button>
                </template>
            </el-table-column>
        </el-table>
    </el-row>
    <template v-if="mode">
        <!-- Form Input -->
        <el-form :model="formState" label-width="120px" label-position="left">
            <el-form-item label="Nhóm vi phạm">
                <el-select
                    v-model="formState.type"
                    value-key="value"
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in typeSelbox"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-form-item>
            <el-form-item label="Thời gian">
                <el-date-picker
                    v-model="formState.time"
                    type="datetime"
                    placeholder="Select date and time"
                    format="DD/MM/YYYY HH:mm:ss"
                    value-format="YYYY/MM/DD HH:mm:ss"
                />
            </el-form-item>
            <el-form-item label="Chi tiết">
                <el-input v-model="formState.description" type="textarea" autosize/>
            </el-form-item>
            <el-form-item label="Hình ảnh">
                <el-upload
                    v-model:file-list="fileList"
                    class="upload-demo"
                    action="/api/violation/store"
                    list-type="picture"
                    :auto-upload="false"
                >
                    <el-button type="primary">Click to upload</el-button>
                    <template #tip>
                        <div class="el-upload__tip">
                            only images file can be uploaded.
                        </div>
                    </template>
                </el-upload>
            </el-form-item>
            <el-form-item>
                <el-button type="success" @click="submitForm" v-if="mode === 'create'">
                    Create
                </el-button>
                <el-button type="success" v-if="mode === 'update'" @click="updateForm">
                    Update
                </el-button>
                <el-button type="info" @click="cancel">
                    Cancel
                </el-button>
            </el-form-item>
        </el-form>
    </template>
</template>
<script>
import { onMounted, computed, watch, ref } from 'vue';
import { callMessage } from '../../Helper/el-message.js';

export default ({
    name: 'employee-violation',
    props: {
        visible: {
            type: Boolean,
            required: true,
        },
        employeeId: {
            type: Number,
            required: true,
        }
    },
    setup(props) {
        const errorMessages = ref();
        const dataSource = ref([]);
        const mode = ref('');
        const typeSelbox = ref([]);
        const formState = ref({});
        const fileList = ref([]);
        const successFiles = computed(() => fileList.value.filter(file => file.status === 'success').map(file => file.uid));
        const readyFiles = computed(() => fileList.value.filter(file => file.status === 'ready').map(file => file.raw));
        const formClear = () => {
            formState.value.id = "";
            formState.value.type = "";
            formState.value.time = "";
            formState.value.description = "";
            fileList.value = [];
        };
        const submitForm = async () => {
            let submitData = {
                type: formState.value.type,
                employee_id: props.employeeId,
                time: formState.value.time,
                description: formState.value.description
            };

            try {
                // Step 1: Make the first API request to create the violation
                const violationId = await createViolation(submitData);

                // Step 2: Make the second API request to upload the images
                if (fileList.value.length > 0 && violationId) {
                    await uploadImage(violationId, successFiles.value, readyFiles.value);
                }

                // Success: Provide feedback to the users
                callMessage('Violation successfully saved.', 'success');

                //reload
                getViolations();
                formClear();
            } catch (error) {
                // Handle the error from createViolation
                errorMessages.value = error.response.data.errors;
                callMessage(errorMessages.value, 'error');
            }
        }
        const updateForm = async () => {
            let submitData = {
                id: formState.value.id,
                type: formState.value.type,
                employee_id: props.employeeId,
                time: formState.value.time,
                description: formState.value.description
            };

            try {
                const response = await axios.patch('/api/violation/update', submitData);

                await uploadImage(formState.value.id, successFiles.value, readyFiles.value);

                callMessage(response.data.success, 'success');

                //reload
                getViolations();
                formClear();
            } catch (error) {
                errorMessages.value = error.response.data.errors; // put message content in ref
                callMessage(errorMessages.value, 'error');
            }
        };
        const createViolation = async (submitData) => {
            try {
                const response = await axios.post('/api/violation/store', submitData);

                return response.data.id; // Assuming the response contains the id
            } catch (error) {
                throw error; // Re-throw the error to handle it in the submitForm function
            }
        };
        const uploadImage = async (violationId, successFiles, readyFiles) => {
            try {
                let formData = new FormData();
                readyFiles.forEach((file, index) => {
                    if (file instanceof File) {
                        formData.append(`ready_files[${index}]`, file);
                    }
                });

                if (successFiles.length === 0) {
                    formData.append('success_files', '');
                } else {
                    successFiles.forEach((file, index) => {
                        formData.append(`success_files[${index}]`, file);
                    });
                }
                formData.append('violation_id', violationId);
                formData.append('mode', mode.value);

                const response = await axios.post('/api/violation/uploadImage', formData);

                return response.data.success;
            } catch (error) {
                throw error; // Re-throw the error to handle it in the submitForm function
            }
        }
        const handleCreate = () => {
            mode.value = 'create';
            formClear();
        }
        const handleEdit = (row) => {
            mode.value = 'update';
            axios.get('/api/violation/get_violations_by_id', {
                params: {
                    id: row.id
                }
            })
            .then(response => {
                formState.value = response.data;

                let files = response.data.files
                fileList.value = files.map(file => ({
                    uid: file.id,
                    name: file.path.split('/').pop(),
                    url: '/' + file.path // set the url to the full path to the file
                }));
            }).catch((error) => {
                formState.value = {};
            });
        }
        const handleDelete = (row) => {
            axios.delete('/api/violation/delete', {
                params: {
                    id: row.id
                }
            })
            .then(response => {
                //get list violations
                getViolations();
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                callMessage(errorMessages.value, 'error');
            })
        }
        const cancel = () => {
            mode.value = '';
            formClear();
        }
        const _fetch = () => {
            //create select boxes
            axios.get('/api/violation/get_types')
            .then(response => {
                typeSelbox.value = response.data;
            }).catch((error) => {
                typeSelbox.value = [];
            });
            formClear();

            //get list violations
            getViolations();
        };
        const getViolations = () => {
            axios.get('/api/violation/get_violations_by_employee_id', {
                params: {
                    employee_id: props.employeeId
                }
            })
            .then(response => {
                dataSource.value = transferData(response.data);
            }).catch((error) => {
                dataSource.value = [];
            });
        }
        const transferData = (data) => {
            var newData = [];

            data.forEach(function(item, index) {
                const imageList = item.files.map((file) => '/' + file.path);
                const typeObject = typeSelbox.value.find(type => type.value === item.type);

                let value = {
                    id: item.id,
                    fullname: item.fullname,
                    time: item.time,
                    description: item.description,
                    type: typeObject ? typeObject.label : "",
                    images: imageList,
                };

                newData.push(value);
            });

            return newData;
        };
        onMounted(() => {
            watch(() => props.visible, (newVal, oldVal) => {
                if (newVal) {
                    dataSource.value = []
                
                    _fetch();
                }
            },
            {
                immediate: true
            })
        });

        return {
            mode,
            dataSource,
            formState,
            typeSelbox,
            fileList,
            submitForm,
            updateForm,
            cancel,
            handleCreate,
            handleEdit,
            handleDelete
        }
    }
})
</script>