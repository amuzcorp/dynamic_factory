<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Overcode\XePlugin\DynamicFactory\Components\DynamicFields\SuperRelate\SuperRelateField;
use Xpressengine\Document\Models\Document;
use Xpressengine\Plugins\Comment\CommentUsable;
use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\Routing\InstanceRoute;
use Xpressengine\Seo\SeoUsable;
use Xpressengine\User\Models\Guest;
use Xpressengine\User\Models\UnknownUser;
use Xpressengine\User\Models\User;

class CptDocument extends Document implements CommentUsable, SeoUsable
{
    protected $casts = [
        'published_at' => 'datetime'
    ];

    use SoftDeletes;

    /**
     * Canonical url
     *
     * @var string
     */
    protected $canonical;

    /**
     * get user id
     *
     * @return string
     */
    public function getUserId()
    {
        $userId = $this->getAttribute('user_id');
        if ($this->getAttribute('user_type') === self::USER_TYPE_ANONYMITY) {
            $userId = '';
        }

        return $userId;
    }

    public function scopeCpt($query, $cpt_id)
    {
        return $query->where('documents.type', $cpt_id);
    }

    public function scopePublic($query)
    {
        return $query->where('documents.status', self::STATUS_PUBLIC)
            ->where('documents.approved', self::APPROVED_APPROVED)
            ->where('documents.display', self::DISPLAY_VISIBLE);
    }

    public function isPublic()
    {
        return $this->status === self::STATUS_PUBLIC &&
            $this->approved === self::APPROVED_APPROVED &&
            $this->display === self::DISPLAY_VISIBLE;
    }

    public function setPublic()
    {
        $this->status = self::STATUS_PUBLIC;
        $this->approved = self::APPROVED_APPROVED;
        $this->display = self::DISPLAY_VISIBLE;

        $this->save();
    }

    public function scopePrivate($query)
    {
        return $query->where('documents.status', self::STATUS_PRIVATE)
            ->where('documents.approved', self::APPROVED_APPROVED)
            ->where('documents.display', self::DISPLAY_SECRET);
    }

    public function isPrivate()
    {
        return $this->status === self::STATUS_PRIVATE &&
            $this->approved === self::APPROVED_APPROVED &&
            $this->display === self::DISPLAY_SECRET;
    }

    public function setPrivate()
    {
        $this->status = self::STATUS_PRIVATE;
        $this->approved = self::APPROVED_APPROVED;
        $this->display = self::DISPLAY_SECRET;

        $this->save();
    }

    public function scopeTemp($query)
    {
        return $query->where('documents.status', self::STATUS_TEMP)
            ->where('documents.approved', self::APPROVED_WAITING)
            ->where('documents.display', self::DISPLAY_HIDDEN);
    }

    public function isTemp()
    {
        return $this->status === self::STATUS_TEMP &&
            $this->approved === self::APPROVED_WAITING &&
            $this->display === self::DISPLAY_HIDDEN;
    }

    public function setTemp()
    {
        $this->status = self::STATUS_TEMP;
        $this->approved = self::APPROVED_WAITING;
        $this->display = self::DISPLAY_HIDDEN;

        $this->save();
    }

    public function scopePublishReserved($query)
    {
        return $query->where('documents.published_at', '>', date('Y-m-d H:i:s'));
    }

    public function isPublishReserved()
    {
        return $this->published_at > date('Y-m-d H:i:s');
    }

    public function scopePublished($query)
    {
        return $query->where('documents.published_at', '<=', date('Y-m-d H:i:s'));
    }

    public function isPublished()
    {
        return $this->published_at <= date('Y-m-d H:i:s');
    }

    public function scopeVisible($query)
    {
        return $query->where('documents.status', static::STATUS_PUBLIC)
            ->where('documents.display', '<>', static::DISPLAY_HIDDEN)
            ->where('documents.approved', static::APPROVED_APPROVED)
            ->where('documents.published_at', '<=', date('Y-m-d H:i:s'));
    }

    /**
     * visible
     *
     * @param Builder $query query
     * @return $this
     */
//    public function scopeVisible(Builder $query)
//    {
//        $query->where('status', static::STATUS_PUBLIC)
//            ->whereIn('display', [static::DISPLAY_VISIBLE, static::DISPLAY_SECRET])
//            ->where('published', static::PUBLISHED_PUBLISHED)
//            ->where(function($query){
//                $query->where('approved',static::APPROVED_APPROVED)
//                    ->orWhere($this->getTable().'.user_id',auth()->id());
//            });
//    }

    /**
     * notice
     *
     * @param Builder $query query
     * @return $this
     */
//    public function scopeNotice(Builder $query)
//    {
//        $query->where('status', static::STATUS_NOTICE)
//            ->whereIn('display', [static::DISPLAY_VISIBLE, static::DISPLAY_SECRET])
//            ->where('published', static::PUBLISHED_PUBLISHED);
//    }

    /**
     * visible with notice
     *
     * @param Builder $query query
     * @return void
     */
//    public function scopeVisibleWithNotice(Builder $query)
//    {
//        $query->whereIn('status', [static::STATUS_PUBLIC, static::STATUS_NOTICE])
//            ->whereIn('display', [static::DISPLAY_VISIBLE, static::DISPLAY_SECRET])
//            ->where('published', static::PUBLISHED_PUBLISHED);
//    }

    public function getTitle()
    {
        $title = str_replace('"', '\"', $this->getAttribute('title'));

        return $title;
    }

