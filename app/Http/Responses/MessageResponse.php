<?php

namespace App\Http\Responses;

class MessageResponse
{
    public const TYPE_ERROR = 'error';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_WARNING = 'warning';
    public const TYPE_INFO = 'info';

    /**
     * Create a new message response to be used in the frontend.
     * @param string $title
     * @param string $type
     * @return array{title: string, type: string}
     */
    public static function create(string $title, string $type): array
    {
        return [
            'title' => $title,
            'type' => $type,
        ];
    }
}