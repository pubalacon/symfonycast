<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{

    public function __construct(string $uploadsPath)
    {
        $this->uploadsPath = $uploadsPath;
    }
    
    public function uploadArticleImage(UploadedFile $uploadedFile): string
    {
        // get the original filename
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        // if needed, transform special chars to url accepted ones
        $newFilename = Urlizer::urlize($originalFilename).'-'.uniqid().'.'.$uploadedFile->guessExtension();
        // set the target dir
        $destination = $this->uploadsPath.'/article_image';
        // move the the temporary file to target dir
        $uploadedFile->move(
            $destination,
            $newFilename
        );

        return $newFilename;
    }
}