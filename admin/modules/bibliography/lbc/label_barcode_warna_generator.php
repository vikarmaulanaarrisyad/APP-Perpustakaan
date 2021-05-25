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

/* Item barcode print */

// key to authenticate
define('INDEX_AUTH', '1');

// main system configuration
require '../../../../sysconfig.inc.php';
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
      echo 'jQuery.ajax({ url: \''.SWB.'lib/phpbarcode/barcode.php?code='.$itemID.'&encoding='.$sysconf['barcode_encoding'].'&scale='.$size.'&mode=png&act=save\', type: \'GET\', error: function() { alert(\'Error creating barcode!\'); } });'."\n";
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
    utility::jsToastr('Item Barcode', $msg, 'warning');
  } else {
    utility::jsToastr('Item Barcode', __('Selected items added to print queue'), 'success');
  }
  exit();
}

// clean print queue
if (isset($_GET['action']) AND $_GET['action'] == 'clear') {
  // update print queue count object
  echo '<script type="text/javascript">top.$(\'#queueCount\').html(\'0\');</script>';
  utility::jsToastr('Item Barcode', __('Print queue cleared!'), 'success');
  unset($_SESSION['barcodes']);
  exit();
}

// barcode pdf download
if (isset($_GET['action']) AND $_GET['action'] == 'print') {
  // check if label session array is available
  if (!isset($_SESSION['barcodes'])) {
    utility::jsToastr('Item Barcode', __('There is no data to print!'), 'error');
    die();
  }
  if (count($_SESSION['barcodes']) < 1) {
    utility::jsToastr('Item Barcode', __('There is no data to print!'), 'error');
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
  $item_q = $dbs->query('SELECT b.title, i.item_code, b.call_number, b.biblio_id FROM item AS i
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

  // load print settings from database to override value from printed_settings file
  loadPrintSettings($dbs, 'barcode');

  /* Modified by Drajat Hasan */
  // load function
  require SB.'admin'.DS.'modules'.DS.'bibliography'.DS.'lbc'.DS.'func.php';

  // load label warna print setting;
  $lbc = loadSettingJSON('setting.json', SB.'files');

  if (count($lbc) == 0)
  {
    utility::jsAlert('File setting belum tersedia. Di setting dulu ya :)');
    exit;
  }

  // chunk barcode array
  $chunked_barcode_arrays = array_chunk($item_data_array, $lbc['chunk']);
  
  // include main template
  include MDLBS.'bibliography/lbc/'.$lbc['temp'].'_template.php';


  // unset the session
  unset($_SESSION['barcodes']);
  // write to file
  $print_file_name = 'label_barcode_warna_gen_print_result_'.strtolower(str_replace(' ', '_', $_SESSION['uname'])).'.html';
  $file_write = @file_put_contents(UPLOAD.$print_file_name, $html_str);
  if ($file_write) {
    // update print queue count object
    echo '<script type="text/javascript">parent.$(\'#queueCount\').html(\'0\');</script>';
    // open result in window
    echo '<script type="text/javascript">top.$.colorbox({href: "'.SWB.FLS.'/'.$print_file_name.'", iframe: true, width: 1341, height: 597, title: "Label Barcode Warna"})</script>';
  } else { utility::jsToastr('Item Barcode', str_replace('{directory}', SB.FLS, __('ERROR! Item barcodes failed to generate, possibly because {directory} directory is not writable')), 'error'); }
  exit();
}

?>
<div class="menuBox">
<div class="menuBoxInner printIcon">
  <div class="per_title">
	  <h2>Label Barcode Warna</h2>
  </div>
  <div class="sub_section">
	  <div class="btn-group">
      <a target="blindSubmit" href="<?php echo MWB; ?>bibliography/lbc/label_barcode_warna_generator.php?action=clear" class="notAJAX btn btn-default"> <?php echo __('Clear Print Queue'); ?></a>
      <a target="blindSubmit" href="<?php echo MWB; ?>bibliography/lbc/label_barcode_warna_generator.php?action=print" class="notAJAX btn btn-default"><?php echo __('Print Barcodes for Selected Data');?></a>
	    <a href="<?php echo MWB; ?>bibliography/lbc/wizard_designer_lbc.php" class="notAJAX btn btn-default openPopUp" width="780" height="500" title="<?php echo __('Change print barcode settings'); ?>"><?php echo __('Change print barcode settings'); ?></a>
	  </div>
    <form name="search" action="<?php echo MWB; ?>bibliography/lbc/label_barcode_warna_generator.php" id="search" method="get" class="form-inline"><?php echo __('Search'); ?> 
    <input type="text" name="keywords" class="form-control col-md-3" />
    <input type="submit" id="doSearch" value="<?php echo __('Search'); ?>" class="btn btn-default" />
    </form>
  </div>
  <div class="infoBox">
  <?php
  echo __('Maximum').' <strong class="text-danger">'.$max_print.'</strong> '.__('records can be printed at once. Currently there is').' ';
  if (isset($_SESSION['barcodes'])) {
    echo '<strong id="queueCount" class="text-danger">'.count($_SESSION['barcodes']).'</strong>';
  } else { echo '<strong id="queueCount" class="text-danger">0</strong>'; }
  echo ' '.__('in queue waiting to be printed.');
  ?>
  </div>
</div>
</div>
<script>
  // Modified by Drajat Hasan
  $('.openPopUp').attr('width', parseInt($(window).width())-10).attr('height', parseInt($(window).height())-50);
</script>
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
    'index.title AS \''.__('Title').'\'',
    'index.call_number AS \'Nomor Panggil\'');
} else {
  require LIB.'biblio_list.inc.php';
  // table spec
  $table_spec = 'item LEFT JOIN biblio ON item.biblio_id=biblio.biblio_id';
  $datagrid->setSQLColumn('item.item_code',
    'item.item_code AS \''.__('Item Code').'\'',
    'biblio.title AS \''.__('Title').'\'');
}
$datagrid->setSQLorder('item.last_update DESC');
// is there any search
if (isset($_GET['keywords']) AND $_GET['keywords']) {
  $keywords = utility::filterData('keywords', 'get', true, true, true);
  $searchable_fields = array('title', 'author', 'subject', 'itemcode');
  $search_str = '';
  // if no qualifier in fields
  if (!preg_match('@[a-z]+\s*=\s*@i', $keywords)) {
    foreach ($searchable_fields as $search_field) {
      $search_str .= $search_field.'='.$keywords.' OR ';
    }
  } else {
    $search_str = $keywords;
  }
  $biblio_list = new biblio_list($dbs, 20);
  $criteria = $biblio_list->setSQLcriteria($search_str);
}
if (isset($criteria)) {
  $datagrid->setSQLcriteria('('.$criteria['sql_criteria'].')');
}
// set table and table header attributes
$datagrid->table_attr = 'id="dataList" class="s-table table"';
$datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
// edit and checkbox property
$datagrid->edit_property = false;
$datagrid->chbox_property = array('itemID', __('Add'));
$datagrid->chbox_action_button = __('Add To Print Queue');
$datagrid->chbox_confirm_msg = __('Add to print queue?');
$datagrid->column_width = array('10%', '85%');
// set checkbox action URL
$datagrid->chbox_form_URL = $_SERVER['PHP_SELF'];
// put the result into variables
$datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 20, $can_read);
if (isset($_GET['keywords']) AND $_GET['keywords']) {
  $msg = str_replace('{result->num_rows}', $datagrid->num_rows, __('Found <strong>{result->num_rows}</strong> from your keywords'));
  echo '<div class="infoBox">'.$msg.' : "'.htmlspecialchars($_GET['keywords']).'"<div>'.__('Query took').' <b>'.$datagrid->query_time.'</b> '.__('second(s) to complete').'</div></div>';
}
echo $datagrid_result;
/* main content end */
