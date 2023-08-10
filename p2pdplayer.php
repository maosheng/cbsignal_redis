<!DOCTYPE html>
<html>
<head>
    <title>妖漫专用播放器</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="content-language" content="zh-CN"/>
    <meta http-equiv="X-UA-Compatible" content="chrome=1"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <meta http-equiv="expires" content="0"/>
    <meta http-equiv="Access-Control-Allow-Origin" content="*" />
    <meta name="referrer" content="never"/>
    <meta name="renderer" content="webkit"/>
    <meta name="msapplication-tap-highlight" content="no"/>
    <meta name="HandheldFriendly" content="true"/>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="APP name">
    <meta name="x5-orientation" content="landscape">
    <meta name="x5-page-mode" content="app"/>
    <meta name="screen-orientation" content="landscape">
    <meta name="browsermode" content="application"/>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="Viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=0"/>
    <meta name="format-detection" content="telephone=no, email=no">
    <!--<link href="https://cdn.bootcss.com/dplayer/1.25.0/DPlayer.min.css" rel="stylesheet">-->
    <link href="cdnbye/dplayer.min.css" rel="stylesheet">
    <style type="text/css">
    html,body{width:100%;height:100%; padding:0; margin:0;}
    #playerCnt{width:100%;height:100%;}
    .dplayer-logo {
    pointer-events: none;
    position: absolute;
    left: 88%;
    top: 20px;
    max-width: 10%;
    /*max-height: 50px;*/
}
#stats{position:fixed;top:5px;left:8px;font-size:12px;color:#fdfdfd;text-shadow:1px 1px 1px #000, 1px 1px 1px #000}
    .dplayer-icons.dplayer-comment-box {
        display: block! important;
        width: 30%;
        margin: 0 auto;
    }
    .dplayer-comment{ display:none! important; }
    .diplayer-loading-icon{ display:none! important; }
    
    </style>
    <script type="text/javascript" src="https://f.ffsup.com/DPlayer/src/js/flv.min.js"></script>
    <script type="text/javascript" src="https://f.ffsup.com/DPlayer/src/js/hls.min.js"></script>
    <script type="text/javascript" src="https://f.ffsup.com/DPlayer/src/js/dash.all.min.js"></script>
    <script type="text/javascript" src="https://f.ffsup.com/DPlayer/src/js/webtorrent.min.js"></script>
    <!--<script src="https://cdn.bootcss.com/dplayer/1.25.0/DPlayer.min.js"></script>-->
    <script src="https://f.ffsup.com/JS/md5.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/cdnbye@latest"></script>-->
    <script src="cdnbye/cdnbye@latest"></script>
    <script src="cdnbye/dplayer@1.25.0"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/dplayer@1.25.0"></script>-->
    <!--<script src="https://cdn.jsdelivr.net/npm/cdnbye-dash@latest"></script>-->
