<?php

namespace ProjectSoft\Component\Food\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_food
 *
 * @copyright   Copyright (C) 2008 ProjectSoft. All rights reserved.
 * @license     MIT Lecense; see LICENSE
 */

/**
 * Контроллер по умолчанию компонента Food
 *
 * @package     Joomla.Administrator
 * @subpackage  com_food
 */
class DisplayController extends BaseController {
	/**
	 * Представление по умолчанию для метода отображения.
	 *
	 * @var string
	 */
	protected $default_view = 'food';

	protected $root = "";

	protected $ext = array("xlsx", "pdf");

	protected $folders = array("food");

	public function display($cachable = false, $urlparams = array()) {
		// Корневая директория
		$this->root     = $this->realPath(JPATH_ROOT);
		// Получаем параметры
		$option   = $this->input->get('option',   'com_food');
		$dir      = $this->input->get('dir',      '');
		$mode     = $this->input->get('mode',     '');
		$file     = $this->input->get('file',     '');
		$new_file = $this->input->get('new_file', '');
		// Параметры директорий
		$params   = ComponentHelper::getParams('com_food');
		$folders  = $params->get('food_folders', 'food');
		$folders  = preg_split('/[\s,;]+/', $folders);
		$food     = array("food");
		$folders  = array_filter(array_unique(array_merge($food, $folders)));
		sort($folders);
		// Пробег по директориям
		$this->folders($folders);
		// Определяем запрос
		// Если параметр $dir существует и не верный - редирект
		if($dir && !in_array($dir, $folders)):
			$this->setMessage(\JText::sprintf('COM_FOOD_DIR_ERROR', $dir), 'error');
			$this->setRedirect('index.php?option=' . $option);
			$this->redirect();
			return;
		endif;
		if($dir):
			// Проверяем тип
			switch ($mode) {
				case 'upload':
					// Загрузка файлов
					$this->uploadFiles($dir);
					return;
					break;
				case 'rename':
					// Переименование
					$this->renameFile($dir, $file, $new_file);
					return;
					break;
				case 'delete':
					// Удаление
					$this->deleteFile($dir, $file);
					return;
					break;
			}
		endif;
		//$print_r = array($option, $dir, $mode, $file, $new_file);
		//@file_put_contents(JPATH_ROOT . "/uri.txt", print_r($folders, true));
		return parent::display($cachable, $urlparams);
	}

	/**
	 * Получение пути файла в правильном формате
	 */
	private function realPath($path = "") {
		$path = rtrim($path, "\\/");
		return str_replace('\\', '/', $path);
	}

	/**
	 * Пробег по директориям
	 */
	private function folders(array $folders) {
		$admin = $this->realPath(JPATH_COMPONENT_ADMINISTRATOR);
		if(is_array($folders)):
			foreach ($folders as $key => $value):
				$folder = $this->root . "/" . $value;
				// Если это не директория
				if(!is_dir($folder)):
					// Создаём
					// @ Спрячем если есть ошибки
					@mkdir($folder, 0755);
					@chmod($folder, 0755);
				endif;
				// Пишем в файл .htaccess
				$file = $folder . "/" . ".htaccess";
				include($admin . "/htaccess/.htaccess.old.php");
				// @ Спрячем если есть ошибки
				@file_put_contents($file, $htaccess);
				@chmod($file, 0644);
			endforeach;
		endif;
	}

	/**
	 * Загрузка файлов
	 */
	private function uploadFiles($dir) {
		$paths = array(
			$this->root,
			$dir
		);
		$success = false;
		$error = false;
		$count_success = 0;
		$count_error = 0;
		$msg_success = "";
		$msg_error = "";
		$startpath = join("/", $paths);
		if(isset($_FILES['userfiles'])):
			foreach ($_FILES['userfiles']['name'] as $i => $name):
				if (empty($_FILES['userfiles']['tmp_name'][$i])) continue;
				$name = $this->translitFile($name);
				$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
				$userfile = array();
				$tmp_name = $_FILES['userfiles']['tmp_name'][$i];
				$path = $startpath . '/' . $name;
				if(in_array($extension, $this->exts)):
					if(@move_uploaded_file($tmp_name, $path)):
						if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN'):
							@chmod($path, 0644);
						endif;
						if(!$success):
							$success = true;
							$msg_success .= '<dl class="dl-horizontal">';
						endif;
						++$count_success;
						$msg_success .= '<dt>' . Text::_('COM_FOOD_UPLOAD_FILE_SUCCESS') . '</dt>';
						$msg_success .= '<dd>' . $dir . "/" . $name . '</dd>';
					else:
						if(!$error):
							$error = true;
							$msg_error .= '<dl class="dl-horizontal">';
						endif;
						++$count_error;
						$msg_error .= '<dt>' . Text::_('COM_FOOD_UPLOAD_FILE_ERROR') . '</dt>';
						$msg_error .= '<dd>' . $dir . "/" . $name . '</dd>';
					endif;
				else:
					if(!$error):
						$error = true;
						$msg_error .= '<dl class="dl-horizontal">';
					endif;
					++$count_error;
					$msg_error .= '<dt>' . Text::_('COM_FOOD_UPLOAD_FILE_ERROR') . '</dt>';
					$msg_error .= '<dd>' . $dir . "/" . $name . '</dd>';
				endif;
			endforeach;
			if($success):
				$msg_success .= '</dl>';
				$msg_success = '<p><strong>' . Text::_('COM_FOOD_UPLOAD_FILES_SUCCESS') . '</strong> ' . $count_success . '</p>' . $msg_success;
				$this->setMessage($msg_success, 'message');
			endif;
			if($error):
				$msg_error .= '</dl>';
				$msg_error = '<p><strong>' . Text::_('COM_FOOD_UPLOAD_FILES_ERROR') . '</strong> ' . $count_error . '</p>' . $msg_error;
				$this->setMessage($msg_error, 'error');
			endif;
		else:
			$this->setMessage(Text::_('COM_FOOD_UOLOAD_FILES_NOT_FOUND'), 'error');
		endif;
		$this->setRedirect('index.php?option=com_food&dir=' . $dir);
		$this->redirect();
		return;
	}

