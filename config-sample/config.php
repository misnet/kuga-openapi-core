<?php
//程序运行时，系统会先读取config.default.php内容，然后再用这个文件中的内容进行覆盖
$_CONFIG['testmodel'] = true;
return $_CONFIG;