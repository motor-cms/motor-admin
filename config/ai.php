
<?php
return [
    'provider' => env('AI_PROVIDER', "openai"),
    'api_version' => env('AI_API_VERSION'),
    'model' => env('AI_MODEL'),
    'api_key' => env('AI_API_KEY')
];
