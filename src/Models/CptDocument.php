<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Xpressengine\Document\Models\Document;
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

    public function dfSlug()
    {
        return $this->hasOne(DfSlug::class, 'target_id');
    }

    public function getSlug()
    {
        $slug = $this->dfSlug;
        return $slug === null ? '' : $slug->slug;
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
}
