<?php

/**
 * Class SassCompiler
 *
 * This simple tool compiles all .scss files in folder A to .css files (with exactly the same name) into folder B.
 * Everything happens right when you run your app, on-the-fly, in pure PHP. No Ruby needed, no configuration needed.
 *
 * SassWatcher is not a standalone compiler, it's just a little method that uses the excellent scssphp compiler written
 * by Leaf Corcoran (https://twitter.com/moonscript), which can be found here: http://leafo.net/scssphp/ and adds
 * automatic compiling to it.
 *
 * The currently supported version of SCSS syntax is 3.2.12, which is the latest one.
 * To avoid confusion: Sass is the name of the language itself, and also the "name" of the "first" version of the
 * syntax (which was quite different than CSS). Then the newer Sass syntax, "SCSS" was added, which is more like CSS, but
 * has Sass functionality.
 *
 * The compiler uses the SCSS syntax, which is recommended and mostly used. The old Sass syntax is not supported.
 *
 * @see Sass Wikipedia: http://en.wikipedia.org/wiki/Sass_%28stylesheet_language%29
 * @see Sass Homepage: http://sass-lang.com/
 * @see scssphp, the used compiler (in PHP): http://leafo.net/scssphp/
 */
class SassCompiler
{
    /**
     * Compiles all .scss files in a given folder into .css files in a given folder
     *
     * @param string $scss_folder source folder where you have your .scss files
     * @param string $css_folder destination folder where you want your .css files
     * @param string $format_style CSS output format, see http://leafo.net/scssphp/docs/#output_formatting for more.
     */
    static public function run($scss_folder, $css_folder, $format_style = "scss_formatter")
    {
        $start = microtime(true);
        $scss_folder = rtrim($scss_folder, '/');
        $css_folder = rtrim($css_folder, '/');
        // scssc will be loaded automatically via Composer
        $scss_compiler = new scssc();
        // set the path where your _mixins are
        $scss_compiler->setImportPaths($scss_folder);
        // set css formatting (normal, nested or minimized), @see http://leafo.net/scssphp/docs/#output_formatting
        $scss_compiler->setFormatter($format_style);
        // get all .scss files from scss folder
        //$filelist = glob($scss_folder . "[!_]*.scss");

        $dirlist = glob($scss_folder . "/*");

        $filelist = self::getFileList($scss_folder);


        foreach ($filelist as $file_path) {
            // get path elements from that file
            $file_path_elements = pathinfo($file_path);
            // get file's name without extension
            $file_name = $file_path_elements['filename'];
            $file_dir = $file_path_elements['dirname'];

            $file_destination_dir = str_replace($scss_folder, $css_folder, $file_dir);

            $in = $file_dir . '/' . $file_name . ".scss";
            $out = $file_destination_dir . '/' . $file_name . ".css";

            if (!is_dir($file_destination_dir)) {
                mkdir($file_destination_dir, 0777, true);
            }


            //if (! is_file($out) || filemtime($in) > filemtime($out)) {

//            $depends = self::getImports($in);
//
//            echo '<br>' ."All dependencies from " . $in;
//            echo '<br>';
//            print_r($depends);

            $string_sass = file_get_contents($in);






            $string_css = $scss_compiler->compile($string_sass);



            file_put_contents($out,$string_css);


            //}

        }

        $end = microtime(true);

        //echo '<br>' . round($end - $start,3).'s';
        //die();

        /*// step through all .scss files in that folder
        foreach ($filelist as $file_path) {
            // get path elements from that file
            $file_path_elements = pathinfo($file_path);
            // get file's name without extension
            $file_name = $file_path_elements['filename'];
            
            $in = $scss_folder . $file_name . ".scss";
            $out = $css_folder . $file_name . ".css";
   
            //if (! is_file($out) || filemtime($in) > filemtime($out)) {
                // get .scss's content, put it into $string_sass
                $string_sass = file_get_contents($in);
                // compile this Sass code to CSS
                $string_css = $scss_compiler->compile($string_sass);
                // write CSS into file with the same filename, but .css extension
                file_put_contents($out,$string_css);
            //}
            
        }*/

    }
    static private function normalizePath($path) {
        $explodedPath = explode('/',$path);

        $normalizedPath = array();
        foreach($explodedPath as $key=>$value) {
            if ($value == '..') {
                array_pop($normalizedPath);
            }

            else {
                $normalizedPath[] = $value;

            }
        }
        echo '<br>';
        $normalizedPath = implode($normalizedPath, '/');
        return $normalizedPath;

    }

    static private function getImports($file) {
        $depends = array();

        $file_path_elements = pathinfo($file);
        $file_name = $file_path_elements['filename'];
        $file_dir = $file_path_elements['dirname'];

        $file_contents = file_get_contents($file);

        preg_match_all("/^@import(.*);/m", $file_contents, $imports);

        if ($imports[1]) {
            $imports = $imports[1];

            foreach($imports as $import) {
                $import = str_replace(['"','\'', ' '], '', $import);

                $import_elements = pathinfo($import);

                $import_file_dir = $import_elements['dirname'];
                if ($import_file_dir == '.') {
                    $import_file_dir = $file_dir;
                }
                else {
                    $import_file_dir = $file_dir . '/' . $import_file_dir;
                }
                $import_file_dir = self::normalizePath($import_file_dir);

                $import_file_name = '_' . $import_elements['filename'] . '.scss';

                $import_file_path = $import_file_dir . '/' . $import_file_name;

                //echo '<br>';
                //echo $import_file_path;

                if (!is_file($import_file_path)) {
                    throw new Exception('Invalid Import: ' . $import_file_path);
                }
                else {
                    $depends[] = $import_file_path;
                    $depends = array_merge($depends, self::getImports($import_file_path));

                }

            }

        }

        return $depends;


    }
    static private function getFileList($scss_folder) {
        $files = array();
        $list = glob($scss_folder . "*");
        foreach ($list as $item) {
            if (is_dir($item)) {

                $files = array_merge($files, self::getFileList($item . '/'));

            }

            else {
                $path_parts = pathinfo($item);

                if ($path_parts['extension'] == 'scss') {

                    if ($path_parts['filename'][0] != '_') {
                        //echo '<br>' . $item;
                        $files[] = $item;
                    }

                }

            }
        }
        return $files;
    }
}
