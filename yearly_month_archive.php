<?php
/*
Plugin Name: Yearly Month Archive
Plugin URI: http://blog.tigion.de/2007/10/16/wordpress-plugin-yearly-month-archive/
Description: Ein nach Jahren unterteiltes Monatsarchiv mit alternativer Ausgabe in Spalten mit oder ohne kleiner Statistik.
Version: 0.1.1
Author: Christoph Zirkelbach
Author URI: http://blog.tigion.de/
*/

/*
 * Todos:
 * - Lizenzangabe angeben
 * - prüfen ob Funktion schon vorhanden ist
 *
 * Notes:
 * - parts from wp_get_archives() (file: general-template.php)
 */

//
function twp_yearly_month_archive($args = '') {
  global $wpdb, $wp_locale;
  
  // variables
  $max_columns = 10;
  $newline = "\n";
  $use_table = true;

  // set default values and parse arguments
  $defaults = array (
    'limit_years' => '',
    'columns' => '',
    'show_empty_months' => false,
    'show_stats' => false,
    'show_post_count' => false
  );

  $r = wp_parse_args( $args, $defaults );
  extract( $r, EXTR_SKIP );

  // check argument 'limit_years'
  if ( '' != $limit_years ) {
    $limit_years = (int) $limit_years;
    $limit_years_sql = ' LIMIT '.$limit_years;
  }

  // check argument 'columns'
  if ($columns > $max_columns)
    $columns = $max_columns;
  elseif ($columns < 1)
    $columns = '';
  else
    $columns = (int) $columns;

  if ($columns != '' && $limit_years != '') {
    if ($columns > $limit_years)
      $columns = $limit_years;
  }

  // use tables?
  if ($columns == '') $use_table = false;
  
  // get years
  $where = apply_filters('getarchives_where', "WHERE post_type = 'post' AND post_status = 'publish'", $r );
  $join = apply_filters('getarchives_join', "", $r);
  $result_years = $wpdb->get_results("SELECT DISTINCT YEAR(post_date) AS `year`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date) ORDER BY post_date DESC".$limit_years_sql);
  
  // show years
  if ($result_years) {
  	echo '<!-- Plugin: Yearly Month Archive -->'.$newline;
  	if ($use_table) echo '<table style="width:100%;"><tr>'.$newline;
    $tmp_columns = 0;
    
    foreach ($result_years as $result_year) {
      $tmp_columns++;
      $text = sprintf('%d', $result_year->year);
      if ($use_table) echo '<td style="width:'.floor(100/$columns).'%;">'.$newline;
      echo '<h2>'.$text.'</h2>'.$newline;

      // get months for year
      $where = apply_filters('getarchives_where', "WHERE post_type = 'post' AND post_status = 'publish' AND YEAR(post_date) = '".$result_year->year."'", $r );
      $join = apply_filters('getarchives_join', "", $r);
      $result_months = $wpdb->get_results("SELECT DISTINCT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC");
      
      // show months
      if ($result_months) {
        echo '<ul>'.$newline;
        $tmp_empty_months = 12;
        
        foreach ($result_months as $result_month) {
          // show empty months
          if ($show_empty_months) {
            for ($tmp_empty_months; $tmp_empty_months > $result_month->month; $tmp_empty_months--) {
              $text = sprintf(__('%1$s'), $wp_locale->get_month($tmp_empty_months));
              echo '<li>'.$text.'</li>'.$newline;
            }
            $tmp_empty_months = $result_month->month - 1;
          }
          
          $url	= get_month_link($result_month->year,	$result_month->month);
          $text = '<a href="'.$url.'">'.sprintf(__('%1$s'), $wp_locale->get_month($result_month->month)).'</a>';
          if ($show_post_count) {
            $text .= ' <small>('.sprintf('%d', $result_month->posts).')</small>';
          }
          echo '<li>'.$text.'</li>'.$newline;
        }
        // show empty monthd
        if ($show_empty_months) {
          for ($tmp_empty_months; $tmp_empty_months > 0; $tmp_empty_months--) {
            $text = sprintf(__('%1$s'), $wp_locale->get_month($tmp_empty_months));
            echo '<li>'.$text.'</li>'.$newline;
          }
        }
        echo '</ul>'.$newline;
          
        // show stats
        if ($show_stats) {
          echo '<p><small><strong>Statistik:</strong><br />
          - Gesamt '.$result_year->posts.' Beitr&auml;ge<br />
          - '.round($result_year->posts/12, 2).' Beitr&auml;ge pro Monat<br />
          - '.round($result_year->posts/365, 2).' Beitr&auml;ge pro Tag</small></p>'.$newline;  
        }

        if ($use_table) echo '</td>'.$newline;
      }
      
      // column layout
      if ($tmp_columns == $columns && count($result_years) != $columns && $use_table) {
      	$tmp_columns = 0;
      	echo '</tr><tr>'.$newline;
      }
    }
    if ($use_table) echo '</tr></table>'.$newline;
  }
}

?>
