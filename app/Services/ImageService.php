<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Image\ImageResource;
use App\Models\Image\Image;
use App\Models\Product\Product;
use App\Repositories\ImageRepository;
use App\Traits\AuthTrait;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImageService
{
    use AuthTrait;

    protected $imageRepository;

    public function __construct(ImageRepository $imageRepository)
    {
        $this->imageRepository = $imageRepository;
    }

    public function getImageById(Image $image)
    {
        try {
            $this->checkGuest();
            $data = ['Image' => ImageResource::make($image)];

            $response = ResponseHelper::jsonResponse($data, 'Image retrieved successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function createImage(array $data, Request $request)
    {
        try {
            $this->checkGuest();
            $this->checkAdmin('Image', 'create');
            if (isset($data['main'])) {
                $data['main'] = filter_var($data['main'], FILTER_VALIDATE_BOOLEAN);
            }

            $this->validateImageData($data);

            $path = $request->file('image')->store('images', 'public');
            $data['image'] = $path;

            $product = Product::findOrFail($data['product_id']);
            $this->checkOwnershipForProducts($product, 'Image', 'create');

            $hasMainImage = Image::where('product_id', $data['product_id'])
                ->where('main', 1)
                ->exists();

            if ($hasMainImage && isset($data['main']) && $data['main'] == 1) {
                return ResponseHelper::jsonResponse([], 'This product already has a main image.', 400);
            }

            $image = $this->imageRepository->create($data);

            return ResponseHelper::jsonResponse(
                ['Image' => ImageResource::make($image)],
                'Image created successfully!',
                201
            );
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    public function updateImage(Image $image, array $data)
    {
        try {
            $this->checkGuest();
            $this->checkAdmin('Image', 'update');
            if (isset($data['main'])) {
                $data['main'] = filter_var($data['main'], FILTER_VALIDATE_BOOLEAN);
            }
            $this->checkOwnership($image->product->store, 'Image', 'update');
            $this->validateImageData($data, 'sometimes');
            $product = Product::findOrFail($data['product_id']);
            $this->checkOwnershipForProducts($product, 'Image', 'update');

            if (isset($data['image'])) {
                if ($image->image && Storage::disk('public')->exists($image->image)) {
                    Storage::disk('public')->delete($image->image);
                }
                $path = $data['image']->store('images', 'public');
                $data['image'] = $path;
            }

            $updatedImage = $this->imageRepository->update($image, $data);

            $data = [
                'Image' => ImageResource::make($updatedImage),
            ];

            $response = ResponseHelper::jsonResponse($data, 'Image updated successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function deleteImage(Image $image)
    {
        try {
            $this->checkGuest();
            $this->checkAdmin('Image', 'delete');
            $this->checkOwnership($image->product, 'Image', 'delete');
            $this->imageRepository->delete($image);
            $response = ResponseHelper::jsonResponse([], 'Image deleted successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function validateImageData(array $data, $rule = 'required')
    {
        $validator = Validator::make($data, [
            'image' => "$rule",
            'main' => "$rule",
            'product_id' => "$rule|exists:products,id",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            throw new HttpResponseException(
                response()->json([
                    'successful' => false,
                    'message' => $errors,
                    'status_code' => 400,
                ], 400)
            );
        }
    }
}
