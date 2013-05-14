<!DOCTYPE html>
<html>
<head>
    <title>Testing: <?php $_TPL->_renderTag("get", array('_arg' => 'title', 'default' => 'Home', ));?></title>
    <style type="text/css">
        html, body, pre {
            margin: 0;
            padding: 0;
            font-family: Monaco, 'Lucida Console', monospace;
            background: #ECECEC;
        }
        pre { padding: 3px; color: #686868; }
        h1 {
            margin: 0;
            background: #92D13D;
            padding: 20px 45px;
            color: #fff;
            text-shadow: 1px 1px 1px rgba(0,0,0,.3);
            border-bottom: 1px solid #4a691b;
            font-size: 28px;
        }
        #detail {
            margin: 0;
            padding: 15px 45px;
            background: #f4f5f0;
            border-top: 4px solid #d1d3cd;
            color: #0d0008;
            text-shadow: 1px 1px 1px rgba(255,255,255,.3);
            font-size: 12px;
            border-bottom: 1px solid white;
        }
        h2, h3 {
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
        h3 {
            font-size: 11px;
            border-top: 0;
            padding-top: 8px;
            padding-bottom: 5px;
            background: #464646;
            border-bottom: 4px solid #2a2a2a;
        }

        a { color: green; }
        a:hover {text-decoration: none;}

        .ico {padding: 2px 0; padding-left: 22px; background-position: 0 50%; background-repeat: no-repeat; }
        .ico.success {
            background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAACR0lEQVR4XqWTT0gUURzHP2921tYx/x1COuRhoUtBBdIhiyhcrHRFKLyJHbpUEBZJHfIQFGW5K5R/ojIIjxrRrYN2CIvQtsCCxP7RIVIWTVpTV52ZXzM9cGzx1oMv8+a93+f7vm+YnxIR/mvkGtQkqKi+wWDsOlLlyX9673KkncF4kopc1iAYHL5Jd7FVlDobb4k9OPOQuye7aDveSnNdE/u37YjlmaFUPEE/uS4apv9E72ZZlCEZ+d4k914iPcPInRf8nfeNRqXn+U5p6DTES9L9T4JaL3aJZTVca+ziyVgjqW99OAqMEChPrgHz9lcwx9gVLcE0OF3XQQWgDVZs2uO763n/o5eZxSkM0we1MMAGFh04VSmEIz8pKwXXIbFqAByMlm3h88xTbBeWPTkCLmALZG04t08AiFhQVAAoDgCYAAJknQxLLjRX6sLkK0XYgGUHWjQMgBmGkAEisJpAKcjMp3VktM7vEeZsaKkM4I4RhWmC0mxgYCiYyUySF4ZkKti+tDeAk68V+RtAGeC4CqUCA5wlRj9NfsHNllAQgeRbBRDAbzQczoNfs4q5BbCzjK5+g+kPtE0Uph8X5BezqRw2RqDzncJ19fUsHw6BswLpNEzOCmmPWfsjWVWt9B+9hVx8ZEhiGLk/jvR+RGscb03JhQElx24jMa8WsHzWRI+FZ1dpqb5M6YS4scy8onAKQgoAHMGLrU/+Pc3AkFfrM+s1U3R7PY2HrjBSm0Bq2rX8ub/m7wHlQMCu084WsBWoyJG/ZuX20R9cggXgsNE2EAAAAABJRU5ErkJggg==);
        }
        .ico.fail {
            background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAA8ElEQVR4XqWTQUrFMBCGv4GCbkTceAFB8DbewHbTnKndGG/gzlO8tSh4AEWQh5sWg3ECsxgKCU/9diX/93dSOoIjQw/c0iYKDBiCsZjcTRMtUggA8SjnQUQQgL3JxyovGmjhMvEUBnk1+UTlz418njOFNxE8Lhu7VeUzld8bb163zyoX5yOEvhTwbaFDC3BOtwAJWKhQOTOH/09QxP1u99sCc6wg/WGC5L/BV+WeDyLUMKc9wbX9B/ci9QlW6J/mOV6MIy/zfNAVSlYd2wvgDm6AeKkHz3rQwmWiSoNgTFZypYEWjyaPfpm2JbSJwa3zD2bxd+x7D7qzAAAAAElFTkSuQmCC);
        }

        table.list {
            width: 100%;
            font-family: Tahoma;
            font-size: 12px;
        }
        table.list th, table.list td { padding: 3px; border-bottom: 1px solid #ebebeb
        }

        table.list th { text-align: left; }
    </style>
</head>
<body>
    <h1><?php $_TPL->_renderTag("get", array('_arg' => 'subTitle', 'default' => ($subTitle ? $subTitle : 'Testing'), ));?></h1>
    %__BLOCK_content__%
</body>
</html><?php if($__extends){ $_TPL->_renderContent(); } ?>