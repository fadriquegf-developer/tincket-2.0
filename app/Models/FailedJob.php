<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class FailedJob extends BaseModel
{
    use CrudTrait;
    use LogsActivity;
    public $timestamps = false;

    protected $table = 'failed_jobs';

    protected $guarded = [];

    protected $casts = [
        'failed_at' => 'datetime',
    ];


    public function retryFailedJobButton()
    {
        $url = url(config('backpack.base.route_prefix') . '/failed-job/' . $this->getKey() . '/retry');

        return '<a href="' . $url . '" class="btn btn-sm btn-link" title="' . __('backend.failed_job.retry_job') . '">'
            .     '<i class="la la-redo"></i> ' . __('backend.failed_job.retry_job')
            . '</a>';
    }
}
