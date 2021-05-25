<?php
/**
 * Template for Biblio List
 * name of memberID text field must be: memberID
 * name of institution text field must be: institution
 *
 * Copyright (C) 2015 Arie Nugraha (dicarve@gmail.com)
 * Create by Eddy Subratha (eddy.subratha@slims.web.id)
 *
 * Slims 8 (Akasia)
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
 */

$label_cache = array();
/**
 *
 * Format bibliographic item list for OPAC display
 *
 * @param   object $dbs
 * @param   array $biblio_detail
 * @param   int $n
 * @param   array $settings
 * @param   array $return_back
 *
 * @return string
 */
function biblio_list_format($dbs, $biblio_detail, $n, $settings = array(), &$return_back = array()) {
  global $label_cache, $sysconf;
  // init output var
  $output     = '';

  $title      = $biblio_detail['title'];
  $biblio_id  = $biblio_detail['biblio_id'];
  $detail_url = SWB.'index.php?p=show_detail&id='.$biblio_id.'&keywords='.$settings['keywords'];
  $cite_url   = SWB.'index.php?p=cite&id='.$biblio_id.'&keywords='.$settings['keywords'];
  $title_link = '<a href="'.$detail_url.'" class="titleField" itemprop="name" property="name" title="'.__('View record detail description for this title').'">'.$title.'</a>';

  // image thumbnail
  $images_loc = '../../images/docs/'.$biblio_detail['image'];
  $thumb_url = './lib/minigalnano/createthumb.php?filename='.urlencode($images_loc).'&width=120';

  // notes
  $notes = getNotes($dbs, $biblio_id);
  if (empty($notes)) {
    $notes .= '<span class="text-label">ISBN/ISSN:</span>&nbsp;' . $biblio_detail['isbn_issn'] . '<br>';
    $notes .= '<span class="text-label">Publish Year:</span>&nbsp;' . $biblio_detail['publish_year'] . '<br>';
  }

  // availability
  $availability = getAvailability($dbs, $biblio_id);
  $class_avail = ($availability > 0) ? '' : 'text-danger';

  // authors
  $_authors = isset($biblio_detail['author'])?$biblio_detail['author']:biblio_list_model::getAuthors($dbs, $biblio_id, true);
  $_authors_string = '';
  if ($_authors) {
    if (!is_array($_authors)) {
      $_authors = explode('-', $_authors);
    }
    foreach ($_authors as $a) {
      $a = trim($a);
      $_authors_string .= '<a href="index.php?author='.urlencode($a).'&search=Search" itemprop="name" property="name" class="btn btn-outline-secondary btn-rounded">'.$a.'</a>';
    }
  }

  $output .= '<div class="card item border-0 elevation-1 mb-6">';
  $output .= '<div class="card-body">';
  $output .= '<div class="row">';
  $output .= '<div class="col-2">';
  $output .= '<img src="'.$thumb_url.'" alt="cover" class="img-fluid rounded" />';
  $output .= '</div>'; // -- close col-2
  $output .= '<div class="col-8">';
  $output .= '<h5><a title="'.__('View record detail description for this title').'" class="card-link text-dark" href="'.$detail_url.'">'.addEllipsis($title, 80).'</a></h5>';
  $output .= '<div class="d-flex authors flex-wrap py-2">';
  $output .= $_authors_string;
  $output .= '</div>'; // -- close d-flex authors flex-wrap
  $output .= '<p>'.$notes.'</p>';
  $output .= '</div>'; // -- close col-8
  $output .= '<div class="col-2">';
  $output .= '<div class="card availability cursor-pointer">';
  $output .= '<div class="card-body pt-3 pb-2 px-1">';
  $output .= '<div class="d-flex availability-content flex-column">';
  $output .= '<span class="label">availability</span>';
  $output .= '<span class="value '.$class_avail.'">'.$availability.'</span>';
  $output .= '</div>'; // -- close d-flex flex-column
  $output .= '<div class="add-to-chart align-items-center justify-content-center flex-column" data-biblio="'.$biblio_id.'">';
  $output .= '<span class="label">Add to basket</span>';
  $output .= '<span class="value"><i class="fas fa-plus"></i></span>';
  $output .= '</div>'; // -- close d-flex flex-column
  $output .= '</div>'; // -- close card-body pt-3 pb-2 px-1
  $output .= '</div>'; // -- close card availability
  $output .= '<a class="btn btn-outline-primary btn-block mt-2 btn-sm" href="'.$detail_url.'">View Detail</a>';
  $output .= '</div>'; // -- close col-2
  $output .= '</div>'; // -- close row
  $output .= '</div>';
  $output .= '</div>';

  // debug
  // $output .= '<code>'.json_encode($biblio_detail).'</code>';

  return $output;
}

function getNotes($dbs, $biblio_id)
{
  $query = $dbs->query('SELECT notes FROM biblio WHERE biblio_id = ' . $biblio_id);
  $data = $query->fetch_row();
  return addEllipsis($data[0], 200);
}

function addEllipsis($string, $length, $end='…')
{
    if (strlen($string) > $length)
    {
        $length -= strlen($end);
        $string  = substr($string, 0, $length);
        $string .= $end;
    }

    return $string;
}

function getAvailability($dbs, $biblio_id)
{
  // get total number of this biblio items/copies
  $_item_q = $dbs->query('SELECT COUNT(*) FROM item WHERE biblio_id='.$biblio_id);
  $_item_c = $_item_q->fetch_row();
  // get total number of currently borrowed copies
  $_borrowed_q = $dbs->query('SELECT COUNT(*) FROM loan AS l INNER JOIN item AS i'
    .' ON l.item_code=i.item_code WHERE l.is_lent=1 AND l.is_return=0 AND i.biblio_id='.$biblio_id);
  $_borrowed_c = $_borrowed_q->fetch_row();
  // total available
  $_total_avail = $_item_c[0]-$_borrowed_c[0];

  return $_total_avail;
}
