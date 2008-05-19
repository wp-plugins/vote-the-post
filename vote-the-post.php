<?php

/*
Plugin Name: Vote the Post
Plugin URI: http://www.1800blogger.com/word-press-voting-plugin/
Description: Rate posts from one to five.
Version: 1.0
Author: Crowd Favorite
Author URI: http://crowdfavorite.com/
*/

class ratepost {

	function ratepost() {
		$this->cookie = '';
	}

	function install() {
		global $wpdb;

		$charset_collate = '';
		if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
			if (!empty($wpdb->charset)) {
				$charset_collate .= sprintf(' DEFAULT CHARACTER SET %s', $wpdb->charset);
			}
			if (!empty($wpdb->collate)) {
				$charset_collate .= ' COLLATE $wpdb->collate';
			}
		}
		$sql_post = 'CREATE TABLE `%s_post` (
					 `post_id` INT(11) NOT NULL PRIMARY KEY,
					 `avg_vote` DOUBLE(3,2) NOT NULL,
					 `count` INT(6) NOT NULL
					 )%s';
		$sql_vote = 'CREATE TABLE `%s_vote` (
					 `post_id` INT(11) NOT NULL,
					 `vote` INT(1) NOT NULL,
					 `ip_address` CHAR(15) NOT NULL,
					 `cookie` CHAR(32) NOT NULL,
					 `user_id` INT(11),
					 INDEX  (`post_id`)
					 )%s';
		$wpdb->query(sprintf($sql_post, $wpdb->ratepost, $charset_collate));
		$wpdb->query(sprintf($sql_vote, $wpdb->ratepost, $charset_collate));

		update_option('ratepost_max_votes_ip', '25');
	}

	function avg_vote($post_id) {
		global $wpdb;
		$starting_avg = '3.00';

		$post = $wpdb->get_row(
			sprintf('SELECT avg_vote from %s_post WHERE post_id = \'%s\'',
				$wpdb->ratepost,
				$wpdb->escape($post_id)
		));

		if (isset($post->avg_vote) and !empty($post->avg_vote)) {
			return $post->avg_vote;
		}

		$wpdb->query(
			sprintf('INSERT INTO %s_post (post_id, avg_vote, count) VALUES (\'%s\',\'%s\',\'1\')',
					$wpdb->ratepost,
					$wpdb->escape($post_id),
					$starting_avg
		));

		return $starting_avg;
	}

	function get_vote($post_id, $user_id, $ip_address, $cookie) {
		global $wpdb;

		$post = $wpdb->get_row(
			sprintf('SELECT vote from %s_vote WHERE post_id = \'%s\' AND ((user_id = \'%s\' AND user_id != 0) OR cookie = \'%s\')',
				$wpdb->ratepost,
				$wpdb->escape($post_id),
				$wpdb->escape($user_id),
				$wpdb->escape($cookie)));

		if (isset($post->vote)) {
			return (int)$post->vote;
		}

		return 0;
	}

	function delete_post($post_id) {
		global $wpdb;

		$wpdb->query(
			sprintf('DELETE FROM %s_post WHERE post_id = \'%s\'',
				$wpdb->ratepost,
				$wpdb->escape($post_id)
		));
	}

	function display_rating($post_id, $user_id, $ip_address, $cookie) {
		$simple_rating = (int)$rating = $this->avg_vote($post_id);
		$user_vote = (int)$this->get_vote($post_id, $user_id, $ip_address, $cookie);
		$status_info = $status = '';
		$hide_more_info = get_option('ratepost_hide_more_info');
		$hide_more_info = empty($hide_more_info) ? false : $hide_more_info;

		if ($user_vote > 0) {
			$status_info = 'Thank you for voting!';
			$status = ' voted';
		}

		$html = '
			<div style="clear: both; float: none;" class="ratepost-clear"></div>
			<div id="ratepost-'.$post_id.'" class="ratepost'.$status.'" rel="'.$simple_rating.'">
				<div class="rating-info">Rating '.$rating.' out of 5</div>
					<span class="rating'.$simple_rating.'"><div class="star1" rel="1"></div><div class="star2" rel="2"></div><div class="star3" rel="3"></div><div class="star4" rel="4"></div><div class="star5" rel="5"></div></span>
				<div class="status-info">'.$status_info.'</div>
		';
		if(!$hide_more_info) {
			$html .= '<div class="info">[<a href="http://www.1800blogger.com/word-press-voting-plugin/" target="_blank">?</a>]</div>';
		}
		$html .= '</div><div style="clear: both; float: none;" class="ratepost-clear"></div>';

		return $html;
	}

	function to_many_ip_votes($post_id, $ip_address) {
		global $wpdb;

		$max_votes_ip = get_option('ratepost_max_votes_ip');
		$max_votes_ip = (int)$max_votes_ip > 0 ? $max_votes_ip : '25';

		$select = $wpdb->get_row(
			sprintf('SELECT count(ip_address) AS count FROM %s_vote
				WHERE post_id = \'%s\' AND ip_address = \'%s\'',
				$wpdb->ratepost,
				$wpdb->escape($post_id),
				$wpdb->escape($ip_address)
		));

		return $select->count > $max_votes_ip;
	}

	function set_vote($post_id, $vote, $ip_address, $user_id, $cookie) {
		global $wpdb;

		if (is_numeric($vote) and is_numeric($post_id) and !$this->to_many_ip_votes($post_id, $ip_address)) {
			$vote = (int)$vote;
			if (!($vote >= 0 and $vote <= 5)) {
				return false;
			}
		} else {
			return false;
		}

		$wpdb->query(
			sprintf('DELETE FROM %s_vote WHERE post_id = \'%s\' AND ((user_id = \'%s\' AND user_id != 0) OR cookie = \'%s\')',
					$wpdb->ratepost,
					$wpdb->escape($post_id),
					$wpdb->escape($user_id),
					$wpdb->escape($cookie)
		));
		if ($vote > 0) {
			$wpdb->query(
				sprintf('INSERT INTO %s_vote
						 (post_id, vote, ip_address, user_id, cookie)
						 VALUES (\'%s\', \'%s\', \'%s\', \'%s\', \'%s\')',
						$wpdb->ratepost,
						$wpdb->escape($post_id),
						$wpdb->escape($vote),
						$wpdb->escape($ip_address),
						$wpdb->escape($user_id),
						$wpdb->escape($cookie)
			));
		}
		$votes = $wpdb->get_results(
			sprintf('SELECT vote from %s_vote WHERE post_id = \'%s\'',
					$wpdb->ratepost,
					$wpdb->escape($post_id)
		));

		$count = $vote_total = 0;

		foreach ($votes as $vote) {
			$vote_total += (int)$vote->vote;
			$count++;
		}

		// Add initial 3-star vote
		$count++;
		$vote_total = $vote_total + 3;

		$avg_vote = (double)$vote_total / (double)$count;
		$avg_vote = $avg_vote > 0 ? round($avg_vote, 2) : 3;
		$avg_vote = (double)$avg_vote;

		$wpdb->query(
			sprintf('UPDATE %s_post SET avg_vote = \'%s\', count = \'%s\' WHERE post_id = \'%s\'',
					$wpdb->ratepost,
					$wpdb->escape($avg_vote),
					$wpdb->escape($count),
					$wpdb->escape($post_id)
		));

		// post id, voter's user id, user's vote (0-5), sum of all votes, total number of votes
		do_action('ratepost_vote', $post_id, $user_id, $vote, $total_vote, $count);
	}
}

