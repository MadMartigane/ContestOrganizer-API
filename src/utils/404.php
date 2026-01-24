<?php

header_remove('Set-Cookie');
header("HTTP/1.1 404 Not Found", true);
header("Content-Type: text/html;charset=UTF-8");

echo('<html>' .
    '<head><title>404 Not Found</title></head>' .
    '<body>' .
    '<center><h1>404 Not Found</h1></center>' .
    '<hr><center>ContestOrganizer</center></hr>' .
    '</body>' .
    '</html>'
);

exit;

?>

