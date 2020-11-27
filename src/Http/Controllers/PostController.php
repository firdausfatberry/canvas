<?php

namespace Canvas\Http\Controllers;

use Canvas\Http\Requests\StorePostRequest;
use Canvas\Models\Post;
use Canvas\Models\Tag;
use Canvas\Models\Topic;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ramsey\Uuid\Uuid;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type', 'published');
        $scope = $request->query('scope', 'user');
        $hasPermission = $request->user('canvas')->isAdmin || $request->user('canvas')->isEditor;

        $posts = Post::when($scope, function ($query, $scope) use ($request, $hasPermission) {
            if ($scope === 'all' && $hasPermission) {
                return $query;
            }

            return $query->where('user_id', $request->user('canvas')->id);
        })->when($type, function ($query, $type) {
            if ($type === 'draft') {
                return $query->draft();
            }

            return $query->published();
        })->where('deleted_at',null)->latest()->withCount('views')->paginate();

        if ($scope === 'all' && $hasPermission) {
            $draftCount = Post::draft()->where('deleted_at',null)->count();
            $publishedCount = Post::published()->where('deleted_at',null)->count();
        } else {
            $draftCount = Post::where('user_id', $request->user('canvas')->id)->where('deleted_at',null)->draft()->count();
            $publishedCount = Post::where('user_id', $request->user('canvas')->id)->where('deleted_at',null)->published()->count();
        }

        return response()->json([
            'posts' => $posts,
            'draftCount' => $draftCount,
            'publishedCount' => $publishedCount,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $uuid = Uuid::uuid4();

        return response()->json([
            'post' => Post::make([
                'id' => $uuid->toString(),
                'slug' => "post-{$uuid->toString()}",
            ]),
            'tags' => Tag::get(['name', 'slug']),
            'topics' => Topic::get(['name', 'slug']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePostRequest $request
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function store(StorePostRequest $request, $id): JsonResponse
    {
        $data = $request->validated();

        $post = Post::when($request->user('canvas')->isContributor, function ($query) {
            return $query->where('user_id', request()->user('canvas')->id);
        }, function ($query) {
            return $query;
        })->with('tags', 'topic')->find($id);

        if (! $post) {
            $post = new Post(['id' => $id]);
        }

        $post->fill($data);

        $post->user_id = $post->user_id ?? request()->user('canvas')->id;

        $post->save();

        $post->topic()->sync($this->syncedTopic($request->input('topic') ?? []));

        $post->tags()->sync($this->syncedTags($request->input('tags') ?? []));

        return response()->json($post->refresh(), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function show(Request $request, $id): JsonResponse
    {
        $hasPermission = $request->user('canvas')->isAdmin || $request->user('canvas')->isEditor;

        if ($hasPermission) {
            $post = Post::with('tags:name,slug', 'topic:name,slug')->find($id);
        } else {
            $post = Post::where('user_id', $request->user('canvas')->id)->with('tags:name,slug', 'topic:name,slug')->find($id);
        }

        if ($post) {
            return response()->json([
                'post' => $post,
                'tags' => Tag::get(['name', 'slug']),
                'topics' => Topic::get(['name', 'slug']),
            ]);
        } else {
            return response()->json(null, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function destroy(Request $request, $id)
    {
        $post = Post::where('user_id', $request->user('canvas')->id)->findOrFail($id);

        $post->update([
        'deleted_at'=> date("Y-m-d H:i:s")
        ]);

        return response()->json(null, 204);
    }

    /**
     * Sync the topic assigned to the post.
     *
     * @param array $incomingTopic
     * @return array
     */
    protected function syncedTopic(array $incomingTopic): array
    {
        if (collect($incomingTopic)->isEmpty()) {
            return [];
        }

        // Since the multiselect component handles single selects differently, when we try and
        // attach an existing topic it will enter as an object in an array. A newly created
        // topic will come in strictly as an array with only a name and a slug.
        $topicToAssign = empty($incomingTopic[0]) ? $incomingTopic : $incomingTopic[0];

        $topic = Topic::firstWhere('slug', $topicToAssign['slug']);

        if (! $topic) {
            $topic = Topic::create([
                'id' => $id = Uuid::uuid4()->toString(),
                'name' => $topicToAssign['name'],
                'slug' => $topicToAssign['slug'],
                'user_id' => request()->user('canvas')->id,
            ]);
        }

        return [(string) $topic->id];
    }

    /**
     * Sync the tags assigned to the post.
     *
     * @param array $incomingTags
     * @return array
     */
    protected function syncedTags(array $incomingTags): array
    {
        if (collect($incomingTags)->isEmpty()) {
            return [];
        }

        $tags = Tag::get(['id', 'name', 'slug']);

        return collect($incomingTags)->map(function ($item) use ($tags) {
            $tag = $tags->firstWhere('slug', $item['slug']);

            if (! $tag) {
                $tag = Tag::create([
                    'id' => $id = Uuid::uuid4()->toString(),
                    'name' => $item['name'],
                    'slug' => $item['slug'],
                    'user_id' => request()->user('canvas')->id,
                ]);
            }

            return (string) $tag->id;
        })->toArray();
    }
}
