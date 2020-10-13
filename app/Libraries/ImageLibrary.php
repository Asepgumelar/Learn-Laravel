<?php

namespace App\Libraries;

use App\Models\Image as ImageModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image as ImgMap;
use Ramsey\Uuid\Uuid;

/**
 * Class ImageLibrary.
 *
 * @author Odenktools Technology
 * @license MIT
 * @copyright (c) 2020, Odenktools Technology.
 *
 * @package App\Libraries
 */
class ImageLibrary
{

    public function saveUserImg($image, $dir, $name)
    {
        $modelImage = new ImageModel();
        $id = Uuid::getFactory()->uuid4()->toString();
        $modelImage->id = $id;
        $resize = $this->resize($image, 128, 128);
        $ext = $image->getClientOriginalExtension();
        $full = $dir . '/' . $id . '.' . $ext;
        Storage::disk('public')->put($full, $resize->encode());
        $nameSlug = Str::slug($name);
        $modelImage->name = Str::limit($nameSlug, 191, '');
        $modelImage->extension = Str::slug($image->getClientOriginalExtension());
        $modelImage->path = $dir;
        $modelImage->image_url = $full;
        $modelImage->data_type = 'original'; //original/inherit
        $modelImage->save();
        return $modelImage->id;


    }


    public function delete($driver, ImageModel $model)
    {
        Storage::disk($driver)->delete($model->image_url);
    }

    public function resize($raw, $standardWidth = 750, $standardHeight = 410)
    {
        $image = ImgMap::make($raw);
        $image = $image->resize($standardWidth, $standardHeight);
        return $image;
    }
}
