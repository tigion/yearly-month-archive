<?php
/*
Plugin Name: Yearly Month Archive
Plugin URI: http://blog.tigion.de/2007/10/16/wordpress-plugin-yearly-month-archive/
Description: Ein nach Jahren unterteiltes Monatsarchiv mit alternativer Ausgabe in Spalten mit oder ohne kleiner Statistik.
Version: 0.4
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

// output graphic archive statistics
function twp_show_archive_stats($archive_stats) {
	$newline = "\n";
	$url = get_bloginfo('wpurl').'/wp-content/plugins/yearly-month-archive/';
	$img1 = "img_bar.jpg";
	$img2 = "img_bar_empty.gif";
	$img3 = "img_bar_max.jpg";
	$img4 = "img_bar_newyear.jpg";
	$img_width = 10;
	$img_height_max = 100;
	$img_height = 0;

	// sort order to past -> future
	sort($archive_stats);
	
	// remove last empty month
	while(true) {
		$tmp_data = explode("_", $archive_stats[count($archive_stats) - 1]);
		if ($tmp_data[2] == '0')
			array_pop($archive_stats);
		else
			break;
	}
	
	// get month count and max post count
	$tmp_year = 0;
	$year_count = 0;
	$max_posts = 0;
	$max_year = 0;
	$min_year = 0;
	for ($i = 0; $i < count($archive_stats); $i++) {
		$tmp_data = explode("_", $archive_stats[$i]);
		$year = $tmp_data[0];
		$month = ltrim($tmp_data[1]," ");
		$posts = $tmp_data[2];
		
		// min year
		if ($min_year == 0 || $min_year > $year)
			$min_year = $year;
			
		// max year
		if ($max_year < $year)
			$max_year = $year;
		
		if ($tmp_year != 0 && $year != $tmp_year)
			$year_count++; 
		
		// max posts
		if ($posts > $max_posts)
			$max_posts = $posts;
			
		$tmp_year = $year;
	}
	$month_count = (12 * $year_count) + $month + ($year_count - 1);
	
	// get image width
	//$img_width = (100 / $month_count);
	$img_width = (100 / ($month_count + 1));  // dirty hack +1
	$img_width = $img_width."%";

	//
	if ($year_count == 1)
		echo '<h2 style="clear:both;padding-top:20px;">'.$min_year.'</h2>'.$newline;
	else
		echo '<h2 style="clear:both;padding-top:20px;">'.$min_year.' - '.$max_year.'</h2>'.$newline;
	echo '<div class="graphic_stats">';

	$tmp_month = 1;
	$tmp_year = 0;
	for ($i = 0; $i < count($archive_stats); $i++) {
		$tmp_data = explode("_", $archive_stats[$i]);
		$year = $tmp_data[0];
		$month = $tmp_data[1];
		$count = $tmp_data[2];

		// new year
		if ($tmp_year != 0 && $tmp_year != $year) {
			echo '<img src="'.$url.$img4.'" width="'.$img_width.'" height="10" alt="" />';
			$tmp_month = 1;
		}

		// get image height
		$img_height = (int) ($count * 100 / $max_posts);

		//
		if ($count == 1)
			$title = $count." Blogbeitrag";
		else
			$title = $count." Blogbeiträge";
		$title .= " (".$month."/".$year.")";
		
		//
		if ($count == $max_posts)
			$img = $img3;
		else
			$img = $img1;    
 
		echo '<img src="'.$url.$img.'" width="'.$img_width.'" height="'.$img_height.'" title="'.$title.'" alt="" />';
	
		$tmp_month++;
		$tmp_year = $year;
	}
	echo '</div>'.$newline;
}

// main function - plugin: yearly month archive
function twp_yearly_month_archive($args = '') {
	global $wpdb, $wp_locale;
	
	// variables
	$max_columns = 10;
	$newline = "\n";
	$css_clear_left = '';
	$result_months_fill[12];
	$archive_stats[] = '';

	// set default values and parse arguments
	$defaults = array (
		'limit_years' => '',
		'columns' => '0',
		'use_container' => 'div',
		'show_empty_months' => false,
		'show_stats' => false,
		'show_post_count' => false,
		'show_graphic_stats' => false,
		'sort_order' => '0'
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

	// check argument 'use_container'
	$use_table = false;
	$use_div = false;
	if ($use_container == "table")
		$use_table = true;
	elseif ($use_container == "div")
		$use_div = true;
	else
		$use_div = true;

	// check argument 'show_empty_months'
	// check argument 'show_stats'
	// check argument 'show_post_count'
	// check argument 'show_graphic_stats'

	// check argument 'sort_order'
	// default: DESC = newest first
	$sort_order_month = 'DESC';
	$sort_order_year = 'DESC';
	if ($sort_order == 1 || $sort_order == 3) {
		// sort month by oldest first
		$sort_order_month = 'ASC';
	}
	if ($sort_order == 2 || $sort_order == 3) {
		// sort year by oldest first
		$sort_order_year = 'ASC';
	}

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
	$order_by = 'ORDER BY post_date '.$sort_order_year;
	$result_years = $wpdb->get_results("SELECT DISTINCT YEAR(post_date) AS `year`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date) $order_by".$limit_years_sql);
	
	// start output
	echo $newline.'<!-- START: Plugin - Yearly Month Archive -->'.$newline;
	
	// show years
	if ($result_years) {
		$month_count = 0;

		// first lines
		echo '<div class="yearly_month_archive">'.$newline;
		if ($use_table) {
			echo '<table style="width:100%;">'.$newline;
			echo '<colgroup width="'.floor(100/$columns).'%" span="'.$columns.'"></colgroup>'.$newline;
			echo '<tr>'.$newline;
		} else {
			echo '<div class="year_row">'.$newline;
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
			$order_by = 'ORDER BY post_date '.$sort_order_month;
			$result_months = $wpdb->get_results("SELECT DISTINCT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) $order_by");

			// fill empty month array
			// clear array
			for ($i = 0; $i < 12; $i++) {
				if ($sort_order_month == 'DESC')
					$idx = 11 - $i;
				else
					$idx = $i;
				$result_months_fill[$idx]->year = $result_year->year;
				$result_months_fill[$idx]->month = $idx + 1;
				$result_months_fill[$idx]->posts = 0;
			}
			// fill month data
			foreach ($result_months as $result_month) {
				foreach ($result_months_fill as $result_month_fill) {
					if ($result_month_fill->month == $result_month->month) {
						$result_month_fill->posts = $result_month->posts;
					}
				}
			}

			// show months
			if ($result_months) {
				echo '<ul>'.$newline;

				foreach ($result_months_fill as $result_month) {
					if ($result_month->posts == 0) {
						if ($show_empty_months) {
							$text = sprintf(__('%1$s'), $wp_locale->get_month($result_month->month));
							echo '<li class="empty_month">'.$text.'</li>';
						}
					} else {
						$url  = get_month_link($result_month->year, $result_month->month);
						$text = '<a href="'.$url.'">'.sprintf(__('%1$s'), $wp_locale->get_month($result_month->month)).'</a>';
						if ($show_post_count) {
							$text .= ' <small>('.sprintf('%d', $result_month->posts).')</small>';
						}
						echo '<li>'.$text.'</li>';
					}

					// save monthly post count
					$archive_stats[$month_count] = $result_month->year.'_'.sprintf("%02d", $result_month->month).'_'.$result_month->posts;
					$month_count++;
				}

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
					echo '</div>'.$newline;
					echo '<div class="year_row">'.$newline;
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
		} else {
			echo '</div>'.$newline;
		}
		
		// show graphic statistics
		if ($show_graphic_stats)
			twp_show_archive_stats ($archive_stats);

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
