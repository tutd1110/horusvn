<template>
    <a-breadcrumb style="margin: 16px 0; padding-top: 14px;">
        <a-breadcrumb-item>{{ main_bread_crumb }}</a-breadcrumb-item>
        <a-breadcrumb-item v-if="bread_crumb_lv1">{{ bread_crumb_lv1 }}</a-breadcrumb-item>
        <a-breadcrumb-item v-if="bread_crumb_lv2">{{ bread_crumb_lv2 }}</a-breadcrumb-item>
    </a-breadcrumb>
</template>
<script>
import { onMounted, ref } from 'vue';

export default ({
    setup() {
        const main_bread_crumb = ref("Home");
        const bread_crumb_lv1 = ref("");
        const bread_crumb_lv2 = ref("");

        const getPathUrl = () => {
            let path_name = window.location.pathname

            let paths = path_name.split("/")

            bread_crumb_lv1.value = paths[1] ? capitalizeFirstLetter(paths[1]) : ""
            if (bread_crumb_lv1.value == "Home") {
                bread_crumb_lv1.value = ""
            }

            bread_crumb_lv2.value = paths[2] ? capitalizeFirstLetter(paths[2]) : ""
        }

        const capitalizeFirstLetter = (string) => {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        onMounted(() => {
            getPathUrl();
        });

        return {
            main_bread_crumb,
            bread_crumb_lv1,
            bread_crumb_lv2,
            getPathUrl
        };
    },
})
</script>
