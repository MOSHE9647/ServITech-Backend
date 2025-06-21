<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandleImageUploads
{
    /**
     * Store multiple images and return their paths, alt texts, and titles.
     * 
     * This method handles the storage of images, generating unique titles based on the related ID and prefix.
     * It also constructs the alt text from the title by removing the file extension.
     *
     * The title format will be: prefix_relatedId_uuid.extension
     * Example: image_12345_550e8400-e29b-41d4-a716-446655440000.jpg
     *
     * The alt text will be: prefix_relatedId
     * Example: image_12345
     *
     * @param array $images         Images to be stored, typically from a request input.
     *                              Each image should be an instance of UploadedFile.
     *                              Example: $request->file('images')
     * @param int|string $relatedId The ID of the related entity (e.g., article, repair request) to which the images belong.
     *                              This is used to create unique titles for the images.
     * @param string $prefix        Prefix for the image title, default is 'image'.
     *                              This helps in identifying the images related to a specific entity.
     *                              Example: 'article', 'repair_request', etc.
     * @param string $directory     Directory where the images will be stored.
     *                              Default is 'default', but can be customized based on the entity type.
     *                              Example: 'articles', 'repairs', etc.
     * @return array                An array of associative arrays containing the stored image paths, titles, and alt texts.
     *                              Each associative array will have the keys 'path', 'title', and 'alt'.
     */
    public function storeImages(array $images, $relatedId, string $prefix = 'image', string $directory = 'default'): array
    {
        $result = [];
        foreach ($images as $image) {
            $extension = $image->getClientOriginalExtension(); // Get the original file extension
            $title = "{$prefix}_{$relatedId}_" . Str::uuid() . ($extension ? ".{$extension}" : '');
            $path = Storage::put($directory, $image);
            $alt = pathinfo("{$prefix}_{$relatedId}", PATHINFO_FILENAME);
            $result[] = [
                'path' => $path,
                'title' => $title,
                'alt' => $alt,
            ];
        }
        return $result;
    }

    /**
     * Delete images from storage.
     *
     * This method deletes images from the storage based on the provided model instances.
     * Each model instance should have a 'path' property and a 'delete' method.
     * The method ensures that the image files are removed from the storage
     * and the corresponding database records are deleted.
     *
     * @param mixed $images Collection, array, or relation of model instances representing the images to be deleted.
     * @return void
     */
    public function deleteImages($images): void
    {
        // Convert collection or relation to array if needed
        if ($images instanceof \Illuminate\Database\Eloquent\Collection) {
            $images = $images->all();
        } elseif (method_exists($images, 'get')) {
            // Handle Eloquent relations
            $images = $images->get()->all();
        }

        foreach ($images as $image) {
            $path = $image->path; // Store the path of the image before deleting
            $image->delete(); // Delete the image record from the database
            
            // Check if the image path exists in storage and delete it
            if ($path && Storage::exists($path)) {
                Storage::delete($path);
            }
        }
    }
}