<template>
    <a-modal v-model:visible="visible" style="width:1500px; font-weight: bold" :footer="null" :maskClosable="false" :closable="true" :title="title">
        <a-row>
            <a-table :dataSource="dataSource" :columns="columns" style = "white-space:pre-wrap"
            :pagination="{position: ['bottomCenter'],pageSize:5,showSizeChanger: false}">
            </a-table>
        </a-row>
    </a-modal>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import axios from 'axios';
import { ref, h } from 'vue';
import dayjs from 'dayjs';
import { useI18n } from 'vue-i18n';

export default ({
    emits: ['saved'],
    setup(props, { emit }) {
        const title = ref("");
        const visible = ref(false);
        const dataSource = ref();
        const columns = ref([
            {
                title: 'Line',
                dataIndex: 'line',
                key: 'line',
                fixed: false,
                align: 'center',
                width: 50,
            },
            {
                title: 'ID',
                dataIndex: 'id',
                key: 'id',
                align: 'center',
                width: 150,
            },
            {
                title: 'Project',
                dataIndex: 'project',
                key: 'project',
                align: 'center',
                width: 150,
            },
            {
                title: 'Department',
                dataIndex: 'department',
                key: 'department',
                align: 'center',
                width: 150,
            },
            {
                title: 'Sticker',
                dataIndex: 'sticker',
                key: 'sticker',
                align: 'center',
                width: 150,
            },
            {
                title: 'Priority',
                dataIndex: 'priority',
                key: 'priority',
                align: 'center',
                width: 150,
            },
            {
                title: 'Fullname',
                dataIndex: 'fullname',
                key: 'fullname',
                align: 'center',
                width: 150,
            },
            {
                title: 'Start Time',
                dataIndex: 'start_time',
                key: 'start_time',
                align: 'center',
                width: 150,
            },
            {
                title: 'End Time',
                dataIndex: 'end_time',
                key: 'end_time',
                align: 'center',
                width: 150,
            },
            {
                title: 'Time',
                dataIndex: 'time',
                key: 'time',
                align: 'center',
                width: 150,
            },
            {
                title: 'Real Time',
                dataIndex: 'real_time',
                key: 'real_time',
                align: 'center',
                width: 150,
            },
            {
                title: 'Progress',
                dataIndex: 'progress',
                key: 'progress',
                align: 'center',
                width: 150,
            },
            {
                title: 'Status',
                dataIndex: 'status',
                key: 'status',
                align: 'center',
                width: 150,
            },
            {
                title: 'Action',
                dataIndex: 'action',
                key: 'action',
                align: 'center',
                width: 150,
            },
        ]);

        //new mode
        const ShowWithPreviewMode = (errors) => {
            title.value = "Preview Errors"
            visible.value = true;

            dataSource.value = errors
        };

        return {
            dataSource,
            columns,
            title,
            visible,
            ShowWithPreviewMode
        };
    }
})
</script>