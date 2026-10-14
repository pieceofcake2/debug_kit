<?php
/**
 * Panel content, adapted from cakephp/debug_kit 5.x templates/Panels/view.php.
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
 * @var array $panel
 */
?>
<h2 class="c-panel__title"><?php echo h($panel['title']); ?></h2>
<div class="c-panel__content">
	<div class="panel-content panel-content-data" id="<?php echo h($panel['panel']); ?>-tab" data-request-id="<?php echo h($panel['request_id']); ?>">
		<?php echo $panel['content']; ?>
	</div>
</div>
