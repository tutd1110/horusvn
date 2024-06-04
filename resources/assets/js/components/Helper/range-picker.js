import dayjs from 'dayjs';
import { buildFormStateTime } from './build-datetime.js';

const strictDateFormat = "YYYY/MM/DD HH:mm:ss";

export const rangePicker = (current, option) => {
    if (option === 4) {
        // Get the start of the current week
        const startOfWeek = dayjs().startOf('week').add(1, 'day');

        // Get the end of the current week
        const endOfWeek = dayjs().startOf('week').add(6, 'day');

        // Disable dates that are outside of the current week
        return (
            current.isBefore(startOfWeek, 'day') || current.isAfter(endOfWeek, 'day')
        );
    } else if (option === 5) {
        // Get the start of the previous week
        const startOfPrevWeek = dayjs().subtract(1, 'week').weekday(1);

        // Get the end of the previous week
        const endOfPrevWeek = dayjs().subtract(1, 'week').weekday(6);

        // Disable dates that are outside of the previous week
        return (
            current.isBefore(startOfPrevWeek, 'day') || current.isAfter(endOfPrevWeek, 'day')
        );
    }
};

export const buildSubmitData = (submitData, formState, datePeriod) => {
    switch(formState.value.option) {
        case 1:
            submitData.start_time = dayjs().format(strictDateFormat)
            submitData.end_time = dayjs().format(strictDateFormat)

            buildFormStateTime(formState, datePeriod)
            if (formState.value.start_time != null && formState.value.end_time != null) {
                submitData.start_time = formState.value.start_time
                submitData.end_time = formState.value.end_time
            }

            break;
        case 2:
            submitData.start_time = dayjs().format(strictDateFormat)
            submitData.end_time = dayjs().format(strictDateFormat)
            break;
        case 3:
            submitData.start_time = dayjs().add(-1, 'day').format(strictDateFormat)
            submitData.end_time = dayjs().add(-1, 'day').format(strictDateFormat)
            break;
        case 4:
            submitData.start_time = dayjs().startOf('week').add(1, 'day').format(strictDateFormat)
            submitData.end_time = dayjs().startOf('week').add(6, 'day').format(strictDateFormat)
            break;
        case 5:
            submitData.start_time = dayjs().subtract(1, 'week').weekday(1).format(strictDateFormat)
            submitData.end_time = dayjs().subtract(1, 'week').weekday(6).format(strictDateFormat)
            break;
        default:
        // code block
    }
}