</head>
<body marginwidth="0" marginheight="0">
<div id="playerCnt"></div>
<div id="stats"></div>
<script type="text/javascript">
    var type='normal';
    var live=false;
    // var PlayUrl=getQueryString('url');
    var PlayUrl = '<?php echo $_GET['url'];?>';
    if(PlayUrl.indexOf('.m3u8')>-1){
        //type='hls';
        //live=true;
        var type = 'customHls';
    }
    else if(PlayUrl.indexOf('magnet:')>-1){
        //type='webtorrent';
        type='customWebTorrent';
    }
    else if(PlayUrl.indexOf('.flv')>-1){
        type='flv';
    }
    else if(PlayUrl.indexOf('.mpd')>-1){
        type='dash';
    }
    else if(navigator.userAgent.match(/iPad|iPhone|iPod|Baidu|UCBrowser/i)) 
    {
        type = 'normal';
    }
    var webdata = {
        set:function(key,val){
            window.sessionStorage.setItem(key,val);
        },
        get:function(key){
            return window.sessionStorage.getItem(key);
        },
        del:function(key){
            window.sessionStorage.removeItem(key);
        },
        clear:function(key){
            window.sessionStorage.clear();
        }
    };
    var customType = null;
    if(type=='customWebTorrent'){
        customType = {
            'customWebTorrent': function(video, player) {
                player.container.classList.add('dplayer-loading');
                const client = new WebTorrent();
                const torrentId = video.src;
                client.add(torrentId, (torrent) => {
                    const file = torrent.files.find((file) => file.name.endsWith('.mp4'));
                    file.renderTo(
                        video,
                        {
                            autoplay: player.options.autoplay,
                        },
                        () => {
                            player.container.classList.remove('dplayer-loading');
                        }
                    );
                });
            }
        }
    }
    else if(type == 'customHls'){
        customType = {
                'customHls': function (video, player) {
                    const hls = new Hls({
                        debug: false,
                        // Other hlsjsConfig options provided by hls.js
                        p2pConfig: {
                                // p2ptoken：'你在cdnbye官网获取的token'
                                // announce:"http://103.167.151.87:8080",       // tracker服务器地址
                                // announce:"https://m.tvku.xyz/addons/dplayer/cdnbye/tracker.php?command=",
                                // wsSignalerAddr:"ws://103.167.151.87:8443",        // 信令服务器地址
                                // wsSignalerAddr:'ws://103.167.151.87:8080/ws',
                                
                                
                                announce:'https://m.tvku.xyz/addons/dplayer/cdnbye/tracker.php?command=',
                                wsSignalerAddr:'wss://m.tvku.xyz/ws',
                                
                                
                                // announce:'https://p2ptrakcer.bapy.top',
                                // wsSignalerAddr:'wss://single.bapy.top/ws',
    
                            logLevel: false,
                            live: false,        // 如果是直播设为true
                            // wsSignalerAddr: 'wss://signal.ffsup.com'
                            // wsSignalerAddr:'ws://103.167.151.87:8443'
                            //'wss://signal.cdnbye.com'
                            //wss://signal.ffsup.com （自有节点）
                            //wss://signal.cdnbye.com (中国节点，默认)
                            //wss://opensignal.gcvow.top (中国节点，由猫云赞助)
                            // Other p2pConfig options provided by CDNBye
                        }
                    });
                    hls.loadSource(video.src);
                    hls.attachMedia(video);
                    hls.p2pEngine.on('stats', function (stats) {
                        _totalP2PDownloaded = stats.totalP2PDownloaded;
                        _totalP2PUploaded = stats.totalP2PUploaded;
                        updateStats();
                    }).on('peerId', function (peerId) {
                        _peerId = peerId;
                    }).on('peers', function (peers) {
                        _peerNum = peers.length;
                        updateStats();
                    });
                }
            }
    }
    var _peerId = '', _peerNum = 0, _totalP2PDownloaded = 0, _totalP2PUploaded = 0;
    var p2pokn = 0,canplaythroughn = 0;
    var dp = new DPlayer({
        container: document.getElementById('playerCnt'),
        autoplay: false,
        screenshot: true, //截屏
        //screenshot: false,
        logo: 'https://f.ffsup.com/images/bg/0x000005.png',
        video: {
            url: PlayUrl,
            live: live,
            pic: 'https://f.ffsup.com/images/bg/0x000009.gif',
            type:type,
            //type: 'customHls',
            
            customType: customType,
            
        },
        danmaku: {
            id: md5(PlayUrl),
            api: 'https://danmaku.mc.0sm.com/',    
            //自有弹幕 https://danmaku.mc.0sm.com/
            maximum: 1000
        },
        contextmenu: [
            {
            text: '妖漫播放器',
            link: 'https://tv.acg.gs/',
            }
        ]
    });
    //dp.notice('播放器加载完毕!', 5000, 0.8);
    dp.video.crossOrigin =null;
    for(i=0;i<2;i++){
        document.getElementsByClassName('dplayer-menu-item')[document.getElementsByClassName('dplayer-menu-item').length-1].parentNode.removeChild(document.getElementsByClassName('dplayer-menu-item')[document.getElementsByClassName('dplayer-menu-item').length-1]);
    }
    dp.notice('视频正在加载!', 5000, 0.8);
    dp.on('canplaythrough', function() {
        if(canplaythroughn==0){
            dp.notice('视频可以播放!', 5000, 0.8);
            canplaythroughn++;
        }
    });
    dp.seek(webdata.get('pay'+PlayUrl));
    setInterval(function(){
        webdata.set('pay'+PlayUrl,dp.video.currentTime);
    },1000);
    dp.on('ended',function(){
　　　　if(parent.MacPlayer.PlayLinkNext!=''){
            top.location.href = parent.MacPlayer.PlayLinkNext;
        }
　　});
    function p2pok() {
        if(p2pokn==0){
            dp.notice('视频P2P已开启!', 5000, 0.8);
            p2pokn++;
        }
    }
    function updateStats() {
        p2pok()
        var text = 'P2P已开启 共享' + (_totalP2PUploaded/1024).toFixed(2) + 'MB' + ' 已加速' + (_totalP2PDownloaded/1024).toFixed(2)
            + 'MB' + ' 连接节点' + _peerNum ;
        document.getElementById('stats').innerText = text
    }
    
    try{
        //document.getElementById('playerCnt').style.height = parent.MacPlayer.Height + 'px';
    }
    catch(e){}
    function getQueryString(name) {
        var reg = new RegExp('(^|&)' + name + '=([^&]*)(&|$)', 'i');
        var r = window.location.search.substr(1).match(reg);
        if (r != null) {
            return unescape(r[2]);
        }
        return null;
    }
    function getQueryVariable(variable)
{
       var query = window.location.search.substring(1);
       var vars = query.split("&");
       for (var i=0;i<vars.length;i++) {
               var pair = vars[i].split("=");
               if(pair[0] == variable){return pair[1];}
       }
       return(false);
}
</script>
</body>
</html>
