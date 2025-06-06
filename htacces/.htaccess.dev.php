<?php
$htaccess = 'AddDefaultCharset UTF-8
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
</IfModule>

# Установить опции
Options +Indexes +ExecCGI +Includes

<IfModule mod_autoindex.c>
	# Сброс IndexIgnore
	IndexIgnoreReset ON

	# Запрещаем индексировать определённые файлы
	IndexIgnore .htaccess *.shtml *.php *.cgi *.html *.js *.css *.ico

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
