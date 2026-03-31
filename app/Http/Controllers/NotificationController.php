<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $limit = max(1, min((int) $request->query('limit', 10), 50));

        $notifications = $user
            ->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (DatabaseNotification $notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            })
            ->values();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    public function go(Request $request, string $notificationId)
    {
        $notification = $request
            ->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $data = $notification->data ?? [];

        if (isset($data['stock_request_id'])) {
            return redirect()->route('stock-requests.show', $data['stock_request_id']);
        }

        if (isset($data['sale_id'])) {
            return redirect()->route('sales.index');
        }

        if (isset($data['drug_id'])) {
            return redirect()->route('drug-units.index');
        }

        if (isset($data['transfer_id']) || isset($data['transfer_depot_id'])) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('dashboard');
    }
}
