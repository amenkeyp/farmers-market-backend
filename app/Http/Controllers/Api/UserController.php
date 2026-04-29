<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $auth = $request->user();
        $q = User::query();

        // Admin sees all. Supervisor sees own operators. Operator: forbidden.
        if ($auth->isOperator()) {
            return $this->error('Forbidden.', 403);
        }
        if ($auth->isSupervisor()) {
            $q->where('created_by', $auth->id)->where('role', User::ROLE_OPERATOR);
        }

        if ($role = $request->query('role')) {
            $q->where('role', $role);
        }
        if ($search = $request->query('search')) {
            $like = '%' . $search . '%';
            $q->where(fn ($qq) => $qq->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }

        return $this->success(
            UserResource::collection($q->orderByDesc('id')->paginate((int) $request->query('per_page', 15)))->response()->getData(true),
            'Users list.'
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $user = User::create($data);
        return $this->success(new UserResource($user), 'User created.', 201);
    }

    public function show(User $user): JsonResponse
    {
        return $this->success(new UserResource($user), 'User detail.');
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $auth = $request->user();
        if ($auth->isSupervisor() && $user->created_by !== $auth->id) {
            return $this->error('Forbidden.', 403);
        }
        $user->update($request->validated());
        return $this->success(new UserResource($user), 'User updated.');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $auth = $request->user();
        if (! $auth->isAdmin() && !($auth->isSupervisor() && $user->created_by === $auth->id)) {
            return $this->error('Forbidden.', 403);
        }
        if ($user->id === $auth->id) {
            return $this->error('You cannot delete yourself.', 422);
        }
        $user->delete();
        return $this->success(null, 'User deleted.');
    }
}
