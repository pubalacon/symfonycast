<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Asset\Context\RequestStackContext;

class UploaderHelper
{

    const ARTICLE_IMAGE = 'article_image';

    public function __construct(string $uploadsPath, RequestStackContext $requestStackContext)
    {
        $this->uploadsPath = $uploadsPath;
        $this->requestStackContext = $requestStackContext;
    }
    
    public function uploadArticleImage(File $file): string
    {
        // get the original filename
        //$originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        //$newFilename = Urlizer::urlize($originalFilename).'-'.uniqid().'.'.$file->guessExtension();
        
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }
        
        // if needed, transform special chars to url accepted ones
        $newFilename = Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME)).'-'.uniqid().'.'.$file->guessExtension();
        // set the target dir
        $destination = $this->uploadsPath.'/'.self::ARTICLE_IMAGE;
        // move the the temporary file to target dir
        $file->move(
            $destination,
            $newFilename
        );

        return $newFilename;
    }

    public function getPublicPath(string $path): string
    {
        // needed if you deploy under a subdirectory
        return $this->requestStackContext
            ->getBasePath().'/uploads/'.$path;
    }
}