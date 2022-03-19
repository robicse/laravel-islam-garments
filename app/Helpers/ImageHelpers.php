<?php
namespace App\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageHelpers {
    public static function imageUpload($image,$path,$field) {
        //make unique name for image
        $currentDate = Carbon::now()->toDateString();
        $imagename = $currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

        // delete old image.....
        if(Storage::disk('public')->exists($path.$field))
        {
            Storage::disk('public')->delete($path.$field);

        }

        //resize image for hospital and upload
        //$proImage = Image::make($image)->resize(100, 100)->save($image->getClientOriginalExtension());
        $proImage = Image::make($image)->save($image->getClientOriginalExtension());
        Storage::disk('public')->put($path. $imagename, $proImage);

        return $imagename;
    }
}
?>
