<?php

namespace App\Events;

use App\Models\Session;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class SessionRatesUpdated
{

    use Dispatchable,
        InteractsWithSockets,
        SerializesModels;

    public $session;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

}