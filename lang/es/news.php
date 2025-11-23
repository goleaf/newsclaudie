<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | News Page Language Lines (Spanish)
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan en la página de noticias
    | para mostrar elementos de noticias filtrados y ordenados. Estas
    | traducciones admiten la experiencia de navegación de noticias,
    | incluidos filtros, ordenación y paginación.
    |
    */

    'title' => 'Noticias',
    'subtitle' => 'Mantente informado con las últimas historias y actualizaciones.',

    // Results count and pagination
    'results_count' => '{0} 0 resultados encontrados|{1} :count resultado|[2,*] :count resultados',
    'showing_range' => 'Mostrando :from a :to de :total resultados',

    // Empty state
    'empty_title' => 'No se encontraron noticias',
    'empty_message' => 'Intenta ajustar tus filtros para ver más resultados.',

    // Filter panel
    'filters' => [
        'heading' => 'Filtros',
        'clear_all' => 'Limpiar todos los filtros',
        
        // Sort options
        'sort_label' => 'Ordenar por',
        'sort_newest' => 'Más recientes primero',
        'sort_oldest' => 'Más antiguos primero',
        
        // Category filter
        'categories_label' => 'Categorías',
        
        // Author filter
        'authors_label' => 'Autores',
        
        // Date range filter
        'date_range_label' => 'Rango de fechas',
        'from_date_label' => 'Desde fecha',
        'to_date_label' => 'Hasta fecha',
    ],

    // News card
    'by_author' => 'Por :author',
    'read_more' => 'Leer más',

];
