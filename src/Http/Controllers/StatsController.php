<?php

namespace Canvas\Http\Controllers;

use Canvas\Models\Post;
use Canvas\Models\User;
use Canvas\Services\StatsAggregatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class StatsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(StatsAggregatorService::getByUserAndScope(
            $request->user('canvas'),
            $request->query('scope', 'user'),
            30
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = User::firstWhere('id', $request->user('canvas')->id);

        if ($user->isAdmin || $user->isEditor) {
            $post = Post::where('deleted_at',null)->find($id);
        } else {
            $post = Post::where('user_id', $request->user('canvas')->id)->where('deleted_at',null)->find($id);
        }

        if (! $post || ! $post->published) {
            return response()->json(null, 404);
        }

        return response()->json(StatsAggregatorService::getForPost($post, 30));
    }
}
