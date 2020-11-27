<?php

namespace Canvas\Http\Controllers;

use Canvas\Models\Post;
use Canvas\Models\Tag;
use Canvas\Models\Topic;
use Canvas\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SearchController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function showPosts(Request $request): JsonResponse
    {
        $user = User::firstWhere('id', $request->user('canvas')->id);

        if ($user->isAdmin || $user->isEditor) {
            $posts = Post::select('id', 'title')->latest()->get();
        } else {
            $posts = Post::where('user_id', $request->user('canvas')->id)->select('id', 'title')->latest()->get();
        }

        $posts->map(function ($post) {
            $post['name'] = $post->title;
            $post['type'] = 'Post';
            $post['route'] = 'edit-post';

            return $post;
        });

        return response()->json(collect($posts)->toArray(), 200);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function showTags(Request $request): JsonResponse
    {
        $tags = Tag::select('id', 'name')->where('deleted_at',null)->latest()->get();

        $tags->map(function ($tag) {
            $tag['type'] = 'Tag';
            $tag['route'] = 'edit-tag';

            return $tag;
        });

        return response()->json(collect($tags)->toArray(), 200);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function showTopics(Request $request): JsonResponse
    {
        $topics = Topic::select('id', 'name')->where('deleted_at',null)->latest()->get();

        $topics->map(function ($topic) {
            $topic['type'] = 'Topic';
            $topic['route'] = 'edit-topic';

            return $topic;
        });

        return response()->json(collect($topics)->toArray(), 200);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function showUsers(Request $request): JsonResponse
    {
        $users = User::select('id', 'name', 'email')->where('deleted_at',null)->latest()->get();

        $users->map(function ($user) {
            $user['type'] = 'User';
            $user['route'] = 'edit-user';

            return $user;
        });

        return response()->json(collect($users)->toArray(), 200);
    }
}
