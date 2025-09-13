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

	var gc = {
		version: `${PACK.version}`,
		src: [
			'bower_components/jquery/dist/jquery.js',
			'bower_components/pdfmake/build/pdfmake.js',
			'bower_components/jszip/dist/jszip.js',
			'bower_components/pdfmake/build/vfs_fonts.js',
			'bower_components/datatables.net/js/dataTables.js',
			'bower_components/datatables.net-buttons/js/dataTables.buttons.js',
			'bower_components/datatables.net-buttons/js/buttons.html5.js',
			'bower_components/datatables.net-buttons/js/buttons.colVis.js',
			'bower_components/datatables.net-buttons/js/buttons.print.js',
			'bower_components/datatables.net-bs/js/dataTables.bootstrap.js',
			'bower_components/datatables.net-buttons-bs/js/buttons.bootstrap.js'
		],
		default: [
			"clean",
			// Компиляция JS
			"concat",
			"uglify",
			// Компиляция CSS
			"less",
			"autoprefixer",
			"cssmin",
			// Копирование вьювера
			"copy:viewer",
			// Копирование основных файлов
			"copy:main",
			"copy:food",
			"copy:htacces",
			// Копирование языка DataTable
			"copy:json",
			// Copy lang
			"copy:lang",
			// Копирование JS
			"copy:test",

			"copy:services",

			"copy:src",

			"copy:tmpl",

			"copy:files",
			// Компиляция XML
			"pug:serv3",
			"pug:serv4",
			"pug:serv5",
			// Архивирование
			"compress",
			// SHA
			"sha",
			"pug:update3",
			"pug:update4",
			"pug:update5",
			// docs
			"pug:docs"
		],
		dev: [
			"sha"
		]
	};

	require('load-grunt-tasks')(grunt);
	require('time-grunt')(grunt);
	require('./modules/sha.js')(grunt);

	let arr = `${PACK.homepage}`.split('/'),
		author = PACK.author.replace(/^(.*)(\s+<.*>)/, "$1"),
		authorEmail = PACK.author.replace(/^(?:.*)\s+<(.*)>/, "$1"),
		authorUrl = "",
		versionPath = path.join(__dirname, "docs", gc.version);

	arr.pop();
	authorUrl = arr.join("/");

	if(!grunt.file.isDir(versionPath)) {
		grunt.file.mkdir(versionPath);
	}
	
	grunt.initConfig({
		globalConfig : gc,
		pkg : PACK,
		clean: {
			docs: [
				`docs/${gc.version}/*.zip`,
				`docs/${gc.version}/*.json`
			]
		},
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
						//'bower_components/bootstrap/less/bootstrap.less',
						'bower_components/datatables.net-bs/css/dataTables.bootstrap.css',
						'bower_components/datatables.net-buttons-bs/css/buttons.bootstrap.css',
						'src-3/less/main.less'
					],
					// component-4x
					'component-4x/com_food/admin/assets/css/main.css' : [
						//'bower_components/bootstrap/less/bootstrap.less',
						'bower_components/datatables.net-bs/css/dataTables.bootstrap.css',
						'bower_components/datatables.net-buttons-bs/css/buttons.bootstrap.css',
						'src-4-5/less/main.less'
					],
					// component-5x
					'component-5x/com_food/admin/assets/css/main.css' : [
						//'bower_components/bootstrap/less/bootstrap.less',
						'bower_components/datatables.net-bs/css/dataTables.bootstrap.css',
						'bower_components/datatables.net-buttons-bs/css/buttons.bootstrap.css',
						'src-4-5/less/main.less'
					],
					// docs
					'src-docs/main.css': [
						'src-docs/less/main.less'
					]
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
					// component-4x
					'component-4x/com_food/admin/assets/css/main.css' : [
						'component-4x/com_food/admin/assets/css/main.css'
					],
					// component-5x
					'component-5x/com_food/admin/assets/css/main.css' : [
						'component-5x/com_food/admin/assets/css/main.css'
					],
					// docs
					'src-docs/main.css': [
						'src-docs/main.css'
					],
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
					// component-4x
					'component-4x/com_food/admin/assets/css/main.min.css' : [
						'component-4x/com_food/admin/assets/css/main.css'
					],
					// component-5x
					'component-5x/com_food/admin/assets/css/main.min.css' : [
						'component-5x/com_food/admin/assets/css/main.css'
					],
					// docs
					'src-docs/main.css': [
						'src-docs/main.css'
					],
				}
			},
		},
		concat: {
			options: {
				separator: "\n",
			},
			app: {
				src: gc.src,
				dest: 'test/js/jquery.min.js'
			},
			// component-3x
			main3: {
				src: [
					'src/js/main.js'
				],
				dest: 'component-3x/com_food/admin/assets/js/main.min.js'
			},
			// component-4x
			main4: {
				src: [
					'src/js/main.js'
				],
				dest: 'component-4x/com_food/admin/assets/js/main.min.js'
			},
			// component-5x
			main5: {
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
			// component-4x
			main4: {
				options : {
					banner : "const componentName = `" + cName + " Joomla CMS 4.x`, Developer = `ProjectSoft`;"
				},
				files: [
					{
						expand: true,
						flatten : true,
						src: [
							'component-4x/com_food/admin/assets/js/main.min.js'
						],
						dest: 'component-4x/com_food/admin/assets/js',
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
							'component-4x/com_food/viewer/app.js'
						],
						dest: 'component-4x/com_food/viewer',
						filter: 'isFile',
						rename: function (dst, src) {
							return dst + '/' + src.replace('.js', '.min.js');
						}
					}
				]
			},
			// component-5x
			main5: {
				options : {
					banner : "const componentName = `" + cName + " Joomla CMS 5.x`, Developer = `ProjectSoft`;"
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
			viewer5: {
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
							"authorUrl": authorUrl,
							"versZip": "3.x"
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
			// component-4x
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
							"authorUrl": authorUrl,
							"versZip": "4.x"
						}
					}
				},
				files: [
					{
						expand: true,
						cwd: __dirname + '/src-4-5/pug/',
						src: [ 'food.pug' ],
						dest: __dirname + '/component-4x/com_food/',
						ext: '.xml'
					},
					{
						expand: true,
						cwd: __dirname + '/src-4-5/pug/',
						src: [ 'config.pug', 'access.pug' ],
						dest: __dirname + '/component-4x/com_food/admin/',
						ext: '.xml'
					},
				]
			},
			// component-5x
			serv5: {
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
							"authorUrl": authorUrl,
							"versZip": "5.x"
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
			update3: {
				options: {
					doctype: 'html',
					client: false,
					pretty: '\t',
					separator:  '\n',
					data: function(dest, src) {
						let json = grunt.file.readJSON(__dirname + '/docs/' + PACK.version + '/com_food-3.x.json');
						json.joomla = "3.[0123456789]";
						json.author = author;
						json.zip = "3.x";
						json.version = PACK.version;
						return json;
					}
				},
				files: [
					{
						expand: true,
						cwd: __dirname + '/src-docs/pug/',
						src: [ 'food-update.pug' ],
						dest: __dirname + '/docs/',
						ext: '-3.x.xml'
					},
				]
			},
			update4: {
				options: {
					doctype: 'html',
					client: false,
					pretty: '\t',
					separator:  '\n',
					data: function(dest, src) {
						let json = grunt.file.readJSON(__dirname + '/docs/' + PACK.version + '/com_food-4.x.json');
						json.joomla = "4.[0123456789]";
						json.author = author;
						json.zip = "4.x";
						json.version = PACK.version;
						return json;
					}
				},
				files: [
					{
						expand: true,
						cwd: __dirname + '/src-docs/pug/',
						src: [ 'food-update.pug' ],
						dest: __dirname + '/docs/',
						ext: '-4.x.xml'
					},
				]
			},
			update5: {
				options: {
					doctype: 'html',
					client: false,
					pretty: '\t',
					separator:  '\n',
					data: function(dest, src) {
						let json = grunt.file.readJSON(__dirname + '/docs/' + PACK.version + '/com_food-5.x.json');
						json.joomla = "5.[0123456789]";
						json.author = author;
						json.zip = "5.x";
						json.version = PACK.version;
						return json;
					}
				},
				files: [
					{
						expand: true,
						cwd: __dirname + '/src-docs/pug/',
						src: [ 'food-update.pug' ],
						dest: __dirname + '/docs/',
						ext: '-5.x.xml'
					},
				]
			},
			docs: {
				options: {
					doctype: 'html',
					client: false,
					pretty: '',
					separator:  '',
					data: function(dest, src) {
						return {};
					}
				},
				files: [
					{
						expand: true,
						cwd: __dirname + '/src-docs/pug/',
						src: [ 'index.pug' ],
						dest: __dirname + '/docs/',
						ext: '.html'
					},
				]
			}
		},
		copy: {
			// Fonts
			main: {
				files: [
					{
						expand: true,
						cwd: 'bower_components/bootstrap/dist/fonts',
						src: '**',
						dest: 'component-3x/com_food/admin/assets/fonts/',
					},
					{
						expand: true,
						cwd: 'bower_components/bootstrap/dist/fonts',
						src: '**',
						dest: 'component-4x/com_food/admin/assets/fonts/',
					},
					{
						expand: true,
						cwd: 'bower_components/bootstrap/dist/fonts',
						src: '**',
						dest: 'component-5x/com_food/admin/assets/fonts/',
					},
				]

			},
			// food
			food: {
				files: [
					{
						expand: true,
						cwd: 'bower_components/food/icons-full',
						src: '**',
						dest: 'component-3x/com_food/icons-full/',
					},
					{
						expand: true,
						cwd: 'bower_components/food/icons-full',
						src: '**',
						dest: 'component-4x/com_food/icons-full/',
					},
					{
						expand: true,
						cwd: 'bower_components/food/icons-full',
						src: '**',
						dest: 'component-5x/com_food/icons-full/',
					},
				]
			},
			// viewer
			viewer: {
				files: [
					{
						expand: true,
						cwd: 'bower_components/food/viewer',
						src: '**',
						dest: 'component-3x/com_food/viewer/',
					},
					{
						expand: true,
						cwd: 'bower_components/food/viewer',
						src: '**',
						dest: 'component-4x/com_food/viewer/',
					},
					{
						expand: true,
						cwd: 'bower_components/food/viewer',
						src: '**',
						dest: 'component-5x/com_food/viewer/',
					},
				]
			},
			// htaccess
			htacces: {
				files: [
					{
						expand: true,
						cwd: 'htacces',
						src: '.*',
						dest: 'component-3x/com_food/admin/models/',
					},
					{
						expand: true,
						cwd: 'htacces',
						src: '.*',
						dest: 'component-4x/com_food/admin/htaccess/',
					},
					{
						expand: true,
						cwd: 'htacces',
						src: '.*',
						dest: 'component-5x/com_food/admin/htaccess/',
					},
				]
			},
			// JSON lang
			json: {
				files: [
					{
						expand: true,
						cwd: 'lang_data',
						src: '**',
						dest: 'component-3x/com_food/admin/assets/js/',
					},
					{
						expand: true,
						cwd: 'lang_data',
						src: '**',
						dest: 'component-4x/com_food/admin/assets/js/',
					},
					{
						expand: true,
						cwd: 'lang_data',
						src: '**',
						dest: 'component-5x/com_food/admin/assets/js/',
					},
				]
			},
			// jquery
			test: {
				files: [
					{
						expand: true,
						cwd: 'test/js',
						src: '**',
						dest: 'component-3x/com_food/admin/assets/js/',
					},
					{
						expand: true,
						cwd: 'test/js',
						src: '**',
						dest: 'component-4x/com_food/admin/assets/js/',
					},
					{
						expand: true,
						cwd: 'test/js',
						src: '**',
						dest: 'component-5x/com_food/admin/assets/js/',
					},
				]
			},
			lang: {
				files: [
					{
						expand: true,
						cwd: 'lang',
						src: '**',
						dest: 'component-3x/com_food/admin/languages/',
					},
					{
						expand: true,
						cwd: 'lang',
						src: '**',
						dest: 'component-4x/com_food/admin/languages/',
					},
					{
						expand: true,
						cwd: 'lang',
						src: '**',
						dest: 'component-5x/com_food/admin/languages/',
					}
				]
			},
			services: {
				files: [
					{
						expand: true,
						cwd: 'src-4-5/services',
						src: '**',
						dest: 'component-4x/com_food/admin/services/',
					},
					{
						expand: true,
						cwd: 'src-4-5/services',
						src: '**',
						dest: 'component-5x/com_food/admin/services/',
					},
				]
			},
			src: {
				files: [
					{
						expand: true,
						cwd: 'src-4-5/src',
						src: '**',
						dest: 'component-4x/com_food/admin/src/',
					},
					{
						expand: true,
						cwd: 'src-4-5/src',
						src: '**',
						dest: 'component-5x/com_food/admin/src/',
					},
				]
			},
			tmpl: {
				files: [
					{
						expand: true,
						cwd: 'src-4-5/tmpl',
						src: '**',
						dest: 'component-4x/com_food/admin/tmpl/',
					},
					{
						expand: true,
						cwd: 'src-4-5/tmpl',
						src: '**',
						dest: 'component-5x/com_food/admin/tmpl/',
					},
				]
			},
			// files
			files: {
				files: [
					// includes files within path
					{
						expand: true,
						cwd: 'src-4-5',
						src: ['\.*'],
						dest: 'component-4x/com_food/admin/',
					},
					{
						expand: true,
						cwd: 'src-4-5',
						src: ['\.*'],
						dest: 'component-5x/com_food/admin/',
					},
					{
						expand: true,
						cwd: 'src-4-5',
						src: ['install.php'],
						dest: 'component-4x/com_food/',
					},
					{
						expand: true,
						cwd: 'src-4-5',
						src: ['install.php'],
						dest: 'component-5x/com_food/',
					},
				],
			},
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
					archive: 'com_food-4.x.zip'
				},
				files: [
					{
						expand: true,
						cwd: './component-4x/',
						src: [
							'com_food/**',
							'com_food/**/.*',
						],
						dest: `/`
					},
				],
			},
			// component-5x
			main5: {
				options: {
					archive: 'com_food-5.x.zip'
				},
				files: [
					{
						expand: true,
						cwd: './component-5x/',
						src: [
							'com_food/**',
							'com_food/**/.*',
						],
						dest: `/`
					},
				],
			},
			// component-3x
			docs3: {
				options: {
					archive: `docs/${gc.version}/com_food-3.x.zip`
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
						dest: `/`
					},
				],
			},
			// component-4x
			docs4: {
				options: {
					archive: `docs/${gc.version}/com_food-4.x.zip`
				},
				files: [
					{
						expand: true,
						cwd: './component-4x/',
						src: [
							'com_food/**',
							'com_food/**/.*',
						],
						dest: `/`
					},
				],
			},
			// component-4x
			docs5: {
				options: {
					archive: `docs/${gc.version}/com_food-5.x.zip`
				},
				files: [
					{
						expand: true,
						cwd: './component-5x/',
						src: [
							'com_food/**',
							'com_food/**/.*',
						],
						dest: `/`
					},
				],
			},
		},
		sha: {
			docs: {
				options: {},
				files: [
					{
						src: [`docs/${gc.version}/com_food-3.x.zip`],
						dest: `docs/${gc.version}/com_food-3.x.json`
					},
					{
						src: [`docs/${gc.version}/com_food-4.x.zip`],
						dest: `docs/${gc.version}/com_food-4.x.json`
					},
					{
						src: [`docs/${gc.version}/com_food-5.x.zip`],
						dest: `docs/${gc.version}/com_food-5.x.json`
					},
				]
			}
		}
	});
	grunt.registerTask('default',	gc.default);
	//grunt.registerTask('default',	gc.dev);
};
