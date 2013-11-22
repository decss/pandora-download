<?php


function stationsImplode($path1, $path2) {
    $dir1 = scandir($path1);
    $dir2 = scandir($path2);
    $dir_diff = array_diff($dir2, $dir1);
    foreach ($dir_diff AS $file) {
        copy($path2. '/' . $file, $path1 . '/' . $file);
    }

    // print_r($dir1);
    // print_r($dir2);
    // print_r($dir_diff);

    foreach ($dir2 as $file) {
        if (!in_array($file, $dir_diff) AND is_file($path1 . '/' . $file)) {
            $file1 = file($path1 . '/' . $file);
            $file2 = file($path2 . '/' . $file);
            if ($file1 != $file2) {
                $file_diff = array_diff($file2, $file1);

                $f = fopen($path1 . '/' . $file, 'a');
                foreach ($file_diff as $str) {
                    fwrite($f, $str);
                }
                fclose($f);
                // print_r($file_diff);
            }
        }
    }


}

// echo '<pre>';
// stationsImplode('1','2');