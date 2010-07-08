<?php
/* 
 *  ====== The Article Importer ======
 *  This enables you to publish Guardian articles directly to your blog.
 *
 *  Here are some of the things it does:
 *       - Browse and search for articles to publish
 *       - Post articles (or 'Save to Drafts') directly from your 'Posts' admin panel
 *       - Automatic check-in and replace to make sure you have published the most current version 
 *         of the article
 *
 *  When publishing articles from the Guardian, please adhere to our publishing guidelines, and be 
 *  aware of the Terms and Conditions.
 *  
 *  Guidelines appear in the plug-in admin panel and the T&Cs are on our web site, but here are some reminders:
 *       - Changes. You mustn't remove or alter the text, links or images you get from us.
 *       - Key. If you don't have a key, get one here: http://www.guardian.co.uk/open-platform. It's required. 
 *         If you do have one, please don't share it or use it anywhere else.
 *       - Ads. Articles come with ads and performance tracking embedded in them. As above, you mustn't 
 *         change or remove them. You can, of course, use your own ads elsewhere on your blog, too.
 *       - Deletions. Sometimes but very rarely we have to remove articles. When that happens, this plug-in 
 *         will replace the Guardian content within your blog post with a message saying that the content is 
 *         not available anymore.
 *         
 *  ======= Notes =======
 *  This plug-in is designed to be used as is. We have several ways of working with partners if you want 
 *  to do something different. Find out more here:
 *
 *  http://www.guardian.co.uk/open-platform
 *
 *  If you have ideas on how to improve the plug-in or other things we could do with WordPress, 
 *  please join the conversation here:
 *
 *  http://groups.google.com/group/guardian-api-talk
 *
 *  Kind Regards,
 *  Daniel Levitt for Guardian News and Media
 *  daniel.levitt@guardian.co.uk
 *  Guardian News & Media Ltd
 *  
 */

	/**
     * Displays the admin page
     * 
     * Features: Table for browsing the api, filter the results using tags, links to publish
     *
     */
    function Guardian_ContentAPI_admin_page() {
    	?>
    	<div class="wrap">
	    	<h2>The Guardian News Feed</h2>
	    	
    		<?php 
    		
    		$publishing_guidelines = Guardian_Dynamic_Text();
	    	?>
	    	
		    <?php 
	    	$api = new GuardianOpenPlatformAPI();
	    	
	    	$str_api_key = get_option ( 'guardian_api_key' );
	    		    	
	    	$api = new GuardianOpenPlatformAPI($str_api_key);
    	
	    	$options = array(
	    		'q' => $_GET['s'],
	    		'tag' => $_GET['tag'],
	    		'section' => $_GET['section'],
	    		'format' => 'json',
	    		'page' => $_GET['p'],
	    		'show-fields' => 'headline,standfirst,trail-text,thumbnail',
	    		'show-refinements' => 'all'
	    	);
	    	$articles = $api->guardian_api_search($options);
	    	
	    	guardian_can_user_publish($articles['userTier']);
	    	
	    	if (!empty($_GET ['contentid'])) {
	    		$message = Guardian_ContentAPI_add_item( $_GET ['contentid'] );
	    		if (!empty($message)) {
	    			echo $message;	
	    		}
	    	}
	    	?>
    		<p>Want news? Find something you like below, click 'Save to Drafts' and then publish away.</p>
	    	
	    	<form action="<?= $_SERVER['PHP_SELF'] ?>" method="get" id="search-plugins">
	    		
	    		<input type="text" value="<?= $_GET['s'] ?>" name="s" size="50">
	    		<label for="plugin-search-input" class="screen-reader-text">Search</label>
	    		<input type="hidden" name="tag" value="<?= $_GET['tag'] ?>"></input>
	    		<input type="hidden" name="section" value="<?= $_GET['section'] ?>"></input>
	    		<input type="hidden" name="page" value="<?= $_GET['page'] ?>"></input>
	    		<input type="submit" class="button" value="Search Articles">
	    		
	    	</form>
	    	
	    	
	    	<?php 
	    	
	    	$headfoot = array();
	    	$headfoot[] = "<th class=\"thumb\" scope=\"col\">Thumbnail</th>";
	    	$headfoot[] = "<th class=\"name\" scope=\"col\">Headline</th>";
	    	$headfoot[] = "<th class=\"num\" scope=\"col\">Published</th>";
	    	$headfoot[] = "<th class=\"desc\" scope=\"col\">Description</th>";
	    	$headfoot[] = "<th class=\"action-links\" scope=\"col\">Actions</th>";
	    	
	    	$headfoot = implode("\n", $headfoot);
	    	
	    	$link = "{$_SERVER['PHP_SELF']}?page={$_GET['page']}&s={$_GET['s']}&tag={$_GET['tag']}&section={$_GET['section']}";
	    	echo render_pagination( $articles['currentPage'], $articles['pages'], $articles['total'], $link, $articles['startIndex'], $articles['startIndex']+count($articles['results'])-1 ); ?>
	    	<hr />
    	
	    	<div id="poststuff" class="metaebox-holder has-right-sidebar">
	    		<div id="side-info-column" class="inner-sidebar">
    				<div id="side-sortables" class="meta-box-sortables ui-sortable">
			    		<?= render_publishing_message($publishing_guidelines); ?>
				    	<?= render_refinements($articles); ?>
				    </div>
				</div>
			</div>
	    
	        
	    	<div id="post-body-content">
		    	<table cellspacing="0" id="install-plugins" class="widefat" style="clear:none;">
		    		<thead>
		    			<tr>
		    				<?= $headfoot; ?>
		    			</tr>
		    		</thead>
		    
		    		<tfoot>
		    			<tr>
		    				<?= $headfoot; ?>
		    			</tr>
		    		</tfoot>
		    
		    		<tbody class="plugins">
		    		<?= render_contentapi_search($articles);	?>
		    		</tbody>
		    	</table>
		    	<br/>
		    	<?= render_simple_pagination($articles['currentPage'], $articles['pages'], $link) ?>
		    </div>
		
		</div>
        <?php
        	
    }
    
    /**
     * Calls the Guardian API passing the ID of the article you need
     *
     * @param $str_item_id				String of the ID.
     */
    function Guardian_ContentAPI_add_item($str_item_id) {
    	
    	$message = array(); // The message that we are going to return at the end of the function.
    	
    	$api = new GuardianOpenPlatformAPI();
    	
    	$str_api_key = get_option ( $api->guardian_api_keynameValue() );
    	
    	$api = new GuardianOpenPlatformAPI($str_api_key);
    	$article = $api->guardian_api_item($str_item_id);
    	
    	$tier = $api->guardian_get_tier();
    	
    	if ($tier == 'Free') { 
    		$message[] = "<div class=\"error\">";
		$message[] = "	<p>You are in <strong>preview mode</strong>.  This plugin requires an access key in order to publish articles from the Guardian. To get your access key <a href=\"http://guardian.mashery.com/\">click here</a> and go through the registration process.  It's pretty quick and painless.</p>";
    		$message[] = "</div>";
    		return implode("\n", $message);
    	}
    	
    	if (empty($article)) { 
    	    $message[] = "<div class=\"error\">";
    		$message[] = "	<p>Hmmm, we're experiencing an unknown error. Please try again.</p>";
    		$message[] = "</div>";
    		return implode("\n", $message);
    	}
    	
    	if ($article [fields] [body] == "<!-- Redistribution rights for this field are unavailable -->") { 
    		$message[] = "<div class=\"error\">";
    		$message[] = "	<p>We are very sorry, but that particular article is not available for redistribution.</p>";
    		$message[] = "</div>";
    		return implode("\n", $message);
    	}
    	
    	$already_exist = Guardian_Published_Already($str_item_id);
    	
    	if (empty($already_exist)) { // Check if the article has already been saved, to stop reposting twice
    		
    		// Get the tags
    		$tags = $article ['tags'];
    		$tagarray = array();
    		foreach ($tags as $tag) {
    			$tag = trim($tag['webTitle']);
    			if (!empty($tag)) {
    				$tagarray[] = $tag;
    			}
    		}
    		$tagarray = implode(', ', $tagarray);
    		
    		$postcontent = "<p><em><strong>PLEASE NOTE</strong>: Add your own commentary here above the horizontal line, but do not make any changes below the line.  (Of course, you should also delete this text before you publish this post.)</em></p>\n\n";
    		
        	$postcontent .= "<hr /><!-- GUARDIAN WATERMARK -->";
    		
        	$postcontent .= "<p><img class=\"alignright\" src=\"http://image.guardian.co.uk/sys-images/Guardian/Pix/pictures/2010/03/01/poweredbyguardian".get_option ( 'guardian_powered_image' ).".png\" alt=\"Powered by Guardian.co.uk\" width=\"140\" height=\"45\" />";
        	$postcontent .= "<a href=\"{$article ['fields'] ['shortUrl']}\">This article was written by {$article ['fields'] ['byline']}, for {$article ['fields'] ['publication']} on ".date("l jS F Y H.i e", strtotime($article ['webPublicationDate']))."</a></p>";
        	
    		if (!empty($article['mediaAssets'])) {
        		foreach ($article['mediaAssets'] as $media) {
        			if ($media['type'] == 'picture') {
        				$postcontent .= "<img src=\"{$media['file']}\" class=\"aligncenter\" alt=\"{$media['fields']['caption']}\">";
        			}
        		}
        	}
        	
        	$postcontent .= $article ['fields'] ['body'];
        	$postcontent .= "<p>guardian.co.uk &#169; Guardian News and Media Limited 2010</p>";
        	$postcontent .= "<!-- END GUARDIAN WATERMARK -->";
        	        	
    		$data = array(
        		'ID' => null,
        		'post_content' => $postcontent,
        		'post_title' => $article ['fields'] ['headline'],
        		'post_excerpt' => $article ['fields'] ['standfirst'],
    			'tags_input' => $tagarray
        	);
        	
        	$guardian_post_id = wp_insert_post( $data );
        	update_post_meta($guardian_post_id, 'guardian_content_api_id', $article ['id']); 
        	// Add the Content API id to post_meta
        	
        	$message[] = "<div class=\"updated\">";
        	$message[] = "	<p><strong>Ready to publish:</strong>  <em>\"{$data['post_title']}\"</em> was successfully saved in <strong><a href=\"".admin_url("edit.php?post_status=draft")."\">Draft Mode</a></strong>. Now you can <strong><a href=\"".admin_url("post.php?action=edit&post={$guardian_post_id}")."\">edit and publish</a></strong> your blog post.</p>";
        	$message[] = "	<p></p><p><em><strong>Note:</strong> Have you read the publishing guidelines, yet?  There are some important reminders to keep in mind.  See them in the box on the right of this admin panel.</em></p>";

        	$message[] = "</div>";
    	} else {
    		$message[] = "<div class=\"error\">";
    		$message[] = "	<p>That article has already been downloaded to your blog. You may need to delete it permanently from the <strong><a href=\"".admin_url("edit.php?post_status=trash")."\">Trash</a></strong>.</p>";
    		$message[] = "</div>";
    	}
    	return implode("\n", $message);
    }
    
    /**
     * Query to see if the article has already been posted from the API before.
     *
     * @param $str_item_id				String of the ID.
     */
    function Guardian_Published_Already ($str_item_id) {
    	global $wpdb;
    	$article_exist = $wpdb->get_row("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'guardian_content_api_id' AND meta_value = '{$str_item_id}' LIMIT 1");
    	return $article_exist;
    }
    
    /*
     * Function to retrieve the publishing guidelines as well as display updates about the API.
     */
    function Guardian_Dynamic_Text() {
    	$file = wp_remote_retrieve_body( wp_remote_get('http://static.guim.co.uk/openplatform/wp-plugin/notices.txt') );
    	$file_arr = explode('=====', $file);
    	
    	$version = trim($file_arr[0]);
    	
    	$publishing_guidelines = $file_arr[1];
    	$error_message = $file_arr[2];
    	$got_latest_message = $file_arr[3];
    	$update_message = str_replace('{plugins_url}', admin_url("plugins.php"), $file_arr[4]);
    	
    	if ($version == get_option('GUARDIAN_NEWS_FEED_VERSION')) {
    		echo "<div class=\"updated\">{$got_latest_message}</div>";
    	} else {
	    	if (!empty($error_message)) {
	    		echo "<div class=\"error\">{$error_message}</div>";
	    	}
	    	echo "<div class=\"updated\">{$update_message}</div>";
    	}
    	
    	return $publishing_guidelines;
    }
    
    /**
     * Send a message to the user if their uset tier is not sufficient enough
     *
     * @param $tier				String of tier.
     */
    function guardian_can_user_publish( $tier = '' ) {
    	$api_request_link = "http://guardian.mashery.com/";
    	
    	if ( empty($tier) || $tier == 'free') {
	    	$error = new WP_Error('error', __("<div class=\"error\"><p>You are in <strong>preview mode</strong>.  This plugin requires an access key in order to publish articles from the Guardian. To get your access key <a href=\"{$api_request_link}\">click here</a> and go through the registration process.  It's pretty quick and painless.</p></strong></div>"));
	    	echo $error->get_error_message();
    	}
    }
    
    /**
     * This is the function that wraps the API content in html for use in the admin table.
     *
     * @param $arr_related_content				Array of content from the API
     */
    function render_contentapi_search($arr_related_content) {
    	
    	$arr_html_output = array ();
    	
    	foreach ( $arr_related_content['results'] as $related_content ) {
    		
    		$description = $related_content ['fields'] ['standfirst'];
    		if (empty($description)) {
    			$description = $related_content ['fields'] ['trailText'];
    		}
    		$image = '';
    		if (!empty($related_content ['fields'] ['thumbnail'])) {
    			$image = "<img src=\"{$related_content ['fields'] ['thumbnail']}\" width=\"140\" height=\"84\" style=\"padding:5px 0;\" alt=\"{$related_content ['fields'] ['headline']}\">";
    		}
    		$link = "{$_SERVER['PHP_SELF']}?page={$_GET['page']}&s={$_GET['s']}&tag={$_GET['tag']}&section={$_GET['section']}&contentid={$related_content ['id']}";
    		
    		$arr_html_output [] = "		<tr>";
    		$arr_html_output [] = "			<td class=\"thumb\">{$image}</td>";
    		$arr_html_output [] = "			<td class=\"name\"><a href=\"{$related_content ['webUrl']}\" alt=\"{$related_content ['fields'] ['headline']}\" title=\"{$related_content ['fields'] ['headline']}\" target=\"_blank\">{$related_content ['fields'] ['headline']}</a></td>";
    		$arr_html_output [] = "			<td class=\"vers\">".date("j/m/Y", strtotime($related_content ['webPublicationDate']))."</td>";
    		$arr_html_output [] = "			<td class=\"desc\">{$description}</td>";
    		$arr_html_output [] = "			<td class=\"action-links\">";
    		$arr_html_output [] = "			<a href=\"{$link}\" alt=\"Save to Drafts\">Save to Drafts</a></td>";
    		$arr_html_output [] = "		</tr>";
    	}
    	
    	$arr_html_output [] = "	</ul>";
    	$arr_html_output [] = "</div>";
    	
    	return implode ( "\n", $arr_html_output );
    }
    
    
    
    /**
     * Renders a more simple pagination, i.e previous and next
     * 
     * @param $current_page		int
     * @param $total_pages		int
     * @param $filename			str
     */
    function render_simple_pagination( $current_page = 1, $total_pages, $filename ) {
    	
    	$pagination = array();
    	
    	if ($total_pages > 1) {
    		
    		if ($current_page <= $total_pages && $current_page != 1) {
    			$previous = $current_page-1;
    			$pagination[] = "	<a href=\"{$filename}&p={$previous}\" class=\"button\" alt=\"Page number {$previous}\"> << Previous </a>";
    		}
    		if ($current_page != $total_pages) {
    			$next = $current_page+1;
    			$pagination[] = "	<a href=\"{$filename}&p={$next}\" class=\"button\" style=\"float:right\" alt=\"Page number {$next}\"> Next >> </a>";
    		}
    		
    	}
    	return implode("\n", $pagination);
    }
    
    /**
     * Renders the Pagination
     * 
     * @param $current_page		int
     * @param $total_pages		int
     * @param $total_items		int
     * @param $filename			str
     */
    function render_pagination( $current_page = 1, $total_pages, $total_items, $filename, $startItem, $endItem ) {
    	
    	$pagination = array();
    	
    	$pagination[] = "<div class=\"tablenav\">";
    	
    	$pagination[] = "	<div class=\"alignleft actions\">";
    	if ($total_pages == 0) {
    		$pagination[] = "	<p class=\"displaying-num\"><strong>There are no results available for your search.  Please try again.</strong></p>";
    	} else {
    		$wording = "page";
    		if ($total_pages > 1) {
    			$wording .= "s";
    		}
	    	$pagination[] = "	<p class=\"displaying-num\">Displaying ".number_format($startItem)." to ".number_format($endItem)." of ".number_format($total_items)." matches.</p>";
    	}
    	$pagination[] = "	</div>";
    		
    	if ($total_pages > 1) {
    		
    		$pagination[] = "	<div class=\"tablenav-pages\">";
    		$pagination[] = "	<span class=\"displaying-num\">Go to page:</span> ";
    		 
    		$swing = 5; // How many numbers either side of the current page we should display
    		 
    		$p = $current_page;
    		$lp = $current_page-$swing;
    		$hp = $current_page+$swing;
    		 
    		while ( ($lp < $total_pages+1) && ($lp <= $hp) ) {
    			if ( ($lp > 0) ) {
    				if ($lp == $p) {
    					$pagination[] = "	<span class=\"page-numbers current\">".number_format($lp)."</span> ";
    				} else {
    					$pagination[] = "	<a href=\"{$filename}&p={$lp}\" class=\"page-numbers\" alt=\"Page number ".number_format($lp)."\">".number_format($lp)."</a> ";
    				}
    			}
    			$lp++;
    		}
    		if ( ($lp-1) != $total_pages) {    			
    			$pagination[] = " ...		<a href=\"{$filename}&p={$total_pages}\" class=\"page-numbers\" alt=\"Page number ".number_format($total_pages)."\">".number_format($total_pages)."</a> ";
    		}
    		
    		$pagination[] = "	</div>";
    	}
    	$pagination[] = "</div>";
    	return implode("\n", $pagination);
    }
    
    
    /*
     * This function renders the filter results sidebar on the right hand side.
     * 
     * @array $articles			We specifically only need $articles[refinementGroups]
     */
    function render_refinements($articles) {
    	
    	$output = array();
    	
    	if (!empty($articles['refinementGroups'])) {

    		$output[] = "	<div class=\"postbox\">";
	        $output[] = "		<h3 class=\"hndle\"><span>Filter your results</span></h3>";
	        
	        if (!empty($_GET['tag'])) {
	        	
	        	$output[] = "		<div class=\"misc-pub-section\">";	        	
	        	$output[] = "			<p>Selected Tags:</p>";
	        	$output[] = "			<div class=\"tagchecklist\">";
	        	
	        	$tags = explode(',', $_GET['tag'] );
	        	
	        	$tags = array_filter(array_unique($tags));
	        	
	        	foreach ($tags as $tag) {
	        		
	        		$tagLink = str_replace(",".$tag, "", ",".$_GET['tag']);
	        		
	        		if ($tagLink[0] == ',') {
	        			$tagLink = substr($tagLink, 1);
	        		}
	        		if ($tagLink[strlen($tagLink)-1] == ',') {
	        			$tagLink = substr($tagLink, 0, -1);
	        		}
	        		$link = "{$_SERVER['PHP_SELF']}?page={$_GET['page']}&s={$_GET['s']}&section={$_GET['section']}&tag={$tagLink}";
	        		$output[] = "				<span><a class=\"ntdelbutton\" href=\"{$link}\" id=\"post_tag-check-num-0\">X</a>&nbsp;{$tag}</span>";
	        	}
	        	$output[] = "			</div>";
	        	$output[] = "		</div>";
	        }
	        
    		if (!empty($_GET['s'])) {
    			$link = "{$_SERVER['PHP_SELF']}?page={$_GET['page']}&s=&tag={$_GET['tag']}&section={$_GET['section']}";
    			$output[] = "		<div class=\"misc-pub-section\">";
    			$output[] = "			<p>Current Search Term:</p>";
	        	$output[] = "			<div class=\"tagchecklist\">";
        		$output[] = "				<span><a class=\"ntdelbutton\" href=\"{$link}\" id=\"post_tag-check-num-0\">X</a>&nbsp;{$_GET['s']}</span>";
	        	$output[] = "			</div>";
	        	$output[] = "		</div>";
	        }
	        
	        if (!empty($_GET['section'])) {
    			$link = "{$_SERVER['PHP_SELF']}?page={$_GET['page']}&s={$_GET['s']}&tag={$_GET['tag']}&section=";
    			$output[] = "		<div class=\"misc-pub-section\">";
    			$output[] = "			<p>Selected Section:</p>";
	        	$output[] = "			<div class=\"tagchecklist\">";
        		$output[] = "				<span><a class=\"ntdelbutton\" href=\"{$link}\" id=\"post_tag-check-num-0\">X</a>&nbsp;{$_GET['section']}</span>";
	        	$output[] = "			</div>";
	        	$output[] = "		</div>";
	        }
	        
        	foreach ($articles['refinementGroups'] as $refinementsGroup) {
        		
        		$output[] = "		<div class=\"misc-pub-section\">";
	        	
	        	$output[] = "		<p><strong>".ucfirst($refinementsGroup['type'])."</strong></p>";
	        	foreach ($refinementsGroup['refinements'] as $refinementItem) {
	        		
	        		if (!preg_match('#/#', $refinementItem['id'] )) {
	        			if ($_GET['section']) {
		        			$sectionLink .= $_GET['section'].",".$refinementItem['id'];
		        		} else {
		        			$sectionLink = $refinementItem['id'];
		        		}
		        		$link = "{$_SERVER['PHP_SELF']}?page={$_GET['page']}&s={$_GET['s']}&tag={$_GET['tag']}&section={$sectionLink}";
		        		$output[] = "		<p><a href=\"{$link}\">{$refinementItem['displayName']}</a> (".number_format($refinementItem['count']).")</p>";
		        		$sectionLink = '';
	        		} else {
	        			if ($_GET['tag']) {
		        			$tagLink .= $_GET['tag'].",".$refinementItem['id'];
		        		} else {
		        			$tagLink = $refinementItem['id'];
		        		}
		        		$link = "{$_SERVER['PHP_SELF']}?page={$_GET['page']}&s={$_GET['s']}&tag={$tagLink}&section={$_GET['section']}";
		        		$output[] = "		<p><a href=\"{$link}\">{$refinementItem['displayName']}</a> (".number_format($refinementItem['count']).")</p>";
		        		$tagLink = '';
	        		}
	        	}
	        	
	        	$output[] = "		</div>";
	        }
        } 
        return implode( "\n", $output );
    }
    
    /*
     * This function renders the user message on the right hand side.
     * 
     */
    function render_publishing_message($publishing_guidelines) {
    	
    	$output[] = "		<div id=\"submitdiv\" class=\"postbox\">";
		$output[] = "			<h3 class=\"hndle\" ><span>Publishing Guidelines</span></h3>";
		$output[] = "			<div class=\"misc-pub-section\">";
		$output[] = "				{$publishing_guidelines}";
		$output[] = "			</div>";
		$output[] = "		</div>";
		
		return implode( "\n", $output );
    }
    
    // These are the Wordpress bits that tie everything together
    function Guardian_ContentAPI_add_pages() {
    	global $wpdb;
    	if (function_exists ( "add_submenu_page" )) {
    		add_submenu_page ( "post.php", __ ( "Guardian News Feed" ), __ ( "Guardian News Feed" ), 2, __FILE__, "Guardian_ContentAPI_admin_page" );
    	}
    }
    
    // Plugin admin menus
    add_action ( "admin_menu", "Guardian_ContentAPI_add_pages" );

?>