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

	protected $root = JPATH_ROOT;

	// Разрешённые расширения
	private $exts = array("xlsx", "pdf");

	private $folders = array("food");

	public function display($cachable = false, $urlparams = array()) {
		// Установка локали
		setlocale(LC_NUMERIC, 'C');
		// Корневая директория
		$this->root = $this->realPath(JPATH_ROOT);
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

		$this->folders  = array_filter(array_unique(array_merge($food, $folders)));
		sort($this->folders);
		// Пробег по директориям
		$this->recursiveFolders();
		// Определяем запрос
		// Если параметр $dir существует и не верный - редирект
		if($dir && !in_array($dir, $this->folders)):
			$this->setMessage(\JText::sprintf('COM_FOOD_DIR_ERROR', $dir), 'error');
			$this->setRedirect('index.php?option=' . $option);
			$this->redirect();
			return;
		endif;
		// Если параметр $dir существует
		if($dir):
			// Проверяем тип действия
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
				default:
					// Ничего не делаем
					break;
			}
		endif;
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
	private function recursiveFolders() {
		$admin = $this->realPath(JPATH_COMPONENT_ADMINISTRATOR);
		if(is_array($this->folders)):
			foreach ($this->folders as $key => $value):
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
		$msg_success   = array();
		$msg_error     = array();
		$startpath     = join("/", $paths);
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
						// Файл загружен
						$msg_success[] = Text::sprintf('COM_FOOD_UPLOAD_FILE_SUCCESS', $dir . "/" . $name);
					else:
						// Неудачная загрузка файла
						$msg_error[] = Text::sprintf('COM_FOOD_UPLOAD_FILE_ERROR', $dir . "/" . $name);
					endif;
				else:
					// Неудачная загрузка файла
					$msg_error[] = Text::sprintf('COM_FOOD_UPLOAD_FILE_ERROR', $dir . "/" . $name);
				endif;
			endforeach;
			// Сообщение о удачной загрузке
			if(count($msg_success)):
				$msg_success = '<p>' . Text::sprintf('COM_FOOD_UPLOAD_FILES_SUCCESS', count($msg_success)) . '</p>' . join("<br>", $msg_success);
				$this->setMessage($msg_success, 'message');
			endif;
			// Сообщение о неудачной загрузке
			if(count($msg_error)):
				$msg_error = '<p>' . Text::sprintf('COM_FOOD_UPLOAD_FILES_ERROR', count($msg_error)) . '</p>' . join("<br>", $msg_error);
				$this->setMessage($msg_error, 'error');
			endif;
		else:
			// Нет файлов для обработки
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
		// Проверка и переименование
		// Путь к исходному файлу
		$path = join('/', array(
			$this->root,
			$dir,
			$file
		));
		// Транслит имени нового файла
		$new_file = $this->translitFile($new_file);
		// Существует ли исходный файл
		if(is_file($path)):
			// Расширение исходного файла
			$extension_old = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			// Входит ли исходный файл в тип разрешённых
			if(in_array($extension_old, $this->exts)):
				// Проверяем расширение нового файла
				$extension_new = strtolower(pathinfo($new_file, PATHINFO_EXTENSION));
				// Входит ли новый файл в тип разрешённых
				if(in_array($extension_new, $this->exts)):
					// Формируем путь к новому файлу
					$new_path = join('/', array(
						$this->root,
						$dir,
						$new_file
					));
					// Существует ли новый файл
					if(is_file($new_path)):
						// Cообщение, что файл существует (новый файл)
						$this->setMessage(Text::sprintf('COM_FOOD_RENEME_FILE_ERROR', join('/', array($dir, $file)), join('/', array($dir, $new_file))), 'error');
					else:
						// Равны ли расширения
						if($extension_old == $extension_new):
							// Переименовываем исходный файл в новый файл
							// @ Спрячем ошибки если они будут
							@rename($path, $new_path);
							// Сообщение, что файл переименован
							$this->setMessage(Text::sprintf('COM_FOOD_RENEME_FILE', join('/', array($dir, $file)), join('/', array($dir, $new_file))), 'message');
						else:
							// Сообщение, что расширения не верные
							$this->setMessage(Text::sprintf('COM_FOOD_RENEME_FILE_ERROR', join('/', array($dir, $file)), join('/', array($dir, $new_file))), 'error');
						endif;
					endif;
				else:
					// Сообщение, что расширениe не верное (новый файл)
					$this->setMessage(Text::sprintf('COM_FOOD_RENEME_FILE_ERROR', join('/', array($dir, $file)), join('/', array($dir, $new_file))), 'error');
				endif;
			else:
				// Сообщение, что расширениe не верное (исходный файл)
				$this->setMessage(Text::sprintf('COM_FOOD_RENEME_FILE_ERROR', join('/', array($dir, $file)), join('/', array($dir, $new_file))), 'error');
			endif;
		else:
			// Файл не существует
			$this->setMessage(Text::sprintf('COM_FOOD_FILE_NOT_FOUND', join('/', array($dir, $file))), 'error');
		endif;
		$this->setRedirect('index.php?option=com_food&dir=' . $dir);
		$this->redirect();
		return;
	}

	/**
	 * Удаление файла
	 */
	private function deleteFile($dir, $file, $redirect = true) {
		$paths = array(
			$this->root,
			$dir,
			$file
		);
		$path = join('/', $paths);
		if(is_file($path)):
			// Проверка и удаление
			$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			if(in_array($extension, $this->exts)):
				// Удаление
				// @ Спрячем ошибки если они будут
				@unlink($path);
				$this->setMessage(Text::sprintf('COM_FOOD_FILE_DELETE', join('/', array($dir, $file))), 'message');
			else:
				// Запрещена работа с данным расширением
				$this->setMessage(Text::sprintf('COM_FOOD_EXTENSION_ERROR', $extension), 'error');
			endif;
		else:
			// Файл не существует
			$this->setMessage(Text::sprintf('COM_FOOD_FILE_NOT_FOUND', join('/', array($dir, $file))), 'error');
		endif;
		if($redirect):
			$this->setRedirect('index.php?option=com_food&dir=' . $dir);
			$this->redirect();
		endif;
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