<?php
/**
 * Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

$_actionLinks = array();
if (!empty($displayUrlToComment)) {
	$_actionLinks[] = sprintf('<a href="%s">%s</a>', $urlToComment . '/' . $comment[$assocName]['slug'], __d('comments', 'View'));
}

if (!empty($allowAddByAuth)) {
	$_actionLinks[] = $commentWidget->link(__d('comments', 'Reply'), array_merge($url, array('comment' => $comment[$assocName]['id'], '#' => 'comment' . $comment[$assocName]['id'])));
	$_actionLinks[] = $commentWidget->link(__d('comments', 'Quote'), array_merge($url, array('comment' => $comment[$assocName]['id'], 'quote' => 1, '#' => 'comment' . $comment[$assocName]['id'])));
	if (!empty($isAdmin)) {
		if (empty($comment[$assocName]['approved'])) {
			$_actionLinks[] = $commentWidget->link(__d('comments', 'Publish'), array_merge($url, array('comment' => $comment[$assocName]['id'], 'comment_action' => 'toggleApprove', '#' => 'comment' . $comment['id'])));
		} else {
			$_actionLinks[] = $commentWidget->link(__d('comments', 'Unpublish'), array_merge($url, array('comment' => $comment[$assocName]['id'], 'comment_action' => 'toggleApprove', '#' => 'comment' . $comment[$assocName]['id'])));
		}
	}
}

$_userLink = $comment[$userModel]['username'];
?>
<div class="comment">
	<div class="header">
		<strong><a name="comment<?php echo $comment[$assocName]['id'];?>"><?php echo $comment[$assocName]['title'];?></a></strong>
		<span style="float: right"><?php echo join('&nbsp;', $_actionLinks);?></span>
		<br/>
		<span class="byTime"><?php echo $_userLink; ?> <?php echo __d('comments', 'posted'); ?> <?php echo $time->timeAgoInWords($comment[$assocName]['created']); ?></span>
	</div>
	<div class="body">
		<?php echo $cleaner->bbcode2js($comment[$assocName]['body']);?>
	</div>
</div>