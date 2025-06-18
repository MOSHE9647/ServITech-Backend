<?php

declare(strict_types=1);

return [
    // Common messages reused with parameters
    'common' => [
        'not_found'         => ':item not found.',
        'creation_failed'   => 'Failed to create :item.',
        'update_failed'     => 'Failed to update :item.',
        'deletion_failed'   => 'Failed to delete :item.',
        'retrieved_all'     => 'List of :items retrieved successfully.',
        'retrieved'         => ':item retrieved successfully.',
        'created'           => ':item created successfully.',
        'updated'           => ':item updated successfully.',
        'deleted'           => ':item deleted successfully.',
    ],

    // Entity-specific messages
    'entities' => [
        'article'           => [
            'plural'    => 'articles',
            'singular'  => 'article',
        ],
        'category'          => [
            'plural'    => 'categories',
            'singular'  => 'category',
        ],
        'subcategory'       => [
            'plural'    => 'subcategories',
            'singular'  => 'subcategory',
        ],
        'repair_request'    => [
            'plural'    => 'repair requests',
            'singular'  => 'repair request',
        ],
        'support_request'   => [
            'plural'    => 'support requests',
            'singular'  => 'support request',
        ],
        'user'              => [
            'plural'    => 'users',
            'singular'  => 'user',
        ],
    ],

    // Specific messages that do not follow the common pattern
    'specific' => [
        'general_not_found' => 'No results found for model :attribute.',
    ],

    'password' => [
        'reset_success'     => 'Password reset successfully.',
        'updated'           => 'Password updated successfully.',
        'invalid'           => 'Invalid password.',
    ],

    'user' => [
        'info_updated'      => 'User information updated successfully.',
        'registered'        => 'User registered successfully.',
        'logged_in'         => 'User logged in successfully.',
        'logged_out'        => 'User logged out successfully.',
        'info_retrieved'    => 'User information retrieved successfully.',
        'already_logged_out'=> 'User is already logged out.',
        'logout_failed'     => 'Failed to log out.',
    ],
];