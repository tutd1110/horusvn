import dayjs from 'dayjs';

const strictDateFormat = "YYYY/MM/DD HH:mm:ss";

export const buildFormStateTime = (formState, datePeriod, dateFormat = null) => {
    const stringFormat = dateFormat ? dateFormat : strictDateFormat;

    if (datePeriod.value !== null && datePeriod.value !== undefined) {
        var startDay = 0;
        var processedStartDay = '';
            //is the start time entered?
        if (datePeriod.value[startDay] !== null && datePeriod.value[startDay] !== undefined) {
            processedStartDay = datePeriod.value[startDay];
            formState.value.start_time = dayjs(processedStartDay).format(stringFormat);
        }
        
        var endDay = 1;
        var processedEndDay = '';
            //is the end time entered?
        if (datePeriod.value[endDay] !== null && datePeriod.value[endDay] !== undefined) {
            processedEndDay = datePeriod.value[endDay];
            formState.value.end_time = dayjs(processedEndDay).format(stringFormat);
        }
    } else {
        formState.value.start_time = null;
        formState.value.end_time = null;
    }
};