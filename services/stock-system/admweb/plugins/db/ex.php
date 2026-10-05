<?php
/*
#### FROM_UNIXTIME #####
SELECT FROM_UNIXTIME( displaytime,  '%Y' ) as Y
FROM xxxx
WHERE FROM_UNIXTIME( a.displaytime,  '%Y' ) = '{$year}'
*/