    /**
     * get compiled content
     *
     * @return string
     */
    public function getContent()
    {
        return compile($this->instance_id, $this->content, $this->format === static::FORMAT_HTML);
    }

    public function getDescription()
    {
        return str_replace(
            ['"', "\n"],
            ['\"', ''],
            $this->getAttribute('pure_content')
        );
    }

    public function getKeyword()
    {
        return [];
    }

    public function getUrl()
    {
        return $this->canonical;
    }

    public function getAuthor()
    {
        if ($this->user !== null) {
            return $this->user;
        } elseif ($this->isGuest() === true) {
            return new Guest;
        } else {
            return new UnknownUser;
        }
    }


    public function getImages()
    {
        $images = [];

        return $images;
    }

    public function setCanonical($url)
    {
        $this->canonical = $url;

        return $this;
    }

    public function isGuest()
    {
        return $this->getAttribute('user_type') === self::USER_TYPE_GUEST;
    }

    public function content()
    {
        return compile($this->instance_id, $this->content, $this->format === static::FORMAT_HTML);
    }

    public function thumb()
    {
        return $this->belongsTo(DfThumb::class, 'id', 'target_id');
    }

    public function favorite()
    {
        return $this->belongsTo(DfFavorite::class, 'id', 'target_id');
    }

    public function slug()
    {
        return $this->belongsTo(DfSlug::class, 'id', 'target_id');
    }

    public function dfSlug()
    {
        return $this->hasOne(DfSlug::class, 'target_id');
    }

    public function comments()
    {
        return $this->belongsToMany(Comment::class, 'comment_target', 'target_id', 'doc_id');
    }

    public function getSlug()
    {
        $slug = $this->dfSlug;
        return $slug === null ? '' : $slug->slug;
    }

    /**
     * get file ids
     *
     * @return array
     */
    public function getFileIds()
    {
        $files = $this->files;
        $ids = [];
        foreach ($files as $file) {
            $ids[] = $file->id;
        }
        return $ids;
    }

    /**
     * 현재 문서의 관련 문서를 불러온다 (use_dynamic = true 일 경우 DF 도 붙여서 불러옴)
     *
     * @param $field_id
     * @param bool $use_dynamic
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function hasDocument($field_id, $use_dynamic = true)
    {
        $tableName = SuperRelateField::TABLE_NAME;

        $query = $this->belongsToMany(CptDocument::class, $tableName, 's_id', 't_id')->where($tableName.'.field_id', $field_id);
        if($use_dynamic){
            $group = sprintf('documents_%s', $this->instance_id);
            $target_group = SuperRelate::Where($tableName.'.field_id', $field_id)->where('s_id', $this->id)->where('s_group', $group)->pluck('t_group')->first();

            $query->setProxyOption(['group' => $target_group, 'table' => 'documents'], false);
        }

        return $query->get();
    }

    /**
     * 현재 문서를 관련 문서로 가지고 있는 문서를 불러온다 (반대관계에서는 다이나믹을 기본적으로는 달지 않는다)
     *
     * @param null $field_id
     * @param null $source_group
     * @param null $target_group
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function belongDocument($field_id = null, $source_group = null)
    {
        $tableName = SuperRelateField::TABLE_NAME;

        $query = $this->belongsToMany(CptDocument::class, $tableName, 't_id', 's_id')->withPivot('t_id');
        if($field_id != null){
            $query->where(sprintf('%s.field_id', $tableName), $field_id);
            if($source_group != null) {
                $query->where(sprintf('%s.s_group', $tableName), $source_group);
                $query->setProxyOption(['group' => $source_group, 'table'=>'documents'], false);
            }
        }

        return $query->get();
    }

    public function hasUser()
    {

    }

    public function belongUser()
    {

    }

    public function schedule()
    {
        return $this->hasMany(\Amuz\XePlugin\Bookings\Models\BookedSchedule::class, 'booked_id', 'id');
    }

    public function taxonomy()
    {
        return $this->hasMany(DfTaxonomy::class, 'target_id', 'id');
    }

    public function getTaxonomies(){
        return $this->taxonomy()->join('df_category_extra','df_category_extra.category_id','=','df_taxonomy.category_id')->get()->keyBy('slug');
    }

    /**
     * get users
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * has user
     *
     * @return bool
     */
    public function hasAuthor()
    {
        return $this->user !== null;
    }

    /**
     * 문서 정보만으로 인스턴스 route 를 생성한다.
     * 하나의 CPT 에서 여러개의 인스턴스를 만들어도 처음의 1개만 반환된다.
     *
     * @return string|null
     */
    public function getCptSlug($instanceId = null)
    {
        $configManager = app('xe.config');
        $config = $configManager->get('module/cpt@cpt');
        $children = $configManager->children($config);
        foreach($children as $child){
            if($child->get('cpt_id') === $this->instance_id) {
                $instanceId = ($instanceId !== null) ? $instanceId : $child->get('instanceId');
                return instance_route('slug', [$this->dfSlug->slug], $instanceId);
            }
        }

        return null;
    }


    /**
     * Returns unique identifier
     *
     * @return string
     */
    public function getUid()
    {
        return $this->getAttribute('id');
    }

    /**
     * Returns instance identifier
     *
     * @return mixed
     */
    public function getInstanceId()
    {
        return $this->getAttribute('instance_id');
    }

    /**
     * Returns the link
     *
     * @param InstanceRoute $route route instance
     * @return string
     */
    public function getLink(InstanceRoute $route)
    {
        return $route->url . '/show/' . $this->getKey();
    }
}
