<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Paste Content Limits
    |--------------------------------------------------------------------------
    |
    | Maximum number of characters allowed for a paste payload. This limit is
    | enforced for both web and API paste creation endpoints.
    |
    */

    'max_content_length' => (int) env('PASTE_MAX_CONTENT_LENGTH', 1000000),
];