function ratepost_init() {
	global $wpdb, $ratepost;

	$ratepost = new ratepost;
	$wpdb->ratepost = sprintf('%sratepost', $wpdb->prefix);

	if (!isset($_COOKIE['wordpress_ratepost'])) {
		$ratepost->cookie = md5(sprintf('ratepost%s%s', time(), $_SERVER['REMOTE_ADDR']));
		setcookie('wordpress_ratepost', $ratepost->cookie, time()+60*60*24*360, COOKIEPATH);
	} else {
		$ratepost->cookie = $_COOKIE['wordpress_ratepost'];
	}

	if (isset($_GET['activate']) and $_GET['activate'] == 'true') {
		$tables = $wpdb->get_col('SHOW TABLES');
		if (!in_array($wpdb->ratepost.'_post', $tables)) {
			$ratepost->install();
		}
	}
}
add_action('init', 'ratepost_init');

function ratepost_the_content($content = '') {
	global $wpdb, $post, $userdata, $ratepost;

	if(!is_feed() and !is_trackback() and !is_page()) {
		$display_rating = $ratepost->display_rating($post->ID, $userdata->ID, $_SERVER['REMOTE_ADDR'], $_COOKIE['wordpress_ratepost']);
		return sprintf('%s%s', $content, $display_rating);
	}
	return $content;
}
add_action('the_content', 'ratepost_the_content');

