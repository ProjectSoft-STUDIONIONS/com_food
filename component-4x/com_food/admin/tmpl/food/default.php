<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_food
 *
 * @copyright   Copyright (C) 2008 ProjectSoft. All rights reserved.
 * @license     MIT Lecense; see LICENSE
 */

use Joomla\CMS\Language\Text;
// Нет прямого доступа к этому файлу
defined('_JEXEC') or die('Нет доступа');
$max_count_files = ini_get("max_file_uploads");
?>
<script type="text/javascript">
	window.MAX_COUNT_FILE = <?= ini_get("max_file_uploads");?>;
	window.J_LANG = "<?= $this->stats['lang']?>";
</script>
<h1><?= Text::_('COM_FOOD_TITLE'); ?></h1>
<div class="clearfix">
	<?php if($this->stats["dir"]): ?>
	<form class="text-right" name="upload" method="post" action="index.php?option=com_food&dir=<?= $this->stats["dir"];?>" enctype="multipart/form-data">
		<input type="hidden" name="mode" value="upload">
		<div id="uploader" class="text-right">
			<label class="btn btn-secondary text-uppercase">
				<i class="glyphicon glyphicon-floppy-save"></i> <?= Text::_("COM_FOOD_SELECT_FILES");?>
				<input type="file" name="userfiles[]" onchange="uploadFiles(this);" multiple accept=".xlsx,.pdf" max="<?= $max_count_files; ?>">
			</label>
			<p id="p_uploads" class="alert alert-info"></p>
			<button class="btn btn-secondary text-uppercase" type="button" onclick="document.upload.submit()"><i class="glyphicon glyphicon-cloud-upload"></i> <?= Text::_("COM_FOOD_UPLOAD_FILES");?></button>
		</div>
	</form>
	<form class="hidden" name="form_mode" method="post" action="index.php?option=com_food&dir=<?= $this->stats["dir"];?>" enctype="multipart/form-data">
		<input type="hidden" name="mode">
		<input type="hidden" name="file">
		<input type="hidden" name="new_file">
	</form>
	<?php endif; ?>
</div>
<div class="folder-title">
	<?php if($this->stats["dir"]): ?>
	<h3><?= Text::sprintf('COM_FOOD_DIR', $this->stats["dir"]); ?> <a href="/<?= $this->stats["dir"]; ?>/" target="_blank"></a></h3>
	<p class="food-title-root"><i class="glyphicon glyphicon-folder-open"></i>&nbsp;<a href="index.php?option=com_food"><?= Text::_('COM_FOOD_DIR_TOP'); ?></a></p>
	<?php else: ?>
	<h3><?= Text::_('COM_FOOD_DIR_ROOT'); ?></h3>
	<?php endif; ?>
</div>
<div class="food-table">
	<div class="table-responsive">
		<table class="table table-bordered table-hover table-food">
			<thead>
				<tr>
				<?php if($this->stats["dir"]):?>
					<th><?= Text::_('COM_FOOD_TABLE_NAME'); ?></th>
					<th style="width: 1%;" class="nowrap"><?= Text::_('COM_FOOD_TABLE_PERMISION'); ?></th>
					<th style="width: 1%;" class="nowrap"><?= Text::_('COM_FOOD_TABLE_CHANGE'); ?></th>
					<th style="width: 1%;" class="nowrap"><?= Text::_('COM_FOOD_TABLE_SIZE'); ?></th>
					<th style="width: 1%;"><?= Text::_('COM_FOOD_TABLE_PARAMETERS'); ?></th>
				<?php else: ?>
					<th class="nowrap" colspan="5"><?= Text::_('COM_FOOD_TABLE_TITLE_NOT_FOUND'); ?></th>
				<?php endif; ?>
				</tr>
			</thead>
			<tbody>
			<?php if($this->stats["files"] && $this->stats["dir"]):?>
				<?php foreach ($this->stats["files"] as $key => $value):
					$tmp_file = $this->realPath(JPATH_ROOT) . "/" . $this->stats["dir"] . "/" . $value;
					$ltime = $this->toDateFormat(filemtime($tmp_file));
					$size = $this->getSize($tmp_file);
					$perms = substr(sprintf('%o', fileperms($tmp_file)), -4);
				?>
				<tr>
					<td><i class="glyphicon glyphicon-file"></i>&nbsp;<a href="/<?= $this->stats["dir"] . "/" . $value; ?>" target="_blank"><?= $value; ?></a></td>
					<td><?= $perms; ?></td>
					<td><?= $ltime; ?></td>
					<td><?= $size; ?></td>
					<td><!-- Переименовать, Удалить -->
						<div class="flex">
							<i class="btn btn-secondary glyphicon glyphicon-edit" data-mode="rename" data-file="<?= $value; ?>" title="<?= Text::sprintf('COM_FOOD_RENAME', $value);?>" onclick="modeFile(this);"></i>
							<i class="btn btn-secondary glyphicon glyphicon-trash" data-mode="delete" data-file="<?= $value; ?>" title="<?= Text::sprintf('COM_FOOD_DELETE', $value);?>" onclick="modeFile(this);"></i>
							<span>-</span>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<?php if($this->stats["dir"]):?>
				<tr>
					<td class="nowrap"><?= Text::_('COM_FOOD_TABLE_NOT_FOUND'); ?></td>
					<td class="nowrap">-</td>
					<td class="nowrap">-</td>
					<td class="nowrap">-</td>
					<td class="nowrap">-</td>
				</tr>
				<?php else: ?>
					<?php foreach($this->stats["folders"] as $key => $value): ?>
				<tr>
					<td class="nowrap" colspan="4"><i class="glyphicon glyphicon-folder-open"></i>&nbsp;<a href="index.php?option=com_food&dir=<?= $value; ?>"><?= $value; ?></a></td>
					<td style="width: 1%;" class="nowrap"><a href="/<?= $value; ?>/" target="_blank"></a></td>
				</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
<?php
/**
 * Версионность файлов
 * Время последнего изменения файлов
 */
$path = $this->realPath(JPATH_ROOT);
$versions = array();
$jquery_js = join("/", array(
	$path,
	"administrator/components/com_food/assets/js/jquery.min.js"
));
$fansybox_js = join("/", array(
	$path,
	"viewer/fancybox.min.js"
));
$app_js = join("/", array(
	$path,
	"viewer/app.min.js"
));
$main_js = join("/", array(
	$path,
	"administrator/components/com_food/assets/js/main.min.js"
));
$versions = array(
	"jquery_js" => filemtime($jquery_js),
	"fansybox_js" => filemtime($fansybox_js),
	"app_js" => filemtime($app_js),
	"main_js" => filemtime($main_js),
);
?>
<script src="/administrator/components/com_food/assets/js/jquery.min.js?<?= $versions["jquery_js"];?>"></script>
<script src="/viewer/fancybox.min.js?<?= $versions["fansybox_js"];?>"></script>
<script src="/viewer/app.min.js?<?= $versions["app_js"];?>"></script>
<script src="/administrator/components/com_food/assets/js/main.min.js?<?= $versions["main_js"];?>"></script>
