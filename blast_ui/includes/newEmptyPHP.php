<?php

$query = "/tmp/AB000263_0.fna";
$blastdb = "HSV6";
$cmd = "blastn -task blastn -query $query -db /home/amir/$blastdb -out /tmp/php.blastn.html -html";
system($cmd,$output);
print_r($cmd);

?>
