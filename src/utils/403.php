<?php

header_remove('Set-Cookie');
header("HTTP/1.1 403 Forbidden", true);
header("Content-Type: text/html;charset=UTF-8");

echo('<html>' .
    '<head><title>403 Forbidden</title></head>' .
    '<body>' .
    '<center><h1>403 Forbidden</h1></center>' .
    '<hr><center>ContestOrganizer</center></hr>' .
    '</body>' .
    '</html>'
);

exit;

?>

