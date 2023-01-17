<?php
namespace App\Traits;
use Illuminate\Support\Facades\Storage;

trait FileUploadTrait{

    public function diskAvatar()
    {
        return Storage::disk("avatar");
    }
}
