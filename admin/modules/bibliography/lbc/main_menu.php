<?php
/**
 * @author Drajat Hasan
 * @email drajathasan20@gmail.com
 * @create date 2020-08-09 10:49:34
 * @modify date 2020-08-09 10:49:34
 * @desc [description]
 */

 // set index auth
if (!defined('INDEX_AUTH'))
{
    // set index auth
    define('INDEX_AUTH', '1');

    require '../../../../sysconfig.inc.php';
}

// start the session
require_once SB.'admin/default/session.inc.php';
require_once SB.'admin/default/session_check.inc.php';

if (isset($_POST['save']))
{
    $_SESSION['type'] = 'main_menu';
    @file_put_contents(SB.'files/setting.json', json_encode($_POST['setting']));
    echo '<script>parent.parent.jQuery.colorbox.close();</script>';
}

$list = [
    'left_right_barcode' => 'Template Kanan Kiri Label Barcode',
    'right_barcode' => 'Template Kanan Label Barcode',
    'left_barcode' => 'Template Kiri Label Barcode',
    'color_classification' => 'Atur Warna PerKlasifikasi',
];

$setting = [];
if (file_exists(SB.'files/setting.json'))
{
    $setting = json_decode(file_get_contents(SB.'files/setting.json'), TRUE);
}


?>
<form method="post" target="blindIframe" action="<?=$_SERVER['PHP_SELF'];?>" style="width: 40%; display: block; margin-left: auto; margin-right: auto">
    <br/>
    <label style="display: block; width: 100%">Cetak Perbaris</label>
    <input type="number" name="setting[chunk]" style="padding: 5px; width: 100%" value="<?=isAvail('chunk', $setting)?>"/>
    <br/>
    <label style="display: block; width: 100%">Template Utama</label>
    <select style="padding: 10px; width: 100%" name="setting[temp]">
        <?php
        $temp = isAvail('temp', $setting);
        foreach ($list as $key => $value) {
            $select = '';
            if ($temp == $key)
            {
                $select = 'selected';
            }

            echo '<option value="'.$key.'" '.$select.'>'.$value.'</option>';
        }
    ?>
    </select>
    <br/>
    <label style="display: block; width: 100%">Orientasi</label>
    <select style="padding: 10px; width: 100%" name="setting[orientasi]">
        <?php 
        // Set orientasi
        $orientasi = isAvail('orientasi', $setting);
        $orientasi_list = ['ladscape', 'portrait'];
        // loop
        foreach ($orientasi_list as $list) {
            $select = '';
            if ($orientasi == $list)
            {
                $select = 'selected';
            }
            echo '<option value="'.$list.'" '.$select.'>'.ucfirst($list).'</option>';
        }
        ?>
    </select>
    <br/>
    <label style="display: block; width: 100%">Tampilkan Print Preview</label>
    <select style="padding: 10px; width: 100%" name="setting[autoprint]">
        <?php
        // set auto print
        $autoprint = isAvail('autoprint', $setting);
        $opt       = ['1' => 'Ya', '0' => 'Tidak'];

        // loop
        foreach ($opt as $key => $value) {
            $select = '';
            if ($key == $autoprint)
            {
                $select = 'selected';
            }

            echo '<option value="'.$key.'" '.$select.'>'.$value.'</option>';
        }
        ?>
    </select>
    <br/>
    <br/>
    <button type="submit" style="padding: 10px; float: right;" name="save">Simpan</button>
</form>
<iframe name="blindIframe" style="display: none;"></iframe>