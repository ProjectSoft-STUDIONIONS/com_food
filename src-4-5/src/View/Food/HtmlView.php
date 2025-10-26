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

use ProjectSoft\Component\Food\Administrator\Lib\SchoolFood;

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
	private $application = null;
	private $input = null;
	private $params = null;

	/**
	 * Отображение основного вида "Food"
	 *
	 * @param   string  $tpl  Имя файла шаблона для анализа; автоматический поиск путей к шаблону.
	 * @return  void
	 */
	public function display($tpl = null) {
		$this->application = Factory::getApplication();
		$this->input = $this->application->getInput();
		// Параметры директорий
		$this->params   = ComponentHelper::getParams('com_food');
		// Получаем параметры
		$option   = $this->input->get('option',   'com_food');
		$dir      = $this->input->get('dir',      '');
		$mode     = $this->input->get('mode',     '');
		$file     = $this->input->get('file',     '');
		$new_file = $this->input->get('new_file', '');

		$autodelete  = intval($this->params->get('food_auto_delete', '0'));
		$autodelete_year  = intval($this->params->get('food_auto_year', '5'));

		$folders  = $this->params->get('food_folders', 'food');
		$folders  = preg_split('/[\s,;]+/', $folders);
		$food     = array("food");
		$folders  = array_filter(array_unique(array_merge($food, $folders)));

		$folders  = array_map('ProjectSoft\\Component\\Food\\Administrator\\Lib\\SchoolFood::TranslitFile', $folders);

		sort($folders);

		$data = array(array("message" => array("success" => array(),"error" => array())));

		if($dir && !in_array($dir, $folders)):
			// Редирект на верхний уровень
			$data["message"]["error"][] = Text::sprintf('COM_FOOD_DIR_ERROR', $dir);
			$this->setEnqueueMessage($data);
			$this->application->redirect("index.php?option=" . $option);
		endif;

		$this->food = new SchoolFood(
			JPATH_ROOT,
			array(
				// Просматриваемая директория
				"path"              => $dir,
				// Автоудаление
				"autodelete"        => $autodelete,
				// Сколько лет
				"year"              => $autodelete_year,
				// Разрешённые директории
				"access_path"       => $folders
			),
			array(
				"delete"               => Text::_('COM_FOOD_FOOD_DELETE'),
				"not_delete"           => Text::_('COM_FOOD_FOOD_NOT_DELETE'),
				"not_file_delete"      => Text::_('COM_FOOD_FOOD_NOT_FILE_DELETE'),
				"rename"               => Text::_('COM_FOOD_FOOD_RENAME'),
				"not_rename"           => Text::_('COM_FOOD_FOOD_NOT_RENAME'),
				"access_rename"        => Text::_('COM_FOOD_FOOD_ACCESS_RENAME'),
				"access_rename_ext"    => Text::_('COM_FOOD_FOOD_ACCESS_RENAME_EXT'),
				"access_path"          => Text::_('COM_FOOD_FOOD_ACCESS_PATH'),
				"access_file"          => Text::_('COM_FOOD_FOOD_ACCESS_FILE'),
				"upload"               => Text::_('COM_FOOD_FOOD_UPLOAD'),
				"not_upload"           => Text::_('COM_FOOD_FOOD_NOT_UPLOAD'),
				"file_exists"          => Text::_('COM_FOOD_FOOD_FILE_EXISTS'),
				"not_found"            => Text::_('COM_FOOD_FOOD_NOT_FOUND'),
				"same_name"            => Text::_('COM_FOOD_FOOD_SAME_NAME'),
			)
		);

		// Директории созданы в SchoolFood классе. Удалены старые файлы.
		// Запись .htaccess
		$admin = $this->realPath(JPATH_COMPONENT_ADMINISTRATOR);
		foreach ($folders as $key => $value):
			try {
				$path = $this->realPath(JPATH_ROOT) . "/" . $value . "/" . ".htaccess";;
				// Записываем .htaccess
				$htaccess = "";
				include($admin . "/htaccess/.htaccess.old.php");
				@file_put_contents($path, $htaccess);
				@chmod($path, 0644);
			}catch(\Exception $e) {
				// Редирект на верхний уровень
				$data["message"]["error"][] = Text::_('COM_FOOD_ERROR');
				$this->setEnqueueMessage($data);
				$this->application->redirect("index.php?option=" . $option);
			}
		endforeach;
		// Определение mode
		switch ($mode) {
			case 'upload':
				$data = $this->food->uploadFiles()->getData()->output;
				$this->setEnqueueMessage($data);
				$this->application->redirect("index.php?option=" . $option . "&dir=" . $dir);
				break;
			case 'rename':
				$data = $this->food->renameFile($file, $new_file)->getData()->output;
				$this->setEnqueueMessage($data);
				$this->application->redirect("index.php?option=" . $option . "&dir=" . $dir);
				break;
			case 'delete':
				$data = $this->food->deleteFile($file)->getData()->output;
				$this->setEnqueueMessage($data);
				$this->application->redirect("index.php?option=" . $option . "&dir=" . $dir);
				break;
			default:
				$data = $this->food->getData()->output;
				$this->setEnqueueMessage($data);
				break;
		}

		// Определяем язык
		$lang = Factory::getLanguage();
		$re = '/-/';
		$str = $lang->get('tag');
		$subst = "_";
		$strLang = preg_replace($re, $subst, $str);

		$this->stats = $data;
		$this->stats["lang"] = $strLang;
		$this->stats["update"] = $this->getUpdate();

		// Кнопки
		$this->addToolbar();

		// Стили
		$doc = Factory::getDocument();
		$styles = array(
			"/viewer/app.min.css",
			"/administrator/components/com_food/assets/css/main.min.css"
		);
		// Добавляем стили
		foreach ($styles as $key => $value):
			if(is_file(JPATH_ROOT . $value)):
				$version = filemtime(JPATH_ROOT . $value);
				$doc->addStyleSheet($value, array('version' => $version));
			endif;
		endforeach;
		// Добавляем JS
		$this->addScripts();
		parent::display($tpl);
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
		//Factory::getApplication()->getInput()->set('hidemainmenu', true);
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

	private function setEnqueueMessage($data) {
		if($data["message"]["success"]):
			$this->application->enqueueMessage("<div>" . implode("</div><div>", $data["message"]["success"]) . "</div>", 'message');
		endif;
		if($data["message"]["error"]):
			$this->application->enqueueMessage("<div>" . implode("</div><div>", $data["message"]["error"]) . "</div>", 'error');
		endif;
	}

	// Добавляем свои переменные языка для JS
	private function addScripts(){
		Text::script('COM_FOOD_TITLE');
		Text::script('COM_FOOD_ERROR_MAX_UPLOAD');
		Text::script('COM_FOOD_ERROR_TYPE_UPLOAD');
		Text::script('COM_FOOD_RENAME_QUAERE');
		Text::script('COM_FOOD_RENAME_ERROR');
		Text::script('COM_FOOD_DELETE_QUAERE');
		Text::script('COM_FOOD_EXPORT');
		Text::script('COM_FOOD_EXPORT_XLSX');
		Text::script('COM_FOOD_EXPORT_TO_XLSX');
		Text::script('COM_FOOD_EXPORT_PDF');
		Text::script('COM_FOOD_EXPORT_TO_PDF');
		Text::script('COM_FOOD_DIRECTORY');
		Text::script('COM_FOOD_SELECT_FILES');
		Text::script('COM_FOOD_FILES_UPLOAD');
		Text::script('COM_FOOD_TITLE_DRAG');
		Text::script('COM_FOOD_TITLE_DRAG_BEFORE');
		Text::script('COM_FOOD_TOOLS');
		Text::script('COM_FFOD_TXT_FILES_SELECT');
		Text::script('COM_FOOD_TXT_FILES_ONE');
		Text::script('COM_FOOD_TXT_FILES_TWO');
		Text::script('COM_FOOD_TXT_FILES_THREE');
	}

	private function getUpdate() {
		return false;
	}
}
