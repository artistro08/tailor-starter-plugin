<?php namespace Artistro08\TailorStarterCompanion\Classes;

use Request;
use Tailor\Models\GlobalRecord;

class ReCaptchaValidator
{

    public function validateReCaptcha($attribute, $value, $parameters)
    {
        $secret_key = GlobalRecord::findForGlobal('Content\Settings')->recaptcha_secret_key;
        $recaptcha  = post('g-recaptcha-response');
        $ip         = Request::getClientIp();
        $URL        = "https://www.google.com/recaptcha/api/siteverify?secret=$secret_key&response=$recaptcha&remoteip=$ip";
        $response   = json_decode(file_get_contents($URL), true);

        return ($response['success'] == true);
    }

}

?>