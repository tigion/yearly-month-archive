<?php
/*
Plugin Name: Yearly Month Archive
Plugin URI: http://blog.tigion.de/2007/10/16/wordpress-plugin-yearly-month-archive/
Description: Ein nach Jahren unterteiltes Monatsarchiv mit alternativer Ausgabe in Spalten mit oder ohne kleiner Statistik.
Version: 0.2
Author: Christoph Zirkelbach
Author URI: http://blog.tigion.de/
*/

/*
 * Copyright 2007 Christoph Zirkelbach  (email: tigion [at] bsd-crew.de)
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/*
 * Todos:
 *
 *
 * Notes:
 * - parts from wp_get_archives() (file: general-template.php)
 */

// add plugin stylesheet
function twp_add_stylesheet() {
  $url = get_bloginfo('wpurl');
  $newline = "\n";
  //$tab = "\t";
  echo $newline;
  echo '<!-- Added By Plugin: Yearly Month Archive -->'.$newline;
  echo '<link rel="stylesheet" type="text/css" href="'.$url.'/wp-content/plugins/yearly-month-archive/yearly_month_archive.css" />'.$newline;
  echo $newline;
}

// fill empty months
function twp_fill_empty_months($tmp_month, $month) {
  global $wp_locale;
  $newline = "\n";
  
  for ($tmp_month; $tmp_month > $month; $tmp_month--) {
    $text = sprintf(__('%1$s'), $wp_locale->get_month($tmp_month));
    echo '<li class="empty_month">'.$text.'</li>'.$newline;
  }
}

// show yearly stats
function twp_show_stats($result_year) {
  $tmp_post = "Beitrag";
  $tmp_posts = "Beitr&auml;ge";
  $tmp = $tmp_posts;

  $output = '<p><small><strong>Statistik:</strong><br />';

  // posts per year
  $result = $result_year->posts;
  if ($result == 1) $tmp = $tmp_post;
  else $tmp = $tmp_posts;
  $output .= '- '.$result.' '.$tmp.'<br />';

  // posts per month
  $result = round($result_year->posts/12, 2);
  if ($result == 1 ) $tmp = $tmp_post;
  else $tmp = $tmp_posts;
  $output .= '- '.$result.' '.$tmp.' pro Monat<br />';

  // post per day
  $result = round($result_year->posts/365, 3);
  if ($result == 1) $tmp = $tmp_post;
  else $tmp = $tmp_posts;
  $output .= '- '.$result.' '.$tmp.' pro Tag</small></p>';

  $output .= $newline;

  echo $output;
}

// main function - plugin: yearly month archive
function twp_yearly_month_archive($args = '') {
  global $wpdb, $wp_locale;
  
  // variables
  $max_columns = 10;
  $newline = "\n";
  $css_clear_left = '';

  // set default values and parse arguments
  $defaults = array (
    'limit_years' => '',
    'columns' => '0',
    'show_empty_months' => false,
    'show_stats' => false,
    'show_post_count' => false,
    'use_container' => 'none'
  );

  $r = wp_parse_args( $args, $defaults );
  extract( $r, EXTR_SKIP );

  // check argument 'limit_years'
  if ($limit_years != '') {
    $limit_years = (int) $limit_years;
    $limit_years_sql = ' LIMIT '.$limit_years;
  }

  // check argument 'columns'
  if ($columns == 0 || $columns == '')
    $columns = 0;
  elseif ($columns > $max_columns)
    $columns = $max_columns;
  elseif ($columns < 1)
    $columns = 1;
  else
    $columns = (int) $columns;

  // check argument 'show_empty_months'
  // check argument 'show_stats'
  // check argument 'show_post_count'

  // check argument 'use_container'
  $use_table = false;
  $use_div = false;
  if ($use_container == "table")
    $use_table = true;
  elseif ($use_container == "div")
    $use_div = true;
  else
    ; // none

  /*// limit higher columns to 'limit_years'
  if ($limit_years != '' && $columns > $limit_years)
    $columns = $limit_years;*/

  // no columns = no div/table
  if ($columns == 0) {
    $use_table = false;
    $use_div = false;
  }

  // get years
  $where = apply_filters('getarchives_where', "WHERE post_type = 'post' AND post_status = 'publish'", $r );
  $join = apply_filters('getarchives_join', "", $r);
  $result_years = $wpdb->get_results("SELECT DISTINCT YEAR(post_date) AS `year`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date) ORDER BY post_date DESC".$limit_years_sql);
  
  // start output
  echo $newline.'<!-- START: Plugin - Yearly Month Archive -->'.$newline;
  
  // show years
  if ($result_years) {
  	// first lines
  	echo '<div class="yearly_month_archive">'.$newline;
  	if ($use_table) {
  	  echo '<table style="width:100%;">'.$newline;
  	  echo '<colgroup width="'.floor(100/$columns).'%" span="'.$columns.'"></colgroup>'.$newline;
  	  echo '<tr>'.$newline;
  	}
    $tmp_columns = 0;
    
    foreach ($result_years as $result_year) {
      $tmp_columns++;
      $text = sprintf('%d', $result_year->year);
      if ($use_table) {
        echo '<td>'.$newline;
      } elseif ($use_div) {
        echo '<div class="year'.$css_clear_left.'" style="width:'.floor(100/$columns).'%;">'.$newline;
      }
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
            twp_fill_empty_months($tmp_empty_months, $result_month->month);
            $tmp_empty_months = $result_month->month - 1;
          }
          
          $url	= get_month_link($result_month->year,	$result_month->month);
          $text = '<a href="'.$url.'">'.sprintf(__('%1$s'), $wp_locale->get_month($result_month->month)).'</a>';
          if ($show_post_count) {
            $text .= ' <small>('.sprintf('%d', $result_month->posts).')</small>';
          }
          echo '<li>'.$text.'</li>'.$newline;
        }
        // show empty months
        if ($show_empty_months)
          twp_fill_empty_months($tmp_empty_months, 0);
        
        echo '</ul>'.$newline;
          
        // show stats
        if ($show_stats)
          twp_show_stats($result_year);

        if ($use_table)
          echo '</td>'.$newline;
        elseif ($use_div)
          echo '</div>'.$newline;
      }
      
      // column layout
      $css_clear_left = '';
      if ($tmp_columns == $columns && count($result_years) != $columns) {
        if ($use_table) {
      	  $tmp_columns = 0;
      	  echo '</tr>'.$newline;
      	  echo '<tr>'.$newline;
        } elseif ($use_div) {
          $tmp_columns = 0;
          $css_clear_left = ',clear_left';
        }
      }
    }
    
    // add empty cells
    if ($use_table) {
      for ($tmp_columns; $tmp_columns < $columns; $tmp_columns++) {
        echo '<td></td>'.$newline;
      }
    }
    
    // last lines
    if ($use_table) {
      echo '</tr>'.$newline;
      echo '</table>'.$newline;
    }
    echo '</div>'.$newline;
  } else {
    // no posts found
    echo '<p>Es sind keine Blogeinträge für ein Archiv vorhanden.</p>'.$newline;
  }
  // end output
  echo '<!-- END: Plugin - Yearly Month Archive -->'.$newline;
}

// actions and filters
if (function_exists('twp_yearly_month_archive')) {
  // add plugin stylesheet to template head
  add_action('wp_head', 'twp_add_stylesheet');
}

?>
