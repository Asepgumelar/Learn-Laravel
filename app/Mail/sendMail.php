<?php

namespace App\Mail;

use App\Http\Controllers\BaseBackendController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class sendMail extends Mailable
{
    use Queueable, SerializesModels;
    public $files;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($files)
    {
        $this->files = $files;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('PSA Document')->view('modules.backend.order.mail.template')
            ->attach(public_path($this->files['pdf1']),[
                'mime' => 'application/pdf'
            ])
            ->attach(public_path($this->files['pdf2']),[
                'mime' => 'application/pdf'
            ])
            ->attach(public_path($this->files['pdf3']),[
                'mime' => 'application/pdf'
            ]);
    }
}