function ratepost_delete_post($post_id) {
	global $ratepost;
	$ratepost->delete_post($post_id);
}
add_action('delete_post', 'ratepost_delete_post');

function ratepost_request_handler() {
	global $wpdb, $userdata, $ratepost, $wp_version;

	if (!empty($_GET['ak_action'])) {
		switch($_GET['ak_action']) {
			case 'ratepost_vote':
				if (isset($_POST['post_id']) and isset($_POST['vote'])) {
					$ratepost->set_vote($_POST['post_id'], $_POST['vote'], $_SERVER['REMOTE_ADDR'], $userdata->ID, $_COOKIE['wordpress_ratepost']);
				}
				die();
				break;
			case 'ratepost_vote_avg':
				if (isset($_POST['post_id'])) {
					echo $ratepost->avg_vote($_POST['post_id']);
				}
				die();
				break;
			case 'ratepost_css':
				header('Content-type: text/css');
?>
div.ratepost { clear:both; padding-top: 10px; }
div.ratepost div { float:left; line-height: 10px; }
div.ratepost span div { width:10px; height:10px; }
div.ratepost div.rating-info { padding-right:10px; }
div.ratepost div.status-info { padding-left:10px; font-size:90%; }
div.ratepost div.info { padding-left:3px; }

div.ratepost span.rating1 div.star1,
div.ratepost span.rating2 div.star1,
div.ratepost span.rating2 div.star2,
div.ratepost span.rating3 div.star1,
div.ratepost span.rating3 div.star2,
div.ratepost span.rating3 div.star3,
div.ratepost span.rating4 div.star1,
div.ratepost span.rating4 div.star2,
div.ratepost span.rating4 div.star3,
div.ratepost span.rating4 div.star4,
div.ratepost span.rating5 div.star1,
div.ratepost span.rating5 div.star2,
div.ratepost span.rating5 div.star3,
div.ratepost span.rating5 div.star4,
div.ratepost span.rating5 div.star5 {
	background:transparent url(<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/vote-the-post/star.png) center center no-repeat;
}

div.ratepost span.rating4 div.star5,
div.ratepost span.rating3 div.star5,
div.ratepost span.rating3 div.star4,
div.ratepost span.rating2 div.star5,
div.ratepost span.rating2 div.star4,
div.ratepost span.rating2 div.star3,
div.ratepost span.rating1 div.star5,
div.ratepost span.rating1 div.star4,
div.ratepost span.rating1 div.star3,
div.ratepost span.rating1 div.star2 {
	background:transparent url(<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/vote-the-post/star-dim.png) center center no-repeat;
}
<?php
				die();
				break;
			case 'ratepost_js':
				header('Content-type: text/javascript');
?>

function ratepostVote(post_id, vote) {
	jQuery.ajax({
		type: 'POST',
		data: {'post_id': post_id, 'vote': vote},
		url: '<?php echo get_bloginfo('wpurl'); ?>/index.php?ak_action=ratepost_vote',
		timeout: 2000,
		error: function() {},
		success: function(r) { 
			ratepostVoteAvg(post_id);
		}
	})
	return false;
}

function ratepostVoteAvg(post_id) {
	jQuery.ajax({
		type: 'POST',
		data: {'post_id': post_id},
		url: '<?php echo get_bloginfo('wpurl'); ?>/index.php?ak_action=ratepost_vote_avg',
		timeout: 2000,
		error: function() {},
		success: function(r) { 
			jQuery('#ratepost-'+post_id).find('div.rating-info').text('Rating ' + r + ' out of 5');
		}
	})
	return false;
}

jQuery(document).ready(function() {
	jQuery('div.ratepost').not('.voted').find('span div').hover(
		function() {
			rating_text = {
				'1': 'Needs Work',
				'2': 'Below Average',
				'3': 'Average',
				'4': 'Very Good',
				'5': 'Excellent'};
			$current = jQuery(this);
			$ratepost = $current.parent().parent();
			new_rating = $current.attr('rel');
			$ratepost.find('span').removeClass().addClass('rating' + new_rating);
			$ratepost.find('div.status-info').text(rating_text[new_rating]);
		},
		function (){
			$ratepost = jQuery(this).parent().parent();
			$ratepost.find('span').removeClass().addClass('rating' + $ratepost.attr('rel'));
			$ratepost.find('div.status-info').text('');
		});
	jQuery('div.ratepost').not('.voted').find('span div').click(function() {
		$current = jQuery(this);
		$ratepost = $current.parent().parent();
		vote = $current.attr('rel');
		post_id = $ratepost.attr('id').split('-')[1];
		$ratepost.find('span div').unbind();
		$ratepost.attr('rel', vote);
		$ratepost.find('span').removeClass().addClass('rating' + vote);
		$ratepost.find('div.status-info').text('Thank you for voting!');
		ratepostVote(post_id, vote);
	});
});
<?php
				die();
				break;
		}
	}
}
add_action('init', 'ratepost_request_handler', 10);

