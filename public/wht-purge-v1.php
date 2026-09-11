<?php
/**
 * Tombstone. The one-shot purge that lived at this path has run and been
 * removed from the repository, but opcache kept serving its compiled bytecode
 * after the file was unlinked. This replaces it with something inert so the
 * cached copy cannot execute again, and then removes itself.
 */
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo "Gone. This one-shot script has already run.\n";
@unlink(__FILE__);
