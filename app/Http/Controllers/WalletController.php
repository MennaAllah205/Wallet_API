<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChargRequest;
use App\Http\Requests\transferRequest;
use App\Models\Notification;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Events\NotificationEvent;

class WalletController extends Controller
{
    public function charge(ChargRequest $request)
    {
        try {
            $request->validated();
            $user = $request->user();

            DB::transaction(function () use ($user, $request) {
                $user->balance += $request->amount;
                $user->save();

                Transaction::create([
                    'type' => 'charge',
                    'status' => 'completed',
                    'amount' => $request->amount,
                    'receiver_id' => $user->id,
                    'notes' => 'Wallet charge',
                ]);

                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'charge',
                    'message' => "Your wallet has been charged with amount: {$request->amount}.",
                ]);

                broadcast(new NotificationEvent(
                    Notification::where('user_id', $user->id)->latest()->first(),
                    $user->id
                ));
            });

            return response()->json([
                'message' => 'Wallet charged successfully',
                'new_balance' => $user->fresh()->balance
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to charge wallet',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    public function transferRequest(transferRequest $request)
    {
        try {
            $request->validated();
            $sender = $request->user();

            if ($sender->id == $request->receiver_id) {
                return response()->json(['message' => 'You cannot transfer to yourself'], 400);
            }

            if ($sender->balance < $request->amount) {
                return response()->json(
                    [
                        "status" => "failed",
                        'message' => 'You don’t have enough balance to complete this transaction'
                    ],
                    400
                );
            }

            $transaction = Transaction::create([
                'type' => 'transfer',
                'status' => 'pending',
                'amount' => $request->amount,
                'sender_id' => $sender->id,
                'receiver_id' => $request->receiver_id,
                'notes' => 'Transfer request'
            ]);

            Notification::create([
                'user_id' => $sender->id,
                'type' => 'transfer',
                'message' => "Transfer request created. Please confirm or cancel."
            ]);

            broadcast(new NotificationEvent(
                Notification::where('user_id', $sender->id)->latest()->first(),
                $sender->id
            ));

            return response()->json([
                'status' => 'true',
                'message' => 'Transfer request created',
                'transaction' => $transaction
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'Failed to create transfer request',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    public function confirmTransfer($id, Request $request)
    {
        try {
            $user = $request->user();
            $transaction = Transaction::where('id', $id)
                ->where('sender_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if (!$transaction) {
                return response()->json(['message' => 'Transaction not found or not authorized'], 404);
            }

            $sender = $transaction->sender;
            $receiver = $transaction->receiver;

            if (!$sender || !$receiver) {
                return response()->json(['message' => 'Sender or receiver not found'], 404);
            }

            DB::transaction(function () use ($transaction, $sender, $receiver) {
                if ($sender->balance < $transaction->amount) {
                    throw new \Exception('You don’t have enough balance to complete this transaction');
                }

                $sender->balance -= $transaction->amount;
                $receiver->balance += $transaction->amount;
                $sender->save();
                $receiver->save();
                $transaction->status = 'completed';
                $transaction->save();

                Notification::create([
                    'user_id' => $sender->id,
                    'type' => 'transfer',
                    'message' => "Transfer of {$transaction->amount} completed successfully."
                ]);

                broadcast(new NotificationEvent(
                    Notification::where('user_id', $sender->id)->latest()->first(),
                    $sender->id
                ));

                Notification::create([
                    'user_id' => $receiver->id,
                    'type' => 'transfer',
                    'message' => "You received {$transaction->amount} from {$sender->name}."
                ]);

                broadcast(new NotificationEvent(
                    Notification::where('user_id', $receiver->id)->latest()->first(),
                    $receiver->id
                ));
            });

            return response()->json([
                'status' => 'true',
                'message' => 'Transfer confirmed successfully',
                'transaction' => $transaction->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'Failed to confirm transfer',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    public function cancelTransfer($id, Request $request)
    {
        try {
            $user = $request->user();
            $transaction = Transaction::where('id', $id)
                ->where('sender_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if (!$transaction) {
                return response()->json(['message' => 'Transaction not found or not authorized'], 404);
            }

            $transaction->status = 'failed';
            $transaction->save();

            Notification::create([
                'user_id' => $transaction->sender_id,
                'type' => 'transfer',
                'message' => "Your transfer of amount: {$transaction->amount} has been canceled."
            ]);

            broadcast(new NotificationEvent(
                Notification::where('user_id', $transaction->sender_id)->latest()->first(),
                $transaction->sender_id
            ));

            return response()->json(['message' => 'Transfer canceled successfully'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel transfer',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }
    public function transactions(Request $request)
    {
        try {
            $user = $request->user();
            $transactions = Transaction::where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id)
                ->with(['sender', 'receiver'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'transactions' => $transactions,
                'count' => $transactions->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch transactions',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }
}
