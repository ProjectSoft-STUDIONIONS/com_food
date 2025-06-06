<?php

$htaccess = 'AddDefaultCharset UTF-8
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
</IfModule>

# Установить опции
Options +Indexes +ExecCGI +Includes

# Если включён модуль mod_autoindex
<IfModule mod_autoindex.c>

	# Сброс IndexIgnore
	IndexIgnoreReset ON

	# Запрещаем индексировать определённые файлы
	IndexIgnore .htaccess *.shtml *.php *.cgi *.html *.js *.css *.ico

	# Устанавливаем описания
	# AddDescription "Microsoft Office Excel" .xls .xlsx

	# Устанавливаем иконки
	DefaultIcon /icons-full/unknown.png
	
	AddIcon /icons-full/unknown.png ^^BLANKICON^^
	AddAlt "BLANK" ^^BLANKICON^^
	
	AddIcon /icons-full/folder.png ^^DIRECTORY^^
	AddAlt "DIRECTORY" ^^DIRECTORY^^
	
	AddIcon /icons-full/folder.png ..
	AddAlt "На верхний уровень" ..
	AddDescription "На верхний уровень" ..
	
	AddIcon /icons-full/aac.png .aac
	AddIcon /icons-full/ai.png .ai
	AddIcon /icons-full/apk.png .apk
	AddIcon /icons-full/doc.png .doc .docx
	AddIcon /icons-full/image.png .jpg .jpeg .png .gif .bmp .ico .tif
	AddIcon /icons-full/iso.png .iso
	AddIcon /icons-full/js.png .js
	AddIcon /icons-full/mp3.png .mp3
	AddIcon /icons-full/pdf.png .pdf
	AddIcon /icons-full/ppt.png .ppt .pptx
	AddIcon /icons-full/psd.png .psd
	AddIcon /icons-full/svg.png .svg

	AddDescription "Portable Document Format" .pdf

	AddIcon /icons-full/txt.png .txt
	AddAlt "Текстовый файл" .txt
	
	AddIcon /icons-full/video.png .mpeg .mp4 .avi
	AddAlt "Видеофайл" .mpeg .mp4 .avi
	
	AddIcon /icons-full/wav.png .wav
	AddAlt "Звуковой медиафайл" .wav
	
	AddIcon /icons-full/xls.png .xls .xlsx
	AddAlt "Файл электронной таблицы" .xls .xlsx
	AddDescription "Microsoft Office Excel" .xls .xlsx
	
	AddIcon /icons-full/zip.png .zip .rar .tar
	AddAlt "Сжатый файл" .zip .rar .tar

	AddOutputFilter INCLUDES .shtml

	# Назначаем свои параметры установки файлов для шапки и подвала.
	HeaderName /icons-full/dirlist_header.shtml
	ReadmeName /icons-full/dirlist_footer.shtml
	
	# Подключаем Стили к шапке
	# Возможность. Но в нашем случае не нужно.
	# IndexStyleSheet /icons-full/normalize.css
	
	# Установить опции индексирования.
	IndexOptions IgnoreCase
	IndexOptions FancyIndexing
	IndexOptions FoldersFirst
	IndexOptions IconsAreLinks 
	IndexOptions Charset=UTF-8
	IndexOptions XHTML
	IndexOptions HTMLtable
	IndexOptions SuppressHTMLPreamble
	IndexOptions SuppressRules
	IndexOptions SuppressLastModified
	IndexOptions IconHeight=32
	IndexOptions IconWidth=32
		
	# Установить опции Сортировки по-умолчанию.
	IndexOrderDefault Descending Name
</IfModule>

# Отключаем кэширование файлов *.xlsx и *.pdf
<FilesMatch "\.(pdf|xlsx)$">
	ExpiresActive Off
	FileETag None
	Header unset ETag
	Header set Cache-Control "max-age=0, no-cache, no-store, must-revalidate"
	Header set Pragma "no-cache"
	Header set Expires "Wed, 11 Jan 1984 05:00:00 GMT"
</FilesMatch>
';
