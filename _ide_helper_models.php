<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models\Banner{
/**
 * 
 *
 * @property string $id
 * @property string|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string $locale
 * @property array<array-key, mixed>|null $options
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Banner\Content> $banners
 * @property-read int|null $banners_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $children
 * @property-read int|null $children_count
 * @property-read mixed $creator
 * @property-read mixed $destroyer
 * @property-read mixed $editor
 * @property-read Category|null $parent
 * @property-read \App\Models\User|null $updater
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category root()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCategory {}
}

namespace App\Models\Banner{
/**
 * 
 *
 * @property string $id
 * @property string|null $banner_category_id
 * @property int $sort
 * @property bool $is_active
 * @property string|null $title
 * @property string|null $description
 * @property string|null $click_url
 * @property string|null $click_url_target
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string $locale
 * @property array<array-key, mixed>|null $options
 * @property int $impression_count
 * @property int $click_count
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Banner\Category|null $category
 * @property-read mixed $creator
 * @property-read mixed $destroyer
 * @property-read mixed $editor
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \App\Models\User|null $updater
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereBannerCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereClickCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereClickUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereClickUrlTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereImpressionCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperContent {}
}

namespace App\Models\Blog{
/**
 * 
 *
 * @property string $id
 * @property string|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string $locale
 * @property array<array-key, mixed>|null $options
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $children
 * @property-read int|null $children_count
 * @property-read mixed $creator
 * @property-read mixed $destroyer
 * @property-read mixed $editor
 * @property-read Category|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Blog\Post> $posts
 * @property-read int|null $posts_count
 * @property-read \App\Models\User|null $updater
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category root()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCategory {}
}

namespace App\Models\Blog{
/**
 * 
 *
 * @property string $id
 * @property string|null $blog_author_id
 * @property string|null $blog_category_id
 * @property bool $is_featured
 * @property string $title
 * @property string $slug
 * @property string $content_raw
 * @property string $content_html
 * @property string|null $content_overview
 * @property \App\Enums\Blog\PostStatus $status
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $last_published_at
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string $locale
 * @property array<array-key, mixed>|null $options
 * @property int $view_count
 * @property int $comments_count
 * @property int|null $reading_time
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $author
 * @property-read \App\Models\Blog\Category|null $category
 * @property-read mixed $creator
 * @property-read mixed $destroyer
 * @property-read mixed $editor
 * @property-read mixed $formatted_published_date
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property \Illuminate\Database\Eloquent\Collection<int, \Spatie\Tags\Tag> $tags
 * @property-read int|null $tags_count
 * @property-read \App\Models\User|null $updater
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post byStatus(\App\Enums\Blog\PostStatus $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post locale($locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereBlogAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereBlogCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereCommentsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereContentHtml($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereContentOverview($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereContentRaw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereLastPublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereReadingTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereScheduledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereViewCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post withAllTags(\ArrayAccess|\Spatie\Tags\Tag|array|string $tags, ?string $type = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post withAllTagsOfAnyType($tags)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post withAnyTags(\ArrayAccess|\Spatie\Tags\Tag|array|string $tags, ?string $type = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post withAnyTagsOfAnyType($tags)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post withAnyTagsOfType(array|string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post withoutTags(\ArrayAccess|\Spatie\Tags\Tag|array|string $tags, ?string $type = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPost {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $firstname
 * @property string $lastname
 * @property string $email
 * @property string|null $phone
 * @property string|null $company
 * @property string|null $employees
 * @property string|null $title
 * @property string $subject
 * @property string $message
 * @property string $status
 * @property string|null $reply_subject
 * @property string|null $reply_message
 * @property \Illuminate\Support\Carbon|null $replied_at
 * @property string|null $replied_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<array-key, mixed>|null $metadata
 * @property-read string $name
 * @property-read \App\Models\User|null $repliedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs search(string $searchTerm)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereEmployees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereFirstname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereLastname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereRepliedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereRepliedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereReplyMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereReplySubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactUs withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperContactUs {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $username
 * @property string $firstname
 * @property string $lastname
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property string|null $deleted_at
 * @property-read mixed $name
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

