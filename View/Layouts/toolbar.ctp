<?php
/**
 * Toolbar layout, adapted from cakephp/debug_kit 5.x templates/layout/toolbar.php.
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
 */
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php echo isset($title) ? h($title) : 'Debug Kit Toolbar'; ?></title>
	<?php
	echo $this->Html->css(['DebugKit./css/reset', 'DebugKit./css/style']);
	echo $this->fetch('css');
	?>
</head>
<body>
	<?php echo $this->fetch('content'); ?>
	<div class="o-loader">
		<?php echo $this->Html->image('DebugKit./img/cake.icon.png', ['class' => 'o-loader__indicator', 'alt' => '']); ?>
	</div>
	<?php echo $this->fetch('script'); ?>
</body>
</html>
