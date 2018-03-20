<?php

//адрес сайта в формате http://site.com
$site = 'http://site.com/';

//адрес страницы с которой хотите начать рекурсивный просмотр сайта
//в формате http://site.com/page.
//Можно не указывать если Вы хотите начать со стартовой страницы
$address = '';

//директория в которую будут сохранятся изображения
$dir_to_save = '/images777';

//если вы хотите сохранять изображения только определенных форматов
//(с определенным расширением файла)
//перечислите их в данном массиве в нижнем регистре
//$allowable_format_image = ['jpg','png','gif];
$allowable_format_image = [];

//если сайт очень большой и вы хотите ограничить
//количество рекурсивных переходов по страницам сайта
//задайте число этих переходов в этой переменной
//если не хотите ограничивать присвойте значение false
$allowable_count_recurs = false;


$site = trim(trim(trim($site),'/'),'\\');
if (!$address) $address = $site;
$site = preg_replace('#^https?://#','',$site);

$dir_to_save = trim(trim(trim($dir_to_save),'/'),'\\');
if(!file_exists($dir_to_save)){
    if($dir_to_save)
        mkdir($dir_to_save);
}

function debug($arr){
    echo '<pre>'.print_r($arr,true).'</pre>';
}

$no_sumbol_name = ['\\','/','*','?','"','<','>',':','|'];
$type_img = [1=>'gif',2=>'jpg',3=>'png',4=>'swf',5=>'psd',6=>'bmp',7=>'tiff',8=>'tiff',9=>'jpc',10=>'jp2',11=>'jpx',12=>'jb2',13=>'swc',14=>'iff',15=>'wbmp',16=>'xbm',17=>'ico',18=>'webp'];

$all_link = [];
$all_link_internal = [];
$link_go = [$address];
$download_img = [];
$downloaded_img = [];
$stylesheet = [];
$count_recurs = 0;

function getAllLink($address){
    global $all_link, $all_link_internal ,$link_go, $download_img, $stylesheet, $site, $allowable_count_recurs, $count_recurs;
    $all_link_internal_go = [];

    $address = trim(trim(trim($address),'/'),'\\');
    $address_ext = pathinfo($address,PATHINFO_EXTENSION);
    if($address_ext == 'php' or $address_ext == 'html' or $address_ext == 'htm')
        $begin_addr = dirname($address);
    else $begin_addr = $address;
    $begin_addr = trim(trim($begin_addr,'/'),'\\');

    $page = file_get_contents($address);
    preg_match_all('#<a.*?href=(["\'])([^"\']+?)\1.*?>#m',$page,$links);
    $links = $links[2];
    foreach ($links as $link){
        $link = trim(trim(trim($link),'/'),'\\');
        if(strpos($link,'#') !== false){
            $link = str_replace('#'.parse_url($link, PHP_URL_FRAGMENT),'',$link);
        }
        if ($link and strpos($link,'javascript:') === false and strpos($link,'mailto:') === false){
            if(!in_array($link,$all_link)){
                $all_link[] = $link;
            }
            if(!in_array($link,$all_link_internal)){
                if(strpos($link,$site) !== false or (strpos($link,'https://') === false and strpos($link,'http://') === false)){
                    $linc_ext = pathinfo($link,PATHINFO_EXTENSION);
                    if(strpos($link,$site) !== false){
                        $all_link_internal[] = $link;
                        if(!in_array($link,$all_link_internal_go) and ($linc_ext == '' or $linc_ext == 'php' or $linc_ext == 'html' or $linc_ext == 'htm')){
                            $all_link_internal_go[] = $link;
                        }
                    }else{
                        if($linc_ext == '' or $linc_ext == 'php' or $linc_ext == 'html' or $linc_ext == 'htm'){
                            $link = $begin_addr.'/'.$link;
                            if(!in_array($link,$all_link_internal_go)){
                                $all_link_internal_go[] = $link;
                            }
                            if(!in_array($link,$all_link_internal)){
                                $all_link_internal[] = $link;
                            }
                        }
                    }
                }
            }
        }
    }

    preg_match_all('#<img\s.*?src=(["\'])([^"\']+?)\1.*?>#',$page,$images);
    $images = $images[2];
    addElementsToArr($images,$download_img,$begin_addr);

    preg_match_all('#url\((.+?)\)#',$page,$urls);
    $urls = $urls[1];
    addElementsToArr($urls,$download_img,$begin_addr);

    preg_match_all('#<link\s.*?rel=(["\'])stylesheet\1\s.*?href=(["\'])([^"\']+?)\1.*?>#',$page,$stylesheet1);
    preg_match_all('#<link\s.*?href=(["\'])([^"\']+?)\1\s.*?rel=(["\'])stylesheet\1.*?>#',$page,$stylesheet2);
    $stylesheet3 = array_merge($stylesheet1[3],$stylesheet2[2]);
    addElementsToArr($stylesheet3,$stylesheet,$begin_addr);


    $count_recurs++;
    foreach ($all_link_internal_go as $go){
        if(!in_array($go,$link_go)){
            if($allowable_count_recurs === false){
                $link_go[] = $go;
                getAllLink($go);
            }elseif($allowable_count_recurs > $count_recurs){
                    $link_go[] = $go;
                    getAllLink($go);
                }

        }
    }

}


