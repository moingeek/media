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
            // $filePath = public_path('storage/' . $media->model_id . '/' . $media->file_name);
            // exec('ffmpeg -i '.$filePath. '-movflags faststart -acodec -vcodec output.mp4');
            $this->addMediaConversion('thumb')
                ->width(368)
                ->height(232)
                ->extractVideoFrameAtSecond(10)
                ->queued()
                ->performOnCollections('media');

            //Watermark video
            $ffmpeg = FFMpeg::create(array(
                'ffmpeg.binaries' => exec('which ffmpeg'),
                'ffprobe.binaries' => exec('which ffprobe')
            ));
            $filePath = public_path('storage/' . $media->model_id . '/' . $media->file_name);
            $watermarkPath = '/home/bakbuck-5/Downloads/mediumbakbuck.png';
            $video = $ffmpeg->open($filePath);
            $format = new \FFMpeg\Format\Video\X264('libmp3lame', 'libx264');            ;
            $video
                ->filters()
                ->watermark($watermarkPath, array(
                    'position' => 'relative',
                    'top' => 10,
                    'right' => 10,
                ));
            $video->save($format, 'watermarked.mp4');
        } else {


            //Thumbnail Creation

            $this->addMediaConversion('thumb')
                ->queued()
                ->width(50)
                ->height(50)
                ->sharpen(10)
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

            // //Image Pieces for Group Game
            // $orgImage = Image::make($filePath);
            // $newImg = $orgImage->resize(600, 600);
            // $pieces = 6;
            // $positions = ['top-left','top','top-right','left','center','right','bottom-left','bottom','bottom-right'];
            // $position = array_rand($positions);
            // for($i= 1 ; $i <= $pieces; $i++){
            //     $cropImage = $newImg;
            //     $cordinate = $this->UniqueRandomNumbersWithinRange(150,550,2);
            //     $piece = $cropImage->resizeCanvas($cordinate[0],$cordinate[1],$positions[$position]);
            //     $piece->save(public_path('storage/' . $media->model_id . '/' . 'piece-' . $i . '-' . $media->file_name));
            }
        }

}
