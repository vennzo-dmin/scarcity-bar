<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProvidersRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'                 => ['nullable', 'array'],
            'email.driver'          => ['nullable', 'in:default,smtp'],
            'email.from_address'    => ['nullable', 'email', 'max:190'],
            'email.from_name'       => ['nullable', 'string', 'max:120'],
            'email.smtp_host'       => ['nullable', 'string', 'max:190'],
            'email.smtp_port'       => ['nullable', 'integer', 'min:1', 'max:65535'],
            'email.smtp_user'       => ['nullable', 'string', 'max:190'],
            'email.smtp_pass'       => ['nullable', 'string', 'max:190'],
            'email.smtp_encryption' => ['nullable', 'in:tls,ssl,none'],

            'sms'                       => ['nullable', 'array'],
            'sms.driver'                => ['nullable', 'in:log,twilio,messagebird'],

            'sms.twilio'                => ['nullable', 'array'],
            'sms.twilio.account_sid'    => ['nullable', 'string', 'max:120'],
            'sms.twilio.auth_token'     => ['nullable', 'string', 'max:190'],
            'sms.twilio.from'           => ['nullable', 'string', 'max:40'],

            'sms.messagebird'           => ['nullable', 'array'],
            'sms.messagebird.api_key'   => ['nullable', 'string', 'max:190'],
            'sms.messagebird.originator'=> ['nullable', 'string', 'max:40'],
        ];
    }
}
