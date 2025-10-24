<?php
$path = $this->realPath(JPATH_ROOT);
?>
<script type="text/javascript">
	window.MAX_COUNT_FILE = <?= ini_get("max_file_uploads");?>;
	window.J_LANG = "<?= $this->stats['lang']?>";
</script>
<div id="food_content" class="clearfix joomla3">
	<div id="joomla3" class="row clearfix">
		<div class="container-fluid clearfix">
			<h1 class="com-food-title"><?= JText::_('COM_FOOD_TITLE'); ?></h1>
			<div class="clearfix">
				<?php if($this->stats["data"]["path"]):?>
				<form class="text-right" name="upload" method="post" action="index.php?option=com_food&dir=<?= $this->stats["data"]["path"];?>" enctype="multipart/form-data">
					<input type="hidden" name="mode" value="upload">
					<input type="file" name="userfiles[]" onchange="uploadFiles(this);" multiple accept=".xlsx,.pdf" max="<?= ini_get("max_file_uploads");?>">
					<p id="p_uploads" class="alert alert-info" data-before-title="<?= JText::_('COM_FOOD_SELECT_UPLOAD'); ?>"></p>
				</form>
				<form class="hidden" name="form_mode" method="post" action="index.php?option=com_food&dir=<?= $this->stats["data"]["path"];?>" enctype="multipart/form-data">
					<input type="hidden" name="mode">
					<input type="hidden" name="file">
					<input type="hidden" name="new_file">
				</form>
				<?php endif;?>
			</div>
			<div class="folder-title">
				<h3><?= $this->stats["food_title"] ? JText::sprintf('COM_FOOD_DIR', $this->stats["food_title"]) : JText::_('COM_FOOD_DIR_ROOT'); ?></h3>
				<?= $this->stats["food_title"] ? '<p class="food-title-root"><i class="food-icon food-icon-folder-open-o"></i>&nbsp;<a href="index.php?option=com_food">' . JText::_('COM_FOOD_DIR_TOP') . '</a> / <a href="index.php?option=com_food&dir=' . $this->stats["data"]["path"] . '">' . $this->stats["data"]["path"] . '</a></p>' : ''; ?>
			</div>
			<div class="food-table">
				<div class="">
					<table class="table table-bordered table-hover table-food">
						<thead>
							<tr>
							<?php if($this->stats["data"]["path"]):?>
								<th><?= JText::_('COM_FOOD_TABLE_NAME'); ?></th>
								<th style="width: 1%;" class="nowrap"><?= JText::_('COM_FOOD_TABLE_PERMISION'); ?></th>
								<th style="width: 1%;" class="nowrap"><?= JText::_('COM_FOOD_TABLE_CHANGE'); ?></th>
								<th style="width: 1%;" class="nowrap"><?= JText::_('COM_FOOD_TABLE_SIZE'); ?></th>
								<th style="width: 1%;"><?= JText::_('COM_FOOD_TABLE_PARAMETERS'); ?></th>
							<?php else: ?>
								<th class="nowrap" colspan="5"><?= JText::_('COM_FOOD_TABLE_TITLE_NOT_FOUND'); ?></th>
							<?php endif; ?>
							</tr>
						</thead>
						<tbody>
						<?php if($this->stats["data"]["files"] && $this->stats["data"]["path"]):?>
							<?php foreach ($this->stats["data"]["files"] as $value):
								// Размещаем
								$name = $value["name"];
								$ltime = $value["time"];
								$size = $value["size"];
								$perms = $value["perms"];
								$link = $value["link"];
								$icon = $value["icon"];
							?>
							<tr>
								<td><i class="food-icon <?= $icon;?>"></i>&nbsp;<a href="<?= $link; ?>" target="_blank"><?= $name; ?></a></td>
								<td><?= $perms; ?></td>
								<td><?= $ltime; ?></td>
								<td><?= $size; ?></td>
								<td><!-- Переименовать, Удалить -->
									<div class="flex">
										<i class="btn btn-default food-icon food-icon-edit" data-mode="rename" data-file="<?= $name; ?>" title="<?= \JText::sprintf('COM_FOOD_RENAME', $name);?>" onclick="modeFile(this);"></i>
										<i class="btn btn-danger food-icon food-icon-trash" data-mode="delete" data-file="<?= $name; ?>" title="<?= \JText::sprintf('COM_FOOD_DELETE', $name);?>" onclick="modeFile(this);"></i>
										<span>-</span>
									</div>
								</td>
							</tr>
							<?php endforeach; ?>
						<?php else: ?>
							<?php if($this->stats["data"]["directory"]):?>
								<?php foreach($this->stats["data"]["directory"] as $value): ?>
							<tr>
								<td class="nowrap" colspan="4"><i class="food-icon food-icon-folder-open-o"></i>&nbsp;<a href="index.php?option=com_food&dir=<?= $value; ?>"><?= $value; ?></a></td>
								<td style="width: 1%;" class="nowrap"><a href="/<?= $value; ?>/" target="_blank"><i class="food-icon food-icon-new-window"></i></a></td>
							</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
			<!-- div><pre><code><?= print_r($this->stats["data"]["directory"], true);?></code></pre></div -->
			<p class="text-left developer">Если возникнут проблемы или вопросы, то обращайтесь в Telegram к <a href="https://t.me/ProjectSoft" target="_blank">ProjectSoft</a> (Чернышёв Андрей)<br>GitHub репозиторий компонента <a href="https://github.com/ProjectSoft-STUDIONIONS/com_food" target="_blank">https://github.com/ProjectSoft-STUDIONIONS/com_food</a></p>
		</div>
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
