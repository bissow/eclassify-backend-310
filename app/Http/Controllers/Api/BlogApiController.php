<?php

namespace App\Http\Controllers\Api;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogFeedback;
use App\Models\Category;
use App\Models\Language;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

/** @tags Blog */
class BlogApiController extends BaseApiController
{
    /** Get Blogs */
    public function getBlog(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'nullable|integer|exists:blog_categories,id',
                'category_slug' => 'nullable|string|exists:blog_categories,slug',
                'blog_id' => 'nullable|integer|exists:blogs,id'
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            $isDetail = !empty($request->id) || !empty($request->slug);

            $blogs = Blog::activeCategory()->with(['translations', 'seoDetail.translations', 'category.translations'])
                ->withCount([
                    'feedbacks as useful_count' => function ($q) {
                        $q->where('is_useful', 1);
                    },
                    'feedbacks as not_useful_count' => function ($q) {
                        $q->where('is_useful', 0);
                    }
                ])
                ->when(!empty($request->id), function ($q) use ($request) {
                    $q->where('blogs.id', $request->id);
                    $this->incrementViews($request->id); // Increment View as detail blog is called
                })
                ->when(!empty($request->slug), function ($q) use ($request) {
                    $q->where('slug', $request->slug);
                    $this->incrementViews(null, $request->slug); // Increment View as detail blog is called
                })
                ->when(!empty($request->category_id), function ($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                })
                ->when(!empty($request->category_slug), function ($q) use ($request) {
                    $q->whereHas('category', function ($q) use ($request) {
                        $q->where('slug', $request->category_slug);
                    });
                })
                ->when(!empty($request->sort_by), function ($q) use ($request) {
                    if ($request->sort_by === 'popular') {
                        $q->orderByDesc('views');
                    }
                })
                ->when(!empty($request->tag), function ($q) use ($request) {
                    $q->where(function ($query) use ($request) {
                        $query->where('tags', 'like', '%' . $request->tag . '%')
                            ->orWhereHas('translations', function ($translationQuery) use ($request) {
                                $translationQuery->where('key', 'tags')->where('value', 'like', '%' . $request->tag . '%');
                            });
                    });
                })
                ->orderByDesc('created_at')
                ->paginate(10);

            // Truncate description and translated description to 500 chars on listing (no id/slug)
            if (!$isDetail) {
                $blogs->getCollection()->transform(function ($blog) {
                    return $this->formateBlogResponse($blog);
                });
            }


            
            $extra = [];

            if($blogs->isEmpty()){
                if($request->has('category_slug')){
                    $categoryData = BlogCategory::with('seoDetail.translations')->where(['slug' => $request->category_slug, 'is_active' => 1])->first();
                    $extra['category'] = $categoryData;
                }
            }
            if ($isDetail) {
                // Blog Detail
                $blog = $blogs->getCollection()->transform(function ($blog) {
                    return $this->formateBlogResponse($blog, true);
                })->first();
                $categoryId = $blog?->category_id;

                // Popular Categories Data
                $extra['popular_categories'] = BlogCategory::where('is_active',1)
                    ->withCount('blogs')
                    ->having('blogs_count', '>', 0)
                    ->orderByDesc('blogs_count')
                    ->limit(5)
                    ->get();

                // Related Articles of same category
                $extra['related_articles'] = $categoryId
                    ? Blog::activeCategory()->with(['translations', 'category'])
                        ->withCount([
                            'feedbacks as useful_count' => fn($q) => $q->where('is_useful', 1),
                            'feedbacks as not_useful_count' => fn($q) => $q->where('is_useful', 0),
                        ])
                        ->where('category_id', $categoryId)
                        ->when(!empty($request->id), function ($q) use ($request) {
                            $q->where('id', '!=', $request->id);  
                        })
                        ->when(!empty($request->slug), function ($q) use ($request) {
                            $q->where('slug', '!=', $request->slug);  
                        })
                        ->inRandomOrder()
                        ->limit(3)
                        ->get()
                        ->map(function ($blog) {
                            return $this->formateBlogResponse($blog);
                        })
                    : [];
            }

            ResponseService::successResponse(__('Blogs fetched successfully'), $blogs, $extra);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getBlog');
            ResponseService::errorResponse(__('Failed to fetch blogs'));
        }
    }

    /** Get All Blog Tags */
    public function getAllBlogTags()
    {
        try {
            $languageCode = request()->header('Content-Language') ?? app()->getLocale();

            $language = Language::select(['id', 'code', 'name'])
                ->where('code', $languageCode)
                ->first();

            if (! $language) {
                return ResponseService::errorResponse('Invalid language code');
            }

            $tagsMap = [];

            Blog::activeCategory()->with(['translations' => function ($q) use ($language) {
                $q->where('language_id', $language->id);
            }])->chunk(100, function ($blogs) use (&$tagsMap) {
                foreach ($blogs as $blog) {
                    $defaultTagsRaw = $blog->tags;
                    $defaultTags = [];
                    if (! empty($defaultTagsRaw)) {
                        if (is_string($defaultTagsRaw)) {
                            $decoded = json_decode($defaultTagsRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && ! empty($decoded)) {
                                $defaultTags = is_array($decoded) ? $decoded : [$decoded];
                            } else {
                                $defaultTags = array_map('trim', explode(',', $defaultTagsRaw));
                            }
                        } elseif (is_array($defaultTagsRaw)) {
                            $defaultTags = $defaultTagsRaw;
                        }
                    }
                    $translatedTagsRaw = $blog->translations->where('key', 'tags')->first()?->value;
                    $translatedTags = [];
                    if (! empty($translatedTagsRaw)) {
                        if (is_string($translatedTagsRaw)) {
                            $decoded = json_decode($translatedTagsRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && ! empty($decoded)) {
                                $translatedTags = is_array($decoded) ? $decoded : [$decoded];
                            } else {
                                $translatedTags = array_map('trim', explode(',', $translatedTagsRaw));
                            }
                        } elseif (is_array($translatedTagsRaw)) {
                            $translatedTags = $translatedTagsRaw;
                        }
                    }
                    if (! empty($translatedTags)) {
                        foreach ($translatedTags as $translatedTag) {
                            $tagsMap[$translatedTag] = $translatedTag;
                        }
                    } else {
                        foreach ($defaultTags as $defaultTag) {
                            $tagsMap[$defaultTag] = $defaultTag;
                        }
                    }
                }
            });
            $result = [];
            foreach ($tagsMap as $defaultTag => $translatedTag) {
                $result[] = [
                    'label' => $translatedTag,
                    'value' => $defaultTag,
                ];
            }

            ResponseService::successResponse('Blog Tags Retrieved Successfully', array_values($result));
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getAllBlogTags');

            return ResponseService::errorResponse('Failed to fetch Tags');
        }
    }

    /** Get Blogs Slug */
    public function getBlogsSlug(Request $request)
    {
        try {
            $blogs = Blog::activeCategory()->without('translations')->select('id', 'slug')->paginate(500);

            if ($blogs->isEmpty()) {
                return ResponseService::errorResponse(__('No active Blogs found.'));
            }

            return ResponseService::successResponse(__('Active Blogs slugs fetched successfully.'), $blogs);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getBlogsSlug');
            ResponseService::errorResponse();
        }
    }

    /** Get Blog Categories */
    public function getBlogCategories(Request $request)
    {
        try {
            $paginationCount = $request->has('per_page') ? $request->per_page : 4;
            $categorySlug = $request->has('slug') ? $request->slug : null;
            if ($paginationCount > 500) {
                $paginationCount = 500;
            } elseif ($paginationCount < 1) {
                $paginationCount = 4;
            }
            $blogCategories = BlogCategory::where('is_active', 1);
            if($categorySlug){
                $blogCategories = $blogCategories->where('slug', $categorySlug);
            }
            $blogCategories = $blogCategories->with('seoDetail.translations','translations')->select('id', 'slug', 'name')->paginate($paginationCount);
            $blogCategories->getCollection()->transform(function ($blogCategory) {
                $blogCategory->setHidden(['translations']);
                if($blogCategory->seoDetail){
                    $blogCategory->seoDetail->setHidden(['translations']);
                }
                return $blogCategory;
            });

            if ($blogCategories->isEmpty()) {
                return ResponseService::errorResponse(__('No active Blog Categories found.'));
            }

            return ResponseService::successResponse(__('Active Blogs slugs fetched successfully.'), $blogCategories);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getBlogCategories');
            ResponseService::errorResponse();
        }
    }

    /** Get Blog Categories Slug */
    public function getBlogCategoriesSlug(Request $request)
    {
        try {
            $blogs = BlogCategory::without('translations')
                ->where('is_active', 1)
                ->select('id', 'slug', 'updated_at')
                ->paginate(500);

            if ($blogs->isEmpty()) {
                return ResponseService::errorResponse(__('No active Blog Categories found.'));
            }

            return ResponseService::successResponse(__('Active Blog Categories slugs fetched successfully.'), $blogs);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getBlogCategoriesSlug');
            ResponseService::errorResponse();
        }
    }

    /** Set Blog Feedback */
    public function setBlogFeedback(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'blog_id' => 'required|integer|exists:blogs,id',
                'is_useful' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            if ($request->is_useful == null || !isset($request->is_useful)) {
                BlogFeedback::where(['blog_id' => $request->blog_id, 'user_id' => $request->user()->id])->delete();
            } else {
                BlogFeedback::updateOrCreate(
                    ['blog_id' => $request->blog_id, 'user_id' => $request->user()->id],
                    ['is_useful' => $request->is_useful == 1 ? 1 : 0]
                );
            }

            ResponseService::successResponse('Feedback recorded successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> setBlogFeedback');
            ResponseService::errorResponse('Failed to record feedback');
        }
    }

    /** Get Popular Blogs */
    public function getPopularBlogs(Request $request)
    {
        try {
            $paginationCount = $request->has('per_page') ? $request->per_page : 4;
            if ($paginationCount > 500) {
                $paginationCount = 500;
            } elseif ($paginationCount < 1) {
                $paginationCount = 4;
            }
            // Get Popular Blogs Data based on pagination count
            $blogs = Blog::activeCategory()->with(['translations', 'category'])
                ->withCount([
                    'feedbacks as useful_count' => fn($q) => $q->where('is_useful', 1),
                    'feedbacks as not_useful_count' => fn($q) => $q->where('is_useful', 0),
                ])
                ->orderByDesc('views')
                ->paginate($paginationCount);

            // Formate Response of pagination data
            $blogs->getCollection()->transform(function ($blog) {
                return $this->formateBlogResponse($blog);
            });

            ResponseService::successResponse('Popular blogs fetched successfully', $blogs);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getPopularBlogs');
            ResponseService::errorResponse('Failed to fetch popular blogs');
        }
    }

    private function incrementViews($id = null, $slug = null)
    {
        if ($id) {
            Blog::where('id', $id)->increment('views');
            return true;
        }
        if ($slug) {
            Blog::where('slug', $slug)->increment('views');
            return true;
        }

        Log::error('Blog views increment failed', [
            'id' => $id,
            'slug' => $slug,
        ]);
        return false;
    }

    private function formateBlogResponse($blog, $isDetail = false)
    {
        if (empty($blog)) {
            return null;
        }
        $blog->setHidden(['translations']);
        if($blog->category){
            $blog->category->setHidden(['translations']);
        }
        if(!$isDetail){
            $translatedDesc = $blog->translated_description ?? null;
            if($translatedDesc){
                $blog->description = mb_strlen($translatedDesc) > 500 ? mb_substr($translatedDesc, 0, 500) : $translatedDesc;
            }
            $desc = $blog->description ?? null;
            if($desc){
                $blog->description = mb_strlen($desc) > 500 ? mb_substr($desc, 0, 500) : $desc;
            }
        }
        return $blog;
    }
}
