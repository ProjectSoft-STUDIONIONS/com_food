<?php

defined( '_JEXEC' ) or die( 'Restricted access' );

class FoodModelsStatistics extends JModelBase
{
	private $exts = array("xlsx", "pdf");

	public function getStats()
	{
		$stats = array();
		$glob_path = $this->realPath(JPATH_ROOT) . "/";
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
		$stats["dir"] = $dir;
		$stats["mode"] = $mode;
		$stats["file"] = $file;
		$stats["new_file"] = $new_file;
		$stats["com_food_path"] = str_replace("\\", "/", str_replace(JPATH_ROOT, "", JPATH_COMPONENT_ADMINISTRATOR)) . "/";

		// Выбираем директории из настроек
		$component = \JComponentHelper::getComponent($option);
		$folders = $component->params->get('food_folders');

		$autodelete = intval($component->params->get('food_auto_delete'));
		$autodelete_year = intval($component->params->get('food_auto_year'));

		$folders = preg_split('/[\s,;]+/', $folders);
		$food = array("food");
		$array = array_filter(array_unique(array_merge($food, $folders)));
		// Сортируем директории
		sort($array);
		// Директории.
		// Создание и запись .htaccess
		foreach ($array as $key => $value):
			$path = $glob_path . $value;
			// Если директория не существует
			if(!is_dir($path)):
				// Создаём директорию
				@mkdir($path);
				@chmod($path, 0755);
			endif;
			// Записываем .htaccess
			$htaccess = "";
			include($this->realPath(__DIR__) . "/.htaccess.old.php");
			@file_put_contents($path . "/.htaccess", $htaccess);
			@chmod($path . "/.htaccess", 0644);
		endforeach;
		$stats["com_food_params"] = $array;

		// Определяем запрос
		if(in_array($stats["dir"], $array)):
			$stats["food_title"] = $stats["dir"] ? $stats["dir"] : false;
		else:
			$stats["food_title"] = false;
			// Если параметр $dir существует и не верный - редирект
			if($stats["dir"]):
				$tpl = \JText::_('COM_FOOD_DIR_ERROR');
				$application->redirect('index.php?option=' . $option, \JText::sprintf($tpl, $stats["dir"]), 'error');
			endif;
		endif;

		// Определяем методы (загрузка, переименование, удаление)
		switch ($mode) {
			case 'upload':
				// Загрузка
				$this->upload($stats);
				break;
			case 'rename':
				// Переименование
				$this->renameFile($stats);
				break;
			case 'delete':
				// Удаление
				$this->deleteFile($stats);
				break;
		}

		// Выполняем поиск директорий или файлов из директории
		$stats["files"] = array();
		if($stats["dir"]):
			// Поиск файлов в директории
			$files_path = $glob_path . $stats["dir"] . "/";
			// Установка локали
			setlocale(LC_NUMERIC, 'C');
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
							// Если разница лет больше autodelete_year
							if($year - $file_year > $autodelete_year):
								// Удаляем файл
								$file_absolute = $this->path_join($files_path, $name);
								@unlink($file_absolute);
								$application->enqueueMessage(\JText::sprintf('COM_FOOD_AUTODELETE_FILE', $name), 'message');
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
		endif;
		//rsort($stats["files"]);
		natsort($stats["files"]);
		$stats["files"] = array_reverse($stats["files"], false);
		$stats["update"] = $this->getUpdate();
		return $stats;
	}

