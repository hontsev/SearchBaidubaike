<!DOCTYPE html>
<html lang="zh-CN">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>baikeSearch</title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="//cdn.bootcss.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="//cdn.bootcss.com/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
      <div class="container">

      <form class="form-signin">
        <h2 class="form-signin-heading">搜索百度百科</h2>
        <input type="text" id="inputMessage" class="form-control" placeholder="关键词" autofocus>
		<input type="text" id="inputUrl" class="form-control" placeholder="链接（用于有多义项的词条搜索）" autofocus>
        <button class="btn btn-lg btn-primary btn-block" type="button" onclick="searchBaike()">搜索</button>
		<h4 class="form-signin-heading" id="showMessage"></h4>
      </form>

    </div> <!-- /container -->
    <script src="js/jquery-1.12.0.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
	<script>
		var outputLabel=$("#showMessage");
		
		//搜索百科内容
		function searchBaike(){
			var keyword=$("#inputMessage").val();
			var url=$("#inputUrl").val();
			$.ajax({
				url:"searchBaike.php?keyword="+keyword+"&url="+url,
				success:function(res){
					outputLabel.html(res);
				},
				error:function(res){
					outputLabel.html(res);
				}
			});
			$("#inputMessage").focus();
		}
	</script>
  </body>
</html>