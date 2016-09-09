<?php 
	$resultData=null;
	if(strlen($_GET["keyword"])>0){
		//通过关键词搜索
		echo "关键词";
		var_dump($_GET["keyword"]);
		//编码关键词里的汉字等
		$keyword=urlencode($_GET["keyword"]);
		$resultData=_getDataByKeyword($keyword);
	}else if(strlen($_GET["url"])>0){
		//通过url搜索
		$pageURL=$_GET["url"];
		echo "访问url";
		var_dump($pageURL);
		$resultData=_getDataByUrl($pageURL);
	}else{
		exit();
	}
	

	$resultData=json_encode($resultData);
	echo "json数据";
	var_dump($resultData);
	//var_dump($contents);
	
	//var_dump($res);
	echo "数组数据";
	$arrayRes=json_decode($resultData);
	var_dump($arrayRes);
	//printArray($arrayRes);
	
	
	
	///主要入口如下三个函数
	
	function _getJsonByKeyword($keyword){
		$formatData=_getDataByKeyword($keyword);
		return json_encode($formatData);
	}
	
	function _getDataByKeyword($keyword){
		$pageURL="http://baike.baidu.com/search/word?word=".$keyword;
		//$contents=_getPageByUrl($pageURL);
		$res=_formatAll($pageURL);
		return $res;
	}
	
	function _getDataByUrl($pageURL){
		$contents=_getPageByUrl($pageURL);
		$res=_format($contents);
		return $res;
	}
	
	
	///
	
	
	
	/// <summary>
	/// 通过关键字获取百度百科的页面html
	/// 返回值：当前页面内容的html字符串
	/// </summary>
	function _getPageByUrl($data){
		$contents=file_get_contents($data);
		return $contents;
	}
	
	/// <summary>
	/// 用于将百度百科页面html转化为规范化数组对象
	/// 返回值：包括所有同义项的数据的规范化数组对象
	/// </summary>
	function _formatAll($pageUrl){
		$formatDataAll=array();
		//获取这个词的全部意义项的网址
		$dataPage=_getAllMeanUrls($pageUrl);
		//var_dump($dataPage);
		//对每个义项分别获取并规范化
		foreach($dataPage as $pageName=>$page){
			array_push($formatDataAll,_getDataByUrl($page));
		}		
		return $formatDataAll;
	}
	
	
	function _getAllMeanUrls($pageUrl){

		$dataPage=array();
		
		$data=_getPageByUrl($pageUrl);
		//预处理
		$data=_beforeFormat($data);
		
		//多义项部分
		preg_match('/个义项<\/a>）<\/div><ul class="custom_dot  para-list list-paddingleft-1">(.*?)<div class="side-content">/s',$data,$info1);
		if(sizeof($info1)>0){
			//这个页面是多义项选择页面，并没有正文
			//preg_match('/custom_dot(.*?)<div class="side-content">/s',$data,$info1);
			//var_dump($info1);
			preg_match_all('/<a[^>]*?href="([^"]*)"[^>]*?>[^：]*：([^<]*?)<\/a>/s',$info1[1],$r);
			//var_dump($r[0]);
			for($i=0;$i<sizeof($r[0]);$i++){
				$dataPage[$r[2][$i]]="http://baike.baidu.com" . $r[1][$i];
			}
		}else{
			//这个页面包含一种义项的正文
			preg_match('/polysemantList-wrapper cmn-clearfix"(.*?)div class="content-wrapper"/s',$data,$info1);
			if(sizeof($info1)>0){
				//这个词条包含一词多义
				//多义项
				//print_r("多义项：");
				foreach($info1 as $c){
					//var_dump($c);
					preg_match_all('/<li class="item">▪(<span class="selected">)?(.*?)(<\/span>)?<\/li>/s',$c,$r);
					//var_dump($r[2]);
					foreach($r[2] as $cc){
						if(preg_match('/<a[^>]*>([^<]*)<\/a>/s',$cc)){
							//其他页面义项
							preg_match('/href=\'([^\']*?)\'([^>]*)>([^<]*)<\/a>/s',$cc,$rr);
							//var_dump($rr[1]);
							//var_dump($rr[3]);
							$dataPage[$rr[3]]="http://baike.baidu.com" . $rr[1];
						}else{
							//当前页面打开的那个义项
							$dataPage[$cc]=$pageUrl;
						}
					}
				}
			}else{
				//这个词条只有唯一含义
				$dataPage["默认"]=$pageUrl;
			}
		}
		return $dataPage;
	}
	
	/// <summary>
	/// 预处理，用于在转化前将页面无用内容替换掉。
	/// 返回值：$data 处理完毕后的结果
	/// </summary>
	function _beforeFormat($data){
		//去除空格占位
		$data=preg_replace('/&nbsp;/',"",$data);
		//引号占位
		$data=preg_replace('/&quot;/',"\"",$data);
		
		$data=preg_replace('/<br\/>/','',$data);
		
		//去除图片引用
		$data=preg_replace('/<div class="lemma-picture(.*?)<\/div>/s','',$data);
		//去除倾斜
		$data=preg_replace('/<i>([^<]*)<\/i>/s','${1}',$data);
		//去除加粗
		$data=preg_replace('/<b>([^<]*)<\/b>/s','${1}',$data);
		//去除带换行的加粗
		//$data=preg_replace('/<b>(.*?)\n<\/b>/s','${1}',$data);
		//去除参考文献引用符
		$data=preg_replace('/<sup>[^<]*<\/sup>/','',$data);
		
		$data=preg_replace('/<em[^>]*>[^<]*<\/em>/','',$data);
		
		$data=preg_replace('/<span class="title-prefix">([^<>]*)<\/span>/s','',$data);
		$data=preg_replace('/<span class="description">([^<>]*)<\/span>/s','',$data);
		$data=preg_replace('/<span class="number">([^<>]*)<\/span>/s','',$data);
		
		$data=preg_replace('/<div class="album[^>]*>[^<]*<\/div>/s','',$data);
		$data=preg_replace('/<div class="album[^>]*>[^<]*<\/div>/s','',$data);
		$data=preg_replace('/<img[^>]*>/s','',$data);
		
		$data=preg_replace('/<div class="description">([^<>]*)<\/div>/s','',$data);
		
		
		return $data;
	}
	
	/// <summary>
	/// 用于将百度百科页面html转化为规范化数组对象
	/// 返回值：当前页面内容的数组对象
	/// </summary>
	function _format($data){
		
		$formatData=array('subtitle'=>null,'summary'=>null,'basic'=>null,'main'=>null);

		//预处理
		$data=_beforeFormat($data);
		$data=_beforeFormat($data);
		
		//去除超链接
		$data=preg_replace('/<a[^>]*>([^<]*)<\/a>/s','${1}',$data);
		
		$data=_beforeFormat($data);
		
		//副标题部分
		preg_match('/<h2>（([^<]*)）<\/h2>/s',$data,$subtitle);
		//var_dump($subtitle);
		if(sizeof($subtitle)>0)$formatData['subtitle']=$subtitle[1];
		else $formatData['subtitle']="默认";
		
		//概述部分
		//print_r("概述：");
		$summaryData=array();
		if(preg_match('/class="lemmaWgt-lemmaSummary lemmaWgt-lemmaSummary-light">\n(.*?)\n\n>>>/s',$data)){
			//这是精品词条，概述格式不同
			preg_match('/class="lemmaWgt-lemmaSummary lemmaWgt-lemmaSummary-light">\n(.*?)\n\n>>>/s',$data,$info2);
			//var_dump($info2);
			$formatData['summary']=$info2[1];
		}else{
			//这是普通格式的词条
			preg_match_all('/lemmaSummary">(.*)<div class="lemmaWgt-lemmaCatalog">/s',$data,$info2);
			//var_dump($info2);
			if(sizeof($info2[0])<=0){
				//若没有概述，我们认为正文段落为概述。防止内容没有捕获到。
				preg_match_all('/<div class="para" label-module="para">([^<]*?)<\/div>/s',$data,$r);
				//print_r("无概述");var_dump($data);var_dump($r);
				foreach($r[1] as $paramStr)array_push($summaryData,$paramStr);
			}else{
				foreach($info2[1] as $c){
					preg_match_all('/<div class="para" label-module="para">([^<]*?)<\/div>/s',$c,$r);
					//var_dump($r[1]);
					foreach($r[1] as $paramStr)array_push($summaryData,$paramStr);
				}
			}

			$formatData['summary']=$summaryData;
		}

		
		//基本信息栏
		//print_r("基本信息：");
		$basicData=array();
		preg_match_all('/<dt class="basicInfo-item name">([^<]*)<\/dt>.?<dd class="basicInfo-item value">.?([^<\n]*).?<\/dd>/s',$data,$info3);
		for($i=0;$i<sizeof($info3[0]);$i++){
			$basicData[$info3[1][$i]]=$info3[2][$i];
		}
		//var_dump($basicData);
		$formatData['basic']=$basicData;
		
		//正文部分
		
		$mainData=array();
		preg_match_all('/<div class="lemmaWgt-lemmaCatalog">(.*?)<div class="side-content">/s',$data,$info4);
		
		if(sizeof($info4[0])<=0){
			//这个词条没有目录，不能采用这种数据摘取方式
			preg_match_all('/lemmaWgt-lemmaTitle-title">(.*?)<div class="side-content">/s',$data,$info4);
		}
		//print_r("正文：");var_dump($info4);
		foreach($info4[0] as $c){
			//按一级目录分块
			preg_match_all('/<h2 class="title-text">(.*?)(<div class="para-title level-2">|<div class="side-content">)/s',$c,$r);
			//print_r("按一级目录分块");var_dump($r);
			foreach($r[0] as $cc){
				//一级目录标题
				preg_match('/<h2 class="title-text">([^<]*?)<\/h2>/',$cc,$title1);
				//var_dump($title1);
				if(sizeof($title1)<=0)$title1[1]="";
				//var_dump($title1[1]);print_r("（↑一级目录）");
				if(preg_match('/<h3/s',$cc)){
					$mainDataL2=array();
					//获取一级目录下内容
					preg_match('/<h2 class="title(.*?)(<h3|<div class="side-content">)/s',$cc,$contentL1);
					preg_match_all('/<div class="para" label-module="para">([^<]*?)<\/div>/s',$contentL1[1],$content1);
					for($i=0;$i<sizeof($content1[1]);$i++){
						$content1[1][$i]=preg_replace('/\n/','',$content1[1][$i]);
						$content1[1][$i]=preg_replace('/\r/','',$content1[1][$i]);
					}
					//按二级目录分块
					preg_match_all('/<h3(.*?)(<div class="para-title level-3">|<div class="para-title level-2">|<div class="side-content">)/s',$cc,$rr);
					//var_dump($rr[1]);
					foreach($rr[0] as $ccc){
						//二级目录标题
						preg_match('/class="title-text">([^<]*?)<\/h3>/',$ccc,$title2);
						//var_dump($title2[1]);print_r("（↑二级目录）");
						//获取二级目录下内容
						preg_match_all('/<div class="para" label-module="para">([^<]*?)<\/div>/s',$ccc,$content2);
						for($i=0;$i<sizeof($content2[1]);$i++){
							$content2[1][$i]=preg_replace('/\n/','',$content2[1][$i]);
							$content2[1][$i]=preg_replace('/\r/','',$content2[1][$i]);
						}
						//var_dump($content2);
						$mainDataL2[$title2[1]]=$content2[1];
					}
					$mainData[$title1[1]]=array('content'=>$content1[1],'sub'=>$mainDataL2);
				}else{
					//直接获取一级目录下内容
					preg_match_all('/<div class="para" label-module="para">([^<]*?)<\/div>/s',$cc,$content1);
					if(sizeof($content1[0])>0){
						//有一级目录内容
						for($i=0;$i<sizeof($content1[1]);$i++){
							$content1[1][$i]=preg_replace('/\n/','',$content1[1][$i]);
							$content1[1][$i]=preg_replace('/\r/','',$content1[1][$i]);
						}
						$mainData[$title1[1]]=array('content'=>$content1[1],'sub'=>null);
					}else{
						$mainData[$title1[1]]=array('content'=>"",'sub'=>null);
					}
				}
			}
		}
		$formatData['main']=$mainData;
		
		//var_dump($formatData);
		return $formatData;
	}
	
	/// <summary>
	/// 用于格式化输出php数组。（临时显示用
	/// 输入：$array 要输出的数组  $times 递归次数，用于记录缩进
	/// </summary>
	function printArray($array,$times=0){
		if(is_string($array)){
			//for($i=0;$i<$times;$i++)echo "&nbsp;";
			echo $array."</br>";
		}else{
			foreach($array as $key=>$value){
				echo "</br>";
				for($i=0;$i<$times;$i++)echo "&nbsp;";
				echo $key.' => ';
				printArray($value,$times+4);
			}
		}
	}
?>