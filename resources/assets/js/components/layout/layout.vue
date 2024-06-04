<template>
    <div class="common-layout">
        <el-container>
            <el-header class="custom-header">
                <menu-bar></menu-bar>
            </el-header>
            <template v-if="title != 'Home' && title != 'Calendar'">
                <el-main>
                    <el-page-header @back="goBack">
                        <template #content>
                            <span class="text-large font-600 mr-3">{{ title }}</span>
                        </template>
                        <div class="mt-4 text-sm font-bold">
                            <slot></slot>
                        </div>
                    </el-page-header>
                </el-main>
            </template>
            <template v-else>
                <slot></slot>
            </template>
        </el-container>
    </div>
</template>
<script>
import { onMounted, ref, computed } from 'vue';
import MenuBar from '../MenuBar/MenuBar.vue';

export default ({
    components: {
        MenuBar
    },
    props: {
        title: {
            type: String,
            required: true,
        }
    },
    setup() {
        const goBack = () => {
            window.history.back(); // Go back to the previous URL
        }

        return {
            goBack
        }
    }
})
</script>
<style lang="scss">
.common-layout {
    background-color: white;
}
.custom-header {
    padding: 0;
}
.mt-4 {
    margin-top: 1rem;
}
</style>