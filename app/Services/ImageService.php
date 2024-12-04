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
use Illuminate\Validation\ValidationException;

class ImageService
{
    use AuthTrait;

    protected $imageRepository;

    public function __construct(ImageRepository $imageRepository)
    {
        $this->imageRepository = $imageRepository;
    }

    public function getAllImages(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $items = $request->query('items', 20);
            $this->checkGuest('Image', 'perform');
            $images = $this->imageRepository->getAll($items, $page);
            $hasMorePages = $images->hasMorePages();

            $data = [
                'Images' => ImageResource::collection($images),
                'hasMorePages' => $hasMorePages,
            ];

            $response = ResponseHelper::jsonResponse($data, 'Images retrieved successfully');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function getMyImages(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $items = $request->query('items', 20);
            $this->checkGuest('Image', 'perform');
            $this->checkAdmin('Image', 'perform');
            $images = $this->imageRepository->getMy($items, $page);
            $hasMorePages = $images->hasMorePages();

            $data = [
                'Images' => ImageResource::collection($images),
                'hasMorePages' => $hasMorePages,
            ];

            $response = ResponseHelper::jsonResponse($data, 'Images retrieved successfully');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function getImageById(Image $image)
    {
        try {
            $this->checkGuest('Image', 'perform');
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
            $this->checkGuest('Image', 'create');
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

    public function getImagesOrderedBy($column, $direction, Request $request)
    {
        try {
            $this->checkGuest('Image', 'order');
            $validColumns = ['created_at', 'updated_at'];
            $validDirections = ['asc', 'desc'];
            if (! in_array($column, $validColumns) || ! in_array($direction, $validDirections)) {
                return ResponseHelper::jsonResponse([], 'Invalid column or direction', 400, false);
            }
            $page = $request->query('page', 1);
            $items = $request->query('items', 20);

            $images = $this->imageRepository->orderBy($column, $direction, $page, $items);
            $hasMorePages = $images->hasMorePages();
            $data = [
                'Images' => ImageResource::collection($images),
                'hasMorePages' => $hasMorePages,
            ];

            $response = ResponseHelper::jsonResponse($data, 'Images ordered successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function getMyImagesOrderedBy($column, $direction, Request $request)
    {
        try {
            $this->checkGuest('Image', 'order');
            $this->checkAdmin('Image', 'order');
            $validColumns = ['created_at', 'updated_at'];
            $validDirections = ['asc', 'desc'];
            if (! in_array($column, $validColumns) || ! in_array($direction, $validDirections)) {
                return ResponseHelper::jsonResponse([], 'Invalid column or direction', 400, false);
            }
            $page = $request->query('page', 1);
            $items = $request->query('items', 20);

            $images = $this->imageRepository->orderMyBy($column, $direction, $page, $items);
            $hasMorePages = $images->hasMorePages();
            $data = [
                'Images' => ImageResource::collection($images),
                'hasMorePages' => $hasMorePages,
            ];

            $response = ResponseHelper::jsonResponse($data, 'Images ordered successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function updateImage(Image $image, array $data)
    {
        try {
            $this->checkGuest('Image', 'update');
            $this->checkAdmin('Image', 'update');
            if (isset($data['main'])) {
                $data['main'] = filter_var($data['main'], FILTER_VALIDATE_BOOLEAN);
            }

            $this->validateImageData($data, 'sometimes');
            $this->checkOwnershipForProducts($image->product, 'Image', 'update');

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
            $this->checkGuest('Image', 'delete');
            $this->checkAdmin('Image', 'delete');
            $this->checkOwnershipForProducts($image->product, 'Image', 'delete');
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
            throw new ValidationException($validator);
        }
    }
}
