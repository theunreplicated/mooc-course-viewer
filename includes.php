<?php
class Helper{
    //const type_unknown=3;
    const type_pages='pages',type_media='media',type_text='text';
    
public static function determineType($additional_data_item){//vom C++-Code übernommen
    if(preg_match('/^[0-9:\\s]{1,}$/',$additional_data_item[5])){
        return self::type_media;
        
    }else if($additional_data_item[5]=='Text'){
        return self::type_text;
    }else if(strstr($additional_data_item[5],'pages')){
        
        return self::type_pages;
    }else{
                throw new Exception("type unnknown");}
    
}  
}
function document_begin(){
header('Content-Type: text/html; charset=utf-8');
echo "<!doctype html><html><body>";

}
function document_end(){echo '<br/><a href="./">Startseite</a></body></html>';}
class StreamHelper{
    function __construct($new_top_dir){//copy n paste von unten
        
    $pathinfo=substr($_SERVER['PATH_INFO'],1);//bei unicode mb_substr
    $ext;
     if(isset($_GET["fileviewmode"])){
          
       
       $_GET["stream"]=pathinfo($_GET["stream"], PATHINFO_FILENAME);

   }
   switch ($pathinfo){
       case Helper::type_media: $ext=$this->url_media_handle_and_ext($new_top_dir."\\".$_GET["stream"]); break;
       case Helper::type_pages:$ext=$this->url_pdf_pages_handle_and_ext();break;
       case Helper::type_text:$_GET["stream"]=str_replace("__","_",$_GET["stream"]);$ext='.json';break;
       default:throw new Exception("pathinfo value unrecognized");
   }

   $path=$new_top_dir."\\".$_GET["stream"].$ext;
    set_time_limit(0);//falls gaaaanz große datei,aber unwahrscheinlich
   if($ext!=='.json'){
       if(file_exists($path)){
     if($ext=='.mp4'){
         require 'videostream.php';
         $stream = new VideoStream($path);
        $stream->start();
         
     }else{      
     echo file_get_contents($path);
     }
       }else{echo "F(ile)N(ot)F(ound)--D(atei) n(icht) g(efunden)";}
    }else{
       
        $data=  file_get_contents($path);
        $json_d=json_decode($data);
        document_begin();
        echo $json_d->html;
        echo '</body></html>';//weils hier mit iframes und nicht mit ajax funktioniert
    }
    }

    function url_pdf_pages_handle_and_ext(){
        $filename=$_GET["stream"].'.pdf';
        header("Content-type: application/pdf");
  header("Content-disposition: inline;filename={$filename}");//http://stackoverflow.com/questions/16847015/php-stream-remote-pdf-to-client-browser
        return '.pdf';
    }
    function url_media_handle_and_ext($new_top_dir){
        echo $new_top_dir;
        if(file_exists($new_top_dir.".mp4"))
        {
       /* $size=filesize($new_top_dir.".mp4");
         header('HTTP/1.0 200 OK');
        header("Content-Type: video/mp4");
        header('Accept-Ranges: bytes');//http://www.echteinfach.tv/test/ipad/test-byterange-2.txt
        header('Content-Length:'.($size));
        header("Content-Disposition: inline;");
         header("Content-Transfer-Encoding: binary\n");
 header('Connection: close');*/
        return '.mp4';
    }else if(file_exists($new_top_dir.".mp3")){
        header('Content-type: audio/mpeg');
    header ("Content-Transfer-Encoding: binary");
    header ("Pragma: no-cache");
        
     return '.mp3';
    }else {return 'Not Found';}
    
    
}};

function tpl_list_items_additional_data($additional_data,$course_id,$is_additional=true){
    if($is_additional){$additional_data=$additional_data[0];}
   
    $num_items=count($additional_data);
        
    $dat='<div id="list_elements">';$erstindex=($is_additional? 0:1);$zweitindex=($is_additional? 1:2);//@TODO:non additional code entfernen,also welcher der nicht darauf aubaut ,osndern auf dne video_urls.json
    
    for($i=0;$i<$num_items;$i++){
        try{//var_dump( $additional_data[$i]) ;
        $url_video=$_SERVER["SCRIPT_NAME"].'/'.Helper::determineType($additional_data[$i]);}catch(Exception $e){//qTODO:test exception
           
            continue;
        }
        if(isset($additional_data[$i]["view_item_suffix"])){
            $view_item_suffix=$additional_data[$i]["view_item_suffix"]."&fileviewmode";
            
        }else{$view_item_suffix=$additional_data[$i][$erstindex].'_'.$additional_data[$i][$zweitindex];}
        $url_video.='?course='.urlencode($course_id).'&view_item='.$view_item_suffix;
        $ueberschrift=$additional_data[$i][2];
        $nr=$additional_data[$i][$erstindex]." - ".$additional_data[$i][$zweitindex];
        $id=$additional_data[$i][$erstindex]."-".$additional_data[$i][$zweitindex];
        $dat.='<a id="'.$id.'" href="'.$url_video.'">'.$nr.($is_additional ? "-".$ueberschrift: "" ).'</a><br/>';
    }
    return $dat.'</div>';
}
function get_stream_url($course_top_dir,$view_item,$type){
    //http://stackoverflow.com/questions/1525830/how-do-i-use-filesystem-functions-in-php-using-utf-8-strings
   return ''.$type.'?course='.urlencode($course_top_dir).'&stream='.(!isset($_GET["fileviewmode"])? str_replace('_','__',$view_item):$view_item).(isset($_GET["fileviewmode"])? "&fileviewmode":'');
}
function tpl_list_player($course_top_dir/*eigene datentypen wären cool für folder wo es dann nen unterschiedlichen typ für folder mit und ohne \\ gibt -keine confusion*/,$view_item){
   //falsche Variablen namen hier,sond eigentlich was anderes
//return "<embed src={$video_link}></embed";
    //type="video/mp4" 

    echo '<div id="player"><video controls autoplay><source src="'.  get_stream_url($course_top_dir, $view_item,Helper::type_media).'"></source></video></div>';
}
function tpl_list_pages($course_top_dir, $view_item){
    
    echo '<embed src="'.  get_stream_url($course_top_dir, $view_item,Helper::type_pages).'"></embed>';//oder gleich /> ?
}
function tpl_list_text($course_top_dir, $view_item){
    
    echo '<iframe src="'.  get_stream_url($course_top_dir, $view_item, Helper::type_text).'"></iframe>';
}
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function handle_view_item($course_top_dir,$view_item,$pathinfo){
   //natürlich gibts n error when pathinfo net gesetzt ist,und es ist auch unnötig das hier zu übergeben weil globale Variable
    $pathinfo=substr($pathinfo,1);//bei unicode mb_substr
   switch ($pathinfo){
       case Helper::type_media:tpl_list_player($course_top_dir, $view_item); break;
       case Helper::type_pages:tpl_list_pages($course_top_dir, $view_item);break;
       case Helper::type_text:tpl_list_text($course_top_dir, $view_item);break;
       default:throw new Exception("pathinfo value unrecognized");
   }
    
return true;
}

?>
