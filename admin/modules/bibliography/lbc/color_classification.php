<?php
/**
 * @author Drajat Hasan
 * @email [drajathasan20@gmail.com]
 * @create date 2020-08-08 16:24:59
 * @modify date 2020-08-08 16:24:59
 * @desc "Label barcode color picker"
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
    $data = ['title' => 'PostgreSQL : a compre', 'call_number' => '{number} Jan p', 'img' => 'SMP001.png'];
}

$style = [
    'content' => ['height' => 117],
    'col' => ['width' => 328],
    'content-hm' => ['width' => 160],
    'barcode-lr' => ['height' => 48, 'width' => 107, 'left' => ['margin' => '34px -29px 30px -20px'], 'right' => ['margin' => '34px -15px 30px -28px']]
];

if (file_exists(SB.'files/left_right_barcode_style.json'))
{
    $style = json_decode(file_get_contents(SB.'files/left_right_barcode_style.json'), TRUE);
}

if (!file_exists(SB.'files/color_classification.json'))
{
    $color = [];
    for ($i=0; $i < 10; $i++) { 
        $color['K'.$i] = 0;
    }
    @file_put_contents(SB.'files/color_classification.json', json_encode($color));
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
include __DIR__.'/left_right_barcode_style.php';

?>
<!-- Left and Right -->
<div id="noprint" style="float: left; height: 100vh; width: 250px">
    <form method="post" action="<?=MWB;?>bibliography/lbc/wizard_designer_lbc.php">
        <input type="hidden" name="type" value="color_classification"/>
        <section style="padding: 10px;">
            <h1 style="text-transform: uppercase; font-size: 14pt; font-weight: 700;">Atur Warna Perklasifikasi</h1>
            
            <!-- Polaa kode item -->
            <label class="block">Pola kode item</label>
            <select class="w-full b-none block bg-white" name="class">
                <option value="">Pilih</option>
                <?php
                    // klasifikasi
                    for ($i=0; $i < 10; $i++) { 
                        echo '<option value="'.$i.'">'.$i.'XX</option>';
                    }
                    // other klasifikasi
                    if (file_exists(SB.'files/other_classification.json'))
                    {
                        $other_class = json_decode(file_get_contents(SB.'files/other_classification.json'), TRUE);
                        var_dump($other_class);
                        foreach ($other_class as $key => $value) {
                            echo '<option value="'.str_replace('K', '', $key).'">'.str_replace('K', '', $key).' XX</option>';
                        }
                    }
                ?>
            </select>
            <label class="block">Klasifikasi Yang Lain</label>
            <input type="text" name="other_class" class="w-full"/>
            <label class="block">Warna</label>
            <input type="text" id="picker" class="picker w-full" value="#ff6161">
            <br/>
            <br/>
            <button style="padding: 10px; float: right;" class="printsample">Cetak Sampel</button>&nbsp;
            <button style="padding: 10px; float: right;">Simpan</button>
        </section>
    </form>
</div>
<div id="printarea" style="float: left;">
    <section id="left-right-barcode" style="display: block; ; width: 100%">
        <?php
        // Main class
        $colorClass = json_decode(file_get_contents(SB.'files/color_classification.json'), TRUE);
        $colorClass = array_chunk($colorClass, 3);
        $idx = 0;
        foreach ($colorClass as $ky => $value) {
            ?>
            <!-- <?=($ky+1)?> Row -->
            <div class="row">
                <!-- Sub row -->
                <div class="sub-row">
                    <?php
                    foreach ($value as $ke => $val) {
                        ?>
                        <!-- <?=($ke+1)?> Col -->
                        <div class="col">
                            <!-- Col barcode left -->
                            <div style="float: left;width: 70px;padding: 5px;">
                                <span class="left-title"><?=$data['title'];?></span>
                                <img class="left-barcode barcode" src="<?=SWB?>images/barcodes/<?=$data['img']?>"/>
                            </div>
                            <!-- Content -->
                            <div class="content">
                                <div class="content-header <?=$val?>" id="K<?=($ke+$idx)?>" style="background-color: <?php echo (preg_match('/^#[a-z0-9]\w+/i', $val))?$val:'#fff';?>">
                                    <?=($sysconf['print']['barcode']['barcode_header_text']?$sysconf['print']['barcode']['barcode_header_text']:$sysconf['library_name'])?>
                                </div>
                                <div class="content-main">
                                    <?php
                                        $cn = explode(' ', $data['call_number']);

                                        foreach ($cn as $callNumber) {
                                            echo '<br/>';
                                            if (preg_match('/({)|(})/i', $callNumber))
                                            {
                                                echo str_replace('{number}', ($ke+$idx).'XX', $callNumber);
                                            }
                                            else
                                            {
                                                echo $callNumber;
                                            }
                                        }
                                    ?>
                                </div>
                            </div>
                            <!-- Col barcode left -->
                            <div style="float: left;width: 70px;padding: 5px;">
                                <span class="right-title"><?=$data['title'];?></span>
                                <img class="right-barcode barcode" src="<?=SWB?>images/barcodes/<?=$data['img']?>"/>
                            </div>
                        </div>
                        <?php
                    }
                    $idx = $idx+3;
                    ?>
                </div>
            </div>
            <?php
        }

        ?>
    </section>
</div>
<script>
    // Javascript
    $('.picker').minicolors({
        // Fires when the value of the color picker changes
        change: function(e){
            // set class
            let k = $('select[name="class"]').val();
            if (k != "")
            {
                // set colour
                $('#K'+k).attr('style', 'background-color:'+e);
                // append input hidden
                if ($('input[name="color[K'+k+']"]').length == 0)
                {
                    let ih = '<input type="hidden" name="color[K'+k+']" value="'+e+'"/>';
                    $('form').append(ih);
                }
                else
                {
                    $('input[name="color[K'+k+']"]').val(e);
                }
            }
            else
            {
                let ok = $('input[name="other_class"]').val();
                // append input hidden
                if ($('input[name="other_color[K'+ok+']"]').length == 0)
                {
                    let ih = '<input type="hidden" name="other_color[K'+ok+']" value="'+e+'"/>';
                    $('form').append(ih);
                }
                else
                {
                    $('input[name="other_color[K'+ok+']"]').val(e);
                }
            }
        },
    });

    // Set empty
    $('input[name="other_class"]').click(function(){
        $('select[name="class"]').val("");
    });

    // set print preview
    $('.printsample').click(function(e){
        e.preventDefault();
        window.print();
    })
</script>