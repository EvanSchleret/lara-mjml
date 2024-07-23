<?php

return [
    /*
     * The path to the MJML binary
     *
     * null|bool|string
     */
    'binary_path' => env('MJML_NODE_PATH', null),

    /*
     * Beautify the output
     *
     * bool
     */
    'beautify' => env('LARA_MJML_BEAUTIFY', false),

    /*
     * Minify the output
     *
     * bool
     */
    'minify' => env('LARA_MJML_MINIFY', true),

    /*
     * Keep comments in the output
     *
     * bool
     */
    'keep_comments' => env('LARA_MJML_KEEP_COMMENTS', false),

    /*
     * Options to pass to the MJML binary
     *
     * array
     */
    'options' => [],
];
