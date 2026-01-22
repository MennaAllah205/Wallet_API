<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Http\Requests\StoreServicesRequest;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Models\Notification;
use App\Events\NotificationEvent;

class ServiceController extends Controller
{
    private ServiceRepositoryInterface $serviceRepository;

    public function __construct(ServiceRepositoryInterface $serviceRepository)
    {
        $this->serviceRepository = $serviceRepository;
    }
    public function index()
    {
        try {
            $services = $this->serviceRepository->getAll();
            return response()->json([
                'services' => $services,
                'count' => $services->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch services',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    public function store(StoreServicesRequest $request)
    {
        try {
            $data = $request->validated();
            $service = $this->serviceRepository->create($data);
            return response()->json([
                'message' => 'Service created successfully',
                'service' => $service
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create service',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $service = $this->serviceRepository->findById($id);
            return response()->json([
                'service' => $service
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Service not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch service',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $service = $this->serviceRepository->update($id, $request);
            return response()->json([
                'message' => 'Service updated successfully',
                'service' => $service
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Service not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update service',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->serviceRepository->delete($id);
            return response()->json([
                'message' => 'Service deleted successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Service not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete service',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    public function purchase($id, Request $request)
    {
        try {
            $user = $request->user();
            $service = $this->serviceRepository->findById($id);

            if ($user->balance < $service->price) {
                return response()->json(['message' => 'Insufficient balance'], 400);
            }

            $this->serviceRepository->purchase($service, $user);

            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => 'purchase',
                'message' => "You have successfully purchased {$service->name} for {$service->price}.",
            ]);

            broadcast(new NotificationEvent($notification, $user->id));

            return response()->json([
                'message' => 'Service purchased successfully',
                'new_balance' => $user->fresh()->balance,
                'service' => $service
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Service not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to purchase service',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    public function notifications(Request $request)
    {
        try {
            $user = $request->user();
            $notifications = $user->notifications()->orderBy('created_at', 'desc')->get();
            return response()->json([
                'notifications' => $notifications
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch notifications',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    public function broadcastNotification(Request $request)
    {
        try {
            $user = $request->user();
            $data = $request->validate([
                'type' => 'required|string',
                'message' => 'required|string'
            ]);
            
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => $data['type'],
                'message' => $data['message'],
            ]);

            broadcast(new NotificationEvent($notification, $user->id));

            return response()->json([
                'status' => 'true',
                'message' => 'Notification broadcasted successfully',
                'notification' => $notification
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'false',
                'message' => 'Failed to broadcast notification',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

}
