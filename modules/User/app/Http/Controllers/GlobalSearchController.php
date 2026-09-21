<?php

namespace Modules\User\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;

class GlobalSearchController extends Controller
{
    /**
     * Handle the navbar's global search via AJAX request.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q'));
        $category = $request->input('category', 'all'); // 'all' or 'users'

        $results = [];

        if ($query === '') {
            return JsonResponseFactory::success('No query.', $results);
        }

        $actor = $request->user();

        // Search users only when the requester is allowed to list users, and
        // only surface individual users they are authorized to view. This
        // prevents leaking names and emails to any authenticated account.
        if (in_array($category, ['all', 'users'], true) && $actor->can('viewAny', User::class)) {
            $like = '%'.addcslashes($query, '%_\\').'%';

            $users = User::query()
                ->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like);
                })
                ->latest()
                ->limit(20)
                ->get();

            foreach ($users as $user) {
                if (! $actor->can('view', $user)) {
                    continue;
                }

                $results[] = [
                    'id' => $user->id,
                    'text' => $user->name,
                    'sub_text' => $user->email,
                    'icon' => 'ph-user-circle',
                    'avatar' => $user->image ?: asset('images/person.png'),
                    'url' => route('admin.users.show', $user->id),
                    'category' => 'User',
                ];

                if (count($results) >= 5) {
                    break;
                }
            }
        }

        return JsonResponseFactory::success('Search results.', $results);
    }
}
