<?php

namespace App\Http\Controllers;

use App\Mail\SendEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Helpers\Utils;
use App\Services\DocumentService;

class EmailController extends Controller
{
    protected $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function sendEmail(Request $request)
    {
        try {
            $data = $this->validateRequest($request);

            if ($request->has('cpf')) {
                $providedBirthDate = $data['birth_date'];
                $apiBirthDate = $data['cpf_info']['result']['data_nascimento'];

                if ($providedBirthDate !== $apiBirthDate) {
                    throw new \Exception('A data de nascimento fornecida não corresponde à data de nascimento retornada da API. Verifique e tente novamente.');
                }
            }

            $recipientEmail = 'sforall@sforall.com.br';
            $fromEmail = env('MAIL_FROM_ADDRESS');

            $data['category'] = $request->input('category') ?? 'Sem categoria';

            Mail::to($recipientEmail)
                ->send(new SendEmail($data, $fromEmail));

            return response()->json([
                'message' => 'E-mail enviado com sucesso!',
                'data' => [
                    'cpf_info' => $data['cpf_info'] ?? null,
                    'cnpj_info' => $data['cnpj_info'] ?? null,
                    'category' => $data['category']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao enviar o e-mail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Erro ao enviar o e-mail', 'error' => $e->getMessage()], 500);
        }
    }

    protected function validateRequest(Request $request)
    {
        if ($request->has('cnpj')) {
            return $this->validateCnpjRequest($request);
        } elseif ($request->has('cpf')) {
            return $this->validateCpfRequest($request);
        }

        throw new \Exception('Nenhum CPF ou CNPJ fornecido.');
    }

    protected function validateCnpjRequest(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'cnpj' => 'required|string|max:18',
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email',
            'user_phone' => 'required|string|max:20',
        ], $this->validationMessages());

        $cnpj = preg_replace('/\D/', '', $request->input('cnpj'));
        $result = $this->documentService->validateCnpj($cnpj);

        return [
            'company_name' => $request->input('company_name'),
            'cnpj' => Utils::formatCnpj($cnpj),
            'user_name' => $request->input('user_name'),
            'user_email' => $request->input('user_email'),
            'user_phone' => Utils::formatPhone($request->input('user_phone')),
            'cnpj_info' => $result,
        ];
    }

    protected function validateCpfRequest(Request $request)
    {
        $request->validate([
            'cpf' => 'required|string|max:14',
            'birth_date' => 'required|date_format:d/m/Y',
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email',
            'user_phone' => 'required|string|max:20',
        ], $this->validationMessages());

        $cpf = preg_replace('/\D/', '', $request->input('cpf'));
        $birthDate = $request->input('birth_date');

        $result = $this->documentService->validateCpf($cpf, $birthDate);

        $returnedBirthDate = $result['result']['data_nascimento'] ?? 'não disponível';

        if ($returnedBirthDate !== 'não disponível' && $returnedBirthDate !== $birthDate) {
            throw new \Exception('A data de nascimento fornecida não corresponde ao CPF informado. Verifique e tente novamente.');
        }

        return [
            'cpf' => Utils::formatCpf($cpf),
            'birth_date' => $birthDate,
            'user_name' => $request->input('user_name'),
            'user_email' => $request->input('user_email'),
            'user_phone' => Utils::formatPhone($request->input('user_phone')),
            'cpf_info' => $result,
        ];
    }

    protected function validationMessages()
    {
        return [
            'company_name.required' => 'O nome da empresa é obrigatório.',
            'cnpj.required' => 'O CNPJ é obrigatório.',
            'user_name.required' => 'O nome do usuário é obrigatório.',
            'user_email.required' => 'O e-mail é obrigatório.',
            'user_phone.required' => 'O telefone é obrigatório.',
            'cpf.required' => 'O CPF é obrigatório.',
            'birth_date.required' => 'A data de nascimento é obrigatória.',
        ];
    }
}
