<?php

namespace App\Http\Controllers\Image;

use App\Http\Controllers\Controller;
use App\Models\Image\Image;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function store(Request $request): JsonResponse
    {
        return $this->imageService->createImage($request->all(), $request);
    }

    public function show(Image $image): JsonResponse
    {
        return $this->imageService->getImageById($image);
    }

    public function update(Image $image, Request $request): JsonResponse
    {
        return $this->imageService->updateImage($image, $request->all());
    }

    public function destroy(Image $image): JsonResponse
    {
        return $this->imageService->deleteImage($image);
    }
}
