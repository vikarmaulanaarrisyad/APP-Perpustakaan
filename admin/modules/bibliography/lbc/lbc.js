/**
 * @author Drajat Hasan
 * @email [drajathasan20@gmail.com]
 * @create date 2020-08-08 21:05:19
 * @modify date 2020-08-08 21:05:19
 * @desc Label barcode Color JS.
 */

'use strict';

// get base url
let baseurl = $('#lbc').data('url');

// set stylesheet
let w1 = Number(window.innerWidth) * Number(75) / Number(100);
let w2 = Number(window.innerWidth) * Number(20) / Number(100);

$('#printarea').attr('style', 'float:left;width:'+w1+'px');
$('#noprint').attr('style', 'height: 100vh; float:left;width:'+w2+'px');

$('#temp').change(function(){
    let view = $(this).val();

    if (view == "")
    {
        self.location.href = './wizard_designer_lbc.php?reset=true';
        return false;
    }
    $('#view').simbioAJAX(baseurl+'admin/modules/bibliography/lbc/'+view+'.php');
});

// Function area
function changeSrcBarcode()
{
    // create barcode sample
    jQuery.ajax({ url: baseurl+'lib/phpbarcode/barcode.php?code='+event.target.value+'&encoding=code128&scale=2&mode=png&act=save', type: 'GET', error: function() { alert('Error creating barcode!'); } });

    let val = event.target.value;

    setTimeout(function(){
        // set sample
        $('.barcode').attr('src', baseurl+'images/barcodes/'+val+'.png');
    }, 2000);
}

/**
 * Change Box Height
 */
function changeBoxHeight()
{
    $('.content').attr('style', 'height: '+event.target.value+'px');
}

/**
 * Change box width
 */
function changeBoxWidth()
{
    $('.col').attr('style', 'width: '+event.target.value+'px');
}

/**
 * Change Content Width
 */
function changeContentWidth()
{
    $('.content-header, .content-main').attr('style', 'width: '+event.target.value+'px');
}

/**
 * Change Barcode Height
 */
function changeBarcodeHeight()
{
    $('.left-barcode, .right-barcode').attr('style', 'height: '+event.target.value+'px');
}

/**
 * Change Barcode Width
 */
function changeBarcodeWidth()
{
    $('.left-barcode, .right-barcode').attr('style', 'width: '+event.target.value+'px');
}

/**
 * Change Text Align
 */
function changeTextAlign()
{
    $('.content-header').attr('style', 'text-align : '+event.target.value);
    $('.content-main').attr('style', 'text-align : '+event.target.value);
}

/**
 * Change margin
 * @param string position 
 */
function changeMargin(position)
{
    $('.'+position+'-barcode').attr('style', 'margin: '+event.target.value);
}

/**
 * Change Font Size
 */
function changeFontSize()
{
    $('.content-main').attr('style', 'font-size:'+event.target.value+'pt');
}