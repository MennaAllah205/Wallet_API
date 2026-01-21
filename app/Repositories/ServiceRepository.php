<?php

namespace App\Repositories;

use App\Models\Service;
use App\Models\Transaction;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceRepository implements ServiceRepositoryInterface
{
    public function getAll()
    {
        return Service::all();
    }

    public function create(Request $request)
    {
        return Service::create(attributes: [
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
        ]);
    }

    public function findById(int $id)
    {
        return Service::findOrFail($id);
    }

    public function update(int $id, Request $request)
    {
        $service = $this->findById($id);
        $service->update($request->all());
        return $service;
    }
    public function delete(int $id)
    {
        $service = $this->findById($id);
        return $service->delete();
    }

    public function purchase(Service $service, User $user)
    {

        DB::transaction(function () use ($service, $user) {
            $user->balance -= $service->price;
            $user->save();

            Transaction::create([
                'sender_id' => $user->id,
                'amount' => $service->price,
                'type' => 'purchase',
                'service_id' => $service->id,
                'status' => 'completed',
                'notes' => 'Purchase of service: ' . $service->name,
            ]);

            Notification::create([
                'user_id' => $user->id,
                'type' => 'purchase',
                'message' => 'You have purchased the service: ' . $service->name,
            ]);

        });
    }

}
