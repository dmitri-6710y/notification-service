<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:5000',
            'recipients' => 'required|array|min:1|max:1000',
            'recipients.*' => 'string|max:255',
            'channel' => 'required|in:sms,email',
            'priority' => 'required|in:high,low',
        ];
    }
}