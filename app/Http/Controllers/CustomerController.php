<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function createCustomer(CustomerRequest $request)
    {
        $validated = $request->validated();

        $response = Http::withHeaders([
            'access_token' => config('services.hub.token_asaas'),
        ])->withoutVerifying()
        ->post(config('services.hub.api_url_asaas') . '/customers', [
            'name' => $validated['name'],
            'cpfCnpj' => $validated['cpf_cnpj'],
            'email' => $validated['email'],
            'mobilePhone' => $validated['phone'],
            'address' => $validated['street'],
            'addressNumber' => $validated['number'],
            'complement' => $validated['complement'],
            'province' => $validated['neighborhood'],
            'postalCode' => $validated['postal_code'],
            'notificationDisabled' => true,
        ]);

        if ($response->successful()) {
            $asaasCustomerId = $response->json()['id'];
            $customerData = [
                'id_customers' => $asaasCustomerId,
                'name' => $validated['name'],
                'cpf_cnpj' => $validated['cpf_cnpj'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'street' => $validated['street'],
                'number' => $validated['number'],
                'complement' => $validated['complement'],
                'neighborhood' => $validated['neighborhood'],
                'postal_code' => $validated['postal_code'],
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
            ];

            $customer = Customer::create($customerData);

            return response()->json(['message' => 'Cliente criado com sucesso', 'data' => $customer], 201);
        }

        Log::error('Falha ao criar cliente na API do Asaas', [
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        return response()->json(['message' => 'Falha ao criar cliente na API do Asaas', 'error' => $response->json()], $response->status());
    }

    public function getAllCustomers()
    {
        try {
            $customers = Customer::orderBy('created_at', 'desc')->get();
            return response()->json($customers, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao recuperar clientes',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
