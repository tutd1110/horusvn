<template>
    <el-dialog v-model="visible" style="width:80%; font-weight: bold;" title="Lịch sử đánh giá">
        <el-row>
            <el-col :span="4" style="margin-right: 10px">
                <label>Thời gian đánh giá</label>
                <a-space>
                    <!-- <a-range-picker v-model:value="datePeriod" :allowEmpty="[true,true]" :format="dateFormat" /> -->
                    <el-date-picker
                        v-model="datePeriod"
                        type="daterange"
                        range-separator="To"
                        start-placeholder="Start date"
                        end-placeholder="End date"
                        :format="dateFormat"
                        style="width: 100%;"
                    />
                </a-space>
            </el-col>
            <el-col :span="3" :offset="0" style="margin-right: 10px">
                <label>Người đánh giá</label>
                <a-form-item :span="5">
                    <el-select 
                        v-model="formState.user_id" 
                        clearable
                        filterable
                        placeholder="Select"
                        style="width: 100%;"
                    >
                        <el-option
                            v-for="item in users"
                            :key="item.id"
                            :label="item.fullname"
                            :value="item.id"
                        />
                    </el-select>
                </a-form-item>
            </el-col>                                                                                     
            <el-col :span="2" :offset="0" style="padding-top: 12px;">
                <a-button block type="primary" v-on:click="search()">Search</a-button>
            </el-col>
        </el-row>
        <!-- table from here -->
        <el-row style="margin-top:30px;">
            <el-col :span="24">
                <a-table :dataSource="dataSource" :columns="columns" :loading="isLoading" :scroll="{  y: 450 }" style="white-space:pre-wrap"
                    @change="onChangeTable" :pagination="paginate">
                </a-table>
            </el-col>
        </el-row>
    </el-dialog>
</template>
<script>
import { Modal } from 'ant-design-vue';
import { onMounted, ref, h } from 'vue';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs';

export default ({
    setup() {
        const { t } = useI18n();
        const users = ref([]);
        const dataSource = ref();
        const errorMessages = ref("");
        const dateFormat = 'YYYY/MM/DD';
        const formState = ref([]);
        const isLoading = ref(false);
        const datePeriod = ref([]);
        const paginate = ref({
            current: 1,
            pageSize: 20,
            total: 0,
            position: ['bottomCenter'],
            showSizeChanger: false
        });
        const columns = ref([
            {
                title: 'Fullname',
                dataIndex: 'fullname',
                key: 'fullname',
                fixed: false,
                align: 'center',
                ellipsis: true,
                width: 150,
            },
            {
                title: 'Loại review',
                dataIndex: 'period_name',
                key: 'period_name',
                fixed: false,
                align: 'center',
                width: 100,
            },
            {
                title: 'Người được đánh giá',
                dataIndex: 'review_user',
                key: 'review_user',
                fixed: false,
                align: 'center',
                width: 100,
            },
            {
                title: 'Ngày gửi đánh giá',
                dataIndex: 'created_at',
                key: 'created_at',
                fixed: false,
                align: 'center',
                width: 100,
            },
        ]);
        const search = () => {
            handleDatePeriod()
            //loading icon
            isLoading.value = true;

            callApi();
        }

        const onChangeTable = (pagination, filters, sorter, {currentDataSource}) => {
            paginate.value.current = pagination.current

            callApi();
        }
        const visible = ref(false)
        const ShowReviewSubmit = () => {
            visible.value = true;
        };

        const callApi = () => {
            isLoading.value = true;

            axios.get('/api/log/get_log_review', {
                params:{
                    user_id: formState.value.user_id,
                    start_time: formState.value.start_time,
                    end_time: formState.value.end_time,
                    current_page: paginate.value.current,
                    // uri: formState.value.uri,
                    uri: '/api/employee/review/submit',
                    // request_body: formState.value.request_body,
                }
            })
            .then(response => {
                    //stop loading icon
                    isLoading.value = false;

                    dataSource.value = response.data.items;
                    paginate.value.total = response.data.totalItems
                })
            .catch((error) => {
                isLoading.value = false;
                errorMessages.value = error.response.data.errors;//put message content in ref
                //When search target data does not exist
                dataSource.value = []; //dataSource empty
                errorModal();//Show error message modally
            });
        }

        const handleDatePeriod = () => {
            if (datePeriod.value !== null && datePeriod.value !== undefined) {
                var startDay = 0;
                var processedStartDay = '';
                    //is the start time entered?
                if (datePeriod.value[startDay] !== null && datePeriod.value[startDay] !== undefined) {
                    processedStartDay = datePeriod.value[startDay];
                    formState.value.start_time = dayjs(processedStartDay).format(dateFormat);
                }
                
                var endDay = 1;
                var processedEndDay = '';
                    //is the end time entered?
                if (datePeriod.value[endDay] !== null && datePeriod.value[endDay] !== undefined) {
                    processedEndDay = datePeriod.value[endDay];
                    formState.value.end_time = dayjs(processedEndDay).format(dateFormat);
                }
            } else {
                formState.value.start_time = null;
                formState.value.end_time = null;
            }
        };

        const errorModal = () => {
            Modal.warning({
                title: t('message.MSG-TITLE-W'),
                content: h('ul', {}, errorMessages.value.split('<br>').map((error) => { return h('li', error) })),
                onOk() {
                    //stop loading icon
                    isLoading.value = false;
                }
            });
        };

        const _fetch = () => {
            //create select boxes
            axios.get('/api/log/get_selboxes')
            .then(response => {
                users.value = response.data;
            })
        };

        onMounted(() => {
            _fetch();
        });

        return {
            users,
            dataSource,
            errorMessages,
            formState,
            datePeriod,
            dateFormat,
            columns,
            paginate,
            onChangeTable,
            isLoading,
            search,
            ShowReviewSubmit,
            visible
        };
    }
})
</script>