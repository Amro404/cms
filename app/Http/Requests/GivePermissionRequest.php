<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GivePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'permission' => 'required|string|exists:permissions,name',
        ];
    }
}