function ratepost_admin_menu() {
	add_menu_page('Vote the Post Admin', 'Vote the Post', 'manage_links', basename(__FILE__), 'ratepost_admin');
	add_options_page('Vote the Post Options', 'Vote the Post', 'manage_options', basename(__FILE__), 'ratepost_options');
}
add_action('admin_menu','ratepost_admin_menu');

function ratepost_admin() {
	global $wpdb;
	// Gets current page and does basic validation
	$paged = (isset($_GET['ratepost_paged']) and is_numeric($_GET['ratepost_paged']) and (int)$_GET['ratepost_paged'] > 0) ? (int)$_GET['ratepost_paged'] : 1;
	$limit = 50;
	$offset = ($paged - 1) * $limit;

	$post_results = $wpdb->get_results(
		sprintf('SELECT * from %s_post ORDER BY post_id DESC LIMIT %s OFFSET %s',
				$wpdb->ratepost,
				$wpdb->escape($limit),
				$wpdb->escape($offset)
	));
	$post_count = $wpdb->get_row(
		sprintf('SELECT count(post_id) as num from %s_post',
				$wpdb->ratepost,
				$wpdb->escape($post_id)
	));

	$has_next = $post_count->num > ($offset + $limit);
	$has_previous = $paged > 1;
?>
<div class="wrap">
	<h2>Vote the Post Viewer</h2>
<?php if (count($post_results) > 0) {?>
	<p>
		<?php if ($has_previous) printf('<a href="%s/wp-admin/admin.php?page=vote-the-post/vote-the-post.php&ratepost_paged=%s">&laquo; Previous Entries</a>', get_bloginfo('wpurl'), ($paged-1)); ?>
		<?php if ($has_previous and $has_next) print(' | '); ?>
		<?php if ($has_next) printf('<a href="%s/wp-admin/admin.php?page=vote-the-post/vote-the-post.php&ratepost_paged=%s">Next Entries &raquo;</a>', get_bloginfo('wpurl'), ($paged+1)); ?>
	</p>
	<table class="widefat">
	  <thead>
		<tr>
		  <th scope="col" width="30%">Post</th>
		  <th scope="col" width="30%">Author</th>
		  <th scope="col" width="20%">Number of Votes</th>
		  <th scope="col" width="20%">Average Vote</th>
		</tr>
	  </thead>
	<tbody id="the-list">
<?php
	$row = -1;
	foreach ($post_results as $result) {
		$class = ++$row % 2 ? 'alternate' : '';
		$post = get_post($result->post_id);
		$author = get_userdata($post->post_author);

		printf('<tr class="%s"><td><a href="%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td></tr>',
			$class, get_permalink($result->post_id),
			$post->post_title,
			$author->nickname,
			$result->count,
			$result->avg_vote);
	} ?>
	</tbody>
	</table>
<?php } else { print('<p>No results found.</p>'); } ?>
</div>
<?php
}


