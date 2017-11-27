<?php
    $dbLocation = 'mysql:dbname=service_map;host=localhost';
    $dbUser = 'root';
    $dbPass = '';
    $db = new PDO($dbLocation, $dbUser, $dbPass, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\''));

    $sql = 'SELECT * FROM tbl_entity_line';

    $query = $db->prepare($sql);
    $query->execute();
    $line_fetch = $query->fetchAll(PDO::FETCH_ASSOC);

    $line_json = [];
    foreach ($line_fetch as $line_fetch_row) {
        $line_json[] = [
            'name'=>$line_fetch_row['name'],
            'color'=>json_decode($line_fetch_row['color'],true),
            'path'=>json_decode($line_fetch_row['path'],true),
            'content'=>$line_fetch_row['content']
        ];
    }

?><!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>Top4 Service Map</title>
</head>
<body>
<style>
    html, body {font-size: 10px;}
    svg text
    {
        font-size: 1.6em;
        font-weight: bold;
        cursor: pointer;
    }
    #canvas_bg
    {
        width: 1000px;
        height: 800px;
    }
    #svg_drawing
    {
        width: 1000px;
        height: 800px;
    }
</style>
<!--<svg width="1800" height="1500" xmlns="http://www.w3.org/2000/svg" fill="transparent">-->
    <!--&lt;!&ndash;<path d="M1140 1070 L1140 900 L850 900 L850 870 L600 870 L600 600 L770 600" stroke="black" stroke-width="6" />&ndash;&gt;-->
    <!--<path d="M1140 1070 L1140 920 C1140 900, 1140 900, 1120 900 L850 900 L850 870 L600 870 L600 600 L770 600" stroke="#f06793" stroke-width="6" />-->
    <!--<circle r="8" cx="1140" cy="1010" stroke="black" stroke-width="5" fill="white" />-->
    <!--<text x="1160" y="1015" fill="#f06793">Hosting</text>-->

    <!--&lt;!&ndash;<path d="M1132 1010 C1132 1021.3, 1148 1021.3, 1148 1010 C1148 998.7, 1132 998.7, 1132 1010 Z" stroke="black" stroke-width="5" fill="white" />&ndash;&gt;-->


    <!--&lt;!&ndash;<path d="M1100 900 C1100 908, 1116 908, 1116 900 L1116 850 C1116 842, 1100 842, 1100 850 Z" stroke="black" stroke-width="5" fill="white" />&ndash;&gt;-->
    <!--<path d="M1100 900 A8 8, 0, 0, 0, 1116 900 L1116 850 A8 8, 0, 0, 0, 1100 850 Z" stroke="black" stroke-width="5" fill="white" />-->
    <!--<path d="M1002.5 592.4 A8 8, 0, 0, 0, 997.5 607.6 L1012.5 612.6 A8 8, 0, 0, 0, 1017.5 597.4 Z" stroke="black" stroke-width="5" fill="white" />-->

<!--</svg>-->
<canvas id="canvas_bg" width="1000" height="800"></canvas>
<table id="point_table">
</table>
<svg id="svg_drawing" xmlns="http://www.w3.org/2000/svg" fill="transparent"></svg>
<script src="jquery-1.11.3.js" type="application/javascript"></script>
<script type="application/javascript">
    $('#canvas_bg').click(function(event){
console.log($('#canvas_bg').offset());
        $('#point_table').append('<tr><td>'+(event.pageX-$('#canvas_bg').offset().left)+'</td><td>'+(event.pageY-$('#canvas_bg').offset().top)+'</td></tr>')
    });
    $(document).ready(function(){
        var canvas = $('#canvas_bg')[0];
        var ctx = canvas.getContext('2d');
        var bg_img = new Image();

        // from database
        var svg_width = $('#svg_drawing').width();
        var line_set = <?=json_encode($line_json)?>;
        line_set.forEach(function(line, index){
console.log(line.color);
            var d = '';
            line.path.forEach(function(turning_point){
                if (!d)
                {
                    d = 'M'+turning_point[0]*svg_width+' '+turning_point[1]*svg_width
                }
                else
                {
                    d += ' L'+turning_point[0]*svg_width+' '+turning_point[1]*svg_width
                }
            });
            $('#svg_drawing').html('<path stroke="rgba('+line.color.join()+',1)" stroke-width="'+0.006*svg_width+'" d="'+d+'" />')
        });

        bg_img.width = 1000;
        bg_img.height = 800;
        bg_img.src = 'background.jpg';

        bg_img.onload = function(){
            ctx.drawImage(bg_img,0, 0,bg_img.width, bg_img.height);
        };
    });
</script>
</body>
</html>