<?php

namespace Modules\Notification\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Notification\Actions\SendPushNotificationAction;
use Modules\Notification\Http\Requests\StorePushNotificationRequest;
use Modules\Notification\Models\PushNotification;
use Modules\Notification\Queries\PushNotificationQuery;
use Mrj\Foundation\Enum\PaginationEnum;
use Mrj\Foundation\Http\Controllers\Controller;

class PushNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PushNotification::class);

        $notifications = PushNotificationQuery::make()
            ->search($request->input('search'))
            ->orderByLatest()
            ->paginate(cappedPerPage((int) $request->input('per_page', PaginationEnum::DEFAULT_PAGINATE)));

        return view('notification::push-notification.index', [
            'notifications' => $notifications,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PushNotification::class);

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('notification::push-notification.create', [
            'users' => $users,
        ]);
    }

    public function store(StorePushNotificationRequest $request, SendPushNotificationAction $action): RedirectResponse
    {
        $this->authorize('create', PushNotification::class);

        $action->execute($request->validated());

        return redirect()->route('admin.push.notification.index')
            ->with('success', 'Push notification queued successfully.');
    }
}
