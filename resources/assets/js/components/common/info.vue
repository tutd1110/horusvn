<template>
    <a-modal v-model:visible="visible" :style="stringStyle" :footer="null" :maskClosable="true"
        :closable="true" :title="title">
        <a-tabs v-model:activeKey="activeKey" :tab-position="tabPosition" animated @change="onChangeTab()">
            <a-tab-pane key="1" tab="Tổng quát">
                <span>Tổng số công việc: {{ dataSource.total_task }}</span>
                <br>
                <span>Đang chờ: {{ dataSource.total_wait }}</span>
                <br>
                <span>Đang làm: {{ dataSource.total_processing  }}</span>
                <br>
                <span>Tạm dừng: {{ dataSource.total_pause }}</span>
                <br>
                <span>Hoàn thành: {{ dataSource.total_complete  }}</span>
                <br>
                <span>Chờ feedback: {{ dataSource.total_wait_fb  }}</span>
                <br>
                <span>Làm lại: {{ dataSource.total_again }}</span>
                <br>
                <span>Quá hạn: {{ dataSource.total_slow }}</span>
                <br>
                <span>Thời gian làm việc dự kiến: {{ dataSource.total_estimate_time }}</span>
                <br>
                <span>Thời gian làm việc thực tế: {{ dataSource.total_time_spent }}</span>
                <br>
                <span>Tổng trọng số: {{ dataSource.total_weight }}</span>
                <br>
                <span>Trọng số đã tích luỹ được: {{ dataSource.total_weight_complete }}</span>
            </a-tab-pane>
            <a-tab-pane key="2" tab="Chi tiết" v-if="is_show_detail_tab">
                <!-- table from here -->
                <a-row style="margin-top:0px">
                        <a-col>
                            <a-table size="small" :dataSource="details" :columns="columns" :scroll="{ x: 900, y: 900 }"
                                :pagination="false" :row-class-name="rowClassName">
                                <template #bodyCell="{column,record}">
                                    <template v-if="column.key === 'final_deadline'" data-index="final_deadline">
                                        <span v-if="record.warning === true" style="color: red">{{record.final_deadline}}</span>
                                        <span v-else>{{record.final_deadline}}</span>
                                    </template>
                                </template>
                            </a-table>
                        </a-col>
                </a-row>
            </a-tab-pane>
        </a-tabs>
    </a-modal>
</template>
<script>
import { ref } from 'vue';
import dayjs from 'dayjs';

export default ({
    setup() {
        const visible = ref(false);
        const title = ref("");
        const dataSource = ref({});
        const details = ref([]);
        const activeKey = ref('1');
        const tabPosition = ref('top');
        const objectParam = ref();
        const dateFormat = "DD/MM/YYYY";
        const stringStyle = ref("width:600px; font-weight: bold");
        const is_show_detail_tab = ref(false);

        const columns = ref([
            {
                title: 'Họ và tên',
                dataIndex: 'fullname',
                key: 'fullname',
                align: 'center',
                width: 50,
            },
            {
                title: 'Final Deadline',
                dataIndex: 'final_deadline',
                key: 'final_deadline',
                align: 'center',
                width: 40,
            },
            {
                title: 'Việc đang chờ',
                dataIndex: 'total_wait',
                key: 'total_wait',
                fixed: false,
                align: 'center',
                width: 30,
            },
            {
                title: 'Việc đang làm',
                dataIndex: 'total_processing',
                key: 'total_processing',
                fixed: false,
                align: 'center',
                width: 30,
            },
            {
                title: 'Việc tạm dừng',
                dataIndex: 'total_pending',
                key: 'total_pending',
                fixed: false,
                align: 'center',
                width: 30,
            },
            {
                title: 'Việc hoàn thành',
                dataIndex: 'total_completed',
                key: 'total_completed',
                fixed: false,
                align: 'center',
                width: 30,
            },
            {
                title: 'Việc quá hạn',
                dataIndex: 'total_overdue',
                key: 'total_overdue',
                fixed: false,
                align: 'center',
                width: 30,
            },
            {
                title: 'Chưa nhập Deadline',
                dataIndex: 'none_deadline',
                key: 'none_deadline',
                fixed: false,
                align: 'center',
                width: 30,
            }
        ]);

        //info mode
        const ShowWithInfoMode = (object, string) => {
            title.value = "Thông tin làm việc"
            visible.value = true;
            objectParam.value = object;
            activeKey.value = '1';

            if (string == 'department') {
                is_show_detail_tab.value = true
            }
            
            axios.get('/api/'+string+'/task/get_task_info', {
                params:object
            })
            .then(response => {
                dataSource.value = response.data;

                dataSource.value.total_estimate_time = roundFloat(response.data.total_estimate_time)
                dataSource.value.total_time_spent = roundFloat(response.data.total_time_spent)
            })
            .catch((error) => {
                dataSource.value = {}; //dataSource empty
            });
        }

        const onChangeTab = () => {
            if (activeKey.value == '2') {
                getTaskInfoDetail();
                stringStyle.value = "width:1000px; font-weight: bold"
            } else if (activeKey.value == '1') {
                // getStickers();
                stringStyle.value = "width:600px; font-weight: bold"
            }
        }

        const getTaskInfoDetail = () => {
            axios.get('/api/department/task/get_task_info_by_employee', {
                params:objectParam.value
            })
            .then(response => {
                details.value = transferData(response.data);
            })
            .catch((error) => {
                details.value = []; //dataSource empty
            });
        }

        const transferData = (data) => {
            try {
                var newData = [];

                data.forEach(function(item, index) {
                    let deadline = item.final_deadline ? dayjs(item.final_deadline) : null
                    let warning = false

                    if (deadline instanceof dayjs) {
                        warning = deadline.isBefore(dayjs(), 'day') ? true : false
                    }

                    let value = {
                        total_wait: item.total_wait,
                        total_processing: item.total_processing,
                        total_pending: item.total_pending,
                        total_completed: item.total_completed,
                        total_overdue: item.total_overdue,
                        fullname: item.fullname,
                        final_deadline: deadline ? dayjs(deadline).format(dateFormat) : null,
                        warning: warning,
                        none_deadline: item.none_deadline
                    };

                    newData.push(value);
                });

                return newData;
            } catch (error) {
                console.log(error)
            }
        }

        const rowClassName = (record, index) => {
            if (record.total_wait == 0 && record.total_processing == 0) {
                return 'table-row-darkgrey';
            }
        };

        const roundFloat = (num) => {
            let power = 10;
            let decimals = 1;

            return Math.round(num * Math.pow(power, decimals)) / Math.pow(power, decimals);
        }

        return {
            dataSource,
            rowClassName,
            details,
            columns,
            is_show_detail_tab,
            activeKey,
            stringStyle,
            tabPosition,
            visible,
            title,
            ShowWithInfoMode,
            onChangeTab
        };
    }
})
</script>
<style lang="scss">
.table-row-darkgrey {
  color: darkgrey;
}
</style>