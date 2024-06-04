import { Modal } from 'ant-design-vue';
import { h } from 'vue';

export const downloadFile = (url, submitData, errorMessages, t) => {
    return new Promise((resolve, reject) => {
        axios.post(url, submitData, { responseType: 'blob' })
        .then(response => {
            let fileURL = window.URL.createObjectURL(new Blob([response.data]));
            let fileLink = document.createElement("a");

            const headerval = decodeURIComponent(
                response.headers["content-disposition"]
            );
            var filename = headerval.split(";")[1].split("=")[1].replace('"', "").replace('"', "");

            fileLink.href = fileURL;
            fileLink.setAttribute("download", filename);
            document.body.appendChild(fileLink);

            fileLink.click();

            resolve()
        })
        .catch(async (error) => {
            // Get the error response data
            let errorData = error.response.data;
            // Extract the error message from the error data
            let errorMessage = await extractErrorMessage(errorData);

            errorMessages.value = errorMessage;//put message content in ref
            errorModal(t, errorMessages);//show error message modally

            reject();
        })
    })
}

const extractErrorMessage = async (errorData) => {
    let errorMessage = null;

    if (errorData instanceof Blob) {
        // Convert the error data to a text string
        let reader = new FileReader();
        reader.readAsText(errorData);
        return new Promise(resolve => {
            reader.onload = function() {
                let errorText = reader.result;
                let errorJson = JSON.parse(errorText);
                errorMessage = errorJson.errors;
                resolve(errorMessage);
            };
        });
    }

    return errorMessage;
}

export const errorModal = (t, errorMessages) => {
    Modal.warning({
        title: t('message.MSG-TITLE-W'),
        content: h('ul', {}, errorMessages.value.split('<br>').map((error) => { return h('li', error) })),
    });
};