<?php
// Retired one-shot diagnostic. Kept as a tombstone because opcache on this host
// keeps serving PHP files that have merely been unlinked.
http_response_code(410);
header('Content-Type: text/plain');
echo "Gone.\n";
