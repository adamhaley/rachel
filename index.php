<html>
<head>
<title>
Rachel Bowman - Aerial Art, Aerial Dance
</title>

<link rel="stylesheet" href="css/main.css" />
<link rel="stylesheet" href="css/sidescroll.css" />

<script language="javascript" src="js/jquery-1.4.2.min.js"></script>
<script language="javascript" src="js/swfobject.js"></script>
<script>
function onYouTubePlayerReady(){
	ytplayer = document.getElementById("player");

	 $("ul.sc_menu li a").click(function(){

                vid = $(this).attr("id");
                url = "http://www.youtube.com/v/" + vid + "?enablejsapi=1&playerapiid=ytplayer";   
		
		ytplayer.loadVideoById(vid);      

        });
}

$(function(){
  //Get our elements for faster access and set overlay width
  var div = $('div.sc_menu'),
               ul = $('ul.sc_menu'),
               // unordered list's left margin
               ulPadding = 15;
  //Get menu width
  var divWidth = div.width();
  //Remove scrollbars
  div.css({overflow: 'hidden'});
  //Find last image container
  var lastLi = ul.find('li:last-child');
  //When user move mouse over menu
  div.mousemove(function(e){
    //As images are loaded ul width increases,
    //so we recalculate it each time
    var ulWidth = lastLi[0].offsetLeft + lastLi.outerWidth() + ulPadding;
    var left = (e.pageX - div.offset().left) * (ulWidth-divWidth) / divWidth;
    div.scrollLeft(left);
  });

});

function loadVideo(vidId){
        
        document.getElementById("playercontainer_" + vidId).style.display='block';
}

function closeVid(vidId){
        document.getElementById("playercontainer_" + vidId).style.display='none';
}

  var params = { allowScriptAccess: "always" };
    var atts = { id: "player" };
    swfobject.embedSWF("http://www.youtube.com/v/9Wj2ZxAddHQ?enablejsapi=1&playerapiid=ytplayer", 
                       "ytapiplayer", "440", "285", "8", null, null, params, atts);


</script>
</head>
<body>
<div id="picture">

</div>
<a href="?p=bio">
<h1>
	<span>Rachel Bowman</span>
</h1>
</a>
<div id="nav">
	<span>
		<a id="bio" href="?p=bio">Bio</a>  
		<!--
		<a id="news" href="?p=news">News</a>
		<a id="blog" href="?p=blog">Blog</a>
		-->
		<a id="press" href="?p=press">Press</a>
		<a id="photos" href="?p=photos">Photos</a>
		<a id="video" href="?p=video">Video</a>
		<a id="contact" href="?p=contact">Contact</a>
		<a id="links" href="http://www.objectdefy.com/" target="_new">Objectdefy.com</a>
	</span>
</div>

<div id="content">
<?
$page = $_GET['p']? $_GET['p'] : 'bio';
include('content/' . $page . '.php');
?>
</div>
<script>
 
	/*
     $('#nav a').click(function() {
	var content = "content/" + $(this).attr("id") + ".txt";
	$('#content').fadeOut(200);

	 $('#content').queue(function(){
		$(this).load(content);
		  $(this).dequeue();

		});
	$('#content').fadeIn(200);
	});
	*/
</script>
</body>
</html>