function addElementsToArr($arr_elements,&$arr_to_add,$begin_addr){
    if($arr_elements){
        foreach ($arr_elements as $url){
            $url = trim(trim(trim(trim(trim($url),'/'),'\\'),"'"),'"');
            if(strpos($url,'https://') === false and strpos($url,'http://') === false and strpos($url,';base64') === false){
                $url = $begin_addr.'/'.$url;
            }
            if(!in_array($url,$arr_to_add)){
                $arr_to_add[] = $url;
            }
        }
    }
}

getAllLink($address);

foreach ($stylesheet as $address_css){
    $css_f_cont = file_get_contents($address_css);
    preg_match_all('#url\((.+?)\)#',$css_f_cont,$urls);
    $urls = $urls[1];

    $address_css = trim(trim(trim($address_css),'/'),'\\');
    $address_ext = pathinfo($address_css,PATHINFO_EXTENSION);
    if($address_ext !== '')
        $begin_addr = dirname($address_css);
    else $begin_addr = $address_css;
    $begin_addr = trim(trim($begin_addr,'/'),'\\');

    foreach ($urls as $url){
        $url = trim(trim(trim(trim(trim($url,'/'),'\\')),"'"),'"');
        if(strpos($url,'https://') === false and strpos($url,'http://') === false and strpos($url,';base64') === false){
            $url = $begin_addr.'/'.$url;
        }
        if(!in_array($url,$download_img)){
            $download_img[] = $url;
        }
    }
}


if (!$dir_to_save){
    echo "Не задана директория для сохранения изображений";
}else{
    $j = 1;
    foreach ($download_img as $img){
        $img_name = $img;
        if(strpos($img_name,'?') !== false)
            $img_name = preg_replace('#\?.*$#','',$img);

        $img_name = str_replace($no_sumbol_name,'',pathinfo($img_name,PATHINFO_BASENAME));
        $img_ext = pathinfo($img_name,PATHINFO_EXTENSION);
        if(!in_array($img_ext,$type_img)){
            if(strpos($img,';base64') !== false){
                $img_name = 'image_base64_'.uniqid().'.'.$type_img[exif_imagetype($img)];
            }else{
                if($new_ext = $type_img[@exif_imagetype($img)])
                    $img_name = $img_name.'.'.$new_ext;
            }
        }
        if(!$allowable_format_image){
            if(@copy($img,"$dir_to_save/".$j.$img_name)){
                $downloaded_img[] = "$dir_to_save/".$j.$img_name;
                echo "$dir_to_save/".$j.$img_name.'<br>';
                $j++;
            }
        }else{
            if(in_array(strtolower($img_ext),$allowable_format_image)){
                if(@copy($img,"$dir_to_save/".$j.$img_name)){
                    $downloaded_img[] = "$dir_to_save/".$j.$img_name;
                    echo "$dir_to_save/".$j.$img_name.'<br>';
                    $j++;
                }
            }
        }
    }
}

//debug($all_link); //все ссылки сайта
//debug($all_link_internal); //все внутренние ссылки сайта
//debug($link_go); // ссылки по которым был произведен переход
//debug($stylesheet); // все css файлы стилей сайта
//debug($download_img); // адреса изображений которые можно скачать
//debug($downloaded_img); // скачанные изображения
