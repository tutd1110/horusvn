import { nextTick } from 'vue';
import { ElLoading } from 'element-plus'

let loadingInstance: ReturnType<typeof ElLoading.service>;
export const openLoading = (targetClass: string) => {
    // Find the target element for the loading indicator
    const targetElement = document.querySelector(`.${targetClass}`) as HTMLElement;

    // Show the loading indicator before loading data
    loadingInstance = ElLoading.service({
        target: targetElement,
        fullscreen: true,
    });

    // Return the loading instance so it can be used later to close the loading
    return loadingInstance;
};
export const closeLoading = () => {
    // Close the loading instance asynchronously after the DOM update cycle
    // nextTick(() => {
    //     loadingInstance.close();
    // });
    // Close the loading instance asynchronously after the DOM update cycle and delay
    // nextTick(() => {
    //     setTimeout(() => {
    //         loadingInstance.close();
    //     }, 1000);
    // });
    loadingInstance.close();
};