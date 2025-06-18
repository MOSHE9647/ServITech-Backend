<?php

declare(strict_types=1);

return [
    // Common messages reused with parameters
    'common' => [
        'not_found'         => ':item no encontrado.',
        'creation_failed'   => 'Error al crear :item.',
        'update_failed'     => 'Error al actualizar :item.',
        'deletion_failed'   => 'No se pudo eliminar :item.',
        'retrieved_all'     => 'Lista de :items obtenida exitosamente.',
        'retrieved'         => ':item obtenido exitosamente.',
        'created'           => ':item creado exitosamente.',
        'updated'           => ':item actualizado exitosamente.',
        'deleted'           => ':item eliminado exitosamente.',
    ],

    // Entity-specific messages
    'entities' => [
        'article'           => [
            'plural'    => 'artículos',
            'singular'  => 'artículo',
        ],
        'category'          => [
            'plural'    => 'categorías',
            'singular'  => 'categoría',
        ],
        'subcategory'       => [
            'plural'    => 'subcategorías',
            'singular'  => 'subcategoría',
        ],
        'repair_request'    => [
            'plural'    => 'solicitudes de reparación',
            'singular'  => 'solicitud de reparación',
        ],
        'support_request'   => [
            'plural'    => 'solicitudes de soporte',
            'singular'  => 'solicitud de soporte',
        ],
        'user'              => [
            'plural'    => 'usuarios',
            'singular'  => 'usuario',
        ],
    ],

    // Specific messages that do not follow the common pattern
    'specific' => [
        'general_not_found' => 'No se encontraron resultados para el modelo :attribute.',
    ],

    'password' => [
        'reset_success'     => 'Contraseña restablecida con éxito.',
        'updated'           => 'Contraseña actualizada con éxito.',
        'invalid'           => 'Contraseña inválida.',
    ],

    'user' => [
        'info_updated'      => 'Información del usuario actualizada con éxito.',
        'registered'        => 'Usuario registrado con éxito.',
        'logged_in'         => 'Usuario conectado con éxito.',
        'logged_out'        => 'Usuario desconectado con éxito.',
        'info_retrieved'    => 'Información del usuario obtenida con éxito.',
        'already_logged_out'=> 'El usuario ya está desconectado.',
        'logout_failed'     => 'Error al cerrar la sesión.',
    ],
];