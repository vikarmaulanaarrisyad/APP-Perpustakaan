<?php
/**
 * @author Drajat Hasan
 * @email drajathasan20@gmail.com
 * @create date 2020-08-10 18:57:37
 * @modify date 2020-08-10 18:57:37
 */

function loadSettingJSON($_str_file_name, $_dir = __DIR__)
{
    $lbc = [];
    if (file_exists($_dir.'/'.$_str_file_name))
    {
        $lbc = json_decode(file_get_contents($_dir.'/'.$_str_file_name), TRUE);
    }
    return $lbc;
}

function isAvail($key, $array)
{
    if (isset($array[$key]))
    {
        return $array[$key];
    }
    return NULL;
}

function getCopyNumber($bid, $item_code)
{
    global $dbs;
    $copy_q = $dbs->query('SELECT item_code FROM item WHERE 
    biblio_id="'.$dbs->escape_string($bid).'" ORDER BY input_date ASC');

    $num = 0;
    while($copy_d = $copy_q->fetch_row())
    {
        if ($copy_d[0] == $item_code)
        {
            $num++;
            return $num;
            break;
        }
        $num++;
    }
}
?>