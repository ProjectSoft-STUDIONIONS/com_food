<?php

defined( '_JEXEC' ) or die( 'Restricted access' );

use SchoolHotFood\SchoolFood;

class FoodModelsStatistics extends JModelBase
{
	private $exts = array("xlsx", "pdf");

	public function getStats()
	{
		$stats = array();
		$glob_path = $this->realPath(JPATH_ROOT) . "/";
		$htaccess_path = $this->realPath(JPATH_ROOT) . "/";
		$application = \JFactory::getApplication();
		// Параметры URL
		$option = $application->input->get('option');
		// Просматриваемая директория
		$dir = $application->input->get('dir');
		// Тип действия
		$mode = $application->input->get('mode');
		// Файл
		$file = $application->input->get('file');
		// Новый Файл
		$new_file = $application->input->get('new_file');
		// Удалим лишние символы из директории
		$dir = trim((string) $dir, " \n\r\t\v\x00\\/|\"'`!@#$%^&*()_-+={}[]|<>?.,");
		// Основные настройки
		$ver  = (string) JVERSION;
		$vers = explode(".", $ver);
		$stats["jversion"] = $vers[0];
		$stats["option"] = $option;
		$stats["com_food_path"] = str_replace("\\", "/", str_replace(JPATH_ROOT, "", JPATH_COMPONENT_ADMINISTRATOR)) . "/";
		$stats["data"] = array();

		// Выбираем директории из настроек
		$component = \JComponentHelper::getComponent($option);
		$folders = $component->params->get('food_folders');
		// Автоудаление
		$autodelete = intval($component->params->get('food_auto_delete'));
		$autodelete_year = intval($component->params->get('food_auto_year'));
		// Директории
		$folders = preg_split('/[\s,;]+/', $folders);
		$food = array("food");
		$array = array_map('\\SchoolHotFood\\SchoolFood::TranslitFile', array_unique(array_merge($food, $folders)));
		$array = array_filter($array);
		// Сортируем директории
		sort($array);
		// Определяем запрос
		if(in_array($dir, $array)):
			$stats["food_title"] = $dir ? $dir : false;
		else:
			$stats["food_title"] = false;
			// Если параметр $dir существует и не верный - редирект
			if($dir):
				$tpl = \JText::_('COM_FOOD_DIR_ERROR');
				$application->redirect('index.php?option=' . $option, \JText::sprintf($tpl, $dir), 'error');
			endif;
		endif;
		$stats["com_food_params"] = $array;
		// Новая
		$foodSchool = new SchoolFood(
			JPATH_ROOT,
			array(
				// Задаём просматриваемую директорию
				"path"              => $dir,
				// Задаём автоудаление
				"autodelete"        => boolval($autodelete),
				// Удалять старше
				"year"              => intval($autodelete_year),
				// Разрешённые директории
				"access_path"       => $stats["com_food_params"]
			),
			array(
				"delete"               => "Файл удалён",
				"not_delete"           => "Файл не удалён",
				"not_file_delete"      => "Файл удалить нельзя",
				"rename"               => "Файл переименован",
				"not_rename"           => "Не удалось переименовать файл",
				"access_rename"        => "Файл переименовать нельзя",
				"access_rename_ext"    => "Нельзя использовать данное расширение",
				"access_path"          => "Доступ к данной директории запрещён",
				"access_file"          => "Файл не поддерживается",
				"upload"               => "Файл загружен",
				"not_upload"           => "Файл не загружен",
				"file_exists"          => "Файл существует",
				"not_found"            => "Файл не существует",
				"same_name"            => "Имена файлов одинаковые"
			)
		);

		// Директории созданы в SchoolFood классе. Удалены старые файлы.
		// Запись .htaccess
		foreach ($array as $key => $value):
			$path = $glob_path . $value;
			// Записываем .htaccess
			$htaccess = "";
			include($this->realPath(__DIR__) . "/.htaccess.old.php");
			@file_put_contents($path . "/.htaccess", $htaccess);
			@chmod($path . "/.htaccess", 0644);
		endforeach;

		// Определяем методы (загрузка, переименование, удаление)
		switch ($mode) {
			case 'upload':
				// Загрузка
				$data = $foodSchool->uploadFiles()->getData()->output;
				$stats["data"] = $data;
				if($data["message"]["success"]):
					$application->enqueueMessage("<div>" . implode("</div><div>", $data["message"]["success"]) . "</div>", 'message');
				endif;
				if($data["message"]["error"]):
					$application->enqueueMessage("<div>" . implode("</div><div>", $data["message"]["error"]) . "</div>", 'error');
				endif;
				// При загрузке - редирект
				$application->redirect('index.php?option=' . $stats["option"] . "&dir=" . $dir);
				break;
			case 'rename':
				// Переименование
				$data = $foodSchool->renameFile($file, $new_file)->getData()->output;
				$stats["data"] = $data;
				if($data["message"]["success"]):
					$application->enqueueMessage("<div>" . implode("</div><div>", $data["message"]["success"]) . "</div>", 'message');
				endif;
				if($data["message"]["error"]):
					$application->enqueueMessage("<div>" . implode("</div><div>", $data["message"]["error"]) . "</div>", 'error');
				endif;
				break;
			case 'delete':
				// Удаление
				$data = $foodSchool->deleteFile($file)->getData()->output;
				$stats["data"] = $data;
				if($data["message"]["success"]):
					$application->enqueueMessage("<div>" . implode("</div><div>", $data["message"]["success"]) . "</div>", 'message');
				endif;
				if($data["message"]["error"]):
					$application->enqueueMessage("<div>" . implode("</div><div>", $data["message"]["error"]) . "</div>", 'error');
				endif;
				break;
			default:
				// Обычный
				$data = $foodSchool->getData()->output;
				$stats["data"] = $data;
				if($data["message"]["success"]):
					$application->enqueueMessage("<div>" . implode("</div><div>", $data["message"]["success"]) . "</div>", 'message');
				endif;
				if($data["message"]["error"]):
					$application->enqueueMessage("<div>" . implode("</div><div>", $data["message"]["error"]) . "</div>", 'error');
				endif;
				break;
				break;
		}

		$stats["update"] = $this->getUpdate();
		$lang = JFactory::getLanguage();
		$re = '/-/';
		$str = $lang->get('tag');
		$subst = "_";
		$stats['lang'] = preg_replace($re, $subst, $str);
		return $stats;
	}

	private function getUpdate() {
		return false;
	}

	/**
	 * Получение пути файла в правильном формате
	 */
	public function realPath($path = "") {
		$path = rtrim($path, "\\/");
		return str_replace('\\', '/', $path);
	}
}