function ratepost_options() {
	$max_votes_ip = get_option('ratepost_max_votes_ip');
	$max_votes_ip = $max_votes_ip == '' ? 25 : $max_votes_ip;
	$hide_more_info = get_option('ratepost_hide_more_info');
	$hide_more_info = empty($hide_more_info) ? false : $hide_more_info;

	$caption = 'Save Options';

	if (isset($_POST['action'])) {
		if (isset($_POST['max_votes_ip']) and is_numeric($_POST['max_votes_ip'])) {
			$max_votes_ip = (int)$_POST['max_votes_ip'] > 0 ? $_POST['max_votes_ip'] : $max_votes_ip;
			update_option('ratepost_max_votes_ip', $max_votes_ip);
		}
		$hide_more_info = isset($_POST['hide_more_info']);
		update_option('ratepost_hide_more_info', $hide_more_info);
	}
	$hide_more_info = $hide_more_info ? 'checked="checked" ' : '';
?>
<div class="wrap">
	<h2>Vote the Post Options</h2>
	<form action="" method="post">
		<fieldset class="options">
			<legend>General Options</legend>
			<table class="editform" width="100%" cellspacing="2" cellpadding="5">
				<tr>
					<th width="33%" scope="row">Max votes per IP address per post:</th>
					<td width="67%"><input type="text" value="<?php echo $max_votes_ip; ?>" name="max_votes_ip" /></td>
				</tr>
				<tr>
					<th width="33%" scope="row">Hide more information link:</th>
					<td width="67%"><input type="checkbox" <?php echo $hide_more_info; ?>name="hide_more_info" /></td>
				</tr>
			</table>
			<div class="submit"><input type="submit" name="action" value="<?php echo $caption; ?>" /></div>
		</fieldset>
	</form>
</div>
<?php
}

function ratepost_head() {
	printf('
		<link rel="stylesheet" type="text/css" href="%s/index.php?ak_action=ratepost_css" />
	', get_bloginfo('wpurl'));
?>
<!--[if lt IE 7]>
<style type="text/css">
div.ratepost div { float:left; line-height: 20px; }
div.ratepost span.rating1 div.star1,
div.ratepost span.rating2 div.star1,
div.ratepost span.rating2 div.star2,
div.ratepost span.rating3 div.star1,
div.ratepost span.rating3 div.star2,
div.ratepost span.rating3 div.star3,
div.ratepost span.rating4 div.star1,
div.ratepost span.rating4 div.star2,
div.ratepost span.rating4 div.star3,
div.ratepost span.rating4 div.star4,
div.ratepost span.rating5 div.star1,
div.ratepost span.rating5 div.star2,
div.ratepost span.rating5 div.star3,
div.ratepost span.rating5 div.star4,
div.ratepost span.rating5 div.star5 {
	background:transparent url(<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/vote-the-post/star.gif) center center no-repeat;
}

div.ratepost span.rating4 div.star5,
div.ratepost span.rating3 div.star5,
div.ratepost span.rating3 div.star4,
div.ratepost span.rating2 div.star5,
div.ratepost span.rating2 div.star4,
div.ratepost span.rating2 div.star3,
div.ratepost span.rating1 div.star5,
div.ratepost span.rating1 div.star4,
div.ratepost span.rating1 div.star3,
div.ratepost span.rating1 div.star2 {
	background:transparent url(<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/vote-the-post/star-dim.gif) center center no-repeat;
}
</style>
<![endif]-->
<?php
}
add_action('wp_head', 'ratepost_head');

function ratepost_foot() {
	printf('
		<script type="text/javascript" src="%s/index.php?ak_action=ratepost_js"></script>
	', get_bloginfo('wpurl'));
}
add_action('wp_footer', 'ratepost_foot');


if (!function_exists('wp_prototype_before_jquery')) {
	function wp_prototype_before_jquery( $js_array ) {
		if ( false === $jquery = array_search( 'jquery', $js_array ) )
			return $js_array;
	
		if ( false === $prototype = array_search( 'prototype', $js_array ) )
			return $js_array;
	
		if ( $prototype < $jquery )
			return $js_array;
	
		unset($js_array[$prototype]);
	
		array_splice( $js_array, $jquery, 0, 'prototype' );
	
		return $js_array;
	}
	
	add_filter( 'print_scripts_array', 'wp_prototype_before_jquery' );
}
wp_enqueue_script('jquery');

?>
