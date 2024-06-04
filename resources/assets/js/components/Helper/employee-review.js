import { computed } from 'vue'
import _ from 'lodash'

export const defaultColumns = [
    {
        title: 'STT',
        key: 'stt',
        align: 'center',
        width: 50,
        customRender: (record) => {
            return record.index+1
        }
    },
    {
        title: 'Nội dung đánh giá',
        dataIndex: 'content',
        key: 'content',
        align: 'center',
        width: 500,
    },
    {
        title: '',
        dataIndex: 'employee_point',
        key: 'employee_point',
        align: 'center',
        width: 150,
    },
    {
        title: '',
        dataIndex: 'mentor_point',
        key: 'mentor_point',
        align: 'center',
        width: 150,
    },
    {
        title: '',
        dataIndex: 'leader_point',
        key: 'leader_point',
        fixed: false,
        align: 'center',
        width: 150,
    },
    {
        title: '',
        dataIndex: 'pm_point',
        key: 'pm_point',
        fixed: false,
        align: 'center',
        width: 150,
    }
];

export function useTotals(content) {
    const totals = computed(() => {
        let totalEmployeePoint = 0
        let totalMentorPoint = 0
        let totalLeaderPoint = 0
        let totalPmPoint = 0

        content.value?.forEach((item) => {
            totalEmployeePoint += item.employee_point
            totalMentorPoint += item.mentor_point
            totalLeaderPoint += item.leader_point
            totalPmPoint += item.pm_point
        })

        return {
            totalEmployeePoint,
            totalMentorPoint,
            totalLeaderPoint,
            totalPmPoint,
        }
    })

    return { totals }
}

export const assignEmployeeFullnameToColumns = (employees) => {

    let updatedColumns = _.cloneDeep(defaultColumns) // create a deep copy of defaultColumns

    for (let i = 0; i < employees.length; i++) {
        const item = employees[i]
        const position = item.position
        let columnKey = ''

        if (position == 0) {
            columnKey = 'employee_point'
        } else if (position == 0.5) {
            columnKey = 'mentor_point'
        } else if (position == 1) {
            columnKey = 'leader_point'
        } else if (position == 2) {
            columnKey = 'pm_point'
        }

        if (columnKey) {
            const columnIndex = updatedColumns.findIndex(column => column.key === columnKey)

            if (columnIndex >= 0 && columnIndex < updatedColumns.length) {
                updatedColumns[columnIndex].title = item.fullname
            }
        }
    }
    
    return updatedColumns.filter(column => column.title) // remove empty columns
}