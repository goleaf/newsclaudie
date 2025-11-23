<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | News Page Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used on the news page for displaying
    | filtered and sorted news items. These translations support the news
    | browsing experience including filters, sorting, and pagination.
    |
    */

    'title' => 'News',
    'subtitle' => 'Stay informed with the latest stories and updates.',

    // Results count and pagination
    'results_count' => '{0} 0 results found|{1} :count result|[2,*] :count results',
    'showing_range' => 'Showing :from to :to of :total results',

    // Empty state
    'empty_title' => 'No news found',
    'empty_message' => 'Try adjusting your filters to see more results.',

    // Filter panel
    'filters' => [
        'heading' => 'Filters',
        'clear_all' => 'Clear all filters',
        
        // Sort options
        'sort_label' => 'Sort by',
        'sort_newest' => 'Newest first',
        'sort_oldest' => 'Oldest first',
        
        // Category filter
        'categories_label' => 'Categories',
        
        // Author filter
        'authors_label' => 'Authors',
        
        // Date range filter
        'date_range_label' => 'Date range',
        'from_date_label' => 'From date',
        'to_date_label' => 'To date',
    ],

    // News card
    'by_author' => 'By :author',
    'read_more' => 'Read more',

];
