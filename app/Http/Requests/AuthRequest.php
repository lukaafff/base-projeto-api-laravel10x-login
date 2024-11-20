<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthRequest extends FormRequest
{
    public function rules()
    {
        return [
            'role' => 'required|string|in:admin,user',
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function authorize()
    {
        return true;
    }

    public function messages()
    {
        return [
            'role.required' => 'O campo papel é obrigatório.',
            'role.string' => 'O papel deve ser uma string válida.',
            'role.in' => 'O papel deve ser um dos seguintes: admin, user.',
            'name.required' => 'O campo nome é obrigatório.',
            'name.string' => 'O nome deve ser uma string válida.',
            'name.min' => 'O nome deve ter pelo menos :min caracteres.',
            'name.max' => 'O nome não pode ter mais de :max caracteres.',
            'email.required' => 'O campo email é obrigatório.',
            'email.string' => 'O email deve ser uma string válida.',
            'email.email' => 'O email deve ser um endereço de e-mail válido.',
            'email.unique' => 'Esse email já está em uso.',
            'password.required' => 'O campo senha é obrigatório.',
            'password.string' => 'A senha deve ser uma string válida.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
        ];
    }
}
