<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Description of GenericMailers
 *
 * @author miquel
 */
class GenericMailer extends Mailable
{

    use Queueable,
        SerializesModels;

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $this->viewData = array_merge($this->viewData, ['brand' => request()->get('brand')]);

        return $this;
    }

}
