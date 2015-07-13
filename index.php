<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include 'includes.php';
function parse_json_data($filepath){
    $raw_data=file_get_contents($filepath);
 return  json_decode('['.ltrim($raw_data,',').']');

//echo json_last_error();
}

$folder_of_course_contents="D:\Aktuelle Dateien\ultimateFORCE\Eigene Dokumente\udemy-downloads";
function sort_by_course_file_number_schlechte_loesung($a,$b){//echo $a[2];
    //return strcmp($a[2], $b[2]);
$number1_a=substr($a[2],0,strpos($a[2],'_'));
$number1_b=substr($b[2],0,strpos($b[2],'_'));//schlechtes copy n paste
    $erg= $number1_a - $number1_b;
    if($erg==0){
       // $number1_a=substr($a[2],strpos($a[2],'_')+2,strlen(substr($a[2],strpos($a[2],'_')+2,strpos($a[2],'.')-1)-1));
        //haett ich vorher auch nur preg_match gemacht
        preg_match("~__([0-9]{1,})\.~",$a[2],$m);
        preg_match("~__([0-9]{1,})\.~",$b[2],$mb);
        if(isset($mb[1])&&isset($m[1])){
            $erg= $m[1] - $mb[1];
           
        }
         else{
                //scheißegal was dann
            }
    }
    return $erg;
}
//kann Daten aus dem ganzen System lesen mit ../ also nicht gut
class data{
     public static  $additional_data_json=false;
    public static $url_data=false;
};
function main_page_controller($folder_of_course_contents,$additional_path=''){
    
        document_begin();
    $scanned_directory = array_diff(scandir($folder_of_course_contents), array('..', '.','_____________directory_of_partial_downloads_list'));//http://php.net/manual/de/function.scandir.php
    foreach($scanned_directory as $k=>$v){
        echo '<a href="?course='.$additional_path.urlencode($v).'">'.mb_convert_encoding($v,'UTF-8').'</a>'."</br>";
        
    }
}
if(isset($_GET["course"])){
  
    $new_top_dir=$folder_of_course_contents."\\".urldecode($_GET["course"]);
      //$new_top_dir=;
      // echo $new_top_dir;
    if(is_dir($new_top_dir))//einfacher Angriff möglich . .. 
    {$json_file=$new_top_dir."\\"."course_additional_data.json";
    if(isset($_GET["stream"])){
        //stream mode
       
        $sth=new StreamHelper($new_top_dir);
       // StreamHelper
        //echo file_get_contents($new_top_dir."\\".$_GET["stream"].".mp4");//so schlecht gemacht,voller Sicherheitslücken
        exit();
    }
       if(file_exists($json_file))
        {
           
            //echo "ok noch desc";
            data::$additional_data_json=parse_json_data($json_file);
            
        }else{
            //datei existiert nicht,check ob course_url ,das andere mit den Dateisuchen ist mir zu aufwändig
             $scanned_directory=array();
            $scanned_directory1 = array_diff(scandir($new_top_dir), array('..', '.'));
            $course_mp4_videos_found=false;$dir_found=false;
            foreach($scanned_directory1 as $k=>$v){
            if(pathinfo($new_top_dir."\\".$v, PATHINFO_EXTENSION)=='mp4'){
                $course_mp4_videos_found=true;//var_dump($v);
                //$scanned_directory[]=array($k-1,0,$v,"Lecture",$k,"00:00");
                 preg_match("~([0-9]{1,})__([0-9]{1,})\.~",$v,$m);
                 if(isset($m[1])&&isset($m[2])){ 
               $scanned_directory[]=array($m[1],$m[2],$v,"Lecture",$k,"00:00");
                 }else{
                     //in normalen Modus gehen,fileview
                    // exit();
                    
                     $scanned_directory[]=array($k,0,$v,"Lecture",$k,"00:00","view_item_suffix"=>$v);
                     //break;
                 }
                  }
                  else if(is_dir($new_top_dir."\\".$v)){
                      $dir_found=true;
                  }else if(pathinfo($new_top_dir."\\".$v, PATHINFO_EXTENSION)=='pdf'){
                      
                       $scanned_directory[]=array($k,0,$v,"Pages",$k,"8 pages","view_item_suffix"=>$v);
                  }
            }
            if(!$course_mp4_videos_found && $dir_found){
              //  $folder_of_course_contents=$new_top_dir;
                main_page_controller($new_top_dir,urldecode($_GET["course"])."/");
                //exit();
            }
            usort($scanned_directory,"sort_by_course_file_number_schlechte_loesung");
            data::$additional_data_json=$scanned_directory;
            //var_dump($scanned_directory);
            //foreach($scanned_directory as $k=>$v){
                //if(pathinfo($new_top_dir."\\".$v, PATHINFO_EXTENSION)=='mp4'){
               // preg_match("~([0-9]{1,})__([0-9]{1,})\.~",$v[2],$m);
               //data::$additional_data_json[]=array($m[1],$m[2],$v[2],"Lecture",$k,"00:00");
               // }
           // }
            
            data::$additional_data_json=array(data::$additional_data_json);
          
            /*$url_file=$new_top_dir."\\"."video_urls.json";
            
            if(file_exists($url_file)){
                data::$url_data=parse_json_data($url_file);
            }else{
                echo "cannot play";
                exit();
                
            }*/
            //scheint nicht zu gehen
                  
        }
        //daten von json wohl da
        $apply_d=false;
                document_begin();
        if(isset($_GET["view_item"])){
           // echo tpl_list_player($_GET["course"],$_GET["view_item"]);
            
            $apply_d=  handle_view_item($_GET["course"],$_GET["view_item"],$_SERVER["PATH_INFO"]);
            
            echo "<br/>";
        }
        if((!isset($_GET["view_item"]))||$apply_d){
            //course-modus
           
            if(data::$additional_data_json){echo tpl_list_items_additional_data(data::$additional_data_json,$_GET["course"]);}
            else if(data::$url_data){             
                echo tpl_list_items_additional_data(data::$url_data, $_GET["course"],false);
              }else{echo "UNNOKNWO_VERY_VERY_HARD_ERROR";exit();}
              
              if($apply_d){echo '<script type="text/javascript">var id="'.str_replace('_','-',$_GET["view_item"]).'";var elem=document.createElement("a");elem.innerHTML=document.getElementById(id).innerHTML+" ";'.
                      'elem.href="#"+id;var newhref=document.getElementById("list_elements").children[id].nextSibling.nextSibling.href;var elem2=document.createElement("a");'
                      . 'elem2.innerHTML=">>Nächstes >>";elem2.href=newhref;document.getElementById(id).style.color="orange";'
                    . 'document.getElementById("player").appendChild(elem);document.getElementById("player").appendChild(elem2);</script>';}
        }
        
        
        
        
        
        
    }
   
}else{
main_page_controller($folder_of_course_contents);
    
    
}
document_end();

?>
