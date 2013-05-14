<!DOCTYPE html>
<html>
<head>
    <title><?php $_TPL->_renderTag("get", array('_arg' => 'title', ));?></title>
</head>
<body>
    %__BLOCK_content__%
</body>
</html>

<style type="text/css">
    html, body, pre {
        margin: 0;
        padding: 0;
        font-family: Monaco, 'Lucida Console', monospace;
        background: #ECECEC;
    }
    h1 {
        margin: 0;
        background: #A31012;
        padding: 20px 45px;
        color: #fff;
        text-shadow: 1px 1px 1px rgba(0,0,0,.3);
        border-bottom: 1px solid #690000;
        font-size: 28px;
    }
    p#detail {
        margin: 0;
        padding: 15px 45px;
        background: #F5A0A0;
        border-top: 4px solid #D36D6D;
        color: #730000;
        text-shadow: 1px 1px 1px rgba(255,255,255,.3);
        font-size: 14px;
        border-bottom: 1px solid #BA7A7A;
    }
    p#detail input {
        background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#AE1113), to(#A31012));
        border: 1px solid #790000;
        padding: 3px 10px;
        text-shadow: 1px 1px 0 rgba(0, 0, 0, .5);
        color: white;
        border-radius: 3px;
        cursor: pointer;
        font-family: Monaco, 'Lucida Console';
        font-size: 12px;
        margin: 0 10px;
        display: inline-block;
        position: relative;
        top: -1px;
    }
    h2 {
        margin: 0;
        padding: 5px 45px;
        padding-bottom: 8px;
        font-size: 12px;
        background: #333;
        color: #fff;
        text-shadow: 1px 1px 1px rgba(0,0,0,.3);
        border-top: 4px solid #2a2a2a;
        font-weight: normal;
    }
    pre {
        margin: 0;
        border-bottom: 1px solid #DDD;
        text-shadow: 1px 1px 1px rgba(255,255,255,.5);
        position: relative;
        font-size: 12px;
        overflow: hidden;
    }
    code {
        color: gray;
    }
    pre span.line {
        text-align: right;
        display: inline-block;
        padding: 5px 5px;
        width: 30px;
        background: #D6D6D6;
        color: #8B8B8B;
        text-shadow: 1px 1px 1px rgba(255,255,255,.5);
        font-weight: bold;
    }
    pre span.code {
        padding: 5px 5px;
        position: absolute;
        right: 0;
        left: 40px;
    }
    pre:first-child span.code {
        border-top: 4px solid #CDCDCD;
    }
    pre:first-child span.line {
        border-top: 4px solid #B6B6B6;
    }
    pre.error span.line {
        background: #A31012;
        color: #fff;
        text-shadow: 1px 1px 1px rgba(0,0,0,.3);
    }
    pre.error {
        color: #A31012;
    }
    pre.error span.marker {
        background: #A31012;
        color: #fff;
        text-shadow: 1px 1px 1px rgba(0,0,0,.3);
    }

    pre.stackTrace { padding: 20px; padding-top: 0; line-height: 18px; color: #6b6b6b; }
    b.stackTraceTitle { font-size: 12px; display: block; padding: 10px; border-top: 1px solid silver; }
</style><?php if($__extends){ $_TPL->_renderContent(); } ?>