<?php
function w($p,$c){file_put_contents($p,$c);echo "Written: ".basename($p)." (".strlen($c)." bytes)\n";}
$b="C:/laragon/www/procurement/resources/views";
