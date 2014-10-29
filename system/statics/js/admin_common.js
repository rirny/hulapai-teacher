function flashChecker(){
	var hasFlash=0;         //是否安装了flash
	var flashVersion=0; //flash版本
	var isIE=/*@cc_on!@*/0;      //是否IE浏览器

	if(isIE){
		var swf = new ActiveXObject('ShockwaveFlash.ShockwaveFlash');
			if(swf) {
				hasFlash=1;
				VSwf=swf.GetVariable("$version");
				flashVersion=parseInt(VSwf.split(" ")[1].split(",")[0]);
			}
	}else{
		if (navigator.plugins && navigator.plugins.length > 0){
		var swf=navigator.plugins["Shockwave Flash"];
		    if (swf){
				hasFlash=1;
		        var words = swf.description.split(" ");
		        for (var i = 0; i < words.length; ++i){
		            if (isNaN(parseInt(words[i]))) continue;
		            flashVersion = parseInt(words[i]);
				}
		    }
		}
	}
	return {f:hasFlash,v:flashVersion};
}

function confirmurl(url,message)
{
	if(confirm(message)) redirect(url);
}
function redirect(url) {
	//if(url.indexOf('://') == -1 && url.substr(0, 1) != '/' && url.substr(0, 1) != '?') url = $('base').attr('href')+url;
	location.href = url;
}
//滚动条
$(function(){
	//window.onresize = function(){

	//}
	//window.onresize();
	//inputStyle
	$(":text").addClass('input-text');
})

/**
 * 全选checkbox,注意：标识checkbox id固定为为check_box
 * @param string name 列表check名称,如 uid[]
 */
function selectall(name) {
	if ($("#check_box").attr("checked")==false) {
		$("input[name='"+name+"']").each(function() {
			this.checked=false;
		});
	} else {
		$("input[name='"+name+"']").each(function() {
			this.checked=true;
		});
	}
}

function openwinx(url,name,title,w,h) {
	if(!w) w=window.screen.width-4;
	if(!h) h=window.screen.height-95;
	var top = (window.screen.availHeight-30-h)/2; //获得窗口的垂直位置;
	var left = (window.screen.availWidth-10-w)/2; //获得窗口的水平位置;
	var obj=window.open(url,name,"top=" + top + ",left=" + left + ",width=" + w + ",height=" + h + ",toolbar=no,menubar=no,scrollbars=yes,resizable=yes,location=no,status=no");
	setTimeout(function(){
	    obj.document.title = title;
	}, 1000) ;
}