	private function getUpdate() {
		// Загружаем манифест
		$path = $this->realPath(JPATH_ROOT);
		$manifest = join("/", array(
			$path,
			'administrator/components/com_food/food.xml'
		));
		$manifest = file_get_contents($manifest);
		$manifestData = new \SimpleXMLElement($manifest);
		// Получаем версию компонента
		$componentVersion = (string) $manifestData->version;
		// Временные данные
		$data = new \stdClass;
		// Дата на прошедшие сутки
		$data->date = date( "Y-m-d", time() - 3600 );
		// Тякущая версия компонента
		$data->version = $componentVersion;
		$update = join("/", array(
			$path,
			'administrator/components/com_food/.update.json'
		));

		// Если нет файла запишем данные
		if(!is_file($update)):
			file_put_contents($update, json_encode($data));
		endif;

		// Получить данные из json.
		$data = @file_get_contents($update);
		$data = json_decode($data);
		// Если есть версия, дата, обновление
		if(isset($data->date) && isset($data->version)):
			$version = $data->version;
			$date = $data->date;
			// Сравниваем дату
			//if(is_object($temp_date)):
				// Если дата в файле больше текущей даты на 7 дней
				$temp_time = strtotime("+1 week", strtotime($date . ' 00:00:00'));
				//$temp_time = strtotime($date . ' 00:00:00') + 3600 * 7;
				// Текущая дата
				$time = strtotime(date('Y-m-d 00:00:00', time()));
				if($temp_time < $time):
					// cURL
					try {
						$url = 'https://api.github.com/repos/ProjectSoft-STUDIONIONS/com_food/releases/latest';
						$headers = array(
							'cache-control: max-age=0',
							'upgrade-insecure-requests: 1',
							'user-agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.97 Safari/537.36',
							'sec-fetch-user: ?1',
							'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
									'x-compress: null',
									'sec-fetch-site: none',
									'sec-fetch-mode: navigate',
									'accept-encoding: deflate, br',
									'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
						);
						$CurlOptions = array(
							CURLOPT_URL 		   => $url,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_FOLLOWLOCATION => true,
							CURLOPT_SSL_VERIFYPEER => false,
							CURLOPT_HEADER         => false,
							CURLOPT_HTTPHEADER     => $headers
						);
						$ch = curl_init();
						curl_setopt_array( $ch, $CurlOptions );
						$json = curl_exec($ch);
						curl_close($ch);
						$json = json_decode($json);
						// compare
						$github = $json->tag_name;
						if( version_compare($componentVersion, $github, '<') ):
							// Новая версия
							$vals = false;
							if(is_array($json->assets)):
								foreach($json->assets as $key => $value ):
									if($value->name == 'com_food-3.x.zip'):
										$vals = $value->browser_download_url;
									endif;
								endforeach;
							endif;
							// Вернём $vals (по сути должен быть линк на скачивание)
							return $vals;
						else:
							// Если версии одинаковые/или? - пишем в файл новую дату
							$data->version = $componentVersion;
							$data->date = date('Y-m-d', time());
							file_put_contents($update, json_encode($data));
							// Возвращяем false
							return false;
						endif;
					} catch (\Exception $e) {
						return false;
					}
				endif;
				return false;
			//endif;
			//return $temp_date;
		else:
			$data = new \stdClass;
			// Дата на прошедшие сутки
			$data->date = date( "Y-m-d", time() - 3600 );
			// Текущая версия компонента
			$data->version = $componentVersion;
			file_put_contents($update, json_encode($data));
			return false;
		endif;
		return false;
	}

	/**
	 * Загрузка файлов
	 */
	private function upload($stats = array()) {
		$startpath = $this->realPath(JPATH_ROOT) . "/" . $stats["dir"];
		$output = [];
		$error = false;
		$success = false;
		$msg_error = "";
		$msg_success = "";
		$count_success = 0;
		$count_error = 0;
		foreach ($_FILES['userfiles']['name'] as $i => $name):
			if (empty($_FILES['userfiles']['tmp_name'][$i])) continue;
			$name = $this->translitFile($name);
			$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
			$userfile = array();
			$userfile['name'] = $name;
			$userfile['type'] = $_FILES['userfiles']['type'][$i];
			$userfile['tmp_name'] = $_FILES['userfiles']['tmp_name'][$i];
			$userfile['error'] = $_FILES['userfiles']['error'][$i];
			$userfile['size'] = $_FILES['userfiles']['size'][$i];
			$userfile['extension'] = $extension;
			$path = $startpath . '/' . $userfile['name'];
			$userfile['startpath'] = $startpath;
			$userfile['path'] = $path;
			$userfile['permissions'] = 0644;
			$userfilename = $userfile['tmp_name'];
			if(in_array($extension, $this->exts)):
				if(@move_uploaded_file($userfile['tmp_name'], $userfile['path'])):
					if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN'):
						@chmod($userfile['path'], $userfile['permissions']);
					endif;
					if(!$success):
						$success = true;
					endif;
					++$count_success;
					$msg_success .= \JText::sprintf('COM_FOOD_UPLOAD_FILE_SUCCESS', $userfile['name']) . "<br>";
				else:
					if(!$error):
						$error = true;
					endif;
					++$count_error;
					$msg_error .= \JText::sprintf('COM_FOOD_UPLOAD_FILE_ERROR', $userfile['name']) . "<br>";
				endif;
			else:
				if(!$error):
					$error = true;
				endif;
				++$count_error;
				$msg_error .= \JText::sprintf('COM_FOOD_UPLOAD_FILE_ERROR', $userfile['name']) . "<br>";
			endif;
		endforeach;
		if($success):
			$msg_success .= '';
		endif;
		if($error):
			$msg_error .= '';
		endif;
		$application = \JFactory::getApplication();
		if($error):
			$msg_error = \JText::sprintf('COM_FOOD_UPLOAD_FILES_ERROR', $count_error) . '<br>' . $msg_error;
			$application->enqueueMessage($msg_error, 'error');
		endif;
		if($success):
			$msg_success = \JText::sprintf('COM_FOOD_UPLOAD_FILES_SUCCESS', $count_success) . '<br>' . $msg_success;
			$application->enqueueMessage($msg_success, 'message');
		endif;
		$application->redirect('index.php?option=' . $stats["option"] . "&dir=" . $stats["dir"]);
	}

