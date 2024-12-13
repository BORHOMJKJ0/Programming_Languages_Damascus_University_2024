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
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
            }
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
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
                $this->checkAdmin('Image', 'create');
                $product = Product::findOrFail($data['product_id']);
                $this->checkOwnershipForProducts($product, 'Image', 'update');
            }
            if (isset($data['main'])) {
                $data['main'] = filter_var($data['main'], FILTER_VALIDATE_BOOLEAN);
            }

            $this->validateImageData($data);

            $path = $request->file('image')->store('images', 'public');
            $data['image'] = $path;

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
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
                $this->checkAdmin('Image', 'update');
                $this->checkOwnership($image->product->store, 'Image', 'update');
                $product = Product::findOrFail($data['product_id']);
                $this->checkOwnershipForProducts($product, 'Image', 'update');

            }
            if (isset($data['main'])) {
                $data['main'] = filter_var($data['main'], FILTER_VALIDATE_BOOLEAN);
            }
            $this->validateImageData($data, 'sometimes');

            if (isset($data['image'])) {
                if ($image->image && Storage::disk('public')->exists($image->image)) {
                    Storage::disk('public')->delete($image->image);
                }
                $path = $data['image']->store('images', 'public');
                $data['image'] = $path;
            }
            $product_id = $data['product_id'] ?? $image->product_id;
            $hasMainImage = Image::where('product_id', $product_id)
                ->where('main', 1)
                ->exists();

            if ($hasMainImage && isset($data['main']) && $data['main'] == 1) {
                return ResponseHelper::jsonResponse([], 'This product already has a main image.', 400);
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
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
                $this->checkAdmin('Image', 'delete');
                $this->checkOwnershipForProducts($image->product, 'Image', 'delete');
            }
            $this->imageRepository->delete($image);
            $response = ResponseHelper::jsonResponse([], 'Image deleted successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function validateImageData(array $data, $rule = 'required')
    {
        $allowedAttributes = ['image', 'main', 'product_id'];

        $unexpectedAttributes = array_diff(array_keys($data), $allowedAttributes);
        if (! empty($unexpectedAttributes)) {
            throw new HttpResponseException(
                ResponseHelper::jsonResponse(
                    [],
                    'You are not allowed to send the following attributes: '.implode(', ', $unexpectedAttributes),
                    400,
                    false
                )
            );
        }
        $validator = Validator::make($data, [
            'image' => "$rule",
            'main' => "$rule",
            'product_id' => "$rule|exists:products,id",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            throw new HttpResponseException(
                ResponseHelper::jsonResponse(
                    [],
                    $errors,
                    400,
                    false
                )
            );
        }
    }
}
