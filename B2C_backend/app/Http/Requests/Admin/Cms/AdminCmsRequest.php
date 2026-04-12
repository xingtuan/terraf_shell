<?php

namespace App\Http\Requests\Admin\Cms;

use Illuminate\Foundation\Http\FormRequest;

abstract class AdminCmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageUsers() ?? false;
    }

    protected function requiredRule(): string
    {
        return $this->isMethod('post') ? 'required' : 'sometimes';
    }
}
