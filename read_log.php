<?php
$lines = file('OUT.txt');
$last = array_slice($lines, -20);
foreach ($last as $l) echo $l;
