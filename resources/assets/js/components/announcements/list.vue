<template>
    <a-comment
        v-for="(item, index) in posts"
        :key="item.id"
    >
        <template #actions>
            <span key="comment-basic-like">
                <a-tooltip title="Like">
                    <template v-if="action === 'liked'">
                        <LikeFilled @click="like" />
                    </template>
                    <template v-else>
                        <LikeOutlined @click="like" />
                    </template>
                </a-tooltip>
                <span style="padding-left: 8px; cursor: auto">
                    {{ likes }}
                </span>
            </span>
            <span key="comment-basic-dislike">
                <a-tooltip title="Dislike">
                    <template v-if="action === 'disliked'">
                        <DislikeFilled @click="dislike" />
                    </template>
                    <template v-else>
                        <DislikeOutlined @click="dislike" />
                    </template>
                </a-tooltip>
                <span style="padding-left: 8px; cursor: auto">
                    {{ dislikes }}
                </span>
            </span>
            <span key="comment-basic-reply-to">Reply to</span>
        </template>
        <template #author><a style="font-weight: bold">{{ item.fullname }}</a></template>
        <template #avatar>
            <a-avatar :src="`${/image/}${item.avatar}`" />
        </template>
        <template #content>
            <a-row>
                <a-col>
                    <p><span style="font-weight: bold">{{ item.title }}</span></p>
                </a-col>
            </a-row>
            <a-row style="margin-top: 10px;">
                <div v-html="renderHTML(item.content)"></div>
            </a-row>
            <a-row style="margin-top: 10px;">
                <a-col
                    v-for="(image, index1) in item.post_files"
                    :key="image.id"
                >
                    <a-image :src="image.path" style="width: 200px; height:200px; margin-right:10px; border-radius: 50%; border: 1px solid #d9d9d9;"></a-image>
                </a-col>
            </a-row>
            <a-row style="margin-top: 10px;">
                <p>Thanks.</p><heart-two-tone twoToneColor="#eb2f96" />
            </a-row>
        </template>
        <template #datetime>
            <a-tooltip v-bind:title="formattedCreatedAt(item.created_at)">
                <span>{{ formattedFromNow(item.created_at) }}</span>
            </a-tooltip>
        </template>
  </a-comment>
</template>
<script>
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { LikeFilled, LikeOutlined, DislikeFilled, DislikeOutlined, HeartTwoTone } from '@ant-design/icons-vue';
import { onMounted, ref, h } from 'vue';
export default ({
    components: {
        LikeFilled,
        LikeOutlined,
        DislikeFilled,
        DislikeOutlined,
        HeartTwoTone
    },
    setup() {
        const likes = ref(0);
        const dislikes = ref(0);
        const action = ref();
        dayjs.extend(relativeTime)

        const like = () => {
            likes.value = 1;
            dislikes.value = 0;
            action.value = 'liked';
        };

        const dislike = () => {
            likes.value = 0;
            dislikes.value = 1;
            action.value = 'disliked';
        };
        const posts = ref([]);

        const _fetch = () => {
            axios.get('/api/announcements/latest')
            .then(response => {
                posts.value = response.data
            })
        };

        const renderHTML = (htmlString) => {
            let content = "";

            if (htmlString) {
                const parser = new DOMParser();
                const parsed = parser.parseFromString(htmlString, "text/html");
                content = parsed.body.innerHTML;
            }
            
            return content;
        }

        const formattedCreatedAt = (date) => {
            let dateOject = dayjs(date, 'DD/MM/YYYY HH:mm:ss');
            return dayjs(dateOject).format('DD:MM:YYYY HH:mm:ss');
        }

        const formattedFromNow = (date) => {
            let dateOject = dayjs(date, 'DD/MM/YYYY HH:mm:ss');
            return dayjs(dateOject).fromNow();
        }

        onMounted(() => {
            _fetch();
        });

        return {
            posts,
            likes,
            dislikes,
            action,
            like,
            dislike,
            renderHTML,
            formattedCreatedAt,
            formattedFromNow
        };
    },
});
</script>