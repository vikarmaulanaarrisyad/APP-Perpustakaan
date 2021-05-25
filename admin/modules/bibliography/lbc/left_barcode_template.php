<?php
/**
 * @author Drajat Hasan
 * @email drajathasan20@gmail.com
 * @create date 2020-08-10 19:46:59
 * @modify date 2020-08-10 19:46:59
 * @desc [description]
 */

$style = [
    'content' => ['height' => 151],
    'col' => ['width' => 330],
    'content-hm' => ['width' => 246],
    'barcode-lr' => ['height' => 48, 'width' => 107, 'left' => ['margin' => '34px -29px 30px -20px'], 'right' => ['margin' => '34px -15px 30px -28px']]
];

if (file_exists(SB.'files/left_barcode_style.json'))
{
    $style = json_decode(file_get_contents(SB.'files/left_barcode_style.json'), TRUE);
}

ob_start();
// include css style
include __DIR__.'/left_barcode_style.php';
?>
<style>
    @page {
        size: <?=$lbc['orientasi']?> !important;
        margin: 10px 0 0 0 !important;
    }

    @media print {
        #noprint {
            display: none !important;
        }
    }
    </style>
<div id="noprint">
    <button style="padding: 5px;" onclick="self.window.print()">
        <!-- Bootstrap icon -->
        <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-printer" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M11 2H5a1 1 0 0 0-1 1v2H3V3a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2h-1V3a1 1 0 0 0-1-1zm3 4H2a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h1v1H2a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-1h1a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1z"/>
        <path fill-rule="evenodd" d="M11 9H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1zM5 8a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H5z"/>
        <path d="M3 7.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/>
        </svg>
        Cetak
    </button>
</div>
<!-- Preview print area -->
<div id="printarea" style="float: left;">
    <!-- Left -->
    <section id="left-barcode" style="display: block; width: 100%">
    <?php
        // set color
        $colorClass = [];
        if (file_exists(SB.'files/color_classification.json'))
        {
            $colorClass = json_decode(file_get_contents(SB.'files/color_classification.json'), TRUE);
        }
        // loop
        foreach ($chunked_barcode_arrays as $ke => $barcode_arrays) 
        {
            ?>
            <!-- <?=$ke?> Row -->
            <div class="row">
                <!-- Sub row -->
                <div class="sub-row">
                <?php
                    foreach ($barcode_arrays as $ky => $barcode) {
                        $class = substr($barcode[2],0,1);
                        ?>                
                        <!-- 1st Col -->
                        <div class="col">
                            <!-- Col barcode left -->
                            <div style="float: left;width: 70px;padding: 5px;">
                                <span class="left-title"><?=substr($barcode[0], 0,20)?></span>
                                <img src="<?=SWB?>images/barcodes/<?=$barcode[1]?>.png" class="left-barcode barcode"/>
                            </div>
                            <!-- Content -->
                            <div class="content">
                                <div class="content-header" style="<?=(isset($colorClass['K'.$class]))?'background-color:'.$colorClass['K'.$class]:'background-color: #fff'?>">
                                    <?=($sysconf['print']['barcode']['barcode_header_text']?$sysconf['print']['barcode']['barcode_header_text']:$sysconf['library_name'])?>
                                </div>
                                <div class="content-main">
                                    <?php
                                        $cn = preg_split("/(?<=\w)\s+(?=[A-Za-z])/m", $barcode[2]); // Thanks Heru Subekti
                                        // Slice Callnumber
                                        foreach ($cn as $callNumber) {
                                            echo '<br/>';
                                            echo $callNumber;
                                        }
                                        echo '<br/>C.'.getCopyNumber($barcode[3], $barcode[1]);
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        <?php
        }
        ?>
    </section>
</div>
<script type="text/javascript">
    <?php
    if ($lbc['autoprint'])
    {
        echo 'self.window.print();';
    }
    ?>
</script>
<?php
$html_str = ob_get_clean();