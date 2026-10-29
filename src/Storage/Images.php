<?php
declare(strict_types=1);
namespace Elang\Storage;
use Elang\Http\HttpException;
final class Images
{
    /** Re-encode accepted raster images, stripping ancillary metadata and active content. */
    public function sanitize(string $bytes):array
    {
        if(!extension_loaded('gd')) throw new \RuntimeException('Install the PHP GD extension for image uploads.');$size=@getimagesizefromstring($bytes);
        if(!$size||!in_array($size['mime'],['image/jpeg','image/png','image/webp'],true)||$size[0]<1||$size[1]<1||$size[0]>4096||$size[1]>4096||$size[0]*$size[1]>12000000) throw new HttpException(422,'Upload a JPEG, PNG, or WebP image of at most 4096 pixels per side and 12 megapixels.');
        $image=@imagecreatefromstring($bytes);if(!$image) throw new HttpException(422,'The image could not be decoded.');ob_start();try { imagealphablending($image,false);imagesavealpha($image,true);$ok=match($size['mime']) { 'image/jpeg'=>imagejpeg($image,null,88),'image/png'=>imagepng($image,null,6),'image/webp'=>imagewebp($image,null,85) };$result=ob_get_contents(); }finally { ob_end_clean();imagedestroy($image); }
        if(!$ok||!is_string($result)) throw new \RuntimeException('The image could not be encoded.');return ['bytes'=>$result,'mime'=>$size['mime'],'extension'=>match($size['mime']) { 'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp' }];
    }
}