	/**
	 * Переименование файла
	 */
	private function renameFile($dir, $file, $new_file) {
		$paths = array(
			$this->root,
			$dir,
			$file
		);
		$new_paths = array(
			$this->root,
			$dir,
			$new_file
		);
		$path = join('/', $paths);
		if(is_file($path)):
			// Проверка и переименование
			$this->setMessage(\JText::sprintf('COM_FOOD_RENEME_FILE', join('/', array($dir, $file)), join('/', array($dir, $new_file))), 'message');
		else:
			// Файл не существует
			$this->setMessage(\JText::sprintf('COM_FOOD_FILE_NOT_FOUND', join('/', array($dir, $file))), 'error');
		endif;
		$this->setRedirect('index.php?option=com_food&dir=' . $dir);
		$this->redirect();
		return;
	}

	/**
	 * Удаление файла
	 */
	private function deleteFile($dir, $file) {
		$paths = array(
			$this->root,
			$dir,
			$file
		);
		$path = join('/', $paths);
		if(is_file($path)):
			// Проверка и удаление
			$this->setMessage(\JText::sprintf('COM_FOOD_FILE_DELETE', join('/', array($dir, $file))), 'message');
		else:
			// Файл не существует
			$this->setMessage(\JText::sprintf('COM_FOOD_FILE_NOT_FOUND', join('/', array($dir, $file))), 'error');
		endif;
		$this->setRedirect('index.php?option=com_food&dir=' . $dir);
		$this->redirect();
		return;
	}

	/**
	 * Очистка имени файла от лишних символов
	 */
	private function stripFileName($filename = "") {
		$filename = strip_tags($filename);
		$filename = preg_replace('/[^\.A-Za-z0-9 _-]/', '', $filename);
		$filename = preg_replace('/\s+/', '-', $filename);
		$filename = preg_replace('/_+/', '-', $filename);
		$filename = preg_replace('/-+/', '-', $filename);
		$filename = trim($filename, '-_.');
		return $filename;
	}

	/**
	 * Транслит имени файла
	 */
	private function translitFile($filename){
		$converter = array(
			'а' => 'a',    'б' => 'b',    'в' => 'v',    'г' => 'g',    'д' => 'd',
			'е' => 'e',    'ё' => 'e',    'ж' => 'zh',   'з' => 'z',    'и' => 'i',
			'й' => 'y',    'к' => 'k',    'л' => 'l',    'м' => 'm',    'н' => 'n',
			'о' => 'o',    'п' => 'p',    'р' => 'r',    'с' => 's',    'т' => 't',
			'у' => 'u',    'ф' => 'f',    'х' => 'h',    'ц' => 'c',    'ч' => 'ch',
			'ш' => 'sh',   'щ' => 'sch',  'ь' => '',     'ы' => 'y',    'ъ' => '',
			'э' => 'e',    'ю' => 'yu',   'я' => 'ya',
	 
			'А' => 'A',    'Б' => 'B',    'В' => 'V',    'Г' => 'G',    'Д' => 'D',
			'Е' => 'E',    'Ё' => 'E',    'Ж' => 'Zh',   'З' => 'Z',    'И' => 'I',
			'Й' => 'Y',    'К' => 'K',    'Л' => 'L',    'М' => 'M',    'Н' => 'N',
			'О' => 'O',    'П' => 'P',    'Р' => 'R',    'С' => 'S',    'Т' => 'T',
			'У' => 'U',    'Ф' => 'F',    'Х' => 'H',    'Ц' => 'C',    'Ч' => 'Ch',
			'Ш' => 'Sh',   'Щ' => 'Sch',  'Ь' => '',     'Ы' => 'Y',    'Ъ' => '',
			'Э' => 'E',    'Ю' => 'Yu',   'Я' => 'Ya',
		);
		$filename = str_replace(array(' ', ','), '-', $filename);
		$filename = strtr($filename, $converter);
		$filename = $this->stripFileName($filename);
		$filename = strtolower($filename);
		return $filename;
	}
}