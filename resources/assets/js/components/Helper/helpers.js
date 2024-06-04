/**
 * Updates the provided task in the data with new values for the specified columns.
 * @param {Array} data - The array of tasks to update.
 * @param {Object} task - The task to update.
 * @param {Array} updateColumns - An array of column names to update.
 */
export const assignNewDataSource = (data, task, updateColumns) => {
    for (let i = 0; i < data.length; i++) {
        if (data[i].id === task.id) {
            let updatedNode = { ...data[i] };
            updateColumns.forEach((column) => {
                if (column in task) {
                    updatedNode[column] = task[column];
                }
            });
            data[i] = updatedNode;

            // Break out of the loop since the task has been found
            break;
        } else {
            if (data[i].grandchildren) {
                assignNewDataSource(data[i].grandchildren, task, updateColumns);
            }
        }
    }
};

export const reloadAfterTaskTimingChanged = (data, args, params) => {
    axios.post('/api/common/reload_from_task_timing', {
        id: args.task_id,
        option: params.option,
        task_issue_ids: args.task_issue_ids,
        task_user_id: args.task_user_id,
        start_time: params.start_time,
        end_time: params.end_time,
    })
    .then(response => {
        const columnsToUpdate = ['start_time', 'end_time', 'total_estimate_time', 'total_time_spent'];

        //assign new data that user has changed after close TaskTiming modal
        assignNewDataSource(data, response.data, columnsToUpdate)
    })
    .catch((error) => {
        if (error.response.data.status = 404) {
            // Remove the task with the specified id from the data array
            removeTaskFromData(data, args.task_id)
        }
    });
}

export const reloadAfterTaskProjectChanged = (data, task_id) => {
    axios.post('/api/common/reload_from_task_project', {
        id: task_id,
    })
    .then(response => {
        let task = response.data;

        //project_id
        if (task.project_id) {
            let projectId = task.project_id.trim();
            if (projectId.startsWith("{") && projectId.endsWith("}")) {
                projectId = projectId.slice(1, -1);
            }
            task.project_id = projectId.split(",").map((id) => parseInt(id.trim()));
        } else {
            task.project_id = []
        }

        const columnsToUpdate = ['project_id', 'sticker_id', 'priority', 'weight'];
        
        //assign new data that user has changed after close TaskProject modal
        assignNewDataSource(data, task, columnsToUpdate)
    })
    .catch((error) => {
        if (error.response.data.status = 404) {
            // Remove the task with the specified id from the data array
            removeTaskFromData(data, task_id)
        }
    });
}

export const onCommonChangeSticker = (id, value, dataSource, stickerSelbox, prioritySelbox) => {
    let sticker_id = value ? value : ""

    let nodeToUpdate = findNodeById(id, dataSource)

    if (nodeToUpdate) {
        nodeToUpdate.sticker_id = sticker_id;

        let submitData = {
            id: id,
            sticker_id: sticker_id,
        };

        const priority = nodeToUpdate.priority;
        if (sticker_id && priority) {
            const stickerIndex = stickerSelbox.findIndex((item) => item.id === sticker_id);

            const priorityIndex = prioritySelbox.findIndex((item) => item.id === priority);

            const element = 'level_'+prioritySelbox[priorityIndex].label

            const weight = stickerSelbox[stickerIndex][element];

            nodeToUpdate.weight = weight ? Number(weight) : null;

            submitData.weight = nodeToUpdate.weight;
        } else {
            nodeToUpdate.weight = "";
            submitData.weight = "";
        }

        return submitData;
    }
}

export const onCommonChangePriority = (id, value, dataSource, stickerSelbox, prioritySelbox) => {
    let priority = value ? value : "";

    let nodeToUpdate = findNodeById(id, dataSource)

    if (nodeToUpdate) {
        nodeToUpdate.priority = priority;

        let submitData = {
            id: id,
            priority: priority,
        };

        const sticker_id = nodeToUpdate.sticker_id;
        if (sticker_id && priority) {
            const stickerIndex = stickerSelbox.findIndex((item) => item.id === sticker_id);

            const priorityIndex = prioritySelbox.findIndex((item) => item.id === priority);

            const element = "level_" + prioritySelbox[priorityIndex].label;
            nodeToUpdate.weight = stickerSelbox[stickerIndex][element];

            const weight = stickerSelbox[stickerIndex][element];

            nodeToUpdate.weight = weight ? Number(weight) : null;

            submitData.weight = nodeToUpdate.weight;
        } else {
            nodeToUpdate.weight = "";
            submitData.weight = "";
        }

        return submitData;
    }
}

const findNodeById = (id, dataSource) => {
    for (const node of dataSource) {
        if (node.id === id) {
            return node;
        } else if (node.grandchildren && node.grandchildren.length) {
            const childNode = findNodeById(id, node.grandchildren);
            if (childNode) {
                return childNode;
            }
        }
    }
    return null;
};

const removeTaskFromData = (data, id) => {
    const index = data.findIndex(task => task.id === id);
    if (index !== -1) {
        data.splice(index, 1);
    } else {
        data.forEach(task => {
            if (task.grandchildren) {
                removeTaskFromData(task.grandchildren, id);
            }
        });
    }
}