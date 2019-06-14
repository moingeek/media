<?php

namespace App;

use FFMpeg\FFMpeg;
use Spatie\Image\Manipulations;
use Intervention\Image\Facades\Image;
use Spatie\MediaLibrary\Models\Media;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasMedia
{
    use Notifiable, HasMediaTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function UniqueRandomNumbersWithinRange($min, $max, $quantity) {
        $numbers = range($min, $max);
        shuffle($numbers);
        return array_slice($numbers, 0, $quantity);
    }

    public function registerMediaConversions(Media $media = null)
    {
        //dd($media->mime_type);
        if ($media->mime_type == 'video/mp4') {

            //Thumbnail Video Creation
            $this->addMediaConversion('thumb')
                ->width(368)
                ->height(232)
                ->extractVideoFrameAtSecond(10)
                ->queued()
                ->performOnCollections('media');

            //Watermark video
            // $ffmpeg = FFMpeg::create(array(
                // 'ffmpeg.binaries' => exec('which ffmpeg'),
                // 'ffprobe.binaries' => exec('which ffprobe')
            // ));
            // $filePath = public_path('storage/' . $media->model_id . '/' . $media->file_name);
            // $watermarkPath = '/home/bakbuck-5/Downloads/watermark.png';
            // $video = $ffmpeg->open($filePath);
            // $format = new FFMpeg\Format\Video\X264();
            // $video
                // ->filters()
                // ->watermark($watermarkPath, array(
                    // 'position' => 'relative',
                    // 'bottom' => 50,
                    // 'right' => 50,
                // ));
            // $video->save($format, 'watermarked.mp4');
        } else {


            //Thumbnail Creation

            $this->addMediaConversion('thumb')
                ->queued()
                ->width(50)
                ->height(50)
                ->sharpen(10)
                ->queued()
                ->optimize();

            //Image with Watermark
            $this->addMediaConversion('watermark')
                ->queued()
                ->watermark('/home/bakbuck-5/Downloads/watermark.png')
                ->watermarkHeight(25, Manipulations::UNIT_PERCENT)
                ->watermarkWidth(100, Manipulations::UNIT_PERCENT)
                ->optimize()
                ->queued()
                ->watermarkOpacity(25);

            //Compressed Image
            $filePath = public_path('storage/' . $media->model_id . '/' . $media->file_name);
            $img = Image::make($filePath);
            $img->save(public_path('storage/' . $media->model_id . '/' . 'compressed-' . $media->file_name), 50);

            //Blur img
            $this->addMediaConversion('blur')
                ->queued()
                ->blur(50);

            //Image Pieces for Group Game
            $full = Image::make($filePath);
            $newImg = $full->resize(600, 600);
            $pieces = 6;
            // for ($i = 0; $i < $pieces; $i++) {
            //     $piece = $newImg->crop(100, 50, rand(1, 250), rand(250, 500));
            //     $piece->save(public_path('storage/' . $media->model_id . '/' . 'piece-' . $i . '-' . $media->file_name), 50);
            // }
            for($i= 1 ; $i <= $pieces; $i++){
                $cropImage = $newImg;
                $cordinate = $this->UniqueRandomNumbersWithinRange(200,600,4);
                $piece = $cropImage->crop($cordinate[0],$cordinate[1],$cordinate[2],$cordinate[3]);
                $piece->save(public_path('storage/' . $media->model_id . '/' . 'piece-' . $i . '-' . $media->file_name));
            }
        }
    }
    
}
