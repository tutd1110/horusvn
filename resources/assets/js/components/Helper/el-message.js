import { ElMessage } from 'element-plus'

export const callMessage = (message, type) => {
    ElMessage({
        dangerouslyUseHTMLString: true,
        message: message,
        type: type,
    })
};