<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Http\Requests\CreditCardPaymentRequest;
use App\Models\Payment;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    public function getAllPayments()
    {
        try {
            $payments = Payment::orderBy('created_at', 'desc')->get();
            return response()->json($payments, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao recuperar pagamentos',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createPayment(PaymentRequest $request)
    {
        $validated = $request->validated();

        $response = Http::withHeaders([
            'access_token' => config('services.hub.token_asaas'),
        ])->withoutVerifying()
          ->post(config('services.hub.api_url_asaas') . '/payments', [
            'customer' => $validated['customer_id'],
            'billingType' => $validated['billingType'],
            'value' => $validated['value'],
            'dueDate' => $validated['dueDate'],
            'installmentCount' => $validated['installmentCount'] ?? null,
            'totalValue' => $validated['totalValue'],
            'description' => $validated['description'] ?? null,
        ]);

        if ($response->successful()) {
            $asaasData = $response->json();
            $paymentData = [
                'customer_id' => $validated['customer_id'],
                'billing_type' => $validated['billingType'],
                'value' => $validated['value'],
                'due_date' => $validated['dueDate'],
                'installment_count' => $validated['installmentCount'],
                'total_value' => $validated['totalValue'],
                'description' => $validated['description'] ?? null,
                'asaas_payment_id' => $asaasData['id'],
                'status' => $asaasData['status'],
                'transaction_date' => $asaasData['dateCreated'],
                'external_link' => $asaasData['invoiceUrl'],
                'last_updated_at' => now(),
            ];

            $payment = Payment::create($paymentData);

            return response()->json(['message' => 'Cobrança criada com sucesso', 'data' => $payment], 201);
        }

        Log::error('Falha ao criar cobrança na API do Asaas', [
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        return response()->json(['message' => 'Falha ao criar cobrança na API do Asaas', 'error' => $response->json()], $response->status());
    }

    public function chargeCreditCardPayment(CreditCardPaymentRequest $request, $id)
    {
        $validated = $request->validated();
        $customer = Customer::where('id_customers', $request->customer_id)->first();

        if (!$customer) {
            Log::warning('Cliente não encontrado.', [
                'customer_id' => $request->customer_id,
            ]);
            return response()->json(['message' => 'Cliente não encontrado.'], 404);
        }
        $response = Http::withHeaders([
            'access_token' => config('services.hub.token_asaas'),
        ])->withoutVerifying()
        ->post(config('services.hub.api_url_asaas') . "/payments/{$id}/payWithCreditCard", [
            'creditCard' => [
                'holderName' => $validated['creditCard']['holderName'],
                'number' => $validated['creditCard']['number'],
                'expiryMonth' => $validated['creditCard']['expiryMonth'],
                'expiryYear' => $validated['creditCard']['expiryYear'],
                'ccv' => $validated['creditCard']['ccv'],
            ],
            'creditCardHolderInfo' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'cpfCnpj' => $customer->cpf_cnpj,
                'postalCode' => $customer->postal_code,
                'addressNumber' => $customer->number,
                'addressComplement' => $customer->complement,
                'mobilePhone' => $customer->phone,
            ]
        ]);

        if ($response->successful()) {
            return response()->json([
                'message' => 'Cobrança no cartão de crédito realizada com sucesso',
                'data' => $response->json(),
            ], 200);
        }

        Log::error('Erro ao cobrar cobrança no cartão de crédito', [
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        return response()->json([
            'message' => 'Erro ao cobrar cobrança no cartão de crédito',
            'error' => $response->json(),
        ], $response->status());
    }

    public function handleWebhook(Request $request)
    {
        $data = $request->all();

        if (isset($data['event'])) {
            switch ($data['event']) {
                case 'PAYMENT_CONFIRMED':
                    return $this->handlePaymentConfirmed($data['payment']);
                default:
                    return response()->json(['message' => 'Evento não tratado'], 400);
            }
        }
        return response()->json(['message' => 'Evento não identificado'], 400);
    }


    private function handlePaymentConfirmed($paymentData)
    {
        $payment = Payment::where('asaas_payment_id', $paymentData['id'])->first();

        if ($payment) {
            $payment->update([
                'status' => $paymentData['status'],
                'last_updated_at' => now(),
            ]);
            return response()->json(['message' => 'Status atualizado com sucesso'], 200);
        }
        return response()->json(['message' => 'Pagamento não encontrado'], 404);
    }

    public function getBillingInfo($id)
    {
        $response = Http::withHeaders([
            'access_token' => config('services.hub.token_asaas'),
        ])->withoutVerifying()
        ->get(config('services.hub.api_url_asaas') . "/payments/{$id}/billingInfo");

        if ($response->successful()) {
            return response()->json([
                'message' => 'Informações de cobrança obtidas com sucesso',
                'data' => $response->json(),
            ], 200);
        }

        Log::error('Erro ao obter informações de cobrança do Asaas', [
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        return response()->json([
            'message' => 'Erro ao obter informações de cobrança',
            'error' => $response->json(),
        ], $response->status());
    }

}
