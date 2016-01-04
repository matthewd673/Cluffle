<?php
class proxy {
	function loadPage($page,$whitelist=array(),$counter=1) {
		$url=$this->parseUrl($page,true);
		if ($url!==false && $url[0]!=="" && in_array($url[2],$whitelist)) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $page);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_NOBODY, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$buff=curl_exec($ch);
			curl_close($ch);
			$results = explode("\n", trim($buff));
			if ($counter > 4) {
				$mime="text";
				$code=true;
				$results=array();
			} else {
				$mime="";
				$code=false;
			}
			$location="";
			$header="";
			foreach($results as $line) {
				if (strtok($line, ':') == 'Content-Type') {
					$header=$line;
					$parts = explode(":", $line);
					$mime=trim($parts[1]);
				}
				if (strtok($line, ':') == 'Location') {
					$header=$line;
					$parts = explode(":", $line, 2);
					$location=trim($parts[1]);
				}
				if (strpos($line,"200")!==false) {
					$code=true;
				}
			}
			if (!$code) {
				if (!$location) {
					header("Location: http://recluffle.herokuapp.com/proxy.php");
				}
				$counter++;
				return $this->loadPage($location,$whitelist,$counter);
				die();
			}
			
			if (strpos($mime,"text")===false) {
				$filehandle=fopen($url[0],"r");
				if ($filehandle!==false) {
					header($header);
					while (($buffer = fread($filehandle, 8192)) != false) {
						echo $buffer;
					}
					fclose($filehandle);
					die();
				}
			}
			$pagehost=$url[1];
			$page=file_get_contents($url[0]);
			
			if ($page===false) {
				return "";
			}
			
			//REMOVE SCRIPTFUCKERY DONE BY IMGUR
			//$page = preg_replace('#<script(.*?)>(.*?)</script>#is','',$page);
			
			//PARSE PAGE
			$hits=array();
			preg_match_all('/src=(["\'])(.*?)\1/', $page, $match);
			foreach ($match[0] as $mat) {
				$mat=substr($mat,5);
				$mat=trim($mat,"\"'");
				$matbuff=trim($mat,"/");
				$url=$this->parseUrl($matbuff,true);
				if ($url!==false && !in_array($url[2],$whitelist)) {
					continue;
				}
				
				if ($url===false) {
					$url=$this->parseUrl($pagehost."/".$matbuff,true);
					if ($url===false) {
						continue;
					}
				}
				$host=$url[1];
				$url=$url[0];
				$hits[$mat]="http://recluffle.herokuapp.com/proxy.php?url=".$url;
			}
			foreach ($match[2] as $mat) {
				$mat=trim($mat,"\"'");
				$matbuff=trim($mat,"/");
				$url=$this->parseUrl($matbuff,true);
				if ($url!==false && !in_array($url[2],$whitelist)) {
					continue;
				}
				
				if ($url===false) {
					$url=$this->parseUrl($pagehost."/".$matbuff,true);
					if ($url===false) {
						continue;
					}
				}
				$host=$url[1];
				$url=$url[0];
				$hits[$mat]="http://recluffle.herokuapp.com/proxy.php?url=".$url;
			}
			
			preg_match_all('/href=(["\'])(.*?)\1/', $page, $match);
			foreach ($match[0] as $mat) {
				$mat=substr($mat,6);
				$mat=trim($mat,"\"'");
				$matbuff=trim($mat,"/");
				$url=$this->parseUrl($matbuff,true);
				if ($url!==false && !in_array($url[2],$whitelist)) {
					continue;
				}
				
				if ($url===false) {
					$url=$this->parseUrl($pagehost."/".$matbuff,true);
					if ($url===false) {
						continue;
					}
				}
				$host=$url[1];
				$url=$url[0];
				$hits[$mat]="http://recluffle.herokuapp.com/proxy.php?url=".$url;
			}
			foreach ($match[2] as $mat) {
				$mat=trim($mat,"\"'");
				$matbuff=trim($mat,"/");
				$url=$this->parseUrl($matbuff,true);
				if ($url!==false && !in_array($url[2],$whitelist)) {
					continue;
				}
				
				if ($url===false) {
					$url=$this->parseUrl($pagehost."/".$matbuff,true);
					if ($url===false) {
						continue;
					}
				}
				$host=$url[1];
				$url=$url[0];
				$hits[$mat]="http://recluffle.herokuapp.com/proxy.php?url=".$url;
			}
			
			$hits=array_filter($hits);
		
			/*
			echo "<pre>";
			print_r($hits);
			echo "</pre>";
			die();
			*/
			
			foreach ($hits as $key=>$hit) {
				$page=str_replace("'".$key."'","'".$hit."'",$page);
				$page=str_replace('"'.$key.'"','"'.$hit.'"',$page);
				$page=str_replace("=".$key." ","=".$hit." ",$page);
			}
			
			
			//END PARSE PAGE
			
			
			header($header);
			
			return $page;
		} else {
			whatthefuck();
		}
	}
	
	function parseUrl($url,$host=false) {
		if (substr($url,0,7)!="http://" && substr($url,0,8)!="https://") {
			$url="http://".$url;
		}
		
		$buffer=parse_url($url);
		if ($buffer===false) {
			return false;
		}
		$host=$buffer["host"];
		$buffer=explode(".",$host);
		
		$count=count($buffer);
		if ($count < 2) {
			return false;
		}
		$domain=$buffer[$count-2].".".$buffer[$count-1];
		
		if ($host!==false) {
			return array($url,$host,$domain);
		} else {
			return $url;
		}
		
		return false;
	}
}
?>
