<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user()?->customer;

        if (! $customer) {
            return response()->json([
                'message' => 'Nenhum cliente vinculado ao usuario autenticado.',
            ], 422);
        }

        $orders = Order::query()
            ->with(['products.categories:id,name', 'payments'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(10);

        return response()->json($orders);
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $customer = $request->user()?->customer;

            if (! $customer) {
                return response()->json([
                    'message' => 'Nenhum cliente vinculado ao usuario autenticado.',
                ], 422);
            }

            $order = DB::transaction(function () use ($request) {
                $totalAmount = 0;
                $syncData = [];

                foreach ($request->products as $item) {
                    $product = Product::findOrFail($item['id']);
                    $quantity = $item['quantity'];

                    $totalAmount += $product->price * $quantity;

                    $syncData[$product->id] = [
                        'quantity' => $quantity,
                        'unit_price' => $product->price,
                    ];
                }

                $order = Order::create([
                    'customer_id' => $request->user()->customer->id,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                ]);

                $order->products()->attach($syncData);
                $order->payments()->create([
                    'amount' => $totalAmount,
                    'method' => $request->input('payment_method', 'pix'),
                    'status' => 'pending',
                ]);

                return $order;
            });

            Log::info('Pedido criado com sucesso.', [
                'order_id' => $order->id,
                'customer_id' => $customer->id,
            ]);

            return response()->json($order->load(['products.categories:id,name', 'payments']), 201);
        } catch (\Throwable $e) {
            Log::error('Erro ao criar pedido.', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Ocorreu um erro ao processar seu pedido.',
            ], 500);
        }
    }

    public function show(Request $request, Order $order)
    {
        $this->ensureOrderOwnership($request, $order);

        return response()->json(
            $order->load(['products.categories:id,name', 'payments', 'customer'])
        );
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $this->ensureOrderOwnership($request, $order);

        $order->update($request->validated());

        Log::info('Pedido atualizado com sucesso.', [
            'order_id' => $order->id,
            'status' => $order->status,
        ]);

        return response()->json(
            $order->load(['products.categories:id,name', 'payments', 'customer'])
        );
    }

    private function ensureOrderOwnership(Request $request, Order $order): void
    {
        $customerId = $request->user()?->customer?->id;

        abort_if($order->customer_id !== $customerId, 403, 'Voce nao pode acessar este pedido.');
    }
}
