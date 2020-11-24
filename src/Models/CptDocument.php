<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Xpressengine\Document\Models\Document;
use Xpressengine\Seo\SeoUsable;
use Xpressengine\User\Models\Guest;
use Xpressengine\User\Models\UnknownUser;

class CptDocument extends Document implements SeoUsable
{
    use SoftDeletes;

    protected $canonical;

    public function getTitle()
    {
        $title = str_replace('"', '\"', $this->getAttribute('title'));

        return $title;
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
}
