<?php

namespace App\Models\Blog;

use App\Enums\Blog\PostStatus;
use App\Models\User;
use App\Traits\HasUserStamp;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Tags\HasTags;
use Illuminate\Support\Str;
use League\HTMLToMarkdown\HtmlConverter;
use Mews\Purifier\Casts\CleanHtml;

/**
 * @mixin IdeHelperPost
 */
class Post extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory, HasUlids, SoftDeletes;
    use HasUserStamp, HasTags;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'blog_posts';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'blog_author_id',
        'blog_category_id',
        'title',
        'slug',
        'content_raw',
        'content_html',
        'content_overview',
        'status',
        'is_featured',
        'published_at',
        'scheduled_at',
        'last_published_at',
        'meta_title',
        'meta_description',
        'locale',
        'options',
        'reading_time',
        'view_count',
        'comments_count',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'date',
        'scheduled_at' => 'datetime',
        'last_published_at' => 'datetime',
        'options' => 'json',
        'reading_time' => 'integer',
        'view_count' => 'integer',
        'comments_count' => 'integer',
        'status' => PostStatus::class,
        // 'content_html'=> CleanHtml::class,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_at',
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from title
        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });

        static::updating(function (Post $post) {
            // if ($post->isDirty('title') && !$post->isDirty('slug')) {
            //     $post->slug = Str::slug($post->title);
            // }
        });

        static::deleting(function (Post $post) {
            if (! $post->isForceDeleting() && $post->status !== PostStatus::DRAFT) {
                $post->status = PostStatus::DRAFT;
                $post->saveQuietly(); // hindari loop event
            }
        });

        // static::saving(function (Post $post) {
        //     $converter = new HtmlConverter([
        //         'header_style' => 'atx', // pakai '#' style
        //     ]);
        //     $post->content_raw = $converter->convert($post->content_html);
        // });
    }

    /**
     * Handle content processing when setting raw content
     */
    // public function setContentRawAttribute($value)
    // {
    //     $this->attributes['content_raw'] = $value;

    // Convert Markdown to HTML
    // $converter = new GithubFlavoredMarkdownConverter([
    //     'html_input' => 'strip',
    //     'allow_unsafe_links' => false,
    // ]);

    // $this->attributes['content_html'] = $converter->convert($value)->getContent();

    // Auto-generate content overview if not set
    // if (empty($this->attributes['content_overview'])) {
    //     $plainText = strip_tags($this->attributes['content_html']);
    //     $this->attributes['content_overview'] = substr($plainText, 0, 157) . '...';
    // }

    // Calculate reading time (avg reading speed: 200 words per minute)
    // $wordCount = str_word_count(strip_tags($this->attributes['content_html']));
    // $this->attributes['reading_time'] = ceil($wordCount / 200);
    // }

    public function setContentHtmlAttribute($value)
    {
        $this->attributes['content_html'] = $value;
        // Auto-generate content overview if not set
        if (empty($this->attributes['content_overview'])) {
            $plainText = strip_tags($this->attributes['content_html']);
            $this->attributes['content_overview'] = substr($plainText, 0, 157) . '...';
        }

        // Calculate reading time (avg reading speed: 200 words per minute)
        $wordCount = str_word_count(strip_tags($this->attributes['content_html']));
        $this->attributes['reading_time'] = ceil($wordCount / 200);

        $converter = new HtmlConverter([
            'header_style' => 'atx', // pakai '#' style
        ]);
        $this->attributes['content_raw'] = $converter->convert($value);
    }

    /**
     * Get the author that owns the post.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blog_author_id');
    }

    /**
     * Get the category that owns the post.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'blog_category_id');
    }

    /**
     * Get the user who last updated this post.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Register media conversions.
     */
    public function registerMediaConversions(Media|null $media = null): void
    {
        $this->addMediaConversion('preview')
            ->format('webp')
            ->quality(90)
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();

        // Add responsive image sizes - always convert to WebP
        $this->addMediaConversion('thumbnail')
            ->format('webp')
            ->quality(85)
            ->fit(Fit::Contain, 150, 150)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->format('webp')
            ->quality(85)
            ->fit(Fit::Contain, 600, 600)
            ->nonQueued();

        $this->addMediaConversion('large')
            ->format('webp')
            ->quality(85)
            ->fit(Fit::Contain, 1280, 720)
            ->nonQueued();
    }

    /**
     * Get the featured image URL
     * @param string $conversion
     * @return string|null
     */
    public function getFeaturedImageUrl(string $conversion = ''): ?string
    {
        $media = $this->getFirstMedia('featured_images');

        if (!$media) {
            return null;
        }

        return $conversion ? $media->getUrl($conversion) : $media->getUrl();
    }

    /**
     * Check if the post has a featured image
     * @return bool
     */
    public function hasFeaturedImage(): bool
    {
        return $this->hasMedia('featured_images');
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_images')
            ->singleFile();

        $this->addMediaCollection('gallery');
    }

    /**
     * Get active and published posts only
     */
    public function scopePublished($query)
    {
        return $query->where('status', PostStatus::PUBLISHED->value)
            ->where('published_at', '<=', now());
    }

    /**
     * Get featured posts only
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get posts by status
     */
    public function scopeByStatus($query, PostStatus $status)
    {
        return $query->where('status', $status->value);
    }

    /**
     * Get posts by locale
     */
    public function scopeLocale($query, $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Track a view for this post
     */
    public function trackView()
    {
        $this->increment('view_count');
    }

    /**
     * Get related posts based on category and tags
     */
    public function getRelatedPosts($limit = 3)
    {
        // Get all the tags of the current post
        $tags = $this->tags->pluck('name')->toArray();

        // Find posts with the same category or tags
        return self::where('id', '!=', $this->id)
            ->where(function ($query) use ($tags) {
                $query->where('blog_category_id', $this->blog_category_id)
                    ->orWhereHas('tags', function ($q) use ($tags) {
                        $q->whereIn('name', $tags);
                    });
            })
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get post URL using slug
     *
     * @return string
     */
    public function getUrl()
    {
        return route('blog.show', ['slug' => $this->slug]);
    }

    /**
     * Get formatted published date
     */
    public function getFormattedPublishedDateAttribute()
    {
        return $this->published_at->format('F j, Y');
    }

    /**
     * Get canonical URL
     */
    public function getCanonicalUrl()
    {
        return route('blog.show', ['slug' => $this->slug]);
    }

    /**
     * Get estimated reading time
     */
    public function getReadingTimeAttribute()
    {
        if ($this->attributes['reading_time'] ?? null) {
            return $this->attributes['reading_time'];
        }

        // Calculate if not already set
        $wordCount = str_word_count(strip_tags($this->content_html));
        return ceil($wordCount / 200); // Average reading speed: 200 words per minute
    }

    /**
     * Share URL generators for social media
     */
    public function getTwitterShareUrl()
    {
        $url = urlencode($this->getCanonicalUrl());
        $title = urlencode($this->title);
        return "https://twitter.com/intent/tweet?url={$url}&text={$title}";
    }

    public function getFacebookShareUrl()
    {
        $url = urlencode($this->getCanonicalUrl());
        return "https://www.facebook.com/sharer/sharer.php?u={$url}";
    }

    public function getLinkedinShareUrl()
    {
        $url = urlencode($this->getCanonicalUrl());
        // $title = urlencode($this->title);
        return "https://www.linkedin.com/sharing/share-offsite/?url={$url}";
    }

    public function getWhatsappShareUrl()
    {
        $url = urlencode($this->getCanonicalUrl());
        $title = urlencode($this->title);
        return "https://api.whatsapp.com/send?text={$title}%20{$url}";
    }


    /**
     * Get the route key for the model.
     *
     * @return string
     */
    // public function getRouteKeyName()
    // {
    //     return 'slug';
    // }

    /**
     * Get previous post
     */
    public function getPreviousPost()
    {
        return self::published()
            ->where('published_at', '<', $this->published_at)
            ->orderBy('published_at', 'desc')
            ->first();
    }

    /**
     * Get next post
     */
    public function getNextPost()
    {
        return self::published()
            ->where('published_at', '>', $this->published_at)
            ->orderBy('published_at', 'asc')
            ->first();
    }


    /**
     * Handle check is published or not
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === PostStatus::PUBLISHED && $this->published_at <= now();
    }
}
