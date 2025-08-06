<?php
namespace App\Services;

use Illuminate\Support\Facades\Mail;

class MailerBrandService
{
    protected array $mailConfig;

    public function __construct(string $brandCode)
    {
        // 1) Cargo la configuración genérica:
        $default = brand_setting('mail');

        // 2) Cargo overrides de archivo (si existen)
        //    No necesito chequear config/brand/{code}/mail.php manualmente,
        //    porque brand_setting('mail') ya incluye ese paso.
        $overrides = brand_setting('mail', []);

        // 3) Fusiono: genérico ← override_de_fichero_ó_BD
        $this->mailConfig = array_replace_recursive($default, $overrides);

        // 4) Inyecto en runtime para que Laravel MailManager USE esta config
        config(['mail' => $this->mailConfig]);
    }

    /**
     * El “to” lo pones luego tú según la campaña.
     */
    public function getMailer()
    {
        return Mail::mailer('smtp');

    }
}
