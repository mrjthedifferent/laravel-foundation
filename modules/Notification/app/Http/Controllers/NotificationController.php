<?php

namespace Modules\Notification\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Notification\Http\Resources\NotificationResource;
use Modules\Notification\Models\Notification;
use Modules\Notification\Queries\NotificationQuery;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $isRead = $request->has('read')
            ? filter_var($request->input('read'), FILTER_VALIDATE_BOOLEAN)
            : null;

        $notifications = NotificationQuery::make()
            ->forUser(Auth::user())
            ->filterByReadStatus($isRead)
            ->filterByType($request->input('type'))
            ->orderByLatest()
            ->paginate(cappedPerPage($request->integer('per_page', config('foundation.pagination.default', 10))));

        if ($request->wantsJson() || $request->is('api/*')) {
            return JsonResponseFactory::paginated(
                'Notifications fetched successfully',
                $notifications,
                fn ($notification) => new NotificationResource($notification)
            );
        }

        return view('notification::index', compact('notifications'));
    }

    public function show(Request $request, string $id): View|JsonResponse
    {
        $notification = Notification::findOrFail($id);

        $this->authorize('view', $notification);

        $notification->markAsRead();

        if ($request->wantsJson() || $request->is('api/*')) {
            return JsonResponseFactory::success('Notification fetched successfully', new NotificationResource($notification));
        }

        $isModal = $request->boolean('isModal');
        $view = $isModal ? 'notification::modals.notification-view' : 'notification::show';

        return view($view, compact('notification'));
    }

    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = Notification::findOrFail($id);

        $this->authorize('delete', $notification);

        $notification->delete();

        if ($request->wantsJson() || $request->is('api/*')) {
            return JsonResponseFactory::success('Notification deleted successfully');
        }

        return redirect()->route('admin.notification.index')
            ->with('success', 'Notification deleted successfully');
    }

    public function markAsRead(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = Notification::findOrFail($id);

        $this->authorize('markAsRead', $notification);

        $notification->markAsRead();

        if ($request->wantsJson() || $request->is('api/*')) {
            return JsonResponseFactory::success('Notification marked as read', new NotificationResource($notification));
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    public function markAsUnread(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = Notification::findOrFail($id);

        $this->authorize('markAsUnread', $notification);

        $notification->markAsUnread();

        if ($request->wantsJson() || $request->is('api/*')) {
            return JsonResponseFactory::success('Notification marked as unread', new NotificationResource($notification));
        }

        return redirect()->back()->with('success', 'Notification marked as unread.');
    }

    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        /** @var User $user */
        $user = Auth::user();

        NotificationQuery::make()
            ->forUser($user)
            ->filterByReadStatus(false)
            ->update(['read_at' => now()]);

        if ($request->wantsJson() || $request->is('api/*')) {
            return JsonResponseFactory::success('All notifications marked as read');
        }

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    public function counts(): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $baseQuery = NotificationQuery::make()->forUser(Auth::user());

        return JsonResponseFactory::success('Notification counts fetched successfully', [
            'total' => $baseQuery->count(),
            'unread' => $baseQuery->filterByReadStatus(false)->count(),
        ]);
    }
}
