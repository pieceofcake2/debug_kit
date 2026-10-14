<?php
/**
 * History Panel Element
 *
 * Adapted from cakephp/debug_kit 5.x templates/element/history_panel.php.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 1.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * @var View $this
 * @var array $content
 */

if (empty($content['current'])):
?>
<p class="c-flash c-flash--warning"><?php echo __d('debug_kit', 'The request history is not available.'); ?></p>
<?php
	return;
endif;

$indexUrl = ['plugin' => 'debug_kit', 'controller' => 'panels', 'action' => 'index'];
foreach (Router::prefixes() as $prefix) {
	$indexUrl[$prefix] = false;
}
$requests = $content['requests'];
$reload = sprintf(
	'<button type="button" class="o-button js-toolbar-load-panel" data-panel="history" data-request="latest">%s</button>',
	__d('debug_kit', 'Reload')
);
$item = function (array $request) use ($indexUrl) {
	$out = sprintf(
		'<a class="c-history-panel__link" data-request="%s" href="%s">',
		h($request['id']),
		h(Router::url(array_merge($indexUrl, [$request['id']])))
	);
	$out .= sprintf('<span class="c-history-panel__time">%s</span>', h(substr((string)$request['requested_at'], 0, 19)));
	foreach (['method', 'status_code', 'content_type'] as $field) {
		$out .= sprintf(' <span class="c-history-panel__bubble">%s</span>', h($request[$field]));
	}
	$out .= sprintf(' <span class="c-history-panel__url">%s</span>', h($request['url']));

	return $out . '</a>';
};
?>
<div id="request-history" class="c-history-panel">
	<?php if (empty($requests)): ?>
		<p class="c-flash c-flash--warning">
			<?php echo __d('debug_kit', 'No previous requests logged.'); ?>
			<?php echo $reload; ?>
		</p>
	<?php else: ?>
		<p>
			<?php echo __d('debug_kit', '%d previous requests available', count($requests)); ?>
			<?php echo $reload; ?>
		</p>
		<ul class="c-history-panel__list">
			<li><?php echo $item($content['current']); ?></li>
			<?php foreach ($requests as $request): ?>
				<li><?php echo $item($request); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<script type="text/html" id="list-template">
		<ul class="c-history-panel__list">
			<li><?php echo $item($content['current']); ?></li>
		</ul>
	</script>
	<script type="text/html" id="list-item-template">
		<li>
			<a class="c-history-panel__link" data-request="{id}" href="<?php echo h(str_replace('__ID__', '{id}', Router::url(array_merge($indexUrl, ['__ID__'])))); ?>">
				<span class="c-history-panel__time">{time}</span>
				<span class="c-history-panel__bubble c-history-panel__xhr">XHR</span>
				<span class="c-history-panel__bubble">{method}</span>
				<span class="c-history-panel__bubble">{status}</span>
				<span class="c-history-panel__bubble">{type}</span>
				<span class="c-history-panel__url">{url}</span>
			</a>
		</li>
	</script>
</div>
