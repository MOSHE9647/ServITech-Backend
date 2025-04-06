<?php

declare(strict_types=1);

return [
    'accepted'               => 'El campo :attribute debe ser aceptado.',
    'accepted_if'            => 'El campo :attribute debe ser aceptado cuando :other sea :value.',
    'active_url'             => 'El campo :attribute debe ser una URL válida.',
    'after'                  => 'El campo :attribute debe ser una fecha posterior a :date.',
    'after_or_equal'         => 'El campo :attribute debe ser una fecha posterior o igual a :date.',
    'alpha'                  => 'El campo :attribute sólo debe contener letras.',
    'alpha_dash'             => 'El campo :attribute sólo debe contener letras, números, guiones y guiones bajos.',
    'alpha_num'              => 'El campo :attribute sólo debe contener letras y números.',
    'array'                  => 'El campo :attribute debe ser un conjunto.',
    'ascii'                  => 'El campo :attribute solo debe contener caracteres alfanuméricos y símbolos de un solo byte.',
    'before'                 => 'El campo :attribute debe ser una fecha anterior a :date.',
    'before_or_equal'        => 'El campo :attribute debe ser una fecha anterior o igual a :date.',
    'between'                => [
        'array'   => 'El campo :attribute tiene que tener entre :min - :max elementos.',
        'file'    => 'El campo :attribute debe pesar entre :min - :max kilobytes.',
        'numeric' => 'El campo :attribute tiene que estar entre :min - :max.',
        'string'  => 'El campo :attribute tiene que tener entre :min - :max caracteres.',
    ],
    'boolean'                => 'El campo :attribute debe tener un valor verdadero o falso.',
    'can'                    => 'El campo :attribute contiene un valor no autorizado.',
    'confirmed'              => 'La confirmación de :attribute no coincide.',
    'contains'               => 'Al campo :attribute le falta un valor obligatorio.',
    'current_password'       => 'La contraseña es incorrecta.',
    'date'                   => 'El campo :attribute debe ser una fecha válida.',
    'date_equals'            => 'El campo :attribute debe ser una fecha igual a :date.',
    'date_format'            => 'El campo :attribute debe coincidir con el formato :format.',
    'decimal'                => 'El campo :attribute debe tener :decimal cifras decimales.',
    'declined'               => 'El campo :attribute debe ser rechazado.',
    'declined_if'            => 'El campo :attribute debe ser rechazado cuando :other sea :value.',
    'different'              => 'El campo :attribute y :other deben ser diferentes.',
    'digits'                 => 'El campo :attribute debe tener :digits dígitos.',
    'digits_between'         => 'El campo :attribute debe tener entre :min y :max dígitos.',
    'dimensions'             => 'El campo :attribute tiene dimensiones de imagen no válidas.',
    'distinct'               => 'El campo :attribute contiene un valor duplicado.',
    'doesnt_end_with'        => 'El campo :attribute no debe finalizar con uno de los siguientes: :values.',
    'doesnt_start_with'      => 'El campo :attribute no debe comenzar con uno de los siguientes: :values.',
    'email'                  => 'El campo :attribute no es un correo válido.',
    'ends_with'              => 'El campo :attribute debe finalizar con uno de los siguientes valores: :values',
    'enum'                   => 'El campo :attribute no está en la lista de valores permitidos.',
    'exists'                 => 'El campo :attribute no existe.',
    'extensions'             => 'El campo :attribute debe tener una de las siguientes extensiones: :values.',
    'file'                   => 'El campo :attribute debe ser un archivo.',
    'filled'                 => 'El campo :attribute es obligatorio.',
    'gt'                     => [
        'array'   => 'El campo :attribute debe tener más de :value elementos.',
        'file'    => 'El campo :attribute debe tener más de :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
        'string'  => 'El campo :attribute debe tener más de :value caracteres.',
    ],
    'gte'                    => [
        'array'   => 'El campo :attribute debe tener como mínimo :value elementos.',
        'file'    => 'El campo :attribute debe tener como mínimo :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser como mínimo :value.',
        'string'  => 'El campo :attribute debe tener como mínimo :value caracteres.',
    ],
    'hex_color'              => 'El campo :attribute debe tener un color hexadecimal válido.',
    'image'                  => 'El campo :attribute debe ser una imagen.',
    'in'                     => 'El campo :attribute no está en la lista de valores permitidos.',
    'in_array'               => 'El campo :attribute debe existir en :other.',
    'integer'                => 'El campo :attribute debe ser un número entero.',
    'ip'                     => 'El campo :attribute debe ser una dirección IP válida.',
    'ipv4'                   => 'El campo :attribute debe ser una dirección IPv4 válida.',
    'ipv6'                   => 'El campo :attribute debe ser una dirección IPv6 válida.',
    'json'                   => 'El campo :attribute debe ser una cadena JSON válida.',
    'list'                   => 'El campo :attribute debe ser una lista.',
    'lowercase'              => 'El campo :attribute debe estar en minúscula.',
    'lt'                     => [
        'array'   => 'El campo :attribute debe tener menos de :value elementos.',
        'file'    => 'El campo :attribute debe tener menos de :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser menor que :value.',
        'string'  => 'El campo :attribute debe tener menos de :value caracteres.',
    ],
    'lte'                    => [
        'array'   => 'El campo :attribute debe tener como máximo :value elementos.',
        'file'    => 'El campo :attribute debe tener como máximo :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser como máximo :value.',
        'string'  => 'El campo :attribute debe tener como máximo :value caracteres.',
    ],
    'mac_address'            => 'El campo :attribute debe ser una dirección MAC válida.',
    'max'                    => [
        'array'   => 'El campo :attribute no debe tener más de :max elementos.',
        'file'    => 'El campo :attribute no debe ser mayor que :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string'  => 'El campo :attribute no debe ser mayor que :max caracteres.',
    ],
    'max_digits'             => 'El campo :attribute no debe tener más de :max dígitos.',
    'mimes'                  => 'El campo :attribute debe ser un archivo con formato: :values.',
    'mimetypes'              => 'El campo :attribute debe ser un archivo con formato: :values.',
    'min'                    => [
        'array'   => 'El campo :attribute debe tener al menos :min elementos.',
        'file'    => 'El tamaño de :attribute debe ser de al menos :min kilobytes.',
        'numeric' => 'El tamaño de :attribute debe ser de al menos :min.',
        'string'  => 'El campo :attribute debe contener al menos :min caracteres.',
    ],
    'min_digits'             => 'El campo :attribute debe tener al menos :min dígitos.',
    'missing'                => 'El campo :attribute no debe estar presente.',
    'missing_if'             => 'El campo :attribute no debe estar presente cuando :other sea :value.',
    'missing_unless'         => 'El campo :attribute no debe estar presente a menos que :other sea :value.',
    'missing_with'           => 'El campo :attribute no debe estar presente si alguno de los campos :values está presente.',
    'missing_with_all'       => 'El campo :attribute no debe estar presente cuando los campos :values estén presentes.',
    'multiple_of'            => 'El campo :attribute debe ser múltiplo de :value',
    'not_in'                 => 'El campo :attribute no debe estar en la lista.',
    'not_regex'              => 'El formato del campo :attribute no es válido.',
    'numeric'                => 'El campo :attribute debe ser numérico.',
    'old_password'           => 'El campo :attribute no coincide con su contraseña actual.',
    'password'               => [
        'letters'       => 'La :attribute debe contener al menos una letra.',
        'mixed'         => 'La :attribute debe contener al menos una letra mayúscula y una minúscula.',
        'numbers'       => 'La :attribute debe contener al menos un número.',
        'symbols'       => 'La :attribute debe contener al menos un símbolo.',
        'uncompromised' => 'La :attribute proporcionada se ha visto comprometida en una filtración de datos (data leak). Elija una :attribute diferente.',
    ],
    'present'                => 'El campo :attribute debe estar presente.',
    'present_if'             => 'El campo :attribute debe estar presente cuando :other es :value.',
    'present_unless'         => 'El campo :attribute debe estar presente a menos que :other sea :value.',
    'present_with'           => 'El campo :attribute debe estar presente cuando :values esté presente.',
    'present_with_all'       => 'El campo :attribute debe estar presente cuando :values estén presentes.',
    'prohibited'             => 'El campo :attribute está prohibido.',
    'prohibited_if'          => 'El campo :attribute está prohibido cuando :other es :value.',
    'prohibited_if_accepted' => 'El campo :attribute está prohibido cuando se acepta :other.',
    'prohibited_if_declined' => 'El campo :attribute está prohibido cuando se rechaza :other.',
    'prohibited_unless'      => 'El campo :attribute está prohibido a menos que :other sea :values.',
    'prohibits'              => 'El campo :attribute prohibe que :other esté presente.',
    'regex'                  => 'El formato del campo :attribute no es válido.',
    'required'               => 'El campo :attribute es obligatorio.',
    'required_array_keys'    => 'El campo :attribute debe contener entradas para: :values.',
    'required_if'            => 'El campo :attribute es obligatorio cuando :other es :value.',
    'required_if_accepted'   => 'El campo :attribute es obligatorio si :other es aceptado.',
    'required_if_declined'   => 'El campo :attribute es obligatorio si :other es rechazado.',
    'required_unless'        => 'El campo :attribute es obligatorio a menos que :other esté en :values.',
    'required_with'          => 'El campo :attribute es obligatorio cuando :values está presente.',
    'required_with_all'      => 'El campo :attribute es obligatorio cuando :values están presentes.',
    'required_without'       => 'El campo :attribute es obligatorio cuando :values no está presente.',
    'required_without_all'   => 'El campo :attribute es obligatorio cuando ninguno de :values está presente.',
    'same'                   => 'Los campos :attribute y :other deben coincidir.',
    'size'                   => [
        'array'   => 'El campo :attribute debe contener :size elementos.',
        'file'    => 'El tamaño de :attribute debe ser :size kilobytes.',
        'numeric' => 'El tamaño de :attribute debe ser :size.',
        'string'  => 'El campo :attribute debe contener :size caracteres.',
    ],
    'starts_with'            => 'El campo :attribute debe comenzar con uno de los siguientes valores: :values',
    'string'                 => 'El campo :attribute debe ser una cadena de caracteres.',
    'timezone'               => 'El campo :attribute debe ser una zona horaria válida.',
    'ulid'                   => 'El campo :attribute debe ser un ULID válido.',
    'unique'                 => 'El campo :attribute ya ha sido registrado.',
    'uploaded'               => 'Subir :attribute ha fallado.',
    'uppercase'              => 'El campo :attribute debe estar en mayúscula.',
    'url'                    => 'El campo :attribute debe ser una URL válida.',
    'uuid'                   => 'El campo :attribute debe ser un UUID válido.',
    'attributes'             => [
        'accesories'               => 'accesorios',
        'address'                  => 'dirección',
        'affiliate_url'            => 'URL de afiliado',
        'age'                      => 'edad',
        'amount'                   => 'cantidad',
        'announcement'             => 'anuncio',
        'area'                     => 'área',
        'article_name'             => 'nombre del artículo',
        'article_type'             => 'tipo de artículo',
        'article_brand'            => 'marca del artículo',
        'article_model'            => 'modelo del artículo',
        'article_serialnumber'     => 'número de serie del artículo',
        'article_accesories'       => 'accesorios del artículo',
        'article_problem'          => 'problema del artículo',
        'audience_prize'           => 'premio del público',
        'audience_winner'          => 'ganador del público',
        'available'                => 'disponible',
        'birthday'                 => 'cumpleaños',
        'body'                     => 'contenido',
        'category'                 => 'categoría',
        'city'                     => 'ciudad',
        'company'                  => 'compañía',
        'compilation'              => 'compilación',
        'concept'                  => 'concepto',
        'conditions'               => 'condiciones',
        'content'                  => 'contenido',
        'contest'                  => 'concurso',
        'country'                  => 'país',
        'cover'                    => 'portada',
        'created_at'               => 'creado el',
        'creator'                  => 'creador',
        'currency'                 => 'moneda',
        'current_password'         => 'contraseña actual',
        'customer'                 => 'cliente',
        'customer_name'            => 'nombre del cliente',
        'date'                     => 'fecha',
        'date_of_birth'            => 'fecha de nacimiento',
        'dates'                    => 'fechas',
        'day'                      => 'día',
        'deleted_at'               => 'eliminado el',
        'description'              => 'descripción',
        'display_type'             => 'tipo de visualización',
        'district'                 => 'distrito',
        'duration'                 => 'duración',
        'email'                    => 'correo electrónico',
        'excerpt'                  => 'extracto',
        'filter'                   => 'filtro',
        'finished_at'              => 'terminado el',
        'first_name'               => 'nombre',
        'gender'                   => 'género',
        'grand_prize'              => 'gran Premio',
        'group'                    => 'grupo',
        'hour'                     => 'hora',
        'image'                    => 'imagen',
        'image_desktop'            => 'imagen de escritorio',
        'image_main'               => 'imagen principal',
        'image_mobile'             => 'imagen móvil',
        'images'                   => 'imágenes',
        'is_audience_winner'       => 'es ganador de audiencia',
        'is_hidden'                => 'está oculto',
        'is_subscribed'            => 'está suscrito',
        'is_visible'               => 'es visible',
        'is_winner'                => 'es ganador',
        'items'                    => 'elementos',
        'key'                      => 'clave',
        'last_name'                => 'apellidos',
        'lesson'                   => 'lección',
        'line_address_1'           => 'línea de dirección 1',
        'line_address_2'           => 'línea de dirección 2',
        'login'                    => 'acceso',
        'message'                  => 'mensaje',
        'middle_name'              => 'segundo nombre',
        'minute'                   => 'minuto',
        'mobile'                   => 'móvil',
        'month'                    => 'mes',
        'name'                     => 'nombre',
        'national_code'            => 'código nacional',
        'number'                   => 'número',
        'old_password'             => 'antigua contraseña',
        'password'                 => 'contraseña',
        'password_confirmation'    => 'confirmación de la contraseña',
        'phone'                    => 'teléfono',
        'photo'                    => 'foto',
        'portfolio'                => 'portafolio',
        'postal_code'              => 'código postal',
        'preview'                  => 'vista preliminar',
        'price'                    => 'precio',
        'product_id'               => 'ID del producto',
        'product_uid'              => 'UID del producto',
        'product_uuid'             => 'UUID del producto',
        'promo_code'               => 'código promocional',
        'province'                 => 'provincia',
        'quantity'                 => 'cantidad',
        'reason'                   => 'razón',
        'recaptcha_response_field' => 'respuesta del recaptcha',
        'received_at'              => 'fecha de recepción',
        'referee'                  => 'árbitro',
        'referees'                 => 'árbitros',
        'reject_reason'            => 'motivo de rechazo',
        'remember'                 => 'recordar',
        'repair_status'            => 'estado de reparación',
        'repair_details'           => 'detalles de la reparación',
        'repair_price'             => 'precio de la reparación',
        'repaired_at'              => 'fecha de reparación',
        'restored_at'              => 'restaurado el',
        'result_text_under_image'  => 'texto bajo la imagen',
        'role'                     => 'rol',
        'rule'                     => 'regla',
        'rules'                    => 'reglas',
        'second'                   => 'segundo',
        'serialnumber'             => 'número de serie',
        'sex'                      => 'sexo',
        'shipment'                 => 'envío',
        'short_text'               => 'texto corto',
        'size'                     => 'tamaño',
        'skills'                   => 'habilidades',
        'slug'                     => 'slug',
        'specialization'           => 'especialización',
        'started_at'               => 'comenzado el',
        'state'                    => 'estado',
        'status'                   => 'estado',
        'street'                   => 'calle',
        'student'                  => 'estudiante',
        'subcategory'              => 'subcategoría',
        'subject'                  => 'asunto',
        'tag'                      => 'etiqueta',
        'tags'                     => 'etiquetas',
        'teacher'                  => 'profesor',
        'terms'                    => 'términos',
        'test_description'         => 'descripción de prueba',
        'test_locale'              => 'idioma de prueba',
        'test_name'                => 'nombre de prueba',
        'text'                     => 'texto',
        'time'                     => 'hora',
        'title'                    => 'título',
        'type'                     => 'tipo',
        'updated_at'               => 'actualizado el',
        'user'                     => 'usuario',
        'username'                 => 'usuario',
        'value'                    => 'valor',
        'winner'                   => 'ganador',
        'work'                     => 'trabajo',
        'year'                     => 'año',
    ],
];
