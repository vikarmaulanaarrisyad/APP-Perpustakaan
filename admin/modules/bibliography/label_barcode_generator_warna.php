<?php
/**
 * Copyright (C) 2013  Arie Nugraha (dicarve@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */
 
  /* This source modified by Sholihul Hadi (http://facebook.com/hadhie) on Sunday, 24 November 2013 */
  /* Panduan modif dan diunggah ulang di https://ruangperpustakaan.com 30 Januari 2021 */

/* Item barcode print */

// key to authenticate
define('INDEX_AUTH', '1');

// main system configuration
require '../../../sysconfig.inc.php';
// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-bibliography');
// start the session
require SB.'admin/default/session.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO.'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';

// privileges checking
$can_read = utility::havePrivilege('bibliography', 'r');

if (!$can_read) {
  die('<div class="errorBox">'.__('You are not authorized to view this section').'</div>');
}

$max_print = 50;

/* RECORD OPERATION */
if (isset($_POST['itemID']) AND !empty($_POST['itemID']) AND isset($_POST['itemAction'])) {
  if (!$can_read) {
    die();
  }
  if (!is_array($_POST['itemID'])) {
    // make an array
    $_POST['itemID'] = array((integer)$_POST['itemID']);
  }
  // loop array
  if (isset($_SESSION['barcodes'])) {
    $print_count = count($_SESSION['barcodes']);
  } else {
    $print_count = 0;
  }
  // barcode size
  $size = 2;
  // create AJAX request
  echo '<script type="text/javascript" src="'.JWB.'jquery.js"></script>';
  echo '<script type="text/javascript">';
  // loop array
  foreach ($_POST['itemID'] as $itemID) {
    if ($print_count == $max_print) {
      $limit_reach = true;
      break;
    }
    if (isset($_SESSION['barcodes'][$itemID])) {
      continue;
    }
    if (!empty($itemID)) {
      $barcode_text = trim($itemID);
      /* replace space */
      $barcode_text = str_replace(array(' ', '/', '\/'), '_', $barcode_text);
      /* replace invalid characters */
      $barcode_text = str_replace(array(':', ',', '*', '@'), '', $barcode_text);
      // send ajax request
      echo 'jQuery.ajax({ url: \''.SWB.'lib/phpbarcode/barcode.php?code='.$itemID.'&encoding='.$sysconf['barcode_encoding'].'&scale='.$size.'&mode=png\', type: \'GET\', error: function() { alert(\'Error creating barcode!\'); } });'."\n";
      // add to sessions
      $_SESSION['barcodes'][$itemID] = $itemID;
      $print_count++;
    }
  }
  echo 'top.$(\'#queueCount\').html(\''.$print_count.'\')';
  echo '</script>';
  // update print queue count object
  sleep(2);
  if (isset($limit_reach)) {
    $msg = str_replace('{max_print}', $max_print, __('Selected items NOT ADDED to print queue. Only {max_print} can be printed at once'));
    utility::jsAlert($msg);
  } else {
    utility::jsAlert(__('Selected items added to print queue'));
  }
  exit();
}

// clean print queue
if (isset($_GET['action']) AND $_GET['action'] == 'clear') {
  // update print queue count object
  echo '<script type="text/javascript">top.$(\'#queueCount\').html(\'0\');</script>';
  utility::jsAlert(__('Print queue cleared!'));
  unset($_SESSION['barcodes']);
  exit();
}

