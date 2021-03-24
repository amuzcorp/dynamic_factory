<?php

namespace Overcode\XePlugin\DynamicFactory\Models;

use Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt\CptModule;
use Xpressengine\Database\Eloquent\DynamicModel;
use Xpressengine\Media\Models\Image;
use Xpressengine\Media\Models\Media;

class DfThumb extends DynamicModel
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'target_id';

    protected $fillable = [
        'target_id',
        'df_thumbnail_file_id',
        'df_thumbnail_external_path',
        'df_thumbnail_path'
    ];


    public function getDfThumbnailPathAttribute($value)
    {
        $thumbnailImage = Image::find($this->df_thumbnail_file_id);
        if ($thumbnailImage == null) {
            return '';
        }

        if ($value !== '') {
            $media = \XeMedia::getHandler(Media::TYPE_IMAGE)->getThumbnail(
                $thumbnailImage,
                CptModule::THUMBNAIL_TYPE,
                'L'
            );

            $value = $media->url();
        }

        return $value;
    }
}