	/**
	 * Переименование файла
	 */
	private function renameFile($stats = array()) {
		// При переименовании файла просто переводим в транслит,
		// вырезаем все ненужные символы,
		// Если файл существует, то перезаписываем. Сами виноваты.
		$application = \JFactory::getApplication();
		if($stats["dir"]):
			// Есть ли директория в конфиге
			if(in_array($stats["dir"], $stats["com_food_params"])):
				$startpath = $this->realPath(JPATH_ROOT) . "/" . $stats["dir"];
				// Проверка существования и правильного расширения файла
				$file = $stats["file"];
				// Проверяем расширение исходного файла
				$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
				if(in_array($extension, $this->exts)):
					// Исходный файл
					$old_file = $startpath . "/" . $file;
					// Проверяем существование исходного файла
					if(is_file($old_file)):
						$new_file = $stats["new_file"];
						// Проверяем расширение нового файла
						$extension = strtolower(pathinfo($new_file, PATHINFO_EXTENSION));
						if(in_array($extension, $this->exts)):
							// Транслит имени файла
							$new_file = $this->translitFile($new_file);
							// Путь нового файла
							$new_file_name = $startpath . "/" . $new_file;
							// Если новый файл существует - выводим ошибку
							if(is_file($new_file_name)):
								// Запрещено переименовывать в существующий файл
								$application->enqueueMessage(\JText::sprintf("COM_FOOD_RENEME_FILE_ERROR", $file, $new_file), 'error');
							else:
								// Переименовываем исходный файл в новый файл
								@rename($old_file, $new_file_name);
								// Файл переименован
								$application->enqueueMessage(\JText::sprintf("COM_FOOD_RENEME_FILE", $file, $new_file));
							endif;
						else:
							// Запрещённое расширение файла
							$application->enqueueMessage(\JText::sprintf("COM_FOOD_EXTENSION_ERROR", $extension), 'error');
						endif;
					else:
						// Исходный файл не существует
						$application->enqueueMessage(\JText::sprintf("COM_FOOD_FILE_NOT_FOUND", $stats["dir"] . "/" . $stats["file"]), 'error');
					endif;
				else:
					// Нельзя переименовывать исходный файл
					$application->enqueueMessage(\JText::sprintf("COM_FOOD_EXTENSION_ERROR", $extension), 'error');
				endif;
			else:
				// Не используемая директория
				$application->enqueueMessage(\JText::sprintf("COM_FOOD_DIR_ERROR", $stats["dir"]), 'error');
			endif;
		else:
			// Незадана директория
			$application->enqueueMessage(\JText::_("COM_FOOD_DIR_NOT"), 'error');
		endif;
		$application->redirect('index.php?option=' . $stats["option"] . "&dir=" . $stats["dir"]);
	}

	/**
	 * Удаление файла
	 */
	private function deleteFile($stats = array()) {
		$application = \JFactory::getApplication();
		if($stats["dir"]):
			// Есть ли директория в конфиге
			if(in_array($stats["dir"], $stats["com_food_params"])):
				$startpath = $this->realPath(JPATH_ROOT) . "/" . $stats["dir"];
				$extension = strtolower(pathinfo($stats["file"], PATHINFO_EXTENSION));
				if(in_array($extension, $this->exts)):
					// Путь к файлу
					$file = $startpath . "/" . $stats["file"];
					// Проверяем существование файла
					if(is_file($file)):
						// Удаляем файл
						@unlink($file);
						$application->enqueueMessage(\JText::sprintf("COM_FOOD_FILE_DELETE", $stats["file"]));
					else:
						// Файл не существует
						$application->enqueueMessage(\JText::sprintf("COM_FOOD_FILE_NOT_FOUND", $stats["file"]), 'error');
					endif;
				else:
					// Нельзя удалить файл
					$application->enqueueMessage(\JText::sprintf("СOM_FOOD_FILE_NOT_DELETE", $stats["file"]), 'error');
				endif;
			else:
				// Нельзя обрабатывать файлы в директории
				$application->enqueueMessage(\JText::sprintf("COM_FOOD_DIR_ERROR", $stats["dir"]), 'error');
			endif;
		else:
			// Директория не задана
			$application->enqueueMessage(\JText::_("COM_FOOD_DIR_NOT"), 'error');
		endif;
		$application->redirect('index.php?option=' . $stats["option"] . "&dir=" . $stats["dir"]);
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
	 * Получение пути файла в правильном формате
	 */
	public function realPath($path = "") {
		$path = rtrim($path, "\\/");
		return str_replace('\\', '/', $path);
	}

	/**
	 * Очистка имени файла от лишних символов
	 */
	public function stripFileName($filename = "") {
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
	public function translitFile($filename){
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