// barcode pdf download
if (isset($_GET['action']) AND $_GET['action'] == 'print') {
  // check if label session array is available
  if (!isset($_SESSION['barcodes'])) {
    utility::jsAlert(__('There is no data to print!'));
    die();
  }
  if (count($_SESSION['barcodes']) < 1) {
    utility::jsAlert(__('There is no data to print!'));
    die();
  }

  // concat all ID together
  $item_ids = '';
  foreach ($_SESSION['barcodes'] as $id) {
    $item_ids .= '\''.$id.'\',';
  }
  // strip the last comma
  $item_ids = substr_replace($item_ids, '', -1);
  // send query to database
  $item_q = $dbs->query('SELECT b.title, i.item_code, i.call_number FROM item AS i
    LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE i.item_code IN('.$item_ids.')');
  $item_data_array = array();
  while ($item_d = $item_q->fetch_row()) {
    if ($item_d[0]) {
      $item_data_array[] = $item_d;
    }
  }

  // include printed settings configuration file
  require SB.'admin'.DS.'admin_template'.DS.'printed_settings.inc.php';
  // check for custom template settings
  $custom_settings = SB.'admin'.DS.$sysconf['admin_template']['dir'].DS.$sysconf['template']['theme'].DS.'printed_settings.inc.php';
  if (file_exists($custom_settings)) {
    include $custom_settings;
  }


  // chunk barcode array
  $chunked_barcode_arrays = array_chunk($item_data_array, $sysconf['print']['label']['items_per_row']);
  // create html ouput
  $html_str = '<!DOCTYPE html>'."\n";
  $html_str .= '<html><head><title>Label Include Barcode Print Result</title>'."\n";
  $html_str .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
  $html_str .= '<meta http-equiv="Pragma" content="no-cache" /><meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, post-check=0, pre-check=0" /><meta http-equiv="Expires" content="Sat, 26 Jul 1997 05:00:00 GMT" />';
  $html_str .= '<style type="text/css">'."\n";
  $html_str .= 'body { padding: 0; margin: 1cm; font-family: '.$sysconf['print']['label']['fonts'].'; font-size: '.$sysconf['print']['barcode']['barcode_font_size'].'pt; background: #fff; }'."\n";
  $html_str .= '.labelStyle { width: '.$sysconf['print']['label']['box_width'].'cm; height: '.$sysconf['print']['label']['box_height'].'cm; text-align: center; margin: '.$sysconf['print']['label']['items_margin'].'cm; border: '.$sysconf['print']['label']['border_size'].'px solid #000000;}'."\n";
  $html_str .= '.rightSide { float: left; width: 64%; height: '.$sysconf['print']['label']['box_height'].'cm; border-right: '.$sysconf['print']['barcode']['barcode_border_size'].'px solid #000000; font-size: '.$sysconf['print']['label']['font_size'].'pt; text-align: center; }'."\n";
  $html_str .= '.leftSide {-moz-transform: rotate(90deg); -ms-transform:rotate(90deg); -o-transform:rotate(90deg); -webkit-transform:rotate(90deg); display: table-cell; float: right; width: 33%; text-align: center; margin-top: 15px; margin-left: 5px; }'."\n";
  $html_str .= '.barcode { display: table-cell; text-align: center; vertical-align: middle; }'."\n";
  $html_str .= '.title{position: absolute;z-index:5;left: 1px;top: 0px;width: 150px; height:1px;}'."\n";
  $html_str .= '.callnumb { font-size: 14pt; font-weight: bold; margin-top: 15px;;} '."\n";
  $html_str .= '.labelHeaderStyle { border-bottom: '.$sysconf['print']['barcode']['barcode_border_size'].'px; font-weight: bold; padding: 5px; margin-bottom: 5px; border-bottom: 1px; text-align: center; }'."\n";
  $html_str .= 'img {padding-left:1px; -moz-transform: rotate(360deg); -ms-transform:rotate(360deg); -o-transform:rotate(360deg); -webkit-transform:rotate(360deg); width: 100%;}'."\n";
  $html_str .= '</style>'."\n";
  $html_str .= '</head>'."\n";
  $html_str .= '<body>'."\n";
  $html_str .= '<a href="#" onclick="window.print()">Print Again</a>'."\n";
  $html_str .= '<table style="margin: 0; padding: 0;" cellspacing="0" cellpadding="0">'."\n";
  // loop the chunked arrays to row
  foreach ($chunked_barcode_arrays as $barcode_rows) {
    $html_str .= '<tr>'."\n";
    foreach ($barcode_rows as $barcode) {
      $html_str .= '<td valign="top">';
      $html_str .= '<div class="labelStyle">';
	  // document title
	  $html_str .= '<div class="leftSide">';
		if ($sysconf['print']['barcode']['barcode_cut_title'] ) {
        $html_str .= substr($barcode[0], 0, $sysconf['print']['barcode']['barcode_cut_title']).'...';
		} else { $html_str .= $barcode[0]; }
      $html_str .= '<div class="barcode">';
      $html_str .= '<img src="'.SWB.IMG.'/barcodes/'.str_replace(array(' '), '_', $barcode[1]).'.png" style="width: '.$sysconf['print']['barcode']['barcode_scale'].'%;" border="0" />';
      $html_str .= '</div>';
      $html_str .= '</div>';
	  $html_str .= '<div class="rightSide">';
	  			     if ($sysconf['print']['barcode']['barcode_include_header_text']) {  }
				  	$color_label = substr($barcode[2],0,1);
					if 	   ($color_label==0){$warna = '#0080C0';}
					elseif ($color_label==1){$warna = '#FFFF00';}
					elseif ($color_label==2){$warna = '#0033CC';}
					elseif ($color_label==3){$warna = '#80FFFF';}
					elseif ($color_label==4){$warna = '#0080FF';}
					elseif ($color_label==5){$warna = '#FF80C0';}
					elseif ($color_label==6){$warna = '#33FF00';}
					elseif ($color_label==7){$warna = '#FF0000';}
					elseif ($color_label==8){$warna = '#C0C0C0';}
					elseif ($color_label==9){$warna = '#8000FF';}
					else					{$warna = '#FFFFFF';}
				$html_str .= '<div style="background-color:'.$warna.';" class="labelHeaderStyle">'.($sysconf['print']['barcode']['barcode_header_text']?$sysconf['print']['barcode']['barcode_header_text']:$sysconf['library_name']).'</div>';
			$html_str .= '<div class="callnumb">';
            $sliced_label = explode(' ', $barcode[2], 5);
            foreach ($sliced_label as $slice_label_item) {
                $html_str .= $slice_label_item.'<br />'; }
            $html_str .= '</div>';
	  $html_str .= '</div>';
	  $html_str .= '</div>';
      $html_str .= '</td>';
    }
    $html_str .= '</tr>'."\n";
  }
  $html_str .= '</table>'."\n";
  $html_str .= '<script type="text/javascript">self.print();</script>'."\n";
  $html_str .= '</body></html>'."\n";
  // unset the session
  unset($_SESSION['barcodes']);
  // write to file
  $print_file_name = 'label_barcode_gen_print_result_'.strtolower(str_replace(' ', '_', $_SESSION['uname'])).'.html';
  $file_write = @file_put_contents(UPLOAD.$print_file_name, $html_str);
  if ($file_write) {
    // update print queue count object
    echo '<script type="text/javascript">parent.$(\'#queueCount\').html(\'0\');</script>';
    // open result in window
    echo '<script type="text/javascript">top.$.colorbox({href: "'.SWB.FLS.'/'.$print_file_name.'", iframe: true, width: 850, height: 500, title: "'.__('Label Barcodes Printing by hadhie').'"})</script>';
  } else { utility::jsAlert('ERROR! Item barcodes failed to generate, possibly because '.SB.FLS.' directory is not writable'); }
  exit();
}

?>
<fieldset class="menuBox">
<div class="menuBoxInner printIcon">
  <div class="per_title">
	  <h2><?php echo __('Label Barcodes Printing'); ?></h2>
  </div>
  <div class="sub_section">
	  <div class="btn-group">
      <a target="blindSubmit" href="<?php echo MWB; ?>bibliography/label_barcode_generator_warna.php?action=clear" class="notAJAX btn btn-default"><i class="glyphicon glyphicon-trash"></i>&nbsp;<?php echo __('Clear Print Queue'); ?></a>
      <a target="blindSubmit" href="<?php echo MWB; ?>bibliography/label_barcode_generator_warna.php?action=print" class="notAJAX btn btn-default"><i class="glyphicon glyphicon-print"></i>&nbsp;<?php echo __('Print Labels for Selected Data');?></a>
	  </div>
    <form name="search" action="<?php echo MWB; ?>bibliography/label_barcode_generator_warna.php" id="search" method="get" style="display: inline;"><?php echo __('Search'); ?> :
    <input type="text" name="keywords" size="30" />
    <input type="submit" id="doSearch" value="<?php echo __('Search'); ?>" class="btn btn-default" />
    </form>
  </div>
  <div class="infoBox">
 
  <?php
  echo __('Maximum').' <font style="color: #f00">'.$max_print.'</font> '.__('records can be printed at once. Currently there is').' ';
  if (isset($_SESSION['barcodes'])) {
    echo '<font id="queueCount" style="color: #f00">'.count($_SESSION['barcodes']).'</font>';
  } else { echo '<font id="queueCount" style="color: #f00">0</font>'; }
  echo ' '.__('in queue waiting to be printed.');
  ?>
  </div>
</div>
</fieldset>
<?php
/* search form end */

// create datagrid
$datagrid = new simbio_datagrid();
/* ITEM LIST */
require SIMBIO.'simbio_UTILS/simbio_tokenizecql.inc.php';
require LIB.'biblio_list_model.inc.php';
// index choice
if ($sysconf['index']['type'] == 'index' || ($sysconf['index']['type'] == 'sphinx' && file_exists(LIB.'sphinx/sphinxapi.php'))) {
  if ($sysconf['index']['type'] == 'sphinx') {
    require LIB.'sphinx/sphinxapi.php';
    require LIB.'biblio_list_sphinx.inc.php';
  } else {
    require LIB.'biblio_list_index.inc.php';
  }
  // table spec
  $table_spec = 'item LEFT JOIN search_biblio AS `index` ON item.biblio_id=`index`.biblio_id';
  $datagrid->setSQLColumn('item.item_code',
    'item.item_code AS \''.__('Item Code').'\'',
'item.call_number, index.call_number AS \''.__('Call Number').'\'',
    'index.title AS \''.__('Title').'\'');
} else {
  require LIB.'biblio_list.inc.php';
  // table spec
  $table_spec = 'item LEFT JOIN biblio ON item.biblio_id=biblio.biblio_id';
  $datagrid->setSQLColumn('item.item_code',
    'item.item_code AS \''.__('Item Code').'\'',
'IF(item.call_number<>\'\', item.call_number, biblio.call_number) AS \''.__('Call Number').'\'',
    'biblio.title AS \''.__('Title').'\'');
}
$datagrid->setSQLorder('item.last_update DESC');
// is there any search
if (isset($_GET['keywords']) AND $_GET['keywords']) {
  $keywords = $dbs->escape_string(trim($_GET['keywords']));
  $searchable_fields = array('title', 'author', 'subject', 'class', 'callnumber', 'itemcode');
  $search_str = '';
  // if no qualifier in fields
  if (!preg_match('@[a-z]+\s*=\s*@i', $keywords)) {
    foreach ($searchable_fields as $search_field) {
      $search_str .= $search_field.'='.$keywords.' OR ';
    }
  } else {
    $search_str = $keywords;
  }
  $biblio_list = new biblio_list($dbs, 10);
  $criteria = $biblio_list->setSQLcriteria($search_str);
}
if (isset($criteria)) {
  $datagrid->setSQLcriteria('('.$criteria['sql_criteria'].')');
}
// set table and table header attributes
$datagrid->table_attr = 'align="center" id="dataList" cellpadding="5" cellspacing="0"';
$datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
// edit and checkbox property
$datagrid->edit_property = false;
$datagrid->chbox_property = array('itemID', __('Add'));
$datagrid->chbox_action_button = __('Add To Print Queue');
$datagrid->chbox_confirm_msg = __('Add to print queue?');
$datagrid->column_width = array('10%', '15%','70%');
// set checkbox action URL
$datagrid->chbox_form_URL = $_SERVER['PHP_SELF'];
// put the result into variables
$datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 40, $can_read);
if (isset($_GET['keywords']) AND $_GET['keywords']) {
  $msg = str_replace('{result->num_rows}', $datagrid->num_rows, __('Found <strong>{result->num_rows}</strong> from your keywords'));
  echo '<div class="infoBox">'.$msg.' : "'.$_GET['keywords'].'"<div>'.__('Query took').' <b>'.$datagrid->query_time.'</b> '.__('second(s) to complete').'</div></div>';
}
echo $datagrid_result;
/* main content end */
