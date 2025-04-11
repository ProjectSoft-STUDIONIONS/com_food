module.exports = function(grunt) {
	var fs = require('fs'),
		PACK = grunt.file.readJSON('package.json'),
		path = require('path'),
		chalk = require('chalk'),
		hash = function (...args) {
			var md5 = require('md5');
			let result = "",
				arr = [];
			if(!args.length){
				let time = (new Date()).getTime();
				arr.push("Not arguments");
				result = md5(time).toString();
			}else{
				let text = "";
				for(let index in args){
					let file = args[index];
					file = path.normalize(path.join(__dirname, file));
					try{
						let buff = fs.readFileSync(file, {
							encoding: "utf8"
						}).toString();
						text += buff;
						arr.push(file);
					}catch(e){
						// Ничего не делаем
						arr.push("Not found");
					}
				}
				result = md5(text).toString();
			}
			arr.push(result);
			grunt.log.oklns([chalk.cyan("Generate hash:") + "\n" + chalk.yellow(arr.join("\n"))]);
			return result;
		},
		cName = 'Компонент «ПИТАНИЕ» для Joomla CMS'.replace(
			/[\u0080-\uFFFF]/g,
			function (s) {
				return "\\u" + ('000' + s.charCodeAt(0).toString(16)).substr(-4);
			}
		);

	require('load-grunt-tasks')(grunt);
	require('time-grunt')(grunt);

	var gc = {
		version: `${PACK.version}`,
		default: [
			// Копирование вьювера
			"copy:viewer3",
			"copy:viewer4",
			// Компиляция JS
			"concat",
			"uglify",
			// Компиляция CSS
			"less",
			"autoprefixer",
			"cssmin",
			// Копирование основных файлов
			"copy:main3",
			"copy:food3",
			"copy:htacces3",
			"copy:main4",
			"copy:food4",
			"copy:htacces4",
			// Копирование языка DataTable
			"copy:json3",
			"copy:json4",
			// Копирование JS
			"copy:test3",
			"copy:test4",
			// Компиляция XML
			"pug",
			// Архивирование
			"compress"
		]
	};

	require('load-grunt-tasks')(grunt);
	require('time-grunt')(grunt);

	let arr = `${PACK.homepage}`.split('/'),
		author = PACK.author.replace(/^(.*)(\s+<.*>)/, "$1"),
		authorEmail = PACK.author.replace(/^(?:.*)\s+<(.*)>/, "$1"),
		authorUrl = "";

	arr.pop();
	authorUrl = arr.join("/");
	
	grunt.initConfig({
		globalConfig : gc,
		pkg : PACK,
		less: {
			css: {
				options : {
					compress: false,
					ieCompat: false,
					plugins: []
				},
				files : {
					// component-3x
					'component-3x/com_food/admin/assets/css/main.css' : [
						'src-3/less/main.less'
					],
					// component-5x
					'component-5x/com_food/admin/assets/css/main.css' : [
						'src-4-5/less/main.less'
					],
				}
			},
		},
		autoprefixer:{
			options: {
				browsers: [
					"last 4 version"
				],
				cascade: true
			},
			css: {
				files: {
					// component-3x
					'component-3x/com_food/admin/assets/css/main.css' : [
						'component-3x/com_food/admin/assets/css/main.css'
					],
					// component-5x
					'component-5x/com_food/admin/assets/css/main.css' : [
						'component-5x/com_food/admin/assets/css/main.css'
					]
				}
			},
		},
		cssmin: {
			options: {
				mergeIntoShorthands: false,
				roundingPrecision: -1
			},
			minify: {
				files: {
					// component-3x
					'component-3x/com_food/admin/assets/css/main.min.css' : [
						'component-3x/com_food/admin/assets/css/main.css'
					],
					// component-5x
					'component-5x/com_food/admin/assets/css/main.min.css' : [
						'component-5x/com_food/admin/assets/css/main.css'
					],
				}
			},
		},
		concat: {
			options: {
				separator: "\n",
			},
			app: {
				src: [
					'bower_components/jquery/dist/jquery.js',
					'bower_components/pdfmake/build/pdfmake.js',
					'bower_components/jszip/dist/jszip.js',
					'bower_components/pdfmake/build/vfs_fonts.js',
					'bower_components/datatables.net/js/dataTables.js',
					'bower_components/datatables.net-buttons/js/dataTables.buttons.js',
					'bower_components/datatables.net-buttons/js/buttons.html5.js',
					'bower_components/datatables.net-bs/js/dataTables.bootstrap.js'
				],
				dest: 'test/js/jquery.min.js'
			},
			// component-3x
			main3: {
				src: [
					'src/js/main.js'
				],
				dest: 'component-3x/com_food/admin/assets/js/main.min.js'
			},
			// component-5x
			main4: {
				src: [
					'src/js/main.js'
				],
				dest: 'component-5x/com_food/admin/assets/js/main.min.js'
			},
		},
		uglify: {
			options: {
				sourceMap: false,
				compress: {
					drop_console: false
				},
				output: {
					ascii_only: true
				}
			},
			// component-3x
			app: {
				files: [
					{
						expand: true,
						flatten : true,
						src: [
							'test/js/jquery.min.js'
						],
						dest: 'test/js',
						filter: 'isFile',
					}
				]
			},
			main3: {
				options : {
					banner : "const componentName = `" + cName + " 3.x`, Developer = `ProjectSoft`;"
				},
				files: [
					{
						expand: true,
						flatten : true,
						src: [
							'component-3x/com_food/admin/assets/js/main.min.js'
						],
						dest: 'component-3x/com_food/admin/assets/js',
						filter: 'isFile',
					}
				]
			},
			viewer3: {
				files: [
					{
						expand: true,
						flatten : true,
						src: [
							'component-3x/com_food/viewer/app.js'
						],
						dest: 'component-3x/com_food/viewer',
						filter: 'isFile',
						rename: function (dst, src) {
							return dst + '/' + src.replace('.js', '.min.js');
						}
					}
				]
			},
			// component-5x
			main4: {
				options : {
					banner : "const componentName = `" + cName + " Joomla CMS 4.x-5.x`, Developer = `ProjectSoft`;"
				},
				files: [
					{
						expand: true,
						flatten : true,
						src: [
							'component-5x/com_food/admin/assets/js/main.min.js'
						],
						dest: 'component-5x/com_food/admin/assets/js',
						filter: 'isFile',
					}
				]
			},
			viewer4: {
				files: [
					{
						expand: true,
						flatten : true,
						src: [
							'component-5x/com_food/viewer/app.js'
						],
						dest: 'component-5x/com_food/viewer',
						filter: 'isFile',
						rename: function (dst, src) {
							return dst + '/' + src.replace('.js', '.min.js');
						}
					}
				]
			}
		},
		pug: {
			// component-3x
			serv3: {
				options: {
					doctype: 'html',
					client: false,
					pretty: '\t',
					separator:  '\n',
					data: function(dest, src) {
						return {
							"version": PACK.version,
							"create": grunt.template.date((new Date()).getTime(), "yyyy-mm-dd"),
							"description": PACK.description.replace(/(com_food|food)/g, "<code>$1</code>"),
							"license": PACK.license,
							"author": author,
							"authorEmail": authorEmail,
							"authorUrl": authorUrl
						}
					}
				},
				files: [
					{
						expand: true,
						cwd: __dirname + '/src-3/pug/',
						src: [ 'food.pug' ],
						dest: __dirname + '/component-3x/com_food/',
						ext: '.xml'
					},
					{
						expand: true,
						cwd: __dirname + '/src-3/pug/',
						src: [ 'config.pug', 'access.pug' ],
						dest: __dirname + '/component-3x/com_food/admin/',
						ext: '.xml'
					},
				]
			},
			// component-5x
			serv4: {
				options: {
					doctype: 'html',
					client: false,
					pretty: '\t',
					separator:  '\n',
					data: function(dest, src) {
						return {
							"version": PACK.version,
							"create": grunt.template.date((new Date()).getTime(), "yyyy-mm-dd"),
							"description": PACK.description.replace(/(com_food|food)/g, "<code>$1</code>"),
							"license": PACK.license,
							"author": author,
							"authorEmail": authorEmail,
							"authorUrl": authorUrl
						}
					}
				},
				files: [
					{
						expand: true,
						cwd: __dirname + '/src-4-5/pug/',
						src: [ 'food.pug' ],
						dest: __dirname + '/component-5x/com_food/',
						ext: '.xml'
					},
					{
						expand: true,
						cwd: __dirname + '/src-4-5/pug/',
						src: [ 'config.pug', 'access.pug' ],
						dest: __dirname + '/component-5x/com_food/admin/',
						ext: '.xml'
					},
				]
			},
		},
		copy: {
			// Fonts
			main3: {
				expand: true,
				cwd: 'bower_components/bootstrap/dist/fonts',
				src: '**',
				dest: 'component-3x/com_food/admin/assets/fonts/',
			},
			main4: {
				expand: true,
				cwd: 'bower_components/bootstrap/dist/fonts',
				src: '**',
				dest: 'component-5x/com_food/admin/assets/fonts/',
			},
			// food
			food3: {
				expand: true,
				cwd: 'bower_components/food/icons-full',
				src: '**',
				dest: 'component-3x/com_food/icons-full/',
			},
			food4: {
				expand: true,
				cwd: 'bower_components/food/icons-full',
				src: '**',
				dest: 'component-5x/com_food/icons-full/',
			},
			// viewer
			viewer3: {
				expand: true,
				cwd: 'bower_components/food/viewer',
				src: '**',
				dest: 'component-3x/com_food/viewer/',
			},
			viewer4: {
				expand: true,
				cwd: 'bower_components/food/viewer',
				src: '**',
				dest: 'component-5x/com_food/viewer/',
			},
			// htaccess
			htacces3: {
				expand: true,
				cwd: 'htacces',
				src: '.*',
				dest: 'component-3x/com_food/admin/models/',
			},
			htacces4: {
				expand: true,
				cwd: 'htacces',
				src: '.*',
				dest: 'component-5x/com_food/admin/htaccess/',
			},
			// JSON lang
			json3: {
				expand: true,
				cwd: 'lang_data',
				src: '**',
				dest: 'component-3x/com_food/admin/assets/js/',
			},
			json4: {
				expand: true,
				cwd: 'lang_data',
				src: '**',
				dest: 'component-5x/com_food/admin/assets/js/',
			},
			// jquery
			test3: {
				expand: true,
				cwd: 'test/js',
				src: '**',
				dest: 'component-3x/com_food/admin/assets/js/',
			},
			test4: {
				expand: true,
				cwd: 'test/js',
				src: '**',
				dest: 'component-5x/com_food/admin/assets/js/',
			}
		},
		compress: {
			// component-3x
			main3: {
				options: {
					archive: 'com_food-3.x.zip'
				},
				files: [
					{
						expand: true,
						cwd: './component-3x/',
						src: [
							'com_food/**',
							'com_food/**/.*',
							'com_food/**/.htacc*',
						],
						dest: '/'
					},
				],
			},
			// component-5x
			main4: {
				options: {
					archive: 'com_food-4.x-5.x.zip'
				},
				files: [
					{
						expand: true,
						cwd: './component-5x/',
						src: [
							'com_food/**',
							'com_food/**/.*',
						],
						dest: '/'
					},
				],
			},
		},
	});
	grunt.registerTask('default',	gc.default);
};
