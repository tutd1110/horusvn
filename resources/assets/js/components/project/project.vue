<template>
    <creat-or-update ref="modalRef" @saved="onSaved"></creat-or-update>
    <el-row :gutter="20">
        <el-col :span="5">
            <label>Name</label>
            <el-select
                v-model="formState.name"
                clearable
                filterable
                style="width: 100%"
            >
                <el-option
                    v-for="item in projects"
                    :key="item.name"
                    :label="item.name"
                    :value="item.name"
                />
            </el-select>
        </el-col>
        <el-col :span="2">
            <label>Code</label>
            <el-select
                v-model="formState.code"
                clearable
                filterable
                style="width: 100%"
            >
                <el-option
                    v-for="item in projects"
                    :key="item.code"
                    :label="item.code"
                    :value="item.code"
                />
            </el-select>
        </el-col>
        <el-col :span="4">
            <label>Member</label>
            <el-select
                v-model="formState.user_ids"
                clearable
                filterable
                multiple
                collapse-tags
                collapse-tags-tooltip
                style="width: 100%"
            >
                <el-option
                    v-for="item in users"
                    :key="item.id"
                    :label="item.fullname"
                    :value="item.id"
                />
            </el-select>
        </el-col>
        <el-col :span="4">
            <label>Period Time</label>
            <el-date-picker
                v-model="datePeriod"
                type="daterange"
                start-placeholder="Start Date"
                end-placeholder="End Date"
                style="width: 100%"
                format="DD/MM/YYYY"
                value-format="YYYY/MM/DD"
            />
        </el-col>
        <el-col :span="2" style="margin-top: 22px">
            <el-space size="small" spacer="|">
                <el-button type="primary" @click="search()" :loading="loadingSearch">Search</el-button>
                <el-button type="success" @click="showRegisterModal">Create</el-button>
            </el-space>
        </el-col>
    </el-row>
    <!-- table from here -->
    <el-row>
        <el-table :data="dataSource" height="1030" border style="width: 100%">
            <el-table-column label="STT" prop="ordinal_number" width="100">
                <template #default="scope">
                    <el-input v-model="scope.row.ordinal_number" @change="onChangeOrdinalNumber(scope.row.id, $event)"/>
                </template>
            </el-table-column>
            <el-table-column label="Name" prop="name" width="400"/>
            <el-table-column label="Code" prop="code" width="200"/>
            <el-table-column label="Start" prop="start_date" width="150"/>
            <el-table-column label="End" prop="end_date" width="150"/>
            <el-table-column label="Note" >
                <template #default="scope">
                    <el-input v-model="scope.row.note" @change="update(scope.row.id, 'note', scope.row.note)"/>
                </template>
            </el-table-column>
            <el-table-column label="Change weight" align="right" width="200">
                <template #default="scope">
                    <el-switch
                        v-model="scope.row.change_weight"
                        class="ml-2"
                        style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949"
                        @change="update(scope.row.id, 'change_weight', scope.row.change_weight)"
                    />
                </template>
            </el-table-column>
            <el-table-column label="Operations" align="right" width="200">
                <template #default="scope">
                    <el-button size="small" @click="showEditModal(scope.row.id, scope.row.updated_at)">Edit</el-button>
                </template>
            </el-table-column>
        </el-table>
    </el-row>
</template>
<script>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { callMessage } from '../Helper/el-message.js';
import CreatOrUpdate from './CreatOrUpdate.vue';

export default ({
    components: {
        CreatOrUpdate,
    },
    setup() {
        const { t } = useI18n();
        const dataSource = ref();
        const loadingSearch = ref(false);
        const modalRef = ref();
        const errorMessages = ref("");
        const formState = ref([]);
        const users = ref([]);
        const projects = ref([]);
        const datePeriod = ref([]);
        const onAction = ref(false);//Detect user actions
        const showRegisterModal = () => {
            modalRef.value.ShowWithAddMode();
        };
        const showEditModal = (id, updated_at) => {
            modalRef.value.ShowWithUpdateMode(id, updated_at);
        };
        const search = () => {
            loadingSearch.value = true;
            handleDatePeriod();

            axios.get('/api/project/get_project', {
                params:{
                    user_ids: formState.value.user_ids,
                    name: formState.value.name,
                    code: formState.value.code,
                    start_date: formState.value.start_time,
                    end_date: formState.value.end_time
                }
            })
            .then(response => {
                loadingSearch.value = false;
                dataSource.value = response.data;
            })
            .catch((error) => {
                loadingSearch.value = false;
                errorMessages.value = error.response.data.errors;//put message content in ref
                //When search target data does not exist
                dataSource.value = []; //dataSource empty
                callMessage(errorMessages.value, 'error');
            });
        }
        const handleDatePeriod = () => {
            if (datePeriod.value) {
                formState.value.start_time = datePeriod.value[0]
                formState.value.end_time = datePeriod.value[1]
            } else {
                formState.value.start_time = null
                formState.value.end_time = null
            }
            //Detect user actions
            onAction.value = true;
        };
        const _fetch = () => {
            //create select boxes
            axios.get('/api/project/get_select_boxes')
            .then(response => {
                let data = response.data;

                users.value = data.users;
                projects.value = data.projects

                if (!onAction.value) {
                    datePeriod.value = [
                        data.date_period.date_start,
                        data.date_period.date_end
                    ]

                    handleDatePeriod()
                }

                getProject();
            })
        };
        const getProject = () => {
            loadingSearch.value = true;
            //get list projects
            axios.get('/api/project/get_project', {
                params: {
                    start_date: formState.value.start_time,
                    end_date: formState.value.end_time
                }
            })
            .then(response => {
                loadingSearch.value = false;
                dataSource.value = response.data;
            })
            .catch((error) => {
                loadingSearch.value = false;
                //When search target data does not exist
                dataSource.value = []; //dataSource empty
                errorMessages.value = error.response.data.errors;//put message content in ref
                callMessage(errorMessages.value, 'error');
            });
        };
        const onChangeOrdinalNumber = (id, event) => {
            update(id, 'ordinal_number', event)
        }
        const update = (id, column, value) => {
            let submitData = {
                id: id,
                [column]: value
            }

            axios.patch('/api/project/quick_update', submitData)
            .then(response => {
                callMessage(response.data.success, 'success');

                search();
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                callMessage(errorMessages.value, 'error');
                search();
            })
        }
        const onSaved = () => {
            onAction.value = true;
            _fetch();
        };
        onMounted(() => {
            onAction.value = false;
            _fetch();
        });
        return {
            dataSource,
            loadingSearch,
            formState,
            datePeriod,
            users,
            projects,
            search,
            onSaved,
            showRegisterModal,
            showEditModal,
            modalRef,
            onChangeOrdinalNumber,
            update
        };
    }
})
</script>