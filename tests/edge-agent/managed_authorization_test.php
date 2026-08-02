<?php

declare(strict_types=1);

use SesameWare\RbtAgent\ManagedAuthorization;

require_once __DIR__ . '/../../server/edge-agent-controller/lib/Protocol.php';
require_once __DIR__ . '/../../server/edge-agent-controller/lib/ManagedAuthorization.php';

$proof = ManagedAuthorization::proof(
    'managed-secret-test',
    'agt_test',
    'rbt-controller',
    7,
    'challenge-123',
    ['overlay.configure', 'mapping.configure', 'mapping.configure'],
);
if ($proof !== '5EyqcPxeK2+iy//q2yb+RVnqoYsSqMVyDu7JUt4NTmU') {
    throw new RuntimeException("unexpected managed authorization proof: $proof");
}
fwrite(STDOUT, "managed_authorization_test: ok\n");
