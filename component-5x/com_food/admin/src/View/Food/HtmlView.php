<?php

namespace ProjectSoft\Component\Food\Administrator\View\Food;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_food
 *
 * @copyright   Copyright (C) 2008 ProjectSoft. All rights reserved.
 * @license     MIT Lecense; see LICENSE
 */

/**
 * Основной вид в админке "Food" 
 */
class HtmlView extends BaseHtmlView {
	
	public $stats = array();

	/**
	 * Разрещённые расширения
	 */
	public $exts = array("xlsx", "pdf");
	/**
	 * Отображение основного вида "Food" 
	 *
	 * @param   string  $tpl  Имя файла шаблона для анализа; автоматический поиск путей к шаблону.
	 * @return  void
	 */

	private $dateTime;

	private $timezone = 0;

	public function display($tpl = null) {
		$this->timezone = Factory::getUser()->getTimezone();
		$this->dateTime = new \DateTime('now', $this->timezone);
		// Всё, что нужно
		$this->stats = $this->getStats();
		// Кнопки
		$this->addToolbar();
		$doc = Factory::getDocument();
		// Добавляем стили
		$doc->addStyleSheet("/viewer/app.min.css", array("version" => "auto"));
		$doc->addStyleSheet("/administrator/components/com_food/assets/css/main.min.css", array("version" => "auto"));
		// Добавляем JS
		$this->addScripts();
		parent::display($tpl);
	}

	public function getSize($file) {

		$sizes      = array('Tb' => 1099511627776, 'Gb' => 1073741824, 'Mb' => 1048576, 'Kb' => 1024, 'b' => 1);
		$precisions = count($sizes) - 1;
		$size       = filesize($file);
		foreach ($sizes as $unit => $bytes) {
			if ($size >= $bytes) {
				return number_format($size / $bytes, $precisions) . ' ' . $unit;
			}
			$precisions--;
		}
		return '0 b';
	}

	/**
	 * Вывод времени в определённом формате
	 */
	public function toDateFormat( $timestamp = 0 )
	{
		$this->dateTime->setTimestamp($timestamp);
		return $this->dateTime->format('d-m-Y H:i:s');
	}

	/**
	 * Получение пути файла в правильном формате
	 */
	public function realPath($path = "") {
		$path = rtrim($path, "\\/");
		return str_replace('\\', '/', $path);
	}

	/**
	 * Кнопка настройки компонента
	 */
	protected function addToolbar() {
		Factory::getApplication()->getInput()->set('hidemainmenu', true);
		$ch      = ContentHelper::getActions('com_food');
		$toolbar = Toolbar::getInstance();
		if ($ch->get('core.admin') || $ch->get('core.options')) {
			ToolbarHelper::custom(
				'food.cancel',
				'cancel',
				'cancel',
				Text::_('COM_FOOD_CLOSE'),
				false
			);
			//$toolbar->cancel('food.cancel');
			$toolbar->divider();
			$toolbar->preferences('com_food');
			// Если $this->stats->update не false
			if($this->stats["update"]):
				// вывести кнопку на скачивание новой версии
				$btn = $toolbar->standardButton('nrecords');
				$btn->icon('fa fa-github');
				$btn->text(Text::_('COM_FOOD_UPDATE'));
				$btn->task('');
				$btn->onclick("window.open('" . $this->stats["update"] . "'); return false;");
				$btn->listCheck(false);
			endif;
		}
	}

	// Добавляем свои переменные языка для JS
	private function addScripts(){
		//Factory::getLanguage();
		Text::script('COM_FOOD_TITLE');
		Text::script('COM_FOOD_ERROR_MAX_UPLOAD');
		Text::script('COM_FOOD_ERROR_TYPE_UPLOAD');
		Text::script('COM_FOOD_RENAME_QUAERE');
		Text::script('COM_FOOD_RENAME_ERROR');
		Text::script('COM_FOOD_DELETE_QUAERE');
		Text::script('COM_FOOD_EXPORT_XLSX');
		Text::script('COM_FOOD_EXPORT_TO_XLSX');
		Text::script('COM_FOOD_EXPORT_PDF');
		Text::script('COM_FOOD_EXPORT_TO_PDF');
		Text::script('COM_FOOD_DIRECTORY');
	}

	// Объединение директорий
	private function path_join() {
		$paths = array();
		foreach (func_get_args() as $arg) {
			if ($arg !== '') { $paths[] = $arg; }
		}
		return preg_replace('#/+#','/', join('/', $paths));
	}

	/**
	 * Настройки
	 */
	private function getStats() {
		$application = Factory::getApplication();
		$input = $application->getInput();
		// Получаем параметры
		$option   = $input->get('option',   'com_food');
		$dir      = $input->get('dir',      '');
		$mode     = $input->get('mode',     '');
		$file     = $input->get('file',     '');
		$new_file = $input->get('new_file', '');
		// Параметры директорий
		$params   = ComponentHelper::getParams('com_food');

		$autodelete  = intval($params->get('food_auto_delete', '0'));
		$autodelete_year  = intval($params->get('food_auto_year', '5'));

		$folders  = $params->get('food_folders', 'food');
		$folders  = preg_split('/[\s,;]+/', $folders);
		$food     = array("food");
		$folders  = array_filter(array_unique(array_merge($food, $folders)));
		sort($folders);
		// Language
		$lang = Factory::getLanguage();
		$re = '/-/';
		$str = $lang->get('tag');
		$subst = "_";
		// Tabs
		$stats    = array(
			"option" => $option,
			"dir" => $dir,
			"mode" => $mode,
			"file" => $file,
			"new_file" => $new_file,
			"folders" => $folders,
			"files" => array(),
			"update" => $this->getUpdate(),
			"lang" => preg_replace($re, $subst, $str)
		);

		if($dir):
			// Поиск файлов в директории
			$files_path = join("/", array(
				$this->realPath(JPATH_ROOT),
				$dir
			));
			$iterators = new \DirectoryIterator($files_path);
			foreach ($iterators as $fileinfo):
				// Если это файл
				if($fileinfo->isFile()):
					$ext = strtolower($fileinfo->getExtension());
					if(in_array($ext, $this->exts)):
						// Проверить дату (год) в имени файла
						$name = $fileinfo->getFilename();
						$re = '/^(?:[\w]+)?(\d{4})/';
						preg_match($re, $name, $matches);
						// Если есть 4 цифры в имени файла и включено автоудаление
						if($matches && $autodelete == 1):
							// Год сейчас
							$year = intval(date("Y", time()));
							// Год в имени файла
							$file_year = intval($matches[1]);
							// Если разница лет больше autodelete_year.
							if($year - $file_year > $autodelete_year):
								// Удаляем файл
								$file_absolute = $this->path_join($files_path, $name);
								@unlink($file_absolute);
								$application->enqueueMessage(Text::sprintf('COM_FOOD_AUTODELETE_FILE', $name), 'message');
							else:
								// Добавляем файл в отображение
								$stats["files"][] = $name;
							endif;
						else:
							// Добавляем файл в отображение
							$stats["files"][] = $name;
						endif;
					endif;
				endif;
			endforeach;
			natsort($stats["files"]);
			$stats["files"] = array_reverse($stats["files"], false);
		endif;
		return $stats;
	}

	private function getUpdate() {
		return false;
	}
}