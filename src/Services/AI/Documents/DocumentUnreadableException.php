<?php

namespace Motor\Admin\Services\AI\Documents;

/**
 * The document reached Mistral but no text came back (image-only file, empty or broken document).
 */
class DocumentUnreadableException extends \RuntimeException {}
