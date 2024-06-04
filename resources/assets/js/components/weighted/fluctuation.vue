<template>
    <a-row>
        <template v-for="(record, index) in ranking">
            <a-col :span="4" :offset="index == 0 ? 6 : 0" style="margin-right: 20px">
                <a-card hoverable style="width: 100%; box-shadow: 0px 2px 6px rgba(0, 0, 0, 0.1);">
                    <template #cover>
                        <img
                            :src="record.avatar"
                            style="width: 80px; height: 80px; border-radius: 50%; margin: auto; display: block;"
                        />
                    </template>
                    <template #actions>
                        <a-tag  :color="index === 0 ? 'gold' : index === 1 ? 'silver' : '#B87333'">
                            <span style="font-weight: bold">{{ "Tier "+ (index+1) }}</span>
                        </a-tag>
                        <span style="font-weight: bold">{{ record.total }}</span>
                    </template>
                    <a-card-meta :title="record.fullname" :description="record.department">
                    </a-card-meta>
                </a-card>
            </a-col>
        </template>
    </a-row>
    <a-list
        class="demo-loadmore-list"
        :loading="initLoading"
        item-layout="horizontal"
        :data-source="list"
    >
        <template #renderItem="{ item }">
            <a-list-item>
                <a-skeleton avatar :title="false" :loading="!!item.loading" active>
                    <a-list-item-meta>
                        <template #description>
                            <a-row>
                                <span>{{ "+ " + item.weight + " trọng số" }}</span>
                            </a-row>
                            <a-row>
                                <span>{{ "Project: " + item.project_name }}</span>
                            </a-row>
                            <a-row>
                                <span>{{ "Task: " + item.task_name }}</span>
                            </a-row>
                        </template>
                        <template #title>
                            <span>{{ item.fullname }}</span>
                        </template>
                        <template #avatar>
                            <a-avatar :src="`${/image/}${item.avatar}`" />
                        </template>
                    </a-list-item-meta>
                    <div>{{ formattedFromNow(item.created_at) }}</div>
                </a-skeleton>
            </a-list-item>
        </template>
    </a-list>
</template>
<script>
import { onMounted, ref, h, reactive, toRefs } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import { TIME_ZONE } from '../const.js'
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(utc);
dayjs.extend(timezone);

export default ({
    setup() {
        const list = ref([]);
        const ranking = ref([]);
        const initLoading = ref(true);
        const loading = ref(false);
        dayjs.extend(relativeTime)

        onMounted(() => {
            axios.get('/api/weighted/fluctuation/list')
            .then(response => {
                initLoading.value = false
                list.value = response.data
            })

            //get top 3 ranking
            axios.get('/api/weighted/fluctuation/get_leader_board')
            .then(response => {
                initLoading.value = false
                ranking.value = response.data
            })
        });

        const formattedFromNow = (date) => {
            let dateOject = dayjs(date).tz(TIME_ZONE.ZONE);
            return dayjs(dateOject).fromNow();
        }

        return {
            ranking,
            list,
            loading,
            initLoading,
            formattedFromNow
        };
    }
})
</script>
<style lang="scss">
.ant-card-bordered .ant-card-cover {
    margin-top: 8px;
    margin-right: -1px;
    margin-left: -1px;
}
.demo-loadmore-list {
  min-height: 350px;
}
</style>