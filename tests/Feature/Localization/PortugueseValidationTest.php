<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PortugueseValidationTest extends TestCase
{
    public function test_validation_messages_and_attribute_names_are_in_portuguese(): void
    {
        $validator = Validator::make([
            'email' => 'endereco-invalido',
            'password' => 'curta',
            'password_confirmation' => 'diferente',
        ], [
            'name' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $this->assertSame('pt_BR', app()->getLocale());
        $this->assertSame('O campo nome é obrigatório.', $validator->errors()->first('name'));
        $this->assertSame(
            'O campo e-mail deve conter um endereço de e-mail válido.',
            $validator->errors()->first('email'),
        );
        $this->assertContains(
            'O campo senha deve conter pelo menos 8 caracteres.',
            $validator->errors()->get('password'),
        );
        $this->assertContains(
            'A confirmação do campo senha não corresponde.',
            $validator->errors()->get('password'),
        );
    }
}
