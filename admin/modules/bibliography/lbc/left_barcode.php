<?php
/**
 * @author Drajat Hasan
 * @email [drajathasan20@gmail.com]
 * @create date 2020-08-08 21:13:33
 * @modify date 2020-08-08 21:13:33
 * @desc Left Barcode
 */

// set index auth
if (!defined('INDEX_AUTH'))
{
    define('INDEX_AUTH', '1');

    require '../../../../sysconfig.inc.php';   
}

// start the session
require_once SB.'admin/default/session.inc.php';
require_once SB.'admin/default/session_check.inc.php';

// check indesign or not
if (isset($_SESSION['INDESIGN']))
{
    // Dummy data
    $data = ['title' => 'PostgreSQL : a compre', 'call_number' => '005.13/3-22 Jan p', 'img' => 'SMP001.png'];
}

$style = [
    'content' => ['height' => 151, 'font_size' => 14],
    'col' => ['width' => 330],
    'content-hm' => ['width' => 246],
    'barcode-lr' => ['height' => 48, 'width' => 107, 'left' => ['margin' => '34px -29px 30px -20px'], 'right' => ['margin' => '34px -15px 30px -28px']]
];

if (file_exists(SB.'files/left_barcode_style.json'))
{
    $style = json_decode(file_get_contents(SB.'files/left_barcode_style.json'), TRUE);
}

// print setting
if (!function_exists('loadPrintSettings'))
{
    // include printed settings configuration file
    require SB.'admin'.DS.'admin_template'.DS.'printed_settings.inc.php';
    
    // check for custom template settings
    $custom_settings = SB.'admin'.DS.$sysconf['admin_template']['dir'].DS.$sysconf['template']['theme'].DS.'printed_settings.inc.php';
    
    if (file_exists($custom_settings)) {
        include_once $custom_settings;
    }

    loadPrintSettings($dbs, 'barcode');
}

if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
    // load item pattern setting from database;
    $itemPattern_q = $dbs->query("SELECT setting_value FROM setting WHERE setting_name = 'batch_item_code_pattern'");

    $itemPattern = [];
    if ($itemPattern_q->num_rows == 1)
    {
        $itemPattern_d = $itemPattern_q->fetch_row();
        $itemPattern = unserialize($itemPattern_d[0]);
    }
}

// include style
include __DIR__.'/left_barcode_style.php';
?>
<!-- Left Panel -->
<div id="noprint" style="float: left; height: 100vh; width: 250px">
    <form method="post" action="<?=MWB;?>bibliography/lbc/wizard_designer_lbc.php">
        <input type="hidden" name="type" value="left_barcode"/>
        <section style="padding: 10px;">
            <h1 style="text-transform: uppercase; font-size: 14pt; font-weight: 700;">Atur Ukuran Barcode</h1>
            
            <!-- Polaa kode item -->
            <label class="block">Pola kode item</label>
            <select class="w-full b-none block bg-white" onchange="changeSrcBarcode()">
                <option value="0">Pilih</option>
                <?php
                    foreach ($itemPattern as $value) {
                        echo '<option value="'.$value.'">'.$value.'</option>';
                    }
                ?>
            </select>
            
            <!-- Tinggi kotak -->
            <label class="block">Besar Font</label>
            <input type="number" name="content[font]" title="Satuan dalam pt." class="w-full b-none block bg-white" onkeyup="changeFontSize()" placeholder="dalam PT" value="<?=$style['content']['font_size']?>"/>

            <!-- Tinggi kotak -->
            <label class="block">Tinggi kotak</label>
            <input type="number" name="content[height]" title="Satuan dalam px. (Rekomendasi >= 120 < 200)" class="w-full b-none block bg-white" onkeyup="changeBoxHeight()" placeholder="Secara default akan otomatis (dalam PX)" value="<?=$style['content']['height']?>"/>

            <!-- Lebar kotak -->
            <label class="block">Lebar kotak</label>
            <input type="number" name="col[width]" title="Satuan dalam px. (Rekomendasi 328)" class="w-full b-none block bg-white" onkeyup="changeBoxWidth()" placeholder="Secara default akan otomatis (dalam PX)" value="<?=$style['col']['width']?>"/>

            <!-- Lebar konten -->
            <label class="block">Lebar konten</label>
            <input type="number" name="content-hm[width]" title="Satuan dalam px. (Rekomendasi 160)" class="w-full b-none block bg-white" onkeyup="changeContentWidth()" placeholder="Secara default akan otomatis (dalam PX)" value="<?=$style['content-hm']['width']?>"/>
            
            <!-- Tinggi barcode -->
            <label class="block">Tinggi Barcode</label>
            <input type="number" name="barcode-lr[height]" title="Satuan dalam px. (Rekomendasi >= 55 <= 65)"  class="w-full b-none block bg-white" onkeyup="changeBarcodeHeight()" placeholder="Secara default akan otomatis (Satuan dalam PX)" value="<?=$style['barcode-lr']['height']?>"/>
            
            <!-- Tinggi barcode -->
            <label class="block">Lebar Barcode</label>
            <input type="number" name="barcode-lr[width]" title="Satuan dalam px. (Rekomendasi >= 55 <= 65)"  class="w-full b-none block 
            bg-white" onkeyup="changeBarcodeWidth()" placeholder="Secara default akan otomatis (Satuan dalam PX)" value="<?=$style['barcode-lr']['width']?>"/>

            <!-- Margin Barcode Kiri -->
            <label class="block">Margin Barcode Kiri</label>
            <input type="text" name="barcode-lr[left][margin]" class="w-full b-none block bg-white" value="<?=$style['barcode-lr']['left']['margin']?>" onkeyup="changeMargin('left')"/>
            <span class="w-full block" style="margin-top: 2px; font-weight: 200">Atas, Kanan, Bawah, Kiri</span>

            <button style="padding: 10px; float: right;">Simpan</button>
        </section>
    </form>
</div>
<!-- Preview print area -->
<div id="printarea" style="float: left;">
    <!-- Left -->
    <section id="left-barcode" style="display: block; width: 100%">
        <!-- 1st Row -->
        <div class="row">
            <!-- Sub row -->
            <div class="sub-row">
                <!-- 1st Col -->
                <div class="col">
                    <!-- Col barcode left -->
                    <div style="float: left;width: 70px;padding: 5px;">
                        <span class="left-title"><?=$data['title'];?></span>
                        <img src="<?=SWB?>images/barcodes/<?=$data['img']?>" class="left-barcode barcode"/>
                    </div>
                    <!-- Content -->
                    <div class="content">
                        <div class="content-header">
                            <?=($sysconf['print']['barcode']['barcode_header_text']?$sysconf['print']['barcode']['barcode_header_text']:$sysconf['library_name'])?>
                        </div>
                        <div class="content-main">
                            <?php
                                $cn = explode(' ', $data['call_number']);

                                foreach ($cn as $callNumber) {
                                    echo '<br/>';
                                    echo $callNumber;
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>