<?php

header_remove('Set-Cookie');
header("HTTP/1.1 401 Unauthorized", true);
header("Content-Type: text/html;charset=UTF-8");

echo('<html>' .
    '<head><title>401 Unauthorized</title></head>' .
    '<body>' .
    '<center><h1>401 Unauthorized</h1></center>' .
    '<hr><center>ContestOrganizer</center></hr>' .
    '</body>' .
    '</html>'
);

exit;

?>

