<template>
    <div class="tracking-container" style="height: 850px;">
        <el-table :data="filterTableData" style="width: 100%;">
            <el-table-column label="ID" prop="value" />
            <el-table-column label="Name" prop="label" sortable />
            <!-- <el-table-column label="Url" prop="url" /> -->
            <el-table-column align="center">
                <template #header>
                    <!-- <el-input v-model="search" size="nomal" placeholder="Type to search" /> -->
                    <el-input
                        v-model="search"
                        class="w-50 m-2"
                        placeholder="Tìm kiếm theo tên"
                        :prefix-icon="Search"
                        />
                </template>
                <template #default="scope">
                    <el-button type="success" v-on:click="blankUrl(scope.row.url)">Truy cập</el-button>
                </template>
            </el-table-column>
        </el-table>
    </div>
</template>

<script lang="ts" setup>
    import axios from 'axios';
    import { onMounted,  ref, computed } from 'vue';
    import dayjs from 'dayjs';
    import { Search } from '@element-plus/icons-vue'
    import { callMessage } from '../Helper/el-message.js';
    import { openLoading, closeLoading } from '../Helper/el-loading';

    const dataSource = ref();
    const errorMessages = ref();

    const search = ref('')
    const filterTableData = computed(() =>
        Array.isArray(dataSource.value)
            ? dataSource.value.filter(
                (data) => !search.value || data.label.toLowerCase().includes(search.value.toLowerCase())
            )
            : []
    )

    const _fetch = () => {
        openLoading('custom-scrollbar'); // Open the loading indicator before loading data
        axios.get('/api/tracking_game/get_tracking_game', {
        })
        .then(response => {
            dataSource.value = response.data;
            console.log(dataSource.value);
            closeLoading(); // Close the loading indicator
        })
        .catch((error) => {
            closeLoading(); // Close the loading indicator
            errorMessages.value = error.response.data.errors;//put message content in ref
            //When search target data does not exist
            dataSource.value = []; //dataSource empty
            callMessage(errorMessages.value, 'error');
        });
    }
    const blankUrl = (url:string) => {
        window.open(url, '_blank');
    }

    onMounted(() => {
        _fetch()
    });
</script>
