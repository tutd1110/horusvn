<template>
    <a-table class="task" :dataSource="dataSource" :columns="columns" :pagination="false" bordered>
        <template #bodyCell="{column,record}">
            <template v-if="column.key === 'warrior'">
                <template v-if="record.warrior !== null">
                    <span :class="getWarriorClass(record.warrior)" style="font-weight: bold">{{ record.warrior }}</span>
                </template>
                <template v-else-if="record.start_date && record.end_date">
                    <a-button type="primary" v-on:click="onViewWarriorTitleButton(record.id, record.start_date, record.end_date)">View</a-button>
                </template>
            </template>
        </template>
    </a-table>
</template>
<script>
import { onMounted, watch, ref } from 'vue';
import { handleUserTimesheet } from '../../Helper/handle-user-timesheet.js';
import dayjs from 'dayjs';

export default ({
    name: 'project-info',
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
        const dataSource = ref();
        const dateFormat = 'DD/MM/YYYY';
        const columns = ref([
            {
                title: 'Tên dự án',
                dataIndex: 'name',
                key: 'name',
                align: 'center',
                width: 100,
            },
            {
                title: 'Ngày bắt đầu',
                dataIndex: 'start_date',
                key: 'start_date',
                fixed: false,
                align: 'center',
                width:  50,
                sorter: (a, b) => {
                    const dateA = dayjs(a.start_date, dateFormat);
                    const dateB = dayjs(b.start_date, dateFormat);
                    return dateA - dateB;
                },
            },
            {
                title: 'Ngày kết thúc',
                dataIndex: 'end_date',
                key: 'end_date',
                align: 'center',
                width: 50,
                sorter: (a, b) => {
                    const dateA = dayjs(a.start_date, dateFormat);
                    const dateB = dayjs(b.start_date, dateFormat);
                    return dateA - dateB;
                },
            },
            {
                title: 'Số ngày',
                dataIndex: 'duration',
                key: 'duration',
                align: 'center',
                width: 40,
                sorter: (a, b) => {
                    return a.duration - b.duration;
                },
            },
            {
                title: 'Warrior Dự án',
                dataIndex: 'warrior',
                key: 'warrior',
                fixed: false,
                align: 'center',
                ellipsis: true,
                resizable: true,
                width: 50
            },
            {
                title: 'Trọng số bộ phận',
                dataIndex: 'department_weight',
                key: 'department_weight',
                fixed: false,
                align: 'center',
                ellipsis: true,
                resizable: true,
                width: 50,
                sorter: (a, b) => {
                    return a.department_weight - b.department_weight;
                },
            },
            {
                title: 'Trọng số dự án',
                dataIndex: 'project_weight',
                key: 'project_weight',
                fixed: false,
                align: 'center',
                width: 50,
                sorter: (a, b) => {
                    return a.project_weight - b.project_weight;
                }
            }
        ]);
        const warriorTitle = ref("");

        const _fetch = () => {
            axios.get('/api/employee/profile/get_projects', {
                params:{
                    employee_id: props.employeeId,
                }
            })
            .then(response => {
                dataSource.value = response.data;
            })
            .catch((error) => {
                //When search target data does not exist
                dataSource.value = []; //dataSource empty
            });
        };

        const onViewWarriorTitleButton = (id, startDate, endDate) => {
            const formState = ref({
                employee_id: props.employeeId,
                start_date: dayjs(startDate, dateFormat).format("YYYY-MM-DD"),
                end_date: dayjs(endDate, dateFormat).format("YYYY-MM-DD")
            });

            //get warrior title
            axios.get('/api/timesheet/get_report', {
                params: formState.value
            })
            .then(response => {
                const current_start_date = dayjs(dayjs().startOf('month')).format(dateFormat);
                const current_end_date = dayjs(dayjs().endOf('month')).format(dateFormat);
                const args = [
                    response.data, "YYYY-MM-DD", formState, current_start_date, current_end_date, "profile"
                ]

                const employee = handleUserTimesheet(...args)

                const index = dataSource.value.findIndex(record => record.id === id);
                if (index !== -1) {
                    dataSource.value[index].warrior = employee.length > 0 ? employee[0].current_title : "";
                }
            })
            .catch((error) => {

            });
        }

        const getWarriorClass = (title) => {
            switch(title) {
                case 'Warrior 1':
                    return 'warrior-green';
                case 'Warrior 2':
                    return 'warrior-orange';
                case 'Warrior 3':
                    return 'warrior-red';
                default:
                return 'warrior-gray';
            }
        }

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
            dataSource,
            columns,
            warriorTitle,
            onViewWarriorTitleButton,
            getWarriorClass
        }
    }
})
</script>
<style lang="scss">
    .warrior-green {
        color: green;
    }

    .warrior-orange {
        color: orange;
    }

    .warrior-red {
        color: #800000;
    }

    .warrior-gray {
        color: gray;
    }
</style>