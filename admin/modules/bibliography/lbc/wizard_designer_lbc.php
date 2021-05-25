<?php

/**
 * @author Drajat Hasan
 * @email [drajathasan20@gmail.com]
 * @create date 2020-08-08 20:22:35
 * @modify date 2020-08-08 20:22:35
 * @desc [description]
 */

// set index auth
define('INDEX_AUTH', '1');

require '../../../../sysconfig.inc.php';

// start the session
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';
// load function
include SB.'admin'.DS.'modules'.DS.'bibliography'.DS.'lbc'.DS.'func.php';

$_SESSION['INDESIGN'] = true;

if (isset($_POST) && count($_POST) > 0)
{
    $_SESSION['type'] = $_POST['type'];
    unset($_POST['type']);
    $style = json_encode($_POST);
    // write file configuration
    if ($_SESSION['type'] != 'color_classification')
    {
        @file_put_contents(SB.'files/'.$_SESSION['type'].'_style.json', $style);
    }
    else
    {
        $color = json_decode(file_get_contents(SB.'files/color_classification.json'), TRUE);

        if (!empty($_POST['color']))
        {
            $color['K'.$_POST['class']] = $_POST['color'];
            foreach ($_POST['color'] as $key => $value) {
                $color[$key] = $value;
            }
        }

        @file_put_contents(SB.'files/color_classification.json', json_encode($color));

        if (isset($_POST['other_class']) && !empty($_POST['other_class']))
        {
            $other_class = [];
            if (file_exists(SB.'files/other_classification.json'))
            {
                $other_class = json_decode(file_get_contents(SB.'files/other_classification.json'), TRUE);

                $other_class['K'.$_POST['class']] = $_POST['color'];
            }
            else
            {
                @file_put_contents(SB.'files/other_classification.json', json_encode(['K'.$_POST['other_class'] => $_POST['other_color']['K'.$_POST['other_class']]]));
            }
        }
    }
}

// include printed settings configuration file
require SB.'admin'.DS.'admin_template'.DS.'printed_settings.inc.php';
// check for custom template settings
$custom_settings = SB.'admin'.DS.$sysconf['admin_template']['dir'].DS.$sysconf['template']['theme'].DS.'printed_settings.inc.php';
if (file_exists($custom_settings)) {
    include $custom_settings;
}

// print setting
loadPrintSettings($dbs, 'barcode');

// load item pattern setting from database;
$itemPattern_q = $dbs->query("SELECT setting_value FROM setting WHERE setting_name = 'batch_item_code_pattern'");

$itemPattern = [];
if ($itemPattern_q->num_rows == 1)
{
    $itemPattern_d = $itemPattern_q->fetch_row();
    $itemPattern = unserialize($itemPattern_d[0]);
}

if (isset($_GET['reset']))
{
    unset($_SESSION['type']);
}

$type = 'main_menu.php';
if (isset($_SESSION['type']))
{
    $type = $_SESSION['type'].'.php';
}

$list = [
            'left_right_barcode' => 'Template Kanan Kiri Label Barcode',
            'right_barcode' => 'Template Kanan Label Barcode',
            'left_barcode' => 'Template Kiri Label Barcode',
            'color_classification' => 'Atur Warna PerKlasifikasi',
        ];
// load label warna print setting;
$lbc = loadSettingJSON('setting.json', SB.'files');

?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <title>Label Barcode Warna</title>
        <script type="text/javascript" src="<?=JWB?>jquery.js"></script>
        <script type="text/javascript" src="<?=JWB?>updater.js"></script>
        <script type="text/javascript" src="./minicolors/jquery.minicolors.js"></script>
        <link rel="stylesheet" href="./minicolors/jquery.minicolors.css">
        <style>
            * {
                font-family: Arial, Helvetica, sans-serif !important;
                margin: 0;
            }
            
            #noprint, .noprint {
                background-color: #dcdcdc;
                color: #585858;
            }

            .block {
                display: block;
                padding: 5px;
                font-weight: bold;
            }

            .w-full {
                width: 100%;
            }

            .b-none {
                border: none;
            }

            .bg-white {
                background-color: #fff;
            }

            .d-none {
                display: none;
            }

            #noprint {
                padding: 10px;
            }

            @page {
                size: <?=$lbc['orientasi']?> !important;
                margin: 10px 0 0 0 !important;
            }
            @media print {
                /* html, body {
                    width: 210mm;
                    height: 297mm;
                } */
                #noprint, .noprint {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="w-full noprint" style="background: #b5b5b5">
            <h1 style="padding: 10px; display: inline-block">Label Barcode Color Wizard :</h1>
            <select id="temp" style="padding: 10px">
                <option value="">Menu utama</option>
                <?php
                foreach ($list as $key => $value) {
                    $select = '';
                    if (str_replace('.php', '', $type) == $key)
                    {
                        $select = 'selected';
                    }
                    echo '<option value="'.$key.'" '.$select.'>'.$value.'</option>';
                }
                ?>
            </select>
        </div>
        <div class="loader d-none" ></div>
        <div id="view">
            <?php
            if (file_exists(__DIR__.'/'.$type))
            {
                include __DIR__.'/'.$type;
            }
            else
            {
                echo 'Tidak ada "View" dari template yang anda pilih';
            }
            ?>
        </div>
        <script id="lbc" src="./lbc.js?ver=<?=date('YmdHis');?>" data-url="<?=SWB?>"></script>
         <?php
            if (!file_exists(SB.'images/barcodes/SMP001.png'))
            {
                // Sample data
                echo '<script>jQuery.ajax({ url: \''.SWB.'lib/phpbarcode/barcode.php?code=SMP001&encoding='.$sysconf['barcode_encoding'].'&scale=2&mode=png&act=save\', type: \'GET\', error: function() { alert(\'Error creating barcode!\'); } });
                self.location.reload();</script>';
            }
        ?>
    </body>
</html>
