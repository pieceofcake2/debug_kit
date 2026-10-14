<?php
/**
 * Toolbar of a recorded request, adapted from cakephp/debug_kit 5.x templates/Requests/view.php.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * @var View $this
 * @var array $toolbar
 */

$noPrefix = [];
foreach (Router::prefixes() as $prefix) {
	$noPrefix[$prefix] = false;
}
?>
<div class="c-panel-content-container js-panel-content-container">
	<span class="c-panel-content-container__close js-panel-close" title="<?php echo __d('debug_kit', 'Close'); ?>"></span>
	<div class="c-panel-content-container__content">
		<!-- content here -->
	</div>
</div>

<ul id="toolbar" class="c-toolbar">
	<li class="c-toolbar__scroll-button c-toolbar__scroll-button--left js-toolbar-scroll-left">
		<span class="c-panel__button">&#x3008;</span>
	</li>
	<li class="c-toolbar__scroll-button c-toolbar__scroll-button--right js-toolbar-scroll-right">
		<span class="c-panel__button">&#x3009;</span>
	</li>
	<li class="c-toolbar__inner-wrapper">
		<ul class="c-toolbar__inner-ul">
		<?php foreach ($toolbar['panels'] as $panel): ?>
			<li class="c-panel js-panel-button is-hidden" data-panel="<?php echo h($panel['panel']); ?>">
				<span class="c-panel__button"><?php echo h($panel['title']); ?></span>
				<?php if ((string)$panel['summary'] !== ''): ?>
				<span class="c-panel__summary"><?php echo h($panel['summary']); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
		</ul>
	</li>
	<li class="c-toolbar__button js-toolbar-toggle">
		<?php echo $this->Html->image('DebugKit./img/cake.icon.png', [
			'alt' => 'Debug Kit',
			'title' => 'CakePHP ' . Configure::version() . ' Debug Kit',
		]); ?>
	</li>
</ul>
<?php
$assets = $toolbar['assets'] + ['css' => [], 'javascript' => []];
if ($assets['css']) {
	$this->Html->css($assets['css'], ['inline' => false]);
}
$this->Html->script('DebugKit./js/jquery', ['inline' => false]);
$this->Html->script('DebugKit./js/legacy', ['inline' => false]);
foreach ($assets['javascript'] as $script) {
	if ($script) {
		$this->Html->script($script, ['inline' => false]);
	}
}
// HtmlHelper::script() always renders type="text/javascript", build the module script tag by hand.
$attributes = [
	'id' => '__debug_kit_app',
	'type' => 'module',
	'src' => $this->Html->assetUrl('DebugKit./js/main.js', ['pathPrefix' => JS_URL]),
	'data-id' => $toolbar['id'],
	'data-url' => Router::url('/', true),
	'data-webroot' => $this->request->webroot,
	'data-panel-url' => Router::url(['plugin' => 'debug_kit', 'controller' => 'panels', 'action' => 'view'] + $noPrefix),
	'data-panels-url' => Router::url(['plugin' => 'debug_kit', 'controller' => 'panels', 'action' => 'index'] + $noPrefix),
];
$tag = '<script';
foreach ($attributes as $name => $value) {
	$tag .= sprintf(' %s="%s"', $name, h($value));
}
$this->append('script', $tag . '></script>');
