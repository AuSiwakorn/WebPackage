<?php
function PERMIT_CHECK($data)
{
    $permitData = [];
    foreach ($data as $k) {
        $permitData = array_merge($permitData, $k);
    }
    return $permitData;
}
