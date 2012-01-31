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

$pager = $this->Paginator;
if ($commentWidget->globalParams['target']) {
	$pager->options(array_merge(
		array('url' => $commentWidget->prepareUrl($url)),
		$commentWidget->globalParams['ajaxOptions']));
} else {
	$pager->options(array('url' => $url));
}
$paging = $pager->params($assocName);
?>

<?php if (!empty(${$viewComments})): ?>
	<div class="paging">
		<?php echo $pager->prev('<< '.__d('comments', 'Most Recent'), array(), null, array('class'=>'disabled'));?>
	 | 	<?php echo $pager->numbers();?>
		<?php echo $pager->next(__d('comments', 'Oldest').' >>', array(), null, array('class'=>'disabled'));?>
	</div>
<?php endif; ?>
