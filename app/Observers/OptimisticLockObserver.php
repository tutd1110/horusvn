<?php

namespace App\Observers;

use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class OptimisticLockObserver
{
    /**
     * Temporary property for optimistic lock checking
     */
    const OPTIMISTIC_LOCK_CHECK_COLUMN = 'updated_at_optimistic_lock_check_column';


    /**
     * Before Insert
     *
     * @param Model $model
     */
    public function creating(Model $model)
    {
        //write code here
    }

    /**
     * Before Update
     *
     * @param Model $model
     */
    public function updating(Model $model)
    {
        //optimistic exclusive control
        if ($this->checkPropExists($model)) {
            $this->check($model, 'cập nhật');
        }
    }

    /**
     * Before soft delete
     *
     * @param Model $model
     */
    public function deleting(Model $model)
    {
        //optimistic exclusive control
        if ($this->checkPropExists($model)) {
            $this->check($model, 'xoá');
        }
    }

    /**
     * Before physical deletion
     *
     * @param Model $model
     */
    public function restoring(Model $model)
    {
        //optimistic exclusive control
        if ($this->checkPropExists($model)) {
            $this->check($model, 'xoá');
        }
    }

    /**
     * Optimistic lock check
     *
     * @param Model $model
     */
    private function check(Model $model, $msgType)
    {
        //check only when there is a temporary property for optimistic locking checking
        if (!$this->checkPropExists($model)) {
            return;
        }

        //get current DB data
        $currentMe = $model->find($model->id);
        $currentUpdatedAt = $currentMe->updated_at;

        //check if updated
        if ($model->{self::OPTIMISTIC_LOCK_CHECK_COLUMN} != $currentUpdatedAt) {
            //optimistic locking exception
            throw new ExclusiveLockException(__('MSG-E-001', ['run' => $msgType]));
        }

        $this->unsetOptimisticLockColumn($model);
    }

    /**
     * Remove temporary properties for optimistic locking
     * If you don't do this, you will get an error when saving () trying to register a value in a non-existent column.
     *
     * @param Model $model
     */
    private function unsetOptimisticLockColumn(Model $model)
    {
        //do nothing if the column does not exist
        if (!$this->checkPropExists($model)) {
            return;
        }
        
        unset($model->{self::OPTIMISTIC_LOCK_CHECK_COLUMN});
    }

    /**
     * Checks for temporary properties for optimistic locking
     *
     * @param Model $model
     * @return bool true
     */
    private function checkPropExists(Model $model)
    {
        return array_key_exists(self::OPTIMISTIC_LOCK_CHECK_COLUMN, $model->getAttributes());
    }
}
