<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationsController extends Controller
{
    /**
     * Display the notifications center.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = SystemNotification::forUser($user)->latest();

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by read status
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'unread') {
                $query->unread();
            } else {
                $query->whereNotNull('read_at');
            }
        }

        $notifications = $query->paginate(20);
        $unreadCount = \App\Services\NotificationService::make()->getUnreadCount($user);

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Mark a notification as read.
     *
     * SECURITY FIX: Validate action_url is internal before redirecting
     * Prevents open redirect vulnerability where attacker can redirect to phishing site
     */
    public function markAsRead(string $id)
    {
        $notification = SystemNotification::forUser(Auth::user())
            ->where('id', $id)
            ->firstOrFail();

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        // Redirect to action URL if available
        // SECURITY: Only redirect to internal URLs (starting with /)
        if ($notification->action_url && $this->isInternalUrl($notification->action_url)) {
            return redirect($notification->action_url);
        }

        // Fallback: check if there's an objective_id in data
        $data = $notification->data ?? [];
        if (isset($data['objective_id'])) {
            return redirect('/dashboard?search=' . urlencode($data['objective_id']));
        }

        // Default: redirect to notifications index
        return redirect()->route('notifications.index');
    }

    /**
     * Validate that URL is internal (not external redirect)
     */
    protected function isInternalUrl(string $url): bool
    {
        // Allow relative URLs starting with /
        if (str_starts_with($url, '/')) {
            return true;
        }

        // Allow URLs that start with the app URL
        if (str_starts_with($url, config('app.url'))) {
            return true;
        }

        // Reject everything else (external URLs, protocol-relative URLs, etc)
        return false;
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        SystemNotification::forUser(Auth::user())
            ->unread()
            ->update(['read_at' => now()]);

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete a notification.
     */
    public function destroy(string $id)
    {
        $notification = SystemNotification::forUser(Auth::user())
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        return redirect()->back()->with('success', 'Notification deleted.');
    }

    /**
     * Delete all read notifications.
     */
    public function clearRead()
    {
        SystemNotification::forUser(Auth::user())
            ->whereNotNull('read_at')
            ->delete();

        return redirect()->back()->with('success', 'All read notifications cleared.');
    }
}
