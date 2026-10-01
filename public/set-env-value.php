<?php
// Retired one-shot setter. Kept as a tombstone rather than deleted, because
// opcache on this host keeps serving PHP files that have merely been unlinked.
http_response_code(410);
header('Content-Type: text/plain');
echo "Gone.\n";
