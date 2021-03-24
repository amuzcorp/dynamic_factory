<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Xpressengine\Document\Models\Document;
use Xpressengine\Plugins\Board\Models\Board;
use Xpressengine\Seo\SeoUsable;
use Xpressengine\User\Models\Guest;
use Xpressengine\User\Models\UnknownUser;
use Xpressengine\User\Models\User;

class CptDocument extends Document implements SeoUsable
{
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

    /**
     * visible
     *
     * @param Builder $query query
     * @return $this
     */
    public function scopeVisible(Builder $query)
    {
        $query->where('status', static::STATUS_PUBLIC)
            ->whereIn('display', [static::DISPLAY_VISIBLE, static::DISPLAY_SECRET])
            ->where('published', static::PUBLISHED_PUBLISHED)
            ->where(function($query){
                $query->where('approved',static::APPROVED_APPROVED)
                    ->orWhere($this->getTable().'.user_id',auth()->id());
            });
    }

    /**
     * notice
     *
     * @param Builder $query query
     * @return $this
     */
    public function scopeNotice(Builder $query)
    {
        $query->where('status', static::STATUS_NOTICE)
            ->whereIn('display', [static::DISPLAY_VISIBLE, static::DISPLAY_SECRET])
            ->where('published', static::PUBLISHED_PUBLISHED);
    }

    /**
     * visible with notice
     *
     * @param Builder $query query
     * @return void
     */
    public function scopeVisibleWithNotice(Builder $query)
    {
        $query->whereIn('status', [static::STATUS_PUBLIC, static::STATUS_NOTICE])
            ->whereIn('display', [static::DISPLAY_VISIBLE, static::DISPLAY_SECRET])
            ->where('published', static::PUBLISHED_PUBLISHED);
    }

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

    public function slug()
    {
        return $this->belongsTo(DfSlug::class, 'id', 'target_id');
    }

    public function dfSlug()
    {
        return $this->hasOne(DfSlug::class, 'target_id');
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

    public function taxonomy()
    {
        return $this->hasMany(DfTaxonomy::class, 'target_id', 'id');
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